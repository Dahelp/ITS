<?php
namespace app\services;

/** A listing belongs to a whole set, independently of its component articles. */
class CompleteAvito
{
    public static function canPublish(array $ad): bool
    {
        return empty($ad['complete_id']) || (
            ($ad['status'] ?? '') === 'active'
            && (int)($ad['stock_quantity'] ?? 0) > 0
            && (float)($ad['price_rub'] ?? 0) > 0
        );
    }
    public static function ensureSchema(): void
    {
        if (!\R::getCell("SHOW COLUMNS FROM avito_ad LIKE 'complete_id'")) {
            \R::exec('ALTER TABLE avito_ad ADD complete_id INT NULL DEFAULT NULL, ADD INDEX idx_avito_complete (complete_id)');
        }
        if (!\R::getCell("SHOW COLUMNS FROM avito_ad LIKE 'stock_quantity'")) {
            \R::exec('ALTER TABLE avito_ad ADD stock_quantity INT NOT NULL DEFAULT 0');
        }
    }

    public static function calculate(array $rows): array
    {
        $price = 0.0; $stock = null; $pieces = 0;
        foreach ($rows as $row) {
            $qty = (int)$row['qty'];
            if ($qty <= 0 || empty($row['product_id'])) { return ['price' => 0, 'stock' => 0, 'pieces' => 0]; }
            $price += max(0, (float)$row['price'] - (float)$row['discount']) * $qty;
            $available = ($row['hide'] ?? '') === 'hide' ? 0 : max(0, (int)($row['rest'] ?? $row['quantity'] ?? 0));
            $stock = min($stock ?? PHP_INT_MAX, intdiv($available, $qty));
            $pieces += $qty;
        }
        return ['price' => (int)round($price), 'stock' => $stock ?? 0, 'pieces' => $pieces];
    }

    public static function totals(int $id): array
    {
        return self::calculate(\R::getAll('SELECT p.id AS product_id, p.price, p.rest, p.quantity, p.hide, c.qty, c.discount
            FROM plagins_complete_product c LEFT JOIN product p ON p.id = c.product_id WHERE c.complete_id = ?', [$id]));
    }

    public static function sync(): int
    {
        $count = 0;
        foreach (\R::getAll('SELECT a.id, a.complete_id, a.review_status, a.deletion_requested_at, a.images_json, a.product_type, a.price_rub, a.manual_fields_json, c.name, c.hide, c.img, c.category_id
            FROM avito_ad a LEFT JOIN plagins_complete c ON c.id = a.complete_id WHERE a.complete_id IS NOT NULL') as $ad) {
            $totals = self::totals((int)$ad['complete_id']);
            $manual = json_decode((string)($ad['manual_fields_json'] ?? ''), true) ?: [];
            if (in_array('price_rub', $manual, true)) $totals['price'] = max(0,(int)$ad['price_rub']);
            $stock = $ad['name'] && $ad['hide'] !== 'hide' && $ad['review_status'] !== 'needs_review' && empty($ad['deletion_requested_at']) ? $totals['stock'] : 0;
            \R::exec('UPDATE avito_ad SET price_rub = ?, stock_quantity = ?, quantity = ?, status = ? WHERE id = ?',
                [$totals['price'] ?: null, $stock, 'за '.max(1, min(8, $totals['pieces'])).' шт.', $stock > 0 && $totals['price'] > 0 ? 'active' : 'archived', $ad['id']]);
            if ((int)$ad['category_id'] === 2 && empty($ad['product_type'])) {
                \R::exec("UPDATE avito_ad SET product_type = 'Мотошины', wheel_axle = 'Любая' WHERE id = ?", [$ad['id']]);
            }
            if ($ad['img'] && empty($ad['images_json'])) {
                \R::exec('UPDATE avito_ad SET images_json = ? WHERE id = ?', [json_encode(['https://its-center.ru/images/complete/baseimg/'.rawurlencode($ad['img'])]), $ad['id']]);
            }
            $count++;
        }
        return $count;
    }

    public static function saveLinks(int $id, string $ids, bool $validateOnly = false): void
    {
        self::ensureSchema();
        $tokens = preg_split('/[\s,;]+/', trim($ids), -1, PREG_SPLIT_NO_EMPTY);
        $ads = [];
        foreach ($tokens as $token) {
            if (!ctype_digit($token)) { throw new \InvalidArgumentException('Укажите числовые ID объявлений Авито'); }
            $ad = \R::getRow('SELECT id, complete_id FROM avito_ad WHERE avito_id = ?', [$token]);
            if (!$ad || (!empty($ad['complete_id']) && (int)$ad['complete_id'] !== $id)) {
                throw new \InvalidArgumentException('Объявление '.$token.' не импортировано или связано с другим комплектом');
            }
            $ads[] = $ad['id'];
        }
        if ($validateOnly) { return; }
        \R::begin();
        try {
            foreach (\R::getAll("SELECT id,manual_fields_json FROM avito_ad WHERE complete_id=? AND avito_id REGEXP '^[0-9]+$'",[$id]) as $previous) {
                if (in_array($previous['id'],$ads)) continue;
                $manual = json_decode((string)($previous['manual_fields_json']??''),true) ?: [];
                $manual[] = 'complete_id';
                \R::exec('UPDATE avito_ad SET manual_fields_json=? WHERE id=?',[json_encode(array_values(array_unique($manual))),$previous['id']]);
            }
            \R::exec("UPDATE avito_ad SET complete_id = NULL, stock_quantity = 0, status = 'archived' WHERE complete_id = ? AND avito_id REGEXP '^[0-9]+$'", [$id]);
            foreach ($ads as $adId) { \R::exec('UPDATE avito_ad SET complete_id = ?, article = NULL WHERE id = ?', [$id, $adId]); }
            \R::commit();
        } catch (\Throwable $e) { \R::rollback(); throw $e; }
        self::sync();
        // The feed excludes unavailable sets; request its processing after an admin edit.
        try {
            $publication = CompleteAvitoPublication::run();
            if (!empty($publication['errors'])) {
                error_log('Avito complete publication: '.json_encode($publication, JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) { error_log('Avito complete publication: '.$e->getMessage()); }
    }
}
