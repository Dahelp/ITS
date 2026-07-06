<?php

$db = require __DIR__ . '/../config/config_db.php';

$pdo = new PDO($db['dsn'], $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$dryRun = !in_array('--apply', $argv, true);

function cleanup_h2_text(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('~\s+~u', ' ', $text);
    return mb_strtolower(trim($text), 'UTF-8');
}

function cleanup_bad_h2_intros(?string $html, int &$removed): string
{
    $html = (string)$html;

    return (string)preg_replace_callback(
        '~<p>\s*(.*?)\s*</p>\s*<h2\b([^>]*)>\s*(.*?)\s*</h2>~isu',
        static function (array $match) use (&$removed): string {
            $intro = cleanup_h2_text($match[1]);
            $h2 = cleanup_h2_text($match[3]);

            if ($h2 === '') {
                return $match[0];
            }

            $isBadDiskIntro = str_starts_with($intro, 'диск ' . $h2)
                && str_contains($intro, 'предназначен для монтажа');
            $isBadTubeIntro = str_starts_with($intro, 'камера ' . $h2)
                && str_contains($intro, 'применяется в пневматических шинах');
            $isBadFilterIntro = str_starts_with($intro, 'фильтр ' . $h2)
                && str_contains($intro, 'применяется для технического обслуживания');
            $isBadTruckTireIntro = str_starts_with($intro, 'грузовая шина ' . $h2)
                && str_contains($intro, 'предназначена для коммерческого транспорта');

            if (!$isBadDiskIntro && !$isBadTubeIntro && !$isBadFilterIntro && !$isBadTruckTireIntro) {
                return $match[0];
            }

            $removed++;
            return '<h2' . $match[2] . '>' . trim($match[3]) . '</h2>';
        },
        $html
    );
}

$targets = [
    ['product', 'content'],
    ['category', 'top_content'],
    ['category', 'content'],
    ['contents', 'content'],
    ['technics_type', 'content'],
    ['plagins_inseo', 'content'],
    ['attribute_value_category_canonical', 'top_content'],
    ['attribute_value_category_canonical', 'content'],
];

$totalRows = 0;
$totalRemoved = 0;

foreach ($targets as [$table, $field]) {
    $stmt = $pdo->query("SELECT id, `$field` AS value FROM `$table` WHERE `$field` LIKE '%<h2%'");
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $removed = 0;
        $newValue = cleanup_bad_h2_intros($row['value'] ?? '', $removed);

        if ($removed <= 0 || $newValue === (string)($row['value'] ?? '')) {
            continue;
        }

        $totalRows++;
        $totalRemoved += $removed;
        echo ($dryRun ? 'WOULD_UPDATE' : 'UPDATED')
            . " {$table}.{$field} id={$row['id']} removed={$removed}" . PHP_EOL;

        if (!$dryRun) {
            $update = $pdo->prepare("UPDATE `$table` SET `$field` = ? WHERE id = ?");
            $update->execute([$newValue, (int)$row['id']]);
        }
    }
}

echo PHP_EOL . 'Mode: ' . ($dryRun ? 'dry-run' : 'apply') . PHP_EOL;
echo "Rows changed: {$totalRows}" . PHP_EOL;
echo "Bad intro blocks removed: {$totalRemoved}" . PHP_EOL;
