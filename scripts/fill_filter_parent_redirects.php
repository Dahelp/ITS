<?php

/**
 * Builds missing parent-category filter redirects from the current database data.
 *
 * Dry-run:
 *   php scripts/fill_filter_parent_redirects.php
 *
 * Apply:
 *   php scripts/fill_filter_parent_redirects.php --apply
 *
 * Cleanup obsolete redirects from catalog source categories:
 *   php scripts/fill_filter_parent_redirects.php --cleanup-catalog-source-redirects
 *   php scripts/fill_filter_parent_redirects.php --cleanup-catalog-source-redirects --apply
 *
 * Audit existing redirects:
 *   php scripts/fill_filter_parent_redirects.php --audit-redirects
 *
 * Optional:
 *   --sources=shiny,industrialnye-shiny,diski,kamery-i-obodnye-lenty,filtry
 *
 * Logic:
 * - For each non-catalog parent category with children, find filters that are
 *   selectable through visible products inside that parent tree.
 * - If /category/{parent}/{filter} has a single safe target category, create
 *   or update an active redirect rule to /category/{target}/{filter}.
 * - If target is ambiguous, do not change DB; write it to ambiguous.json.
 */

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? '/index.php';

require __DIR__ . '/../config/init.php';
require __DIR__ . '/../config/db_bootstrap.php';

$apply = in_array('--apply', $argv, true);
$cleanupCatalogSourceRedirects = in_array('--cleanup-catalog-source-redirects', $argv, true);
$auditRedirects = in_array('--audit-redirects', $argv, true);
$sourceAliases = cliListOption($argv, '--sources=');
$excludedSourceAliases = ['uslugi'];
$now = date('Y-m-d H:i:s');
$outDir = ROOT . '/tmp/filter-parent-redirects-' . date('Ymd-His');

if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$categories = [];
$categoryIdByAlias = [];
$childrenByParent = [];

foreach (\R::getAll("SELECT id, parent_id, type_id, name, alias FROM category WHERE alias <> ''") as $row) {
    $id = (int)$row['id'];
    $parentId = (int)$row['parent_id'];
    $alias = (string)$row['alias'];

    $categories[$id] = $row;
    $categoryIdByAlias[$alias] = $id;
    $childrenByParent[$parentId][] = $id;
}

if ($cleanupCatalogSourceRedirects) {
    $obsoleteRedirects = findCatalogSourceRedirects($categories, $childrenByParent);

    file_put_contents($outDir . '/catalog_source_redirects.json', json_encode($obsoleteRedirects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    file_put_contents($outDir . '/catalog_source_redirects_backup.json', json_encode($obsoleteRedirects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    if ($apply && $obsoleteRedirects) {
        \R::begin();
        try {
            deleteRowsById(
                'attribute_value_category_canonical',
                array_map(static fn($row) => (int)$row['id'], $obsoleteRedirects)
            );
            \R::commit();
        } catch (\Throwable $e) {
            \R::rollback();
            fwrite(STDERR, $e->getMessage() . PHP_EOL);
            exit(1);
        }
    }

    echo json_encode([
        'mode' => $apply ? 'cleanup-apply' : 'cleanup-plan',
        'out_dir' => $outDir,
        'catalog_source_redirects' => count($obsoleteRedirects),
        'by_source' => array_count_values(array_map(static fn($i) => $i['source_category_alias'], $obsoleteRedirects)),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit;
}

if ($auditRedirects) {
    $redirectAudit = auditExistingRedirects($categories, $childrenByParent);

    file_put_contents($outDir . '/redirect_audit.json', json_encode($redirectAudit, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    echo json_encode([
        'mode' => 'audit',
        'out_dir' => $outDir,
        'total_redirects' => count($redirectAudit['all_redirects']),
        'ok' => count($redirectAudit['ok']),
        'obsolete_catalog_source' => count($redirectAudit['obsolete_catalog_source']),
        'target_not_descendant' => count($redirectAudit['target_not_descendant']),
        'target_has_no_products' => count($redirectAudit['target_has_no_products']),
        'target_is_catalog_source' => count($redirectAudit['target_is_catalog_source']),
        'redirect_to_self' => count($redirectAudit['redirect_to_self']),
        'by_source' => array_count_values(array_map(static fn($i) => $i['source_category_alias'], $redirectAudit['all_redirects'])),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit;
}

$sourceCategoryIds = [];

if ($sourceAliases) {
    foreach ($sourceAliases as $alias) {
        if (!empty($categoryIdByAlias[$alias])) {
            $sourceCategoryId = (int)$categoryIdByAlias[$alias];
            if ((int)($categories[$sourceCategoryId]['type_id'] ?? 0) !== 1) {
                $sourceCategoryIds[] = $sourceCategoryId;
            }
        }
    }
} else {
    foreach ($childrenByParent as $parentId => $children) {
        if ($parentId <= 0 || empty($children) || empty($categories[$parentId])) {
            continue;
        }

        $alias = (string)$categories[$parentId]['alias'];
        if (
            in_array($alias, $excludedSourceAliases, true)
            || (int)($categories[$parentId]['type_id'] ?? 0) === 1
        ) {
            continue;
        }

        $sourceCategoryIds[] = (int)$parentId;
    }
}

$sourceCategoryIds = array_values(array_unique($sourceCategoryIds));

$planned = [];
$alreadyRedirected = [];
$ambiguous = [];
$backup = [];
$seen = [];

foreach ($sourceCategoryIds as $sourceCategoryId) {
    $source = $categories[$sourceCategoryId] ?? null;
    if (!$source) {
        continue;
    }

    $sourceIds = descendantIds($sourceCategoryId, $childrenByParent, true);
    if (count($sourceIds) <= 1) {
        continue;
    }

    $attrRows = \R::getAll(
        "SELECT DISTINCT av.id, av.value, av.alias
        FROM attribute_value av
        JOIN attribute_product ap ON ap.attr_id = av.id
        JOIN product p ON p.id = ap.product_id
        WHERE av.hide = 'show'
          AND av.alias <> ''
          AND p.hide = 'show'
          AND p.category_id IN (" . implode(',', array_map('intval', $sourceIds)) . ")
        ORDER BY av.alias"
    );

    foreach ($attrRows as $attr) {
        $attrId = (int)$attr['id'];
        $chosen = chooseTargetCategory($attrId, $sourceCategoryId, $categories, $childrenByParent);

        if (!$chosen['target_id']) {
            if (!empty($chosen['ambiguous'])) {
                $ambiguous[] = [
                    'source_category_id' => $sourceCategoryId,
                    'source_category_alias' => $source['alias'],
                    'attr_id' => $attrId,
                    'attr_alias' => $attr['alias'],
                    'attr_value' => $attr['value'],
                    'reason' => $chosen['reason'],
                    'candidates' => $chosen['candidates'] ?? [],
                ];
            }
            continue;
        }

        $targetCategoryId = (int)$chosen['target_id'];
        if ($targetCategoryId === $sourceCategoryId || empty($categories[$targetCategoryId])) {
            continue;
        }

        $key = $sourceCategoryId . ':' . $attrId;
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;

        $target = $categories[$targetCategoryId];
        $existingSourceRule = getRule($attrId, $sourceCategoryId);

        if ($existingSourceRule) {
            $backup[] = $existingSourceRule;
        }

        $already = $existingSourceRule
            && (int)$existingSourceRule['is_active'] === 1
            && (string)$existingSourceRule['mode'] === 'redirect'
            && (int)$existingSourceRule['redirect_category_id'] === $targetCategoryId;

        if ($already) {
            $alreadyRedirected[] = [
                'source_category_id' => $sourceCategoryId,
                'source_category_alias' => $source['alias'],
                'source_category_name' => $source['name'],
                'target_category_id' => $targetCategoryId,
                'target_category_alias' => $target['alias'],
                'target_category_name' => $target['name'],
                'attr_id' => $attrId,
                'attr_alias' => $attr['alias'],
                'attr_value' => $attr['value'],
                'source_url' => '/category/' . $source['alias'] . '/' . $attr['alias'],
                'target_url' => '/category/' . $target['alias'] . '/' . $attr['alias'],
                'decision' => $chosen['reason'],
            ];
            continue;
        }

        $targetRule = getRule($attrId, $targetCategoryId);
        if ($targetRule) {
            $backup[] = $targetRule;
        }

        $planned[] = [
            'source_category_id' => $sourceCategoryId,
            'source_category_alias' => $source['alias'],
            'source_category_name' => $source['name'],
            'target_category_id' => $targetCategoryId,
            'target_category_alias' => $target['alias'],
            'target_category_name' => $target['name'],
            'attr_id' => $attrId,
            'attr_alias' => $attr['alias'],
            'attr_value' => $attr['value'],
            'source_url' => '/category/' . $source['alias'] . '/' . $attr['alias'],
            'target_url' => '/category/' . $target['alias'] . '/' . $attr['alias'],
            'decision' => $chosen['reason'],
            'source_existing_mode' => $existingSourceRule['mode'] ?? null,
            'target_has_landing' => $targetRule
                && (string)$targetRule['mode'] === 'landing'
                && (int)$targetRule['is_active'] === 1,
        ];
    }
}

file_put_contents($outDir . '/planned.json', json_encode($planned, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
file_put_contents($outDir . '/already_redirected.json', json_encode($alreadyRedirected, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
file_put_contents($outDir . '/ambiguous.json', json_encode($ambiguous, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
file_put_contents($outDir . '/backup.json', json_encode(array_values(uniqueRowsById($backup)), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

if ($apply && $planned) {
    \R::begin();
    try {
        foreach ($planned as $item) {
            ensureLanding((int)$item['attr_id'], (int)$item['target_category_id'], $now);
            upsertRedirect((int)$item['attr_id'], (int)$item['source_category_id'], (int)$item['target_category_id'], $now);
        }
        \R::commit();
    } catch (\Throwable $e) {
        \R::rollback();
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo json_encode([
    'mode' => $apply ? 'apply' : 'plan',
    'out_dir' => $outDir,
    'sources' => array_values(array_map(static fn($id) => $categories[$id]['alias'] ?? $id, $sourceCategoryIds)),
    'planned' => count($planned),
    'already_redirected' => count($alreadyRedirected),
    'ambiguous' => count($ambiguous),
    'planned_by_source' => array_count_values(array_map(static fn($i) => $i['source_category_alias'], $planned)),
    'already_redirected_by_source' => array_count_values(array_map(static fn($i) => $i['source_category_alias'], $alreadyRedirected)),
    'ambiguous_by_source' => array_count_values(array_map(static fn($i) => $i['source_category_alias'], $ambiguous)),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

function cliListOption(array $argv, string $prefix): array
{
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return array_values(array_filter(array_map('trim', explode(',', substr($arg, strlen($prefix))))));
        }
    }

    return [];
}

function findCatalogSourceRedirects(array $categories, array $childrenByParent): array
{
    $catalogSourceIds = [];

    foreach ($categories as $categoryId => $category) {
        if (
            (int)($category['type_id'] ?? 0) === 1
            && !empty($childrenByParent[(int)$categoryId])
        ) {
            $catalogSourceIds[] = (int)$categoryId;
        }
    }

    if (!$catalogSourceIds) {
        return [];
    }

    return \R::getAll(
        "SELECT
            avcc.*,
            source.alias AS source_category_alias,
            source.name AS source_category_name,
            target.alias AS target_category_alias,
            target.name AS target_category_name,
            av.alias AS attr_alias,
            av.value AS attr_value,
            CONCAT('/category/', source.alias, '/', av.alias) AS source_url,
            CONCAT('/category/', target.alias, '/', av.alias) AS target_url
        FROM attribute_value_category_canonical avcc
        JOIN category source ON source.id = avcc.category_id
        LEFT JOIN category target ON target.id = avcc.redirect_category_id
        JOIN attribute_value av ON av.id = avcc.attr_value_id
        WHERE avcc.category_id IN (" . implode(',', array_map('intval', $catalogSourceIds)) . ")
          AND avcc.mode = 'redirect'
        ORDER BY source.alias, av.alias, avcc.id"
    );
}

function auditExistingRedirects(array $categories, array $childrenByParent): array
{
    $rows = \R::getAll(
        "SELECT
            avcc.id,
            avcc.attr_value_id,
            avcc.category_id AS source_category_id,
            avcc.redirect_category_id AS target_category_id,
            source.alias AS source_category_alias,
            source.name AS source_category_name,
            source.type_id AS source_type_id,
            target.alias AS target_category_alias,
            target.name AS target_category_name,
            target.type_id AS target_type_id,
            av.alias AS attr_alias,
            av.value AS attr_value,
            CONCAT('/category/', source.alias, '/', av.alias) AS source_url,
            CONCAT('/category/', target.alias, '/', av.alias) AS target_url
        FROM attribute_value_category_canonical avcc
        JOIN category source ON source.id = avcc.category_id
        LEFT JOIN category target ON target.id = avcc.redirect_category_id
        JOIN attribute_value av ON av.id = avcc.attr_value_id
        WHERE avcc.is_active = 1
          AND avcc.mode = 'redirect'
        ORDER BY source.alias, av.alias, avcc.id"
    );

    $audit = [
        'all_redirects' => [],
        'ok' => [],
        'obsolete_catalog_source' => [],
        'target_not_descendant' => [],
        'target_has_no_products' => [],
        'target_is_catalog_source' => [],
        'redirect_to_self' => [],
    ];

    foreach ($rows as $row) {
        $sourceId = (int)$row['source_category_id'];
        $targetId = (int)$row['target_category_id'];
        $attrId = (int)$row['attr_value_id'];

        $problems = [];

        if ($targetId <= 0 || empty($categories[$targetId])) {
            $problems[] = 'missing_target';
        }

        if ($targetId === $sourceId) {
            $problems[] = 'redirect_to_self';
            $audit['redirect_to_self'][] = $row;
        }

        if (
            (int)($categories[$sourceId]['type_id'] ?? 0) === 1
            && !empty($childrenByParent[$sourceId])
        ) {
            $problems[] = 'obsolete_catalog_source';
            $audit['obsolete_catalog_source'][] = $row;
        }

        if (
            $targetId > 0
            && (int)($categories[$targetId]['type_id'] ?? 0) === 1
            && !empty($childrenByParent[$targetId])
        ) {
            $problems[] = 'target_is_catalog_source';
            $audit['target_is_catalog_source'][] = $row;
        }

        if (
            $targetId > 0
            && $sourceId > 0
            && $targetId !== $sourceId
            && !isDescendantOf($targetId, $sourceId, $categories)
        ) {
            $problems[] = 'target_not_descendant';
            $audit['target_not_descendant'][] = $row;
        }

        if ($targetId > 0 && !targetHasProductsForAttr($targetId, $attrId, $childrenByParent)) {
            $problems[] = 'target_has_no_products';
            $audit['target_has_no_products'][] = $row;
        }

        $row['problems'] = $problems;
        $audit['all_redirects'][] = $row;

        if (!$problems) {
            $audit['ok'][] = $row;
        }
    }

    return $audit;
}

function targetHasProductsForAttr(int $targetCategoryId, int $attrId, array $childrenByParent): bool
{
    $targetIds = descendantIds($targetCategoryId, $childrenByParent, true);
    if (!$targetIds) {
        return false;
    }

    return (int)\R::getCell(
        "SELECT COUNT(DISTINCT p.id)
        FROM product p
        JOIN attribute_product ap ON ap.product_id = p.id
        WHERE p.hide = 'show'
          AND ap.attr_id = ?
          AND p.category_id IN (" . implode(',', array_map('intval', $targetIds)) . ")",
        [$attrId]
    ) > 0;
}

function deleteRowsById(string $table, array $ids): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) {
        return;
    }

    foreach (array_chunk($ids, 500) as $chunk) {
        \R::exec(
            "DELETE FROM {$table} WHERE id IN (" . implode(',', $chunk) . ")"
        );
    }
}

function chooseTargetCategory(int $attrId, int $sourceCategoryId, array $categories, array $childrenByParent): array
{
    $landingRows = \R::getAll(
        "SELECT avcc.category_id, c.alias, c.name
        FROM attribute_value_category_canonical avcc
        JOIN category c ON c.id = avcc.category_id
        WHERE avcc.attr_value_id = ?
          AND avcc.is_active = 1
          AND avcc.mode = 'landing'
          AND c.alias <> ''",
        [$attrId]
    );

    $landingDescendants = [];
    $sourceLanding = null;
    foreach ($landingRows as $row) {
        $cid = (int)$row['category_id'];
        if ($cid === $sourceCategoryId) {
            $sourceLanding = $row;
        } elseif (isDescendantOf($cid, $sourceCategoryId, $categories)) {
            $landingDescendants[$cid] = $row;
        }
    }

    if ($sourceLanding && empty($childrenByParent[$sourceCategoryId])) {
        return [
            'target_id' => $sourceCategoryId,
            'reason' => 'source_existing_landing',
        ];
    }

    if (count($landingDescendants) === 1) {
        return [
            'target_id' => (int)array_key_first($landingDescendants),
            'reason' => 'single_existing_landing',
        ];
    }

    $productCategories = productCategoriesForAttrInSource($attrId, $sourceCategoryId, $childrenByParent);
    $targetId = deepestSingleBranch($sourceCategoryId, $productCategories, $categories, $childrenByParent);

    if ($targetId && $targetId !== $sourceCategoryId) {
        return [
            'target_id' => $targetId,
            'reason' => 'single_product_branch',
        ];
    }

    $candidates = [];
    foreach ($productCategories as $cid) {
        if (!empty($categories[$cid])) {
            $candidates[] = [
                'id' => $cid,
                'alias' => $categories[$cid]['alias'],
                'name' => $categories[$cid]['name'],
            ];
        }
    }

    return [
        'target_id' => null,
        'ambiguous' => true,
        'reason' => 'multiple_product_branches_without_single_landing',
        'candidates' => $candidates,
    ];
}

function productCategoriesForAttrInSource(int $attrId, int $sourceCategoryId, array $childrenByParent): array
{
    $sourceIds = descendantIds($sourceCategoryId, $childrenByParent, true);

    return array_values(array_unique(array_map('intval', \R::getCol(
        "SELECT DISTINCT p.category_id
        FROM attribute_product ap
        JOIN product p ON p.id = ap.product_id
        WHERE ap.attr_id = ?
          AND p.hide = 'show'
          AND p.category_id IN (" . implode(',', array_map('intval', $sourceIds)) . ")",
        [$attrId]
    ))));
}

function deepestSingleBranch(int $sourceCategoryId, array $productCategories, array $categories, array $childrenByParent): ?int
{
    $current = $sourceCategoryId;

    while (true) {
        $branches = [];
        foreach ($childrenByParent[$current] ?? [] as $childId) {
            foreach ($productCategories as $productCategoryId) {
                if ($productCategoryId === $childId || isDescendantOf($productCategoryId, $childId, $categories)) {
                    $branches[$childId] = true;
                    break;
                }
            }
        }

        if (count($branches) !== 1) {
            return null;
        }

        $current = (int)array_key_first($branches);

        if (empty($childrenByParent[$current])) {
            return $current;
        }
    }
}

function descendantIds(int $categoryId, array $childrenByParent, bool $includeSelf): array
{
    $ids = $includeSelf ? [$categoryId] : [];
    $queue = [$categoryId];

    while ($queue) {
        $current = array_shift($queue);
        foreach ($childrenByParent[$current] ?? [] as $childId) {
            $childId = (int)$childId;
            if (!in_array($childId, $ids, true)) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }
    }

    return $ids;
}

function isDescendantOf(int $categoryId, int $ancestorId, array $categories): bool
{
    $current = $categories[$categoryId] ?? null;

    while ($current) {
        $parentId = (int)$current['parent_id'];
        if ($parentId === $ancestorId) {
            return true;
        }
        if ($parentId <= 0 || empty($categories[$parentId])) {
            return false;
        }
        $current = $categories[$parentId];
    }

    return false;
}

function getRule(int $attrId, int $categoryId): ?array
{
    $row = \R::getRow(
        "SELECT *
        FROM attribute_value_category_canonical
        WHERE attr_value_id = ?
        AND category_id = ?
        LIMIT 1",
        [$attrId, $categoryId]
    );

    return $row ?: null;
}

function ensureLanding(int $attrId, int $categoryId, string $now): void
{
    $existing = getRule($attrId, $categoryId);

    if ($existing) {
        if ((string)$existing['mode'] === 'landing' && (int)$existing['is_active'] === 1) {
            return;
        }

        \R::exec(
            "UPDATE attribute_value_category_canonical
            SET is_active = 1,
                mode = 'landing',
                redirect_category_id = NULL,
                updated_at = ?
            WHERE id = ?",
            [$now, (int)$existing['id']]
        );
        return;
    }

    \R::exec(
        "INSERT INTO attribute_value_category_canonical
        (attr_value_id, category_id, is_active, mode, source, redirect_category_id, seo_h1, title, description, keywords, top_content, content, img, canonical_url, robots, created_at, updated_at)
        VALUES (?, ?, 1, 'landing', 'manual', NULL, '', '', '', '', '', '', '', '', '', ?, ?)",
        [$attrId, $categoryId, $now, $now]
    );
}

function upsertRedirect(int $attrId, int $categoryId, int $targetCategoryId, string $now): void
{
    $existing = getRule($attrId, $categoryId);

    if ($existing) {
        \R::exec(
            "UPDATE attribute_value_category_canonical
            SET is_active = 1,
                mode = 'redirect',
                redirect_category_id = ?,
                source = 'manual',
                updated_at = ?
            WHERE id = ?",
            [$targetCategoryId, $now, (int)$existing['id']]
        );
        return;
    }

    \R::exec(
        "INSERT INTO attribute_value_category_canonical
        (attr_value_id, category_id, is_active, mode, source, redirect_category_id, seo_h1, title, description, keywords, top_content, content, img, canonical_url, robots, created_at, updated_at)
        VALUES (?, ?, 1, 'redirect', 'manual', ?, '', '', '', '', '', '', '', '', '', ?, ?)",
        [$attrId, $categoryId, $targetCategoryId, $now, $now]
    );
}

function uniqueRowsById(array $rows): array
{
    $result = [];

    foreach ($rows as $row) {
        if (!empty($row['id'])) {
            $result[(int)$row['id']] = $row;
        }
    }

    return $result;
}
