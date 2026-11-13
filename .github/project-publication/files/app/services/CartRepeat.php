<?php
namespace app\services;

use app\models\Cart;

class CartRepeat
{
    public function addRows(array $rows, Cart $cart): array
    {
        $result = ['added' => 0, 'skipped' => 0, 'limited' => 0];
        foreach ($rows as $row) {
            $product = \R::findOne('product', "id = ? AND hide != 'hide'", [(int)$row['product_id']]);
            $qty = max(0, (int)$row['qty']);
            if (!$product || !$qty) { $result['skipped']++; continue; }
            $mod = null;
            // A removed modification must not silently turn into the base product.
            if ((string)$row['article'] !== (string)$product->article) {
                $mod = \R::findOne('modification', 'product_id = ? AND article = ?', [$product->id, (string)$row['article']]);
                if (!$mod) { $result['skipped']++; continue; }
            }
            if ((float)($mod ? $mod->price : $product->price) <= 0) {
                $result['skipped']++;
                continue;
            }
            $key = (string)$product->id . ($mod ? '-' . $mod->id : '');
            $existing = (int)($_SESSION['cart'][$key]['qty'] ?? 0);
            $available = $cart->availableForRow($key, ['product_id' => (int)$product->id]);
            $added = min($qty, max(0, $available - $existing));
            if (!$added) { $result['skipped']++; continue; }
            // Recreate this row so a previously cached price is refreshed too.
            // setQuantity() in addToCart recalculates all totals. Keep promo and
            // currency state even when this is the only existing cart row.
            unset($_SESSION['cart'][$key]);
            $cart->addToCart($product, $existing + $added, null, $mod);
            $actual = max(0, (int)($_SESSION['cart'][$key]['qty'] ?? 0) - $existing);
            $result['added'] += $actual;
            if ($actual < $qty) $result['limited']++;
        }
        return $result;
    }
}
