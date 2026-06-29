<?php

if (PHP_SAPI !== 'cli') {
    $expectedToken = defined('H2_CONTENT_TEXTS_WEB_TOKEN') ? (string)H2_CONTENT_TEXTS_WEB_TOKEN : '';
    $givenToken = isset($_GET['token']) ? (string)$_GET['token'] : '';

    header('Content-Type: text/plain; charset=utf-8');

    if ($expectedToken === '' || !hash_equals($expectedToken, $givenToken)) {
        http_response_code(403);
        echo 'Forbidden' . PHP_EOL;
        exit(1);
    }
}

$db = require __DIR__ . '/../config/config_db.php';

$pdo = new PDO($db['dsn'], $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$dryRun = PHP_SAPI === 'cli'
    ? in_array('--dry-run', $argv, true)
    : (($_GET['apply'] ?? '') !== '1');
$sourceFile = __DIR__ . '/h2_content_texts_2026-06-29.json';
$rows = json_decode((string)file_get_contents($sourceFile), true);

if (!is_array($rows)) {
    throw new RuntimeException('Cannot read H2 source file: ' . $sourceFile);
}

$stats = ['updated' => 0, 'skipped' => 0, 'not_found' => 0];

function h2_text(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('~\s+~u', ' ', $text);
    return mb_strtolower(trim((string)$text), 'UTF-8');
}

function h2_any(?string $html): bool
{
    return is_string($html) && preg_match('~<h2\b~i', $html) === 1;
}

function h2_exact(?string $html, string $h2): bool
{
    if (!is_string($html) || $html === '') {
        return false;
    }

    if (!preg_match_all('~<h2\b[^>]*>(.*?)</h2>~isu', $html, $matches)) {
        return false;
    }

    $needle = h2_text($h2);
    foreach ($matches[1] as $heading) {
        if (h2_text($heading) === $needle) {
            return true;
        }
    }

    return false;
}

function h2_remove(?string $html, string $h2): string
{
    $needle = h2_text($h2);
    return (string)preg_replace_callback(
        '~\s*<h2\b[^>]*>(.*?)</h2>\s*~isu',
        static fn(array $m): string => h2_text($m[1]) === $needle ? "\n" : $m[0],
        (string)$html
    );
}

function h2_is_directive(string $instruction): bool
{
    $lower = mb_strtolower($instruction, 'UTF-8');
    return $instruction === ''
        || mb_strpos($lower, 'не требуется') !== false
        || mb_strpos($lower, 'большим текстом') !== false
        || mb_strpos($lower, 'нижний текст') !== false
        || mb_strpos($lower, 'после картинки') !== false
        || mb_strpos($lower, '<h2>') !== false;
}

function h2_override(string $instruction, string $fallback): string
{
    if (preg_match('~<h2\b[^>]*>(.*?)</h2>~isu', $instruction, $match)) {
        return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return $fallback;
}

function h2_snippet(string $h2, string $intro): string
{
    $parts = [];
    if ($intro !== '') {
        $parts[] = '<p>' . htmlspecialchars($intro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    }
    $parts[] = '<h2>' . htmlspecialchars($h2, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
    return implode(PHP_EOL, $parts);
}

function h2_prepend(?string $html, string $h2, string $intro): string
{
    $html = trim((string)$html);
    $snippet = h2_snippet($h2, $intro);
    return $html === '' ? $snippet : $snippet . PHP_EOL . PHP_EOL . $html;
}

function h2_one(PDO $pdo, string $sql, array $params): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function h2_save(PDO $pdo, bool $dryRun, string $table, int $id, string $field, string $value): void
{
    if ($dryRun) {
        return;
    }

    $stmt = $pdo->prepare("UPDATE `$table` SET `$field` = ? WHERE id = ?");
    $stmt->execute([$value, $id]);
}

function h2_save_avcc(PDO $pdo, bool $dryRun, int $id, string $field, string $value): void
{
    if ($dryRun) {
        return;
    }

    $stmt = $pdo->prepare("UPDATE attribute_value_category_canonical SET `$field` = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$value, $id]);
}

function h2_category_ids(PDO $pdo, int $categoryId): array
{
    $ids = [$categoryId];
    $queue = [$categoryId];

    while ($queue) {
        $placeholders = implode(',', array_fill(0, count($queue), '?'));
        $stmt = $pdo->prepare("SELECT id FROM category WHERE parent_id IN ($placeholders)");
        $stmt->execute($queue);
        $queue = [];

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $id = (int)$id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
                $queue[] = $id;
            }
        }
    }

    return $ids;
}

function h2_filter_value(PDO $pdo, int $categoryId, string $alias): ?array
{
    $ids = h2_category_ids($pdo, $categoryId);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$alias], $ids);

    $row = h2_one($pdo, "
        SELECT av.id, COUNT(DISTINCT p.id) AS products_count
        FROM attribute_value av
        INNER JOIN attribute_product ap ON ap.attr_id = av.id
        INNER JOIN product p ON p.id = ap.product_id
        WHERE av.alias = ?
          AND av.hide = 'show'
          AND p.hide = 'show'
          AND p.category_id IN ($placeholders)
        GROUP BY av.id
        ORDER BY products_count DESC, av.id ASC
        LIMIT 1
    ", $params);

    return $row ?: h2_one($pdo, "SELECT id FROM attribute_value WHERE alias = ? AND hide = 'show' ORDER BY id ASC LIMIT 1", [$alias]);
}

function h2_avcc(PDO $pdo, bool $dryRun, int $categoryId, int $attrValueId): ?array
{
    $row = h2_one($pdo, "
        SELECT id, top_content, content
        FROM attribute_value_category_canonical
        WHERE category_id = ? AND attr_value_id = ?
        LIMIT 1
    ", [$categoryId, $attrValueId]);

    if ($row) {
        return $row;
    }

    if ($dryRun) {
        return ['id' => 0, 'top_content' => '', 'content' => ''];
    }

    $stmt = $pdo->prepare("INSERT INTO attribute_value_category_canonical
        (attr_value_id, category_id, is_active, mode, source, created_at, updated_at)
        VALUES (?, ?, 1, 'landing', 'manual', NOW(), NOW())");
    $stmt->execute([$attrValueId, $categoryId]);

    return ['id' => (int)$pdo->lastInsertId(), 'top_content' => '', 'content' => ''];
}

function h2_podbor_inseo(PDO $pdo, bool $dryRun, int $categoryId, string $h2): array
{
    $row = h2_one($pdo, "SELECT id, content FROM plagins_inseo WHERE tip = 'product' AND category_id = ? LIMIT 1", [$categoryId]);
    if ($row) {
        return $row;
    }

    if ($dryRun) {
        return ['id' => 0, 'content' => ''];
    }

    $stmt = $pdo->prepare("INSERT INTO plagins_inseo
        (tip, category_id, name, content, title, description, keywords, hide)
        VALUES ('product', ?, ?, '', ?, '', '', 'show')");
    $stmt->execute([$categoryId, $h2, $h2]);

    return ['id' => (int)$pdo->lastInsertId(), 'content' => ''];
}

function h2_apply(array $fields, string $targetField, string $h2, string $intro, ?string $removeH2): array
{
    $newFields = $fields;
    if ($removeH2) {
        foreach ($newFields as $field => $value) {
            $newFields[$field] = h2_remove($value, $removeH2);
        }
    }

    foreach ($newFields as $value) {
        if (h2_exact($value, $h2)) {
            return ['status' => 'skip', 'reason' => 'h2 already exists', 'fields' => $newFields];
        }
    }

    foreach ($newFields as $value) {
        if (h2_any($value)) {
            return ['status' => 'skip', 'reason' => 'another h2 already exists', 'fields' => $newFields];
        }
    }

    $newFields[$targetField] = h2_prepend($newFields[$targetField] ?? '', $h2, $intro);
    return ['status' => 'update', 'reason' => $targetField, 'fields' => $newFields];
}

function h2_path(string $url): string
{
    $path = parse_url(trim($url), PHP_URL_PATH) ?: '';
    return '/' . trim(rawurldecode($path), '/');
}

foreach ($rows as $row) {
    $path = h2_path((string)$row['url']);
    $parts = array_values(array_filter(explode('/', trim($path, '/')), static fn($v) => $v !== ''));
    $originalH2 = trim((string)$row['h2']);
    $instruction = trim((string)$row['instruction']);
    $h2 = h2_override($instruction, $originalH2);
    $removeH2 = $h2 !== $originalH2 ? $originalH2 : null;
    $intro = h2_is_directive($instruction) ? '' : $instruction;
    $target = $intro !== '' ? 'top_content' : 'content';
    $label = $path . ' -> ' . $h2;

    try {
        if (($parts[0] ?? '') === 'category' && !empty($parts[1])) {
            $category = h2_one($pdo, "SELECT id, top_content, content FROM category WHERE alias = ? LIMIT 1", [$parts[1]]);
            if (!$category) {
                echo "NOT_FOUND category: $label" . PHP_EOL;
                $stats['not_found']++;
                continue;
            }

            if (count($parts) === 2) {
                $result = h2_apply([
                    'top_content' => $category['top_content'] ?? '',
                    'content' => $category['content'] ?? '',
                ], $target, $h2, $intro, $removeH2);

                if ($result['status'] === 'update' || $removeH2) {
                    h2_save($pdo, $dryRun, 'category', (int)$category['id'], 'top_content', $result['fields']['top_content']);
                    h2_save($pdo, $dryRun, 'category', (int)$category['id'], 'content', $result['fields']['content']);
                    echo "UPDATED category {$result['reason']}: $label" . PHP_EOL;
                    $stats['updated']++;
                } else {
                    echo "SKIP category {$result['reason']}: $label" . PHP_EOL;
                    $stats['skipped']++;
                }
                continue;
            }

            $attr = h2_filter_value($pdo, (int)$category['id'], implode('/', array_slice($parts, 2)));
            if (!$attr) {
                echo "NOT_FOUND filter: $label" . PHP_EOL;
                $stats['not_found']++;
                continue;
            }

            $avcc = h2_avcc($pdo, $dryRun, (int)$category['id'], (int)$attr['id']);
            if (!$avcc) {
                echo "SKIP filter no landing row in dry-run: $label" . PHP_EOL;
                $stats['skipped']++;
                continue;
            }

            $result = h2_apply([
                'top_content' => $avcc['top_content'] ?? '',
                'content' => $avcc['content'] ?? '',
            ], $target, $h2, $intro, $removeH2);

            if ($result['status'] === 'update' || $removeH2) {
                h2_save_avcc($pdo, $dryRun, (int)$avcc['id'], 'top_content', $result['fields']['top_content']);
                h2_save_avcc($pdo, $dryRun, (int)$avcc['id'], 'content', $result['fields']['content']);
                echo "UPDATED filter {$result['reason']}: $label" . PHP_EOL;
                $stats['updated']++;
            } else {
                echo "SKIP filter {$result['reason']}: $label" . PHP_EOL;
                $stats['skipped']++;
            }
            continue;
        }

        if (in_array(($parts[0] ?? ''), ['pages', 'services', 'articles', 'news'], true) && !empty($parts[1])) {
            $type = h2_one($pdo, "SELECT id FROM content_type WHERE param_url = ? LIMIT 1", [$parts[0]]);
            $content = $type ? h2_one($pdo, "SELECT id, content FROM contents WHERE alias = ? AND type_id = ? LIMIT 1", [$parts[1], (int)$type['id']]) : null;
            if (!$content) {
                echo "NOT_FOUND content: $label" . PHP_EOL;
                $stats['not_found']++;
                continue;
            }

            $result = h2_apply(['content' => $content['content'] ?? ''], 'content', $h2, $intro, $removeH2);
            if ($result['status'] === 'update' || $removeH2) {
                h2_save($pdo, $dryRun, 'contents', (int)$content['id'], 'content', $result['fields']['content']);
                echo "UPDATED content {$result['reason']}: $label" . PHP_EOL;
                $stats['updated']++;
            } else {
                echo "SKIP content {$result['reason']}: $label" . PHP_EOL;
                $stats['skipped']++;
            }
            continue;
        }

        if (($parts[0] ?? '') === 'technics' && ($parts[1] ?? '') === 'type' && !empty($parts[2])) {
            $type = h2_one($pdo, "SELECT id, content FROM technics_type WHERE alias = ? LIMIT 1", [$parts[2]]);
            if (!$type) {
                echo "NOT_FOUND technics_type: $label" . PHP_EOL;
                $stats['not_found']++;
                continue;
            }

            $result = h2_apply(['content' => $type['content'] ?? ''], 'content', $h2, $intro, $removeH2);
            if ($result['status'] === 'update' || $removeH2) {
                h2_save($pdo, $dryRun, 'technics_type', (int)$type['id'], 'content', $result['fields']['content']);
                echo "UPDATED technics_type {$result['reason']}: $label" . PHP_EOL;
                $stats['updated']++;
            } else {
                echo "SKIP technics_type {$result['reason']}: $label" . PHP_EOL;
                $stats['skipped']++;
            }
            continue;
        }

        if (($parts[0] ?? '') === 'product' && !empty($parts[1])) {
            $product = h2_one($pdo, "SELECT id, content FROM product WHERE alias = ? LIMIT 1", [$parts[1]]);
            if (!$product) {
                echo "NOT_FOUND product: $label" . PHP_EOL;
                $stats['not_found']++;
                continue;
            }

            $result = h2_apply(['content' => $product['content'] ?? ''], 'content', $h2, $intro, $removeH2);
            if ($result['status'] === 'update' || $removeH2) {
                h2_save($pdo, $dryRun, 'product', (int)$product['id'], 'content', $result['fields']['content']);
                echo "UPDATED product {$result['reason']}: $label" . PHP_EOL;
                $stats['updated']++;
            } else {
                echo "SKIP product {$result['reason']}: $label" . PHP_EOL;
                $stats['skipped']++;
            }
            continue;
        }

        if (($parts[0] ?? '') === 'podbor' && !empty($parts[1])) {
            $category = h2_one($pdo, "SELECT id FROM category WHERE alias = ? LIMIT 1", [$parts[1]]);
            if (!$category) {
                echo "NOT_FOUND podbor category: $label" . PHP_EOL;
                $stats['not_found']++;
                continue;
            }

            $inseo = h2_podbor_inseo($pdo, $dryRun, (int)$category['id'], $h2);
            $result = h2_apply(['content' => $inseo['content'] ?? ''], 'content', $h2, $intro, $removeH2);
            if ($result['status'] === 'update' || $removeH2) {
                h2_save($pdo, $dryRun, 'plagins_inseo', (int)$inseo['id'], 'content', $result['fields']['content']);
                echo "UPDATED podbor {$result['reason']}: $label" . PHP_EOL;
                $stats['updated']++;
            } else {
                echo "SKIP podbor {$result['reason']}: $label" . PHP_EOL;
                $stats['skipped']++;
            }
            continue;
        }

        echo "SKIP unsupported page without editable text field: $label" . PHP_EOL;
        $stats['skipped']++;
    } catch (Throwable $e) {
        echo "ERROR {$e->getMessage()}: $label" . PHP_EOL;
        $stats['not_found']++;
    }
}

echo PHP_EOL . 'Mode: ' . ($dryRun ? 'dry-run' : 'apply') . PHP_EOL;
echo "Updated: {$stats['updated']}" . PHP_EOL;
echo "Skipped: {$stats['skipped']}" . PHP_EOL;
echo "Not found/errors: {$stats['not_found']}" . PHP_EOL;
