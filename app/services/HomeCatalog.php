<?php
namespace app\services;

class HomeCatalog
{
    public static function load(): array
    {
        $categories = \R::getAll("SELECT id, parent_id, name, alias FROM category WHERE hide = 'show' OR hide = '' OR hide IS NULL ORDER BY position, id");
        $counts = \R::getAll("SELECT category_id, COUNT(*) AS total FROM product WHERE hide = 'show' GROUP BY category_id");
        $groups = self::build($categories, $counts);
        if (isset($groups[2])) {
            $groups[2]['sizes'] = \R::getAll("SELECT av.value, av.alias, COUNT(DISTINCT p.id) AS total
                FROM attribute_value av JOIN attribute_product ap ON ap.attr_id = av.id
                JOIN product p ON p.id = ap.product_id
                WHERE p.category_id = 2 AND p.hide = 'show' AND av.hide = 'show'
                  AND av.alias REGEXP '^[0-9]+x[0-9]+.*-[0-9]+$'
                GROUP BY av.id, av.value, av.alias ORDER BY total DESC, av.id LIMIT 4");
        }
        return $groups;
    }

    public static function build(array $categories, array $counts): array
    {
        $byId = $children = $direct = [];
        foreach ($categories as $category) {
            $byId[(int)$category['id']] = $category;
            $children[(int)$category['parent_id']][] = (int)$category['id'];
        }
        foreach ($counts as $row) $direct[(int)$row['category_id']] = (int)$row['total'];
        $total = static function(int $root) use ($children, $direct): int {
            $queue = [$root]; $visited = []; $sum = 0;
            while ($queue) {
                $id = array_pop($queue);
                if (isset($visited[$id])) continue;
                $visited[$id] = true;
                $sum += $direct[$id] ?? 0;
                foreach ($children[$id] ?? [] as $child) $queue[] = $child;
            }
            return $sum;
        };
        $groups = [];
        foreach ([1, 2, 25, 3, 4, 36] as $id) {
            if (!isset($byId[$id])) continue;
            $group = $byId[$id];
            $group['total'] = $total($id);
            $group['children'] = [];
            foreach ($children[$id] ?? [] as $child) {
                $group['children'][] = $byId[$child] + ['total' => $total($child)];
            }
            usort($group['children'], static function($a, $b){ return $b['total'] <=> $a['total']; });
            $groups[$id] = $group;
        }
        return $groups;
    }
}
