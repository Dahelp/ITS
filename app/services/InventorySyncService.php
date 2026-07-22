<?php

namespace app\services;

use app\models\admin\Cron;

final class InventorySyncService
{
    private $client;
    public function __construct(?InventoryApiClient $client = null) { $this->client = $client ?: new InventoryApiClient(); }

    public function run(int $cronId, string $categories, string $mode = 'shadow', int $canaryPercent = 5, int $limit = 0): array
    {
        if (!in_array($mode, ['shadow', 'canary', 'live'], true)) throw new \InvalidArgumentException('mode must be shadow, canary or live');
        $ids = array_values(array_filter(array_map('intval', explode(',', $categories))));
        if (!$ids) throw new \InvalidArgumentException('categories is empty');
        $sql = 'SELECT * FROM product WHERE category_id IN (' . implode(',', $ids) . ') ORDER BY id';
        if ($limit > 0) $sql .= ' LIMIT ' . min(100000, $limit);
        $products = \R::getAll($sql);
        $stats = ['mode' => $mode, 'seen' => count($products), 'api' => 0, 'cache' => 0, 'db_fallback' => 0, 'changed' => 0, 'zero_blocked' => 0, 'updated' => 0];
        $date = date('Y-m-d'); $dateTime = date('Y-m-d H:i:s');
        foreach ($products as $product) {
            $article = InventoryApiClient::normalizeArticle((string)$product['article']);
            $result = $this->client->fetch($article);
            if (empty($result['ok'])) { $stats['db_fallback']++; continue; }
            $stats[$result['source'] === 'api' ? 'api' : 'cache']++;
            $data = $result['data'];
            $apiQty = (int)$data['rest'] + (int)$data['reserve'];
            $dbQty = (int)$product['quantity'];
            $needsUpdate = $apiQty !== $dbQty
                || (int)($data['rest'] ?? 0) !== (int)($product['rest'] ?? 0)
                || (int)($data['reserve'] ?? 0) !== (int)($product['reserve'] ?? 0)
                || abs((float)($data['price_rozn'] ?? 0) - (float)($product['price'] ?? 0)) > 0.009
                || abs((float)($data['price_opt'] ?? 0) - (float)($product['opt_price'] ?? 0)) > 0.009;
            if ($needsUpdate) $stats['changed']++;
            $canWrite = $mode === 'live' || ($mode === 'canary' && (abs(crc32($article)) % 100) < max(0, min(100, $canaryPercent)));
            $suspiciousZero = $apiQty === 0 && $dbQty > 0 && $result['source'] !== 'cache';
            if ($suspiciousZero && getenv('INVENTORY_ALLOW_ZERO_TRANSITIONS') !== '1') {
                $stats['zero_blocked']++;
                $this->client->log('zero_blocked', $article, ['db_quantity' => $dbQty]);
                continue;
            }
            if ($canWrite && $needsUpdate) { Cron::updateProduct($product, $data, $date, $dateTime); $stats['updated']++; }
        }
        $productIds = array_map('intval', array_column($products, 'id'));
        $mods = $productIds ? \R::getAll('SELECT * FROM modification WHERE product_id IN (' . implode(',', $productIds) . ') ORDER BY id') : [];
        $stats['modifications_seen'] = count($mods);
        $stats['modifications_updated'] = 0;
        foreach ($mods as $mod) {
            $article = InventoryApiClient::normalizeArticle((string)$mod['article']);
            $result = $this->client->fetch($article);
            if (empty($result['ok'])) { $stats['db_fallback']++; continue; }
            $data = $result['data'];
            $apiQty = (int)$data['rest'] + (int)$data['reserve'];
            $dbQty = (int)$mod['quantity'];
            $needsUpdate = $apiQty !== $dbQty
                || abs((float)($data['price_rozn'] ?? 0) - (float)($mod['price'] ?? 0)) > 0.009
                || abs((float)($data['price_spec'] ?? 0) - (float)($mod['spec_price'] ?? 0)) > 0.009
                || abs((float)($data['price_opt'] ?? 0) - (float)($mod['opt_price'] ?? 0)) > 0.009;
            $canWrite = $mode === 'live' || ($mode === 'canary' && (abs(crc32($article)) % 100) < max(0, min(100, $canaryPercent)));
            if ($apiQty === 0 && $dbQty > 0 && $result['source'] !== 'cache' && getenv('INVENTORY_ALLOW_ZERO_TRANSITIONS') !== '1') { $stats['zero_blocked']++; continue; }
            if ($canWrite && $needsUpdate) {
                Cron::updateModification((string)$mod['article'], $apiQty, (float)$data['price_rozn'], (float)$data['price_spec'], (float)$data['price_opt'], $date);
                $stats['modifications_updated']++;
            }
        }
        Cron::writeLog('[INVENTORY_API] ' . json_encode($stats, JSON_UNESCAPED_UNICODE), $cronId);
        if ($mode !== 'shadow' && $stats['updated'] > 0) Cron::finalizeCronUpdate($cronId, $dateTime, $date);
        return $stats;
    }
}
