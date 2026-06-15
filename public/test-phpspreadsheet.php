<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключаем автолоадер composer
require __DIR__ . '/../vendor/autoload.php';

echo '<pre>';

// 1. Проверка наличия класса IOFactory
echo "IOFactory exists? ";
var_dump(class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class));

// 2. Проверка наличия класса Spreadsheet
echo "Spreadsheet exists? ";
var_dump(class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class));

echo "</pre>";
