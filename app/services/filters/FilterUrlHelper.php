<?php

namespace app\services\filters;

class FilterUrlHelper
{
    public static function buildCategoryFilterPath(string $categoryAlias, string $filterValueAlias): string
    {
        $categoryAlias = trim($categoryAlias, '/');
        $filterValueAlias = trim($filterValueAlias, '/');

        if ($categoryAlias === '' || $filterValueAlias === '') {
            return '';
        }

        return '/category/' . rawurlencode($categoryAlias) . '/' . self::encodePathValue($filterValueAlias);
    }

    public static function buildCategoryFilterUrl(string $categoryAlias, string $filterValueAlias): string
    {
        $path = self::buildCategoryFilterPath($categoryAlias, $filterValueAlias);

        if ($path === '') {
            return '';
        }

        return rtrim(PATH, '/') . $path;
    }

    public static function buildBestCategoryFilterPath(int $filterValueId, string $filterValueAlias, string $fallbackGroupUrl = ''): string
    {
        $filterValueAlias = trim($filterValueAlias, '/');
        if ($filterValueId <= 0 || $filterValueAlias === '') {
            return self::buildFallbackFilterPath($fallbackGroupUrl, $filterValueAlias);
        }

        static $cache = [];
        $cacheKey = $filterValueId . ':' . $filterValueAlias;

        if (!array_key_exists($cacheKey, $cache)) {
            $categoryAlias = '';
            $landingRows = \R::getAll(
                "SELECT c.id, c.alias
                 FROM attribute_value_category_canonical avcc
                 INNER JOIN category c ON c.id = avcc.category_id
                 WHERE avcc.attr_value_id = ?
                   AND avcc.is_active = 1
                   AND avcc.mode = 'landing'
                   AND c.alias <> ''
                 ORDER BY avcc.id ASC
                 ",
                [$filterValueId]
            );

            if (!empty($landingRows)) {
                $catModel = new \app\models\Category();

                foreach ($landingRows as $landingRow) {
                    $categoryId = (int)($landingRow['id'] ?? 0);
                    $landingAlias = trim((string)($landingRow['alias'] ?? ''), '/');

                    if ($categoryId <= 0 || $landingAlias === '') {
                        continue;
                    }

                    $categoryIds = $catModel->getIds($categoryId);
                    $categoryIds = !$categoryIds ? (string)$categoryId : $categoryIds . $categoryId;
                    $hasProducts = (int)\R::getCell(
                        "SELECT COUNT(DISTINCT p.id)
                         FROM product p
                         INNER JOIN attribute_product ap ON ap.product_id = p.id
                         WHERE p.hide = 'show'
                           AND p.category_id IN ($categoryIds)
                           AND ap.attr_id = ?",
                        [$filterValueId]
                    );

                    if ($hasProducts > 0) {
                        $categoryAlias = $landingAlias;
                        break;
                    }
                }
            }

            if ($categoryAlias === '') {
                $categoryAlias = (string)\R::getCell(
                    "SELECT c.alias
                     FROM attribute_product ap
                     INNER JOIN product p ON p.id = ap.product_id
                     INNER JOIN category c ON c.id = p.category_id
                     WHERE ap.attr_id = ?
                       AND p.hide = 'show'
                       AND c.alias <> ''
                     GROUP BY c.id, c.alias
                     ORDER BY COUNT(*) DESC, c.id ASC
                     LIMIT 1",
                    [$filterValueId]
                );
            }

            $cache[$cacheKey] = trim($categoryAlias, '/');
        }

        if ($cache[$cacheKey] !== '') {
            return self::buildCategoryFilterPath($cache[$cacheKey], $filterValueAlias);
        }

        return self::buildFallbackFilterPath($fallbackGroupUrl, $filterValueAlias);
    }

    private static function buildFallbackFilterPath(string $groupUrl, string $filterValueAlias): string
    {
        $groupUrl = trim($groupUrl, '/');
        $filterValueAlias = trim($filterValueAlias, '/');

        if ($groupUrl === '' || $filterValueAlias === '') {
            return '';
        }

        return '/' . rawurlencode($groupUrl) . '/' . self::encodePathValue($filterValueAlias);
    }

    private static function encodePathValue(string $value): string
    {
        $parts = array_map('rawurlencode', explode('/', $value));
        return implode('/', $parts);
    }
}
