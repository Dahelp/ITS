<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$autoload = $projectRoot . '/vendor/autoload.php';
if (!is_file($autoload)) {
    $autoload = dirname($projectRoot, 2) . '/vendor/autoload.php';
}
require $autoload;
require $projectRoot . '/app/models/admin/Avito.php';

use app\models\admin\Avito;

$base = [
    'id' => 1,
    'title' => 'Тестовое объявление',
    'description' => 'Описание',
    'category' => 'Запчасти и аксессуары',
    'ad_type' => 'Товар приобретен на продажу',
    'item_condition' => 'Новое',
];

$diskXml = Avito::buildXml([$base + [
    'goods_type' => 'Шины, диски и колёса', 'product_type' => 'Диски',
    'rim_diameter' => '22.5', 'rim_bolts' => '10', 'rim_bolts_diameter' => '335',
    'rim_type' => 'Штампованные', 'rim_dia' => '281', 'rim_offset' => '0', 'rim_width' => '9',
    'rims_repair' => 'Нет',
]]);
if (!str_contains($diskXml, '<RimDIA>281</RimDIA>') || !str_contains($diskXml, '<RimsRepair>Нет</RimsRepair>')
    || str_contains($diskXml, '<TireSectionWidth>')) {
    fwrite(STDERR, "FAILED: disk profile XML is incorrect\n");
    exit(1);
}

$tireXml = Avito::buildXml([$base + [
    'goods_type' => 'Шины, диски и колёса', 'product_type' => 'Шины для грузовиков и спецтехники',
    'brand' => 'Boto', 'tire_section_width' => '17.5', 'tire_aspect_ratio' => '25',
    'rim_diameter' => '25', 'speed_index' => 'A8', 'ply_rating' => '20',
]]);
if (!str_contains($tireXml, '<SpeedIndex>A8</SpeedIndex>') || !str_contains($tireXml, '<PlyRayting>20</PlyRayting>')
    || str_contains($tireXml, '<RimDIA>')) {
    fwrite(STDERR, "FAILED: tire profile XML is incorrect\n");
    exit(1);
}

$partXml = Avito::buildXml([$base + [
    'goods_type' => 'Запчасти', 'product_type' => 'Для грузовиков и спецтехники',
    'spare_part_type' => 'Двигатели и комплектующие', 'technic_spare_part_type' => 'Топливная система',
    'fuel_system_part_type' => 'Сепараторы и фильтры', 'manufacturer' => 'Fleetguard',
]]);
if (!str_contains($partXml, '<FuelSystemPartType>Сепараторы и фильтры</FuelSystemPartType>')
    || !str_contains($partXml, '<Manufacturer>Fleetguard</Manufacturer>') || str_contains($partXml, '<RimDIA>')) {
    fwrite(STDERR, "FAILED: spare-part profile XML is incorrect\n");
    exit(1);
}

$analysis = Avito::analyzeRow($base + ['product_type' => 'Диски', 'rim_dia' => '290.5']);
if (!preg_grep('~DIA .+ отсутствует~u', $analysis['warnings'])) {
    fwrite(STDERR, "FAILED: invalid Avito DIA must be reported\n");
    exit(1);
}

echo "Avito category feed tests passed\n";
