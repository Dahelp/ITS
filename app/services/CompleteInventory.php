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
        return self::calculate(self::rows($id));
    }

    public static function forCompleteDetails(int $id): array
    {
        $rows = self::rows($id); $summary = self::calculate($rows); $items = [];
        foreach ($rows as $row) {
            $required = max(1, (int)($row['qty'] ?? 1));
            $total = max(0, (int)($row['quantity'] ?? 0) + (int)($row['modification_quantity'] ?? 0));
            $reserve = max(0, (int)($row['reserve'] ?? 0)); $free = max(0, $total - $reserve);
            $items[] = ['product_id'=>(int)($row['product_id']??0),'article'=>(string)($row['article']??''),'name'=>(string)($row['name']??''),
                'required'=>$required,'stock'=>$total,'reserve'=>$reserve,'available'=>$free,'active'=>($row['hide']??'')!=='hide',
                'sets'=>($row['hide']??'')==='hide'?0:intdiv($free,$required),'shortage'=>max(0,$required-$free)];
        }
        return $summary + ['items'=>$items];
    }

    private static function rows(int $id): array
    {
        return \R::getAll('SELECT cp.qty, p.id AS product_id, p.article, p.name, p.quantity, p.reserve, p.hide,
            COALESCE((SELECT SUM(m.quantity) FROM modification m WHERE m.product_id = p.id), 0) AS modification_quantity
            FROM plagins_complete_product cp LEFT JOIN product p ON p.id = cp.product_id WHERE cp.complete_id = ? ORDER BY p.name, p.id', [$id]);
    }
}
