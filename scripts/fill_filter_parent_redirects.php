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
 * Optional:
 *   --sources=shiny,industrialnye-shiny,diski,kamery-i-obodnye-lenty,filtry
 *
 * Logic:
 * - For each parent category with children, find filters that are selectable
 *   through visible products inside that parent tree.
 * - If /category/{parent}/{filter} has a single safe target category, create
 *   or update an active redirect rule to /category/{target}/{filter}.
 * - If target is ambiguous, do not change DB; write it to ambiguous.json.
 */

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? '/index.php';

require __DIR__ . '/../config/init.php';
require __DIR__ . '/../config/db_bootstrap.php';

$apply = in_array('--apply', $argv, true);
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

foreach (\R::getAll("SELECT id, parent_id, name, alias FROM category WHERE alias <> ''") as $row) {
    $id = (int)$row['id'];
    $parentId = (int)$row['parent_id'];
    $alias = (string)$row['alias'];

    $categories[$id] = $row;
    $categoryIdByAlias[$alias] = $id;
    $childrenByParent[$parentId][] = $id;
}

$sourceCategoryIds = [];

if ($sourceAliases) {
    foreach ($sourceAliases as $alias) {
        if (!empty($categoryIdByAlias[$alias])) {
            $sourceCategoryIds[] = (int)$categoryIdByAlias[$alias];
        }
    }
} else {
    foreach ($childrenByParent as $parentId => $children) {
        if ($parentId <= 0 || empty($children) || empty($categories[$parentId])) {
            continue;
        }

        $alias = (string)$categories[$parentId]['alias'];
        if (in_array($alias, $excludedSourceAliases, true)) {
            continue;
        }

        $sourceCategoryIds[] = (int)$parentId;
    }
}

$sourceCategoryIds = array_values(array_unique($sourceCategoryIds));

$planned = [];
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
    'ambiguous' => count($ambiguous),
    'by_source' => array_count_values(array_map(static fn($i) => $i['source_category_alias'], $planned)),
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
    foreach ($landingRows as $row) {
        $cid = (int)$row['category_id'];
        if ($cid !== $sourceCategoryId && isDescendantOf($cid, $sourceCategoryId, $categories)) {
            $landingDescendants[$cid] = $row;
        }
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
