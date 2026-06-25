<?php

$db = require __DIR__ . '/../config/config_db.php';

$pdo = new PDO($db['dsn'], $db['user'], $db['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$column = $pdo->prepare("
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'contents'
      AND COLUMN_NAME = 'img_source'
");
$column->execute();

if ((int)$column->fetchColumn() === 0) {
    $pdo->exec("
        ALTER TABLE contents
        ADD img_source ENUM('internet','ai','original','editor') NOT NULL DEFAULT 'internet'
        AFTER img
    ");
}

$pdo->exec("UPDATE contents SET img_source = 'internet' WHERE img_source IS NULL OR img_source = ''");

echo "contents.img_source is ready" . PHP_EOL;
