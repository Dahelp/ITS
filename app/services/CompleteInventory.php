<?php
namespace app\services;

/** Quantities are whole sets, limited by their scarcest component. */
class CompleteInventory
{
    public static function calculate(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (empty($row['product_id']) || (int)$row['qty'] <= 0 || ($row['hide'] ?? '') === 'hide') {
                return ['stock' => 0, 'reserve' => 0, 'available' => 0];
            }
            $id = (int)$row['product_id'];
            if (!isset($items[$id])) $items[$id] = $row + ['required' => 0];
            $items[$id]['required'] += (int)$row['qty'];
        }
        $stock = $available = null;
        foreach ($items as $item) {
            $total = max(0, (int)$item['quantity'] + (int)($item['modification_quantity'] ?? 0));
            $free = max(0, $total - max(0, (int)$item['reserve']));
            $stock = min($stock ?? PHP_INT_MAX, intdiv($total, $item['required']));
            $available = min($available ?? PHP_INT_MAX, intdiv($free, $item['required']));
        }
        $stock ??= 0; $available ??= 0;
        return ['stock' => $stock, 'reserve' => $stock - $available, 'available' => $available];
    }

    public static function forComplete(int $id): array
    {
        return self::calculate(\R::getAll('SELECT cp.qty, p.id AS product_id, p.quantity, p.reserve, p.hide,
            COALESCE((SELECT SUM(m.quantity) FROM modification m WHERE m.product_id = p.id), 0) AS modification_quantity
            FROM plagins_complete_product cp LEFT JOIN product p ON p.id = cp.product_id WHERE cp.complete_id = ?', [$id]));
    }
}
