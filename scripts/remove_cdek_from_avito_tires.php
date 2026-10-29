<?php
require dirname(__DIR__).'/config/init.php';
require CONF.'/db_bootstrap.php';

$rows = \R::getAll("SELECT id, product_type, title, description
    FROM avito_ad
    WHERE description LIKE '%СДЭК%'");
$updated = 0;
$skipped = 0;

foreach ($rows as $row) {
    $type = mb_strtolower(trim((string)($row['product_type'] ?? '')), 'UTF-8');
    $title = mb_strtolower(strip_tags((string)($row['title'] ?? '')), 'UTF-8');
    $isTire = in_array($type, [
        'мотошины',
        'шины для грузовиков и спецтехники',
    ], true) || preg_match('~(?<![\p{L}\p{N}])шин(?:а|ы|ы для|окомплект)?(?![\p{L}\p{N}])~u', $title);

    if (!$isTire) {
        $skipped++;
        continue;
    }

    $before = (string)$row['description'];
    $after = preg_replace('~\bСДЭК\b\s*(?:[,;:/\\|—–-]\s*)?~iu', '', $before);
    $after = preg_replace('~,\s*,+~u', ', ', $after);
    $after = preg_replace('~[ \t]{2,}~u', ' ', $after);
    $after = preg_replace('~[ \t]+([,.!?;:])~u', '$1', $after);
    $after = preg_replace('~(<br\s*/?>)\s*[,;:/\\|—–-]+\s*~iu', '$1', $after);

    if ($after !== $before) {
        \R::exec('UPDATE avito_ad SET description=? WHERE id=?', [$after, (int)$row['id']]);
        $updated++;
    }
}

echo json_encode(['matched'=>count($rows),'updated'=>$updated,'skipped_non_tire'=>$skipped], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
