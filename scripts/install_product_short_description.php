<?php

$db = require dirname(__DIR__) . '/config/config_db.php';
$pdo = new PDO($db['dsn'], $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$query = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = 'short_description'"
);
$query->execute();

if (!$query->fetchColumn()) {
    $pdo->exec("ALTER TABLE product ADD short_description TEXT NULL AFTER price_rrs");
    echo "Column product.short_description added.\n";
} else {
    echo "Column product.short_description already exists.\n";
}
$alias = '8-25x16-5-et14-pcd203-2-hub152-4-disk-dlya-minipogruzchika';
$text = '<p>Диск 8.25x16.5 LANTIAN для минипогрузчиков и строительной техники. Подходит для шин 10-16.5, имеет вылет ET14, PCD 203.2 и центральное отверстие HUB 152.4 мм.</p>';
$seed = $pdo->prepare("UPDATE product SET short_description = ? WHERE alias = ? AND TRIM(COALESCE(short_description, '')) = ''");
$seed->execute([$text, $alias]);
echo "Seeded demo product short description rows: " . $seed->rowCount() . "\n";