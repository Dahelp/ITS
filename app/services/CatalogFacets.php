<?php
namespace app\services;

/** Facet IDs stay in the existing attribute tables; no inferred product facts. */
final class CatalogFacets
{
    public static function ids($raw): array
    {
        $values = is_array($raw) ? $raw : explode(',', is_scalar($raw) ? (string)$raw : '');
        $ids = [];
        foreach (array_slice($values, 0, 200) as $value) {
            if (is_scalar($value) && preg_match('/^[1-9][0-9]{0,8}$/D', trim((string)$value))) {
                $ids[] = (int)$value;
            }
        }
        return array_values(array_unique($ids));
    }

    public static function groups(array $ids): array
    {
        if (!$ids) return [];
        $rows = \R::getAll('SELECT id, attr_group_id FROM attribute_value WHERE hide=\'show\' AND id IN ('.implode(',', self::ids($ids)).')');
        $groups = [];
        foreach ($rows as $row) $groups[(int)$row['attr_group_id']][] = (int)$row['id'];
        return $groups;
    }

    /** OR within each group, AND between groups. Unknown IDs never widen results. */
    public static function predicate(array $ids, string $product = 'product', ?int $excludeGroup = null): string
    {
        if (!in_array($product, ['product', 'p', 'src'], true)) throw new \InvalidArgumentException('Invalid product alias');
        $ids = self::ids($ids);
        if (!$ids) return '';
        $groups = self::groups($ids);
        if (count(array_merge([], ...array_values($groups))) !== count($ids)) return ' AND 1=0';
        $sql = '';
        foreach ($groups as $group => $values) {
            if ($group === $excludeGroup) continue;
            $sql .= ' AND '.CatalogFacetBridge::condition((int)$group,$values,$product);
        }
        return $sql;
    }

    public static function options(string $categoryIds, array $selected, array $state, ?array $groupOrder = null): array
    {
        $categories = self::ids($categoryIds);
        if (!$categories) return [];
        $csv = implode(',', $categories);
        $groups = \R::getAssoc("SELECT ag.id, ag.title FROM attribute_group ag JOIN attribute_category ac ON ac.group_id=ag.id WHERE ac.category_id IN ($csv) GROUP BY ag.id, ag.title ORDER BY ag.title");
        if ($groupOrder !== null) {
            $order = self::ids($groupOrder);
            if (!$order) return [];
            $groups = CatalogDisplay::orderedGroups(\R::getAssoc('SELECT id,title FROM attribute_group WHERE id IN ('.implode(',', $order).')'), $order);
        }
        $result = [];
        foreach ($groups as $group => $title) {
            $extra = self::predicate($selected, 'product', (int)$group).' '.CatalogSelection::where($state);
            // The unfiltered category universe keeps zero-count options visible.
            $relation=CatalogFacetBridge::hasOverrides((int)$group)?CatalogFacetBridge::relationSql():'attribute_product';
            $rows = \R::getAll("SELECT av.id, av.value, av.alias, COUNT(DISTINCT CASE WHEN 1=1 $extra THEN product.id END) AS count
                FROM attribute_value av JOIN $relation ap ON ap.attr_id=av.id JOIN product ON product.id=ap.product_id
                WHERE av.hide='show' AND av.attr_group_id=? AND product.hide='show' AND product.category_id IN ($csv)
                GROUP BY av.id,av.value,av.alias ORDER BY av.value", [(int)$group]);
            if ($rows) $result[] = ['id'=>(int)$group, 'title'=>$title, 'values'=>$rows];
        }
        return $result;
    }
}
