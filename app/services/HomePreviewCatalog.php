<?php
namespace app\services;

class HomePreviewCatalog
{
    public static function tree(array $categories, array $counts): array
    {
        $map = $children = $direct = [];
        foreach ($categories as $category) {
            $map[(int)$category['id']] = $category;
            $children[(int)$category['parent_id']][] = (int)$category['id'];
        }
        foreach ($counts as $count) $direct[(int)$count['category_id']] = (int)$count['total'];
        foreach ($map as $root => &$category) {
            $pending = [$root]; $seen = []; $total = 0;
            while ($pending) {
                $id = array_pop($pending);
                if (isset($seen[$id])) continue;
                $seen[$id] = $id;
                $total += $direct[$id] ?? 0;
                foreach ($children[$id] ?? [] as $child) $pending[] = $child;
            }
            $category['total'] = $total;
            $category['ids'] = array_values($seen);
        }
        unset($category);
        return $map;
    }

    public static function load(): array
    {
        $groups = self::tree(
            \R::getAll("SELECT id, parent_id, name, alias, img FROM category WHERE hide = 'show' OR hide = '' OR hide IS NULL ORDER BY position, id"),
            \R::getAll("SELECT category_id, COUNT(*) AS total FROM product WHERE hide = 'show' GROUP BY category_id")
        );
        foreach ([1, 2] as $root) {
            if (empty($groups[$root])) continue;
            $ids = implode(',', array_map('intval', $groups[$root]['ids']));
            $sizeOrder = $root === 1
                ? "CASE av.value WHEN '10-16.5' THEN 1 WHEN '12-16.5' THEN 2 WHEN '12.5/80-18' THEN 3 WHEN '16.9-28' THEN 4 WHEN '11-16.5' THEN 5 WHEN '14-17.5' THEN 6 ELSE 7 END, "
                : '';
            $groups[$root]['sizes'] = \R::getAll("SELECT av.id, av.value, av.alias, COUNT(DISTINCT p.id) AS total
                FROM attribute_value av JOIN attribute_product ap ON ap.attr_id = av.id JOIN product p ON p.id = ap.product_id
                WHERE av.attr_group_id = 2 AND av.hide = 'show' AND av.alias <> '' AND p.hide = 'show' AND p.category_id IN ($ids)
                GROUP BY av.id, av.value, av.alias ORDER BY {$sizeOrder}total DESC, av.id LIMIT 8");
        }
        if (!empty($groups[2])) {
            // Studio edits use original upload photos as tread references; frames normalize visible tire height.
            $groups[2]['patterns'] = \R::getAll("SELECT av.id, av.value, av.alias, COUNT(DISTINCT p.id) AS total,
                MIN(NULLIF(p.img, '')) AS img
                FROM attribute_value av JOIN attribute_product ap ON ap.attr_id = av.id JOIN product p ON p.id = ap.product_id
                WHERE av.attr_group_id = 3 AND av.hide = 'show' AND av.alias <> '' AND p.hide = 'show' AND p.category_id = 2 AND av.alias IN ('knight', 'eos', 'p357', 'p311')
                GROUP BY av.id, av.value, av.alias ORDER BY total DESC, av.id LIMIT 4");
        }
        $patternFrames = [
            'knight' => '-7.44 -8.44 810.88 810.88',
            'eos' => '17.22 40.22 742.56 742.56',
            'p357' => '-1.36 5.64 790.72 790.72',
            'p311' => '-36.38 -36.38 865.76 865.76',
        ];
        foreach ($groups[2]['patterns'] ?? [] as $index => $pattern) {
            $groups[2]['patterns'][$index]['image_url'] = '/images/home-preview/atv-' . $pattern['alias'] . '-studio.webp';
            $groups[2]['patterns'][$index]['image_viewbox'] = $patternFrames[$pattern['alias']];
        }
        return $groups;
    }
}
