<?php

require dirname(__DIR__) . '/config/init.php';
require CONF . '/db_bootstrap.php';

if (!\R::getCell("SHOW TABLES LIKE 'cross_reference_context'")) {
    fwrite(STDERR, "Run scripts/install_unified_crosses.php first.\n");
    exit(2);
}

function wheelCrossManufacturer(string $code): string
{
    $rules = [
        'TOYOTA' => '~^(441|442|443|444|447)[A-Z0-9]+71$~',
        'KOMATSU' => '~^(3EB|34A)~',
        'MITSUBISHI' => '~^(9123|91B39)~',
        'NISSAN' => '~^40300~',
        'DOOSAN' => '~^(D480|A373726)~',
        'HANGCHA' => '~^(N163|3E211|AIA)~',
        'HELI' => '~^(22304|22574|23654|C0L|C0W|W115)~',
        'LONKING' => '~^(LG|60205000)~',
        'MAXIMAL' => '~^M30~',
        'TCM' => '~^(1101|21002|21003|24439|24454|30130|3530|43401|52356|54456|54458)~',
        'JAC' => '~^(F31A4|H24N4)~',
        'LIUGONG' => '~^30X~',
        'YALE' => '~^582004010$~',
    ];
    foreach ($rules as $manufacturer => $pattern) {
        if (preg_match($pattern, $code)) return $manufacturer;
    }
    return '';
}

function modelsForManufacturer(string $fitment, string $manufacturer): string
{
    if ($manufacturer === '' || $fitment === '') return '';
    $aliases = [
        'DOOSAN' => ['DOOSAN ', 'DAEWOO '],
        'HANGCHA' => ['HC ', 'HANGCHA '],
    ];
    $prefixes = $aliases[$manufacturer] ?? [$manufacturer . ' '];
    $result = [];
    foreach (preg_split('~\s*,\s*~u', $fitment) ?: [] as $item) {
        $item = trim($item);
        foreach ($prefixes as $prefix) {
            if (stripos($item, $prefix) === 0) {
                $model = trim(mb_substr($item, mb_strlen($prefix)));
                if ($model !== '') $result[$model] = true;
                break;
            }
        }
    }
    return implode(', ', array_keys($result));
}

$rows = \R::getAll(
    "SELECT DISTINCT cr.id, cr.normalized_code, pcc.fitment_text
     FROM product_cross pc
     JOIN cross_reference cr ON cr.id=pc.cross_id
     LEFT JOIN product_cross_context pcc ON pcc.product_id=pc.product_id
     WHERE pc.product_type='wheel' AND pc.source='wheel_cross_excel'"
);

$known = 0;
$unknown = 0;
foreach ($rows as $row) {
    $manufacturer = wheelCrossManufacturer((string)$row['normalized_code']);
    $models = modelsForManufacturer((string)$row['fitment_text'], $manufacturer);
    $confidence = $manufacturer === '' ? 'unknown' : ($models === '' ? 'inferred' : 'catalog');
    $note = $manufacturer === ''
        ? 'Производитель не подтверждён: требуется проверка по каталогу OEM.'
        : 'Производитель определён по структуре OEM-номера; модели отобраны из применяемости соответствующего диска.';
    \R::exec(
        "INSERT INTO cross_reference_context (cross_id,manufacturer,equipment_models,source_note,confidence)
         VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE manufacturer=VALUES(manufacturer),
         equipment_models=VALUES(equipment_models),source_note=VALUES(source_note),confidence=VALUES(confidence)",
        [(int)$row['id'], $manufacturer !== '' ? $manufacturer : null, $models !== '' ? $models : null, $note, $confidence]
    );
    $manufacturer === '' ? $unknown++ : $known++;
}

\R::exec("UPDATE product_cross_context SET description=REPLACE(description, 'в к-те', 'в комплекте') WHERE source='wheel_cross_excel'");
echo "Wheel cross metadata seeded: known={$known}, unknown={$unknown}. Descriptions normalized.\n";
