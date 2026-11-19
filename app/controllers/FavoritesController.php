<?php
namespace app\controllers;

use app\widgets\product\Product;
use ishop\App;

class FavoritesController extends AppController
{
    private function ids(): array
    {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) return [];
        return array_slice(array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) { return $id > 0; }))), 0, 500);
    }

    public function indexAction()
    {
        $this->setMeta('Избранное');
    }

    public function itemsAction()
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $ids = $userId > 0
            ? array_map('intval', \R::getCol('SELECT product_id FROM product_wishlists WHERE user_id = ?', [$userId]))
            : $this->ids();
        $products = [];
        if ($ids) {
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $products = \R::getAll("SELECT * FROM product WHERE hide = 'show' AND id IN ($marks)", $ids);
        }
        $context = Product::buildContext($products);
        $currency = App::$app->getProperty('currency');
        ob_start();
        foreach ($products as $product) {
            echo '<div class="col-12 col-sm-6 col-lg-4 col-xl-3" data-favorite-item="' . (int)$product['id'] . '">';
            new Product($product, $currency, '', $context);
            echo '</div>';
        }
        $html = ob_get_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'html' => $html, 'ids' => array_map('intval', array_column($products, 'id'))]);
        exit;
    }

    public function mergeAction()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        header('Content-Type: application/json; charset=utf-8');
        if (!$userId) { http_response_code(401); echo json_encode(['ok' => false]); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['favorites_csrf']) ||
            !hash_equals($_SESSION['favorites_csrf'], (string)($_POST['csrf'] ?? ''))) {
            http_response_code(403); echo json_encode(['ok' => false]); exit;
        }
        foreach ($this->ids() as $id) {
            if (!\R::getCell("SELECT id FROM product WHERE id = ? AND hide = 'show'", [$id])) continue;
            \R::exec('INSERT INTO product_wishlists (product_id, user_id) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM product_wishlists WHERE product_id = ? AND user_id = ?)', [$id, $userId, $id, $userId]);
        }
        $count = (int)\R::count('product_wishlists', 'user_id = ?', [$userId]);
        echo json_encode(['ok' => true, 'count' => $count]);
        exit;
    }
}
