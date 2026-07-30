<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }

$root = dirname(__DIR__);
$directories = [
    $root . '/public/xls/nalichie',
    '/home/s/shinaspec/its50.ru/public_html/xls/nalichie',
];

$result = [
    'time' => date('c'),
    'php_user' => get_current_user(),
    'effective_uid' => function_exists('posix_geteuid') ? posix_geteuid() : null,
    'directories' => [],
];

foreach ($directories as $directory) {
    clearstatcache(true, $directory);
    $entry = [
        'path' => $directory,
        'exists' => is_dir($directory),
        'realpath' => realpath($directory) ?: null,
        'writable' => is_writable($directory),
        'owner' => @fileowner($directory),
        'group' => @filegroup($directory),
        'permissions' => is_dir($directory) ? substr(sprintf('%o', fileperms($directory)), -4) : null,
        'files' => [],
    ];

    foreach (['filtrs.csv', 'kamery.csv', 'tovars.csv'] as $fileName) {
        $path = $directory . '/' . $fileName;
        $entry['files'][$fileName] = [
            'exists' => is_file($path),
            'size' => is_file($path) ? filesize($path) : null,
            'modified_at' => is_file($path) ? date('c', filemtime($path)) : null,
            'writable' => is_writable($path),
            'owner' => @fileowner($path),
            'group' => @filegroup($path),
            'permissions' => is_file($path) ? substr(sprintf('%o', fileperms($path)), -4) : null,
        ];
    }

    $probe = $directory . '/.inventory_write_probe_' . getmypid();
    $probeRenamed = $probe . '.renamed';
    error_clear_last();
    $bytes = @file_put_contents($probe, "probe\n", LOCK_EX);
    $entry['probe_write'] = $bytes !== false;
    $entry['probe_rename'] = $bytes !== false && @rename($probe, $probeRenamed);
    $entry['probe_cleanup'] = !file_exists($probeRenamed) || @unlink($probeRenamed);
    if (file_exists($probe)) @unlink($probe);
    $entry['last_error'] = error_get_last();
    $result['directories'][] = $entry;
}

$log = $root . '/public/cron/logs/cron_36.log';
$result['cron_log'] = [
    'path' => $log,
    'exists' => is_file($log),
    'size' => is_file($log) ? filesize($log) : null,
    'modified_at' => is_file($log) ? date('c', filemtime($log)) : null,
    'tail' => [],
];
if (is_file($log)) {
    $lines = @file($log, FILE_IGNORE_NEW_LINES);
    if (is_array($lines)) $result['cron_log']['tail'] = array_slice($lines, -80);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
