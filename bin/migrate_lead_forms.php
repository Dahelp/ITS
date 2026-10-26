<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/config/init.php';
require CONF . '/db_bootstrap.php';
$columns = [
    'callback' => ['contact_name'=>'VARCHAR(200) NULL','contact_email'=>'VARCHAR(254) NULL','comment'=>'TEXT NULL','source_path'=>'VARCHAR(500) NULL'],
    'mail_oneclick' => ['quantity'=>'INT UNSIGNED NULL','unit_price'=>'DECIMAL(14,2) NULL','source_path'=>'VARCHAR(500) NULL'],
];
foreach ($columns as $table => $fields) {
    $existing = array_column(R::getAll("SHOW COLUMNS FROM `$table`"), 'Field');
    foreach ($fields as $name=>$type) if (!in_array($name,$existing,true)) {
        R::exec("ALTER TABLE `$table` ADD `$name` $type");
        echo "$table.$name added\n";
    }
}
echo "Lead schema ready\n";
