<?php

declare(strict_types=1);

/**
 * Removes the retired manufacturer/model data and trademark mentions.
 * Dry-run is the default. Use --apply only after reviewing the backup.
 *
 * Usage:
 *   php bin/remove_cfmoto.php
 *   php bin/remove_cfmoto.php --apply
 */

define('ROOT', dirname(__DIR__));
define('CONF', ROOT . '/config');
require ROOT . '/vendor/autoload.php';
require CONF . '/db_bootstrap.php';

$apply = in_array('--apply', $argv, true);
$manufacturerId = 71;
$crossVendorId = 244;
$modelIds = [994, 995, 996];
$imageNames = [
    'd0e95edf201d958dbafc72.webp',
    '9210386f5576350499320231abcd7dfa.webp',
    '9210386f5576350499320231abcd7dfa.avif',
    'cecbef290822994782dc4145832206b1.webp',
    'cecbef290822994782dc4145832206b1.avif',
    'fa0d2733e45882eee479d5113e8ede0e.webp',
    'fa0d2733e45882eee479d5113e8ede0e.avif',
];
$backupDir = ROOT . '/tmp/cfmoto-cleanup-backup-' . date('Ymd-His');

if (!mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Cannot create backup directory: ' . $backupDir);
}

$modelSlots = implode(',', array_fill(0, count($modelIds), '?'));
$backup = [
    'created_at' => date(DATE_ATOM),
    'mode' => $apply ? 'apply' : 'dry-run',
    'technics_manufacturer' => R::getAll('SELECT * FROM technics_manufacturer WHERE id = ?', [$manufacturerId]),
    'technics' => R::getAll("SELECT * FROM technics WHERE id IN ($modelSlots) ORDER BY id", $modelIds),
    'technics_tiposize' => R::getAll("SELECT * FROM technics_tiposize WHERE technics_id IN ($modelSlots) ORDER BY id", $modelIds),
    'plagins_cross_vendor' => R::getAll('SELECT * FROM plagins_cross_vendor WHERE id = ?', [$crossVendorId]),
    'plagins_cross' => R::getAll('SELECT * FROM plagins_cross WHERE vendor_id = ? ORDER BY id', [$crossVendorId]),
    'avito_ad' => R::getAll(
        "SELECT * FROM avito_ad
          WHERE description REGEXP '(cf[[:space:]_-]*moto|sf[[:space:]_-]*moto|сф[[:space:]_-]*мото|цфмото)'
          ORDER BY id"
    ),
    'chat_session' => R::getAll(
        "SELECT * FROM chat_session
          WHERE page_url REGEXP '(cf[[:space:]_-]*moto|sf[[:space:]_-]*moto|сф[[:space:]_-]*мото|цфмото|cf500[[:space:]]+x5|сфорсе|kvadrocikl-moto-cf-)'"
    ),
];

$databaseBackupJson = json_encode(
    $backup,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE
);
if ($databaseBackupJson === false || file_put_contents(
    $backupDir . '/database.json',
    $databaseBackupJson
) === false) {
    throw new RuntimeException('Cannot write database backup: ' . $backupDir . '/database.json');
}

$assetRoots = [
    ROOT . '/public/images/technics/baseimg',
    ROOT . '/public/images/technics/mini',
    ROOT . '/public/images/technics_manufacturer/baseimg',
];
$backedUpAssets = [];
foreach ($assetRoots as $assetRoot) {
    foreach ($imageNames as $imageName) {
        $source = $assetRoot . '/' . $imageName;
        if (!is_file($source)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($source, strlen(ROOT) + 1));
        $destination = $backupDir . '/files/' . $relative;
        $destinationDir = dirname($destination);
        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
            throw new RuntimeException('Cannot create backup directory: ' . $destinationDir);
        }
        if (!copy($source, $destination)) {
            throw new RuntimeException('Cannot back up image: ' . $source);
        }
        $backedUpAssets[] = ['source' => $source, 'backup' => $destination];
    }
}
$fileBackupJson = json_encode(
    $backedUpAssets,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE
);
if ($fileBackupJson === false || file_put_contents(
    $backupDir . '/files.json',
    $fileBackupJson
) === false) {
    throw new RuntimeException('Cannot write file backup manifest: ' . $backupDir . '/files.json');
}

$summary = [
    'backup_dir' => $backupDir,
    'manufacturer_rows' => count($backup['technics_manufacturer']),
    'model_rows' => count($backup['technics']),
    'size_rows' => count($backup['technics_tiposize']),
    'cross_vendor_rows' => count($backup['plagins_cross_vendor']),
    'cross_rows' => count($backup['plagins_cross']),
    'avito_rows' => count($backup['avito_ad']),
    'chat_session_rows' => count($backup['chat_session']),
    'image_files' => count($backedUpAssets),
];

if (!$apply) {
    echo json_encode($summary + ['status' => 'dry-run'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
    exit;
}

R::begin();
try {
    foreach ($backup['avito_ad'] as $row) {
        $cleaned = preg_replace(
            '~шина\s+на\s+(?:cf[\s_-]*moto|sf[\s_-]*moto|сф[\s_-]*мото|цфмото)~iu',
            'шина для квадроцикла',
            (string)$row['description']
        );
        $cleaned = preg_replace(
            '~(?:cf[\s_-]*moto|sf[\s_-]*moto|сф[\s_-]*мото|цфмото)~iu',
            'квадроцикл',
            (string)$cleaned
        );
        R::exec('UPDATE avito_ad SET description = ? WHERE id = ?', [$cleaned, (int)$row['id']]);
    }

    foreach ($backup['plagins_cross'] as $row) {
        R::exec('DELETE FROM plagins_cross WHERE id = ?', [(int)$row['id']]);
    }
    R::exec('DELETE FROM plagins_cross_vendor WHERE id = ?', [$crossVendorId]);
    R::exec("DELETE FROM technics_tiposize WHERE technics_id IN ($modelSlots)", $modelIds);
    R::exec("DELETE FROM technics WHERE id IN ($modelSlots)", $modelIds);
    R::exec('DELETE FROM technics_manufacturer WHERE id = ?', [$manufacturerId]);
    R::exec(
        "UPDATE chat_session
            SET page_url = 'https://its-center.ru/category/atv'
          WHERE page_url REGEXP '(cf[[:space:]_-]*moto|sf[[:space:]_-]*moto|сф[[:space:]_-]*мото|цфмото|cf500[[:space:]]+x5|сфорсе|kvadrocikl-moto-cf-)'"
    );
    R::commit();
} catch (Throwable $exception) {
    R::rollback();
    throw $exception;
}

foreach ($backedUpAssets as $asset) {
    if (is_file($asset['source']) && !unlink($asset['source'])) {
        throw new RuntimeException('Database updated, but image could not be removed: ' . $asset['source']);
    }
}

echo json_encode($summary + ['status' => 'applied'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
