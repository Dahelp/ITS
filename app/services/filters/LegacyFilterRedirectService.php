<?php

namespace app\services\filters;

class LegacyFilterRedirectService
{
    public function resolve(string $filterGroupUrl, string $filterValueAlias): ?string
    {
        $filterGroupUrl = trim($filterGroupUrl, '/');
        $filterValueAlias = trim($filterValueAlias, '/');

        if ($filterGroupUrl === '' || $filterValueAlias === '') {
            return null;
        }

        $group = \R::findOne(
            'attribute_group',
            'url_params = ?',
            [$filterGroupUrl]
        );

        if (!$group || empty($group->id)) {
            return null;
        }

        $value = \R::findOne(
            'attribute_value',
            "attr_group_id = ? AND alias = ? AND hide = 'show'",
            [(int)$group->id, $filterValueAlias]
        );

        if (!$value || empty($value->id)) {
            return null;
        }

        $bestCategory = \R::getRow(
            "SELECT c.alias, COUNT(*) AS product_count
             FROM attribute_product ap
             INNER JOIN product p ON p.id = ap.product_id
             INNER JOIN category c ON c.id = p.category_id
             WHERE ap.attr_id = ?
               AND p.hide = 'show'
               AND c.alias <> ''
             GROUP BY c.id, c.alias
             ORDER BY product_count DESC, c.id ASC
             LIMIT 1",
            [(int)$value->id]
        );

        if (empty($bestCategory['alias'])) {
            return rtrim(PATH, '/') . '/catalog';
        }

        return FilterUrlHelper::buildCategoryFilterUrl(
            (string)$bestCategory['alias'],
            (string)$value->alias
        );
    }
}
