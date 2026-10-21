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

if (Avito::publicationStatus(1) !== 'active' || Avito::publicationStatus(0) !== 'archived') {
    fwrite(STDERR, "FAILED: publication status must follow available stock\n");
    exit(1);
}

if (Avito::extractAvitoPublicationStatus(['status' => 'active']) !== 'active'
    || Avito::extractAvitoPublicationStatus(['status' => ['value' => 'blocked']]) !== 'blocked') {
    fwrite(STDERR, "FAILED: Avito API publication status extraction\n");
    exit(1);
}

if (Avito::avitoPublicationPresentation('active')[0] !== 'Опубликовано'
    || Avito::avitoPublicationPresentation('removed')[0] !== 'Снято с публикации'
    || Avito::avitoPublicationPresentation(null)[0] !== 'Не проверено') {
    fwrite(STDERR, "FAILED: Avito publication status presentation\n");
    exit(1);
}

$generated = Avito::buildGeneratedDescription([
    'name' => 'Фильтр гидравлический ABC',
    'content' => 'Для погрузчика',
    'attribute_text' => 'Бренд: EKKA Артикул: ABC',
]);
if (Avito::generatedProductType(['name' => 'Фильтр гидравлический ABC']) !== 'Фильтры и фильтроэлементы'
    || !str_contains($generated, 'Работаем с НДС 22%')
    || !str_contains($generated, '<h3>Характеристики</h3>')
    || !str_contains($generated, '<ul>')
    || !str_contains($generated, '<p><strong>Фильтр гидравлический ABC</strong></p>')) {
    fwrite(STDERR, "FAILED: generated Avito product template\n");
    exit(1);
}

$manualAd = (object)['manual_fields_json' => '["title"]'];
Avito::rememberManualFields($manualAd, ['description', 'title']);
if (Avito::manualFields($manualAd) !== ['description', 'title']) {
    fwrite(STDERR, "FAILED: manually edited Avito fields must be preserved\n");
    exit(1);
}

foreach (['НДС 20%', 'включая НДС: 20 %', 'НДС&nbsp;20%'] as $description) {
    if (!str_contains(Avito::normalizeDescriptionNds($description), 'НДС 22%')) {
        fwrite(STDERR, "FAILED: NDS 20% description was not normalized\n");
        exit(1);
    }
}

$base = [
    'id' => 1,
    'title' => 'Тестовое объявление',
    'description' => 'Описание',
    'category' => 'Запчасти и аксессуары',
    'ad_type' => 'Товар от производителя',
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

$motoXml = Avito::buildXml([$base + [
    'goods_type' => 'Шины, диски и колёса', 'product_type' => 'Мотошины',
    'brand' => 'Superguider', 'model' => 'ATV-1', 'rim_diameter' => '12',
    'tire_section_width' => '25', 'tire_aspect_ratio' => '8', 'wheel_axle' => 'Любая',
    'tire_type' => 'Всесезонные', 'speed_index' => 'A8', 'ply_rating' => '6',
]]);
foreach (['<Brand>Superguider</Brand>', '<Model>ATV-1</Model>', '<RimDiameter>12</RimDiameter>',
    '<TireSectionWidth>25</TireSectionWidth>', '<TireAspectRatio>8</TireAspectRatio>', '<WheelAxle>Любая</WheelAxle>'] as $tag) {
    if (!str_contains($motoXml, $tag)) {
        fwrite(STDERR, "FAILED: moto tire profile misses {$tag}\n");
        exit(1);
    }
}
foreach (['<TireType>', '<SpeedIndex>', '<PlyRayting>'] as $tag) {
    if (str_contains($motoXml, $tag)) {
        fwrite(STDERR, "FAILED: moto tire profile contains unsupported {$tag}\n");
        exit(1);
    }
}

$motoAnalysis = Avito::analyzeRow($base + [
    'product_type' => 'Мотошины', 'rim_diameter' => '12', 'tire_section_width' => '25',
    'tire_aspect_ratio' => '8', 'wheel_axle' => 'Любая',
]);
if (in_array('Модель Avito', $motoAnalysis['missingRequired'], true)
    || in_array('Высота профиля', $motoAnalysis['missingRequired'], true)) {
    fwrite(STDERR, "FAILED: moto tire required field analysis is incorrect\n");
    exit(1);
}

$motoFlotationXml = Avito::buildXml([$base + [
    'title' => 'Шина 13x6-5 P336 EKKA', 'goods_type' => 'Шины, диски и колёса',
    'product_type' => 'Мотошины', 'rim_diameter' => '5', 'tire_section_width' => '6',
    'tire_aspect_ratio' => 'Другое', 'wheel_axle' => 'Любая',
]]);
if (!str_contains($motoFlotationXml, '<RimDiameter>5</RimDiameter>')
    || !str_contains($motoFlotationXml, '<TireSectionWidth>6</TireSectionWidth>')
    || !str_contains($motoFlotationXml, '<TireAspectRatio>Другое</TireAspectRatio>')) {
    fwrite(STDERR, "FAILED: ATV flotation size is not exported correctly\n");
    exit(1);
}
$motoProvisionalAnalysis = Avito::analyzeRow(array_merge($base, [
    'title' => 'Шина 20x10-10 P361 EKKA', 'product_type' => 'Мотошины',
    'ad_external_id' => 'its-9092', 'description' => str_repeat('Описание товара ', 50),
    'price_rub' => 5500, 'goods_type' => 'Шины, диски и колёса',
    'manager_name' => 'ИТС-Центр', 'contact_phone' => '8 495 424-98-90',
    'address' => 'Подольск', 'images_json' => json_encode(['https://example.test/1.jpg', 'https://example.test/2.jpg']),
    'rim_diameter' => '10', 'tire_section_width' => '10',
    'tire_aspect_ratio' => 'Другое', 'wheel_axle' => 'Любая',
]));
if ($motoProvisionalAnalysis['status'] !== 'warning'
    || $motoProvisionalAnalysis['label'] !== 'Заполнено временно — требуется уточнение'
    || !str_contains($motoProvisionalAnalysis['provisionalRequired']['tire_aspect_ratio'] ?? '', 'ожидаемое значение 20')) {
    fwrite(STDERR, "FAILED: provisional Avito dictionary value must stay visible as a warning\n");
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

$adWithOptionalGaps = $base + [
    'ad_external_id' => 'test-1', 'description' => str_repeat('Описание товара ', 50), 'price_rub' => 1000,
    'category' => 'Запчасти и аксессуары', 'goods_type' => 'Шины, диски и колёса',
    'product_type' => 'Диски', 'manager_name' => 'ИТС-Центр', 'contact_phone' => '8 495 424-98-90',
    'address' => 'Подольск', 'images_json' => json_encode(['https://example.test/1.jpg', 'https://example.test/2.jpg']),
    'rim_diameter' => '18', 'rim_bolts' => '5', 'rim_bolts_diameter' => '335',
    'rim_type' => 'Штампованные', 'rim_dia' => '281', 'rim_offset' => '0', 'rim_width' => '9',
    'review_status' => 'confirmed',
];
$optionalAnalysis = Avito::analyzeRow($adWithOptionalGaps);
if ($optionalAnalysis['status'] !== 'warning' || $optionalAnalysis['label'] !== 'Подтверждено — есть рекомендации') {
    fwrite(STDERR, "FAILED: optional gaps must use the yellow warning status\n");
    exit(1);
}

echo "Avito category feed tests passed\n";
