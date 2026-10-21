<?php
namespace app\services;

/** Builds the internal product/listing card shown beside an Avito conversation. */
class AvitoListingCard
{
    public static function availableQuantity($stock, $reserve): int
    {
        return max(0, (int)$stock - max(0, (int)$reserve));
    }

    public static function find(string $itemId): array
    {
        $itemId = trim($itemId);
        if ($itemId === '' || strlen($itemId) > 32 || !ctype_digit($itemId)) {
            throw new \InvalidArgumentException('Некорректный идентификатор объявления.');
        }
        $row = \R::getRow("SELECT
                a.id AS ad_id, a.avito_id, a.ad_external_id, a.avito_url,
                a.article, a.title, a.description, a.price_rub, a.images_json, a.complete_id,
                c.alias AS complete_alias, c.hide AS complete_hide,
                p.id AS product_id, p.name AS product_name, p.alias, p.price AS product_price,
                COALESCE(p.quantity, p.rest, 0) AS stock_quantity,
                COALESCE(p.reserve, 0) AS reserve_quantity,
                p.img, p.unload_img
            FROM avito_ad a
            LEFT JOIN product p ON p.article = a.article AND a.complete_id IS NULL
            LEFT JOIN plagins_complete c ON c.id = a.complete_id
            WHERE a.avito_id = ? OR a.ad_external_id = ?
            ORDER BY (a.avito_id = ?) DESC
            LIMIT 1", [$itemId, $itemId, $itemId]);
        if (!$row) throw new \InvalidArgumentException('Связанное объявление не найдено в админке.');

        $images = self::images((string)($row['images_json'] ?? ''));
        if (!$images && !empty($row['product_id'])) {
            foreach (['unload_img' => 'unload', 'img' => 'baseimg'] as $field => $folder) {
                $file = trim((string)($row[$field] ?? ''));
                if ($file !== '') $images[] = rtrim(PATH, '/') . '/images/product/' . $folder . '/' . rawurlencode($file);
            }
            foreach (\R::getCol('SELECT img FROM gallery WHERE product_id = ? ORDER BY id', [(int)$row['product_id']]) as $file) {
                $file = trim((string)$file);
                if ($file !== '') $images[] = rtrim(PATH, '/') . '/images/product/gallery/' . rawurlencode($file);
            }
            $images = array_values(array_unique($images));
        }

        $stock = max(0, (int)($row['stock_quantity'] ?? 0));
        $reserve = max(0, (int)($row['reserve_quantity'] ?? 0));
        $inventory = ['stock' => $stock, 'reserve' => $reserve, 'available' => self::availableQuantity($stock, $reserve)];
        if (!empty($row['complete_id'])) {
            $inventory = !empty($row['complete_alias']) && $row['complete_hide'] !== 'hide'
                ? CompleteInventory::forComplete((int)$row['complete_id'])
                : ['stock' => 0, 'reserve' => 0, 'available' => 0];
        }
        $description = trim(html_entity_decode(strip_tags((string)($row['description'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $description = preg_replace('~[ \t]+~u', ' ', preg_replace('~\R{3,}~u', "\n\n", $description)) ?? $description;
        return [
            'id' => (int)$row['ad_id'],
            'avito_id' => (string)($row['avito_id'] ?: $row['ad_external_id']),
            'article' => (string)($row['article'] ?? ''),
            'title' => (string)($row['title'] ?: $row['product_name']),
            'description' => $description,
            'price' => (int)round((float)($row['price_rub'] ?: $row['product_price'])),
            'stock' => $inventory['stock'],
            'reserve' => $inventory['reserve'],
            'available' => $inventory['available'],
            'stock_unit' => !empty($row['complete_id']) ? 'компл.' : 'шт.',
            'images' => $images,
            'edit_url' => rtrim(PATH, '/') . '/admin/avito/edit?id=' . (int)$row['ad_id'],
            'product_url' => !empty($row['complete_alias'])
                ? rtrim(PATH, '/') . '/complete/' . rawurlencode($row['complete_alias'])
                : (!empty($row['product_id']) ? rtrim(PATH, '/') . '/product/' . rawurlencode($row['alias']) : ''),
            'avito_url' => (string)($row['avito_url'] ?? ''),
        ];
    }

    private static function images(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return [];
        $images = [];
        foreach ($decoded as $image) {
            $url = is_string($image) ? $image : (is_array($image) ? ($image['url'] ?? '') : '');
            $url = trim((string)$url);
            if (self::safeImageUrl($url)) $images[] = $url;
        }
        return array_values(array_unique($images));
    }

    private static function safeImageUrl(string $url): bool
    {
        $parts = parse_url($url);
        return is_array($parts) && strtolower((string)($parts['scheme'] ?? '')) === 'https'
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
    }
}
