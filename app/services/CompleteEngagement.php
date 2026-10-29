<?php
namespace app\services;

final class CompleteEngagement
{
    private static bool $ready = false;

    public static function ensureSchema(): void
    {
        if (self::$ready) return;
        \R::exec('CREATE TABLE IF NOT EXISTS review_complete (review_id INT NOT NULL, complete_id INT NOT NULL, PRIMARY KEY (review_id, complete_id), INDEX idx_review_complete_set (complete_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        \R::exec('CREATE TABLE IF NOT EXISTS complete_wishlists (id INT NOT NULL AUTO_INCREMENT, complete_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_complete_wishlist (complete_id,user_id), INDEX idx_complete_wishlist_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        if (!\R::getCell("SHOW COLUMNS FROM order_product LIKE 'complete_id'")) {
            \R::exec('ALTER TABLE order_product ADD complete_id INT NULL DEFAULT NULL, ADD INDEX idx_order_product_complete (complete_id, order_id)');
        }
        self::$ready = true;
    }

    public static function metrics(int $completeId): array
    {
        self::ensureSchema();
        return [
            'reviews' => (int)\R::getCell('SELECT COUNT(*) FROM review_complete WHERE complete_id=?', [$completeId]),
            'favorites' => (int)\R::getCell('SELECT COUNT(*) FROM complete_wishlists WHERE complete_id=?', [$completeId]),
            'sales' => self::sales($completeId),
            'certification' => self::certification($completeId),
        ];
    }

    public static function sales(int $completeId): int
    {
        self::ensureSchema();
        $required = [];
        foreach (\R::getAll('SELECT product_id,qty FROM plagins_complete_product WHERE complete_id=?', [$completeId]) as $row) {
            $required[(int)$row['product_id']] = (int)$row['qty'];
        }
        if (!$required) return 0;
        $orders = [];
        foreach (\R::getAll('SELECT order_id,product_id,SUM(qty) qty FROM order_product WHERE complete_id=? GROUP BY order_id,product_id', [$completeId]) as $row) {
            $orders[(int)$row['order_id']][(int)$row['product_id']] = (int)$row['qty'];
        }
        return self::soldSetsFromRows($required, $orders);
    }

    public static function soldSetsFromRows(array $required, array $orders): int
    {
        $total = 0;
        foreach ($orders as $quantities) {
            $sets = null;
            foreach ($required as $productId => $qty) {
                $qty = max(1, (int)$qty);
                $sets = min($sets ?? PHP_INT_MAX, intdiv(max(0, (int)($quantities[$productId] ?? 0)), $qty));
            }
            $total += max(0, (int)($sets ?? 0));
        }
        return $total;
    }

    public static function certification(int $completeId): array
    {
        if (!ProductCertificationService::hasProductFields()) return ['label'=>'Сертификация: не указано','class'=>'danger'];
        $rows = \R::getAll('SELECT p.id,p.category_id,p.brand_id,p.certification_required FROM plagins_complete_product cp JOIN product p ON p.id=cp.product_id WHERE cp.complete_id=?', [$completeId]);
        if (!$rows) return ['label'=>'Сертификация: не указано','class'=>'danger'];
        $unknown = false; $required = [];
        foreach ($rows as $row) {
            if ($row['certification_required'] === null || $row['certification_required'] === '') $unknown = true;
            elseif ((int)$row['certification_required'] === 1) $required[] = $row;
        }
        if (!$required && !$unknown) return ['label'=>'Сертификация: не требуется','class'=>'success'];
        if (!$required) return ['label'=>'Сертификация: не указано','class'=>'danger'];
        foreach ($required as $row) {
            $product = \R::load('product', (int)$row['id']);
            if (!ProductCertificationService::forProduct($product)['documents']) return ['label'=>'Сертификация: требуется документ','class'=>'warning'];
        }
        return ['label'=>'Сертификация: документы есть','class'=>'success'];
    }

    public static function reviews(int $completeId): array
    {
        self::ensureSchema();
        return \R::getAll("SELECT r.* FROM review_complete rc JOIN review r ON r.id=rc.review_id WHERE rc.complete_id=? AND r.hide='show' ORDER BY r.id DESC", [$completeId]);
    }

    public static function addReview(int $completeId, int $userId, string $content, int $point, string $name): void
    {
        self::ensureSchema();
        $content = trim($content); $point = max(1, min(5, $point)); $name = trim($name);
        if (mb_strlen($content, 'UTF-8') < 3) throw new \DomainException('Напишите отзыв длиной не менее трёх символов.');
        if ($name === '') $name = 'Покупатель';
        \R::exec("INSERT INTO review (content,point,date_post,hide,finger_up,finger_down,user_id,uname) VALUES (?,?,CURDATE(),'show',0,0,?,?)", [$content,$point,$userId,$name]);
        $reviewId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
        \R::exec('INSERT INTO review_complete (review_id,complete_id) VALUES (?,?)', [$reviewId,$completeId]);
    }

    public static function toggleFavorite(int $completeId, int $userId): bool
    {
        self::ensureSchema();
        $id = (int)\R::getCell('SELECT id FROM complete_wishlists WHERE complete_id=? AND user_id=?', [$completeId,$userId]);
        if ($id) { \R::exec('DELETE FROM complete_wishlists WHERE id=?', [$id]); return false; }
        \R::exec('INSERT INTO complete_wishlists (complete_id,user_id) VALUES (?,?)', [$completeId,$userId]);
        return true;
    }

    public static function isFavorite(int $completeId, int $userId): bool
    {
        self::ensureSchema();
        return $userId > 0 && (bool)\R::getCell('SELECT 1 FROM complete_wishlists WHERE complete_id=? AND user_id=?', [$completeId,$userId]);
    }
}
