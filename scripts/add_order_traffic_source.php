<?php
$db=require dirname(__DIR__) . '/config/config_db.php';
$pdo=new \PDO($db['dsn'],$db['user'],$db['pass']);
$pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
$exists=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order' AND COLUMN_NAME = 'traffic_source'")->fetchColumn();
if($exists===0) $pdo->exec("ALTER TABLE `order` ADD `traffic_source` VARCHAR(32) NOT NULL DEFAULT 'direct_visit' COMMENT 'search, yandex_direct or direct_visit'");
echo "order.traffic_source is ready".PHP_EOL;
