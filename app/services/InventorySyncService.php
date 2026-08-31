<?php

namespace app\services;

use app\models\admin\Cron;

final class InventorySyncService
{
    private const FIXED_STOCK_CATEGORIES = [
        'demontazh-montazh-shin' => 999,
    ];

    private $client;
    public function __construct(?InventoryApiClient $client = null) { $this->client = $client ?: new InventoryApiClient(); }

    public function run(int $cronId, string $categories, string $mode = 'shadow', int $canaryPercent = 5, int $limit = 0, string $onlyArticle = ''): array
    {
        if (!in_array($mode, ['shadow', 'canary', 'live'], true)) throw new \InvalidArgumentException('mode must be shadow, canary or live');
        $allCategories = trim($categories) === '*';
        $ids = $allCategories ? [] : array_values(array_filter(array_map('intval', explode(',', $categories))));
        if (!$allCategories && !$ids) throw new \InvalidArgumentException('categories is empty');
        $sql = 'SELECT * FROM product';
        $conditions = [];
        $params = [];
        if (!$allCategories) $conditions[] = 'category_id IN (' . implode(',', $ids) . ')';
        $onlyArticle = InventoryApiClient::normalizeArticle($onlyArticle);
        if ($onlyArticle !== '') {
            $conditions[] = 'article = ?';
            $params[] = $onlyArticle;
        }
        if ($conditions) $sql .= ' WHERE ' . implode(' AND ', $conditions);
        $sql .= ' ORDER BY id';
        if ($limit > 0) $sql .= ' LIMIT ' . min(100000, $limit);
        $products = \R::getAll($sql, $params);
        $fixedStockByCategoryId = $this->fixedStockByCategoryId();
        $stats = ['mode' => $mode, 'seen' => count($products), 'api' => 0, 'cache' => 0, 'db_fallback' => 0, 'changed' => 0, 'updated' => 0, 'fixed_stock_updated' => 0];
        $stats['fixed_stock_enforced'] = $mode === 'live'
            ? $this->enforceFixedStock($fixedStockByCategoryId)
            : 0;
        $date = date('Y-m-d'); $dateTime = date('Y-m-d H:i:s');
        foreach ($products as $index => $product) {
            $this->keepDatabaseConnectionAlive($index);
            $article = InventoryApiClient::normalizeArticle((string)$product['article']);
            $result = $this->client->fetch($article);
            if (empty($result['ok'])) { $stats['db_fallback']++; continue; }
            if (($result['source'] ?? '') === 'stale_cache') { $stats['db_fallback']++; continue; }
            $stats[$result['source'] === 'api' ? 'api' : 'cache']++;
            $data = $result['data'];
            $fixedQuantity = $fixedStockByCategoryId[(int)$product['category_id']] ?? null;
            if ($fixedQuantity !== null) {
                // Services have no physical stock in 1C. Keep their virtual stock
                // while still allowing prices and other product data to refresh.
                $data['rest'] = $fixedQuantity;
                $data['reserve'] = 0;
                $stats['fixed_stock_updated']++;
            }
            $apiQty = (int)$data['rest'];
            $dbQty = (int)$product['quantity'];
            $needsUpdate = $apiQty !== $dbQty
                || (int)($data['rest'] ?? 0) !== (int)($product['rest'] ?? 0)
                || (int)($data['reserve'] ?? 0) !== (int)($product['reserve'] ?? 0)
                || abs((float)($data['price_rozn'] ?? 0) - (float)($product['price'] ?? 0)) > 0.009
                || abs((float)($data['price_opt'] ?? 0) - (float)($product['opt_price'] ?? 0)) > 0.009;
            if ($needsUpdate) $stats['changed']++;
            $canWrite = $mode === 'live' || ($mode === 'canary' && (abs(crc32($article)) % 100) < max(0, min(100, $canaryPercent)));
            if ($canWrite && $needsUpdate) { Cron::updateProduct($product, $data, $date, $dateTime); $stats['updated']++; }
        }
        $productIds = array_map('intval', array_column($products, 'id'));
        $mods = $productIds ? \R::getAll('SELECT * FROM modification WHERE product_id IN (' . implode(',', $productIds) . ') ORDER BY id') : [];
        $stats['modifications_seen'] = count($mods);
        $stats['modifications_updated'] = 0;
        foreach ($mods as $index => $mod) {
            $this->keepDatabaseConnectionAlive($index);
            $article = InventoryApiClient::normalizeArticle((string)$mod['article']);
            $result = $this->client->fetch($article);
            if (empty($result['ok'])) { $stats['db_fallback']++; continue; }
            if (($result['source'] ?? '') === 'stale_cache') { $stats['db_fallback']++; continue; }
            $data = $result['data'];
            $apiQty = (int)$data['rest'];
            $dbQty = (int)$mod['quantity'];
            $needsUpdate = $apiQty !== $dbQty
                || abs((float)($data['price_rozn'] ?? 0) - (float)($mod['price'] ?? 0)) > 0.009
                || abs((float)($data['price_spec'] ?? 0) - (float)($mod['spec_price'] ?? 0)) > 0.009
                || abs((float)($data['price_opt'] ?? 0) - (float)($mod['opt_price'] ?? 0)) > 0.009;
            $canWrite = $mode === 'live' || ($mode === 'canary' && (abs(crc32($article)) % 100) < max(0, min(100, $canaryPercent)));
            if ($canWrite && $needsUpdate) {
                Cron::updateModification((string)$mod['article'], $apiQty, (float)$data['price_rozn'], (float)$data['price_spec'], (float)$data['price_opt'], $date);
                $stats['modifications_updated']++;
            }
        }
        Cron::writeLog('[INVENTORY_API] ' . json_encode($stats, JSON_UNESCAPED_UNICODE), $cronId);
        if ($mode !== 'shadow' && $stats['updated'] > 0) Cron::finalizeCronUpdate($cronId, $dateTime, $date);
        return $stats;
    }

    /** @return array<int, int> category ID => fixed quantity */
    private function fixedStockByCategoryId(): array
    {
        $result = [];
        foreach (self::FIXED_STOCK_CATEGORIES as $alias => $quantity) {
            $categoryId = (int)\R::getCell('SELECT id FROM category WHERE alias = ? LIMIT 1', [$alias]);
            if ($categoryId > 0) {
                $result[$categoryId] = $quantity;
            }
        }

        return $result;
    }

    /** @param array<int, int> $fixedStockByCategoryId */
    private function enforceFixedStock(array $fixedStockByCategoryId): int
    {
        $updated = 0;
        foreach ($fixedStockByCategoryId as $categoryId => $quantity) {
            $updated += \R::exec(
                'UPDATE product SET quantity = ?, rest = ?, reserve = 0, stock_status_id = 1 '
                . 'WHERE category_id = ? AND (quantity <> ? OR rest <> ? OR reserve <> 0 OR stock_status_id <> 1)',
                [$quantity, $quantity, $categoryId, $quantity, $quantity]
            );
        }

        return $updated;
    }

    private function keepDatabaseConnectionAlive(int $index): void
    {
        if ($index % 20 === 0) \R::getCell('SELECT 1');
    }
}
