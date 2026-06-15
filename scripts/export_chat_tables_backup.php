<?php

$db = require __DIR__ . '/../config/config_db.php';
$pdo = new PDO($db['dsn'], $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = [
    'chat_message',
    'chat_operator',
    'chat_session',
    'chat_settings',
    'tg_topic_map',
    'tblchatclientmessages',
    'tblchatgroupmembers',
    'tblchatgroupmessages',
    'tblchatgroups',
    'tblchatgroupsharedfiles',
    'tblchatmessages',
    'tblchatsettings',
    'tblchatsharedfiles',
];

$existing = [];
foreach ($tables as $table) {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    if ($stmt->fetchColumn()) {
        $existing[] = $table;
    }
}

$stamp = date('Ymd_His');
$file = __DIR__ . "/chat_tables_backup_{$stamp}.sql";
$out = [];
$out[] = '-- Backup of online chat tables';
$out[] = '-- Created: ' . date('Y-m-d H:i:s');
$out[] = '-- Database DSN: ' . $db['dsn'];
$out[] = '';
$out[] = 'SET NAMES utf8;';
$out[] = 'SET FOREIGN_KEY_CHECKS=0;';
$out[] = '';

foreach ($existing as $table) {
    $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch();
    $out[] = '-- --------------------------------------------------------';
    $out[] = '-- Table `' . $table . '`';
    $out[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
    $out[] = $create['Create Table'] . ';';
    $out[] = '';

    $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`')->fetchAll();
    foreach ($rows as $row) {
        $columns = array_map(static fn($col) => '`' . str_replace('`', '``', $col) . '`', array_keys($row));
        $values = array_map(static fn($value) => $value === null ? 'NULL' : $pdo->quote((string)$value), array_values($row));
        $out[] = 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
    }
    $out[] = '';
}

$out[] = 'SET FOREIGN_KEY_CHECKS=1;';
$out[] = '';

file_put_contents($file, implode(PHP_EOL, $out));
echo $file . PHP_EOL;
