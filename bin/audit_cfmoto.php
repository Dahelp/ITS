<?php

declare(strict_types=1);

/**
 * Read-only audit for CFMOTO trademark variants in the database and project files.
 *
 * Usage:
 *   php bin/audit_cfmoto.php
 *   php bin/audit_cfmoto.php --output=tmp/cfmoto-audit
 */

define('ROOT', dirname(__DIR__));
define('CONF', ROOT . '/config');

require ROOT . '/vendor/autoload.php';
require CONF . '/db_bootstrap.php';

$outputArg = null;
foreach ($argv as $argument) {
    if (strpos($argument, '--output=') === 0) {
        $outputArg = substr($argument, strlen('--output='));
    }
}

$outputDir = $outputArg ?: ('tmp/cfmoto-audit-' . date('Ymd-His'));
if (!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $outputDir)) {
    $outputDir = ROOT . '/' . ltrim(str_replace('\\', '/', $outputDir), '/');
}
if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    throw new RuntimeException('Cannot create output directory: ' . $outputDir);
}

$regexp = '(cf[[:space:]_-]*moto|sf[[:space:]_-]*moto|сф[[:space:]_-]*мото|цфмото|cf500[[:space:]]+x5|сфорсе|kvadrocikl-moto-cf-)';
$phpRegexp = '~(?:cf[\s_-]*moto|sf[\s_-]*moto|сф[\s_-]*мото|цфмото|cf500\s+x5|сфорсе|kvadrocikl-moto-cf-)~iu';
$textTypes = ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'json', 'enum', 'set'];
$ignoredDirectories = ['.git', 'vendor', 'node_modules', 'tmp', 'cache'];

function csvFile(string $path, array $headers, array $rows): void
{
    $handle = fopen($path, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Cannot write ' . $path);
    }
    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, $headers, ';');
    foreach ($rows as $row) {
        fputcsv($handle, $row, ';');
    }
    fclose($handle);
}

function excerpt(string $value, string $pattern, int $radius = 100): string
{
    if (!preg_match($pattern, $value, $match, PREG_OFFSET_CAPTURE)) {
        return mb_substr(preg_replace('~\s+~u', ' ', $value) ?? $value, 0, $radius * 2, 'UTF-8');
    }
    $byteOffset = $match[0][1];
    $charOffset = mb_strlen(substr($value, 0, $byteOffset), 'UTF-8');
    $start = max(0, $charOffset - $radius);
    $snippet = mb_substr($value, $start, $radius * 2 + mb_strlen($match[0][0], 'UTF-8'), 'UTF-8');
    return trim(preg_replace('~\s+~u', ' ', $snippet) ?? $snippet);
}

$dbName = (string)\R::getCell('SELECT DATABASE()');
$tables = \R::getCol(
    'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?',
    [$dbName, 'BASE TABLE']
);
$dbHits = [];

foreach ($tables as $table) {
    $columns = \R::getAll(
        'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY
           FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
          ORDER BY ORDINAL_POSITION',
        [$dbName, $table]
    );
    $primaryColumns = [];
    foreach ($columns as $column) {
        if (($column['COLUMN_KEY'] ?? '') === 'PRI') {
            $primaryColumns[] = (string)$column['COLUMN_NAME'];
        }
    }
    foreach ($columns as $column) {
        if (!in_array(strtolower((string)$column['DATA_TYPE']), $textTypes, true)) {
            continue;
        }
        $columnName = (string)$column['COLUMN_NAME'];
        $quotedTable = '`' . str_replace('`', '``', (string)$table) . '`';
        $quotedColumn = '`' . str_replace('`', '``', $columnName) . '`';
        $selectParts = [$quotedColumn . ' AS matched_value'];
        foreach ($primaryColumns as $primaryColumn) {
            $quotedPrimary = '`' . str_replace('`', '``', $primaryColumn) . '`';
            $selectParts[] = $quotedPrimary . ' AS `pk_' . str_replace('`', '``', $primaryColumn) . '`';
        }
        $rows = \R::getAll(
            'SELECT ' . implode(', ', $selectParts) . ' FROM ' . $quotedTable .
            ' WHERE ' . $quotedColumn . ' REGEXP ? LIMIT 10000',
            [$regexp]
        );
        foreach ($rows as $row) {
            $primaryKey = [];
            foreach ($primaryColumns as $primaryColumn) {
                $primaryKey[$primaryColumn] = $row['pk_' . $primaryColumn] ?? null;
            }
            $value = (string)($row['matched_value'] ?? '');
            preg_match_all($phpRegexp, $value, $matches);
            $dbHits[] = [
                'table' => (string)$table,
                'primary_key' => json_encode($primaryKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'column' => $columnName,
                'matches' => implode(', ', array_values(array_unique($matches[0] ?? []))),
                'excerpt' => excerpt($value, $phpRegexp),
            ];
        }
    }
}

$fileHits = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator(ROOT, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $current) use ($ignoredDirectories): bool {
            return !$current->isDir() || !in_array($current->getFilename(), $ignoredDirectories, true);
        }
    )
);
foreach ($iterator as $fileInfo) {
    if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
        continue;
    }
    $absolutePath = $fileInfo->getPathname();
    $relativePath = str_replace('\\', '/', substr($absolutePath, strlen(ROOT) + 1));
    $nameMatch = preg_match($phpRegexp, $relativePath) === 1;
    $contentMatch = false;
    $matchList = [];
    $snippet = '';
    if ($fileInfo->getSize() <= 20 * 1024 * 1024) {
        $content = file_get_contents($absolutePath);
        if ($content !== false && strpos($content, "\0") === false && preg_match_all($phpRegexp, $content, $matches)) {
            $contentMatch = true;
            $matchList = array_values(array_unique($matches[0] ?? []));
            $snippet = excerpt($content, $phpRegexp);
        }
    }
    if ($nameMatch || $contentMatch) {
        $fileHits[] = [
            'path' => $relativePath,
            'location' => $nameMatch && $contentMatch ? 'filename_and_content' : ($nameMatch ? 'filename' : 'content'),
            'matches' => implode(', ', $matchList),
            'excerpt' => $snippet,
        ];
    }
}

csvFile($outputDir . '/database_hits.csv', ['table', 'primary_key', 'column', 'matches', 'excerpt'], $dbHits);
csvFile($outputDir . '/file_hits.csv', ['path', 'location', 'matches', 'excerpt'], $fileHits);

$result = [
    'generated_at' => date(DATE_ATOM),
    'database' => $dbName,
    'patterns' => ['CFMOTO', 'CF MOTO', 'CF-MOTO', 'SFMOTO', 'SF MOTO', 'SF-MOTO', 'СФМОТО', 'СФ МОТО', 'ЦФМОТО', 'CF500 X5', 'СФОРСЕ', 'kvadrocikl-moto-cf-*'],
    'database_hits' => $dbHits,
    'file_hits' => $fileHits,
];
file_put_contents(
    $outputDir . '/audit.json',
    json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
);

$markdown = "# Аудит обозначений CFMOTO\n\n";
$markdown .= '- Дата: ' . $result['generated_at'] . "\n";
$markdown .= '- Совпадений в БД: ' . count($dbHits) . "\n";
$markdown .= '- Совпадений в файлах: ' . count($fileHits) . "\n\n";
$markdown .= "Подробности: `database_hits.csv`, `file_hits.csv`, `audit.json`.\n";
file_put_contents($outputDir . '/README.md', $markdown);

echo $outputDir . PHP_EOL;
echo 'Database hits: ' . count($dbHits) . PHP_EOL;
echo 'File hits: ' . count($fileHits) . PHP_EOL;
