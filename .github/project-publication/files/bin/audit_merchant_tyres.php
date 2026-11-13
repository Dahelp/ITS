<?php
/** Read-only audit of an XML snapshot; never connects to or changes the database. */
require dirname(__DIR__) . '/vendor/autoload.php';
use app\services\MerchantTyreAttributes;
$input = $argv[1] ?? '';
if (!is_file($input)) { fwrite(STDERR, "Usage: php bin/audit_merchant_tyres.php feed.xml\n"); exit(1); }
$xml = simplexml_load_file($input, 'SimpleXMLElement', LIBXML_NONET);
if (!$xml) throw new RuntimeException('Invalid XML');
if (($argv[2] ?? '') === '--preview') {
    foreach ($xml->shop->offers->offer as $offer) {
        $attributes = [];
        foreach ($offer->param as $p) $attributes[(string)$p['name']] = trim((string)$p);
        unset($offer->param);
        foreach (MerchantTyreAttributes::enrich((string)$offer->name, $attributes) as $name=>$value) {
            $element = $offer->addChild('param');
            $node = dom_import_simplexml($element);
            $node->appendChild($node->ownerDocument->createTextNode($value));
            $element->addAttribute('name', $name);
        }
    }
    // Historical snapshot date is deliberately retained: this is not a live stock feed.
    echo $xml->asXML();
    exit;
}
$result = ['feed_date'=>(string)$xml['date'], 'offers'=>0, 'missing'=>[], 'items'=>[]];
$seen = [];
foreach ($xml->shop->offers->offer as $offer) {
    $id = (string)$offer['id'];
    if (isset($seen[$id])) throw new RuntimeException('Duplicate ID: '.$id);
    $seen[$id] = true;
    $attributes = [];
    foreach ($offer->param as $p) $attributes[(string)$p['name']] = trim((string)$p);
    $missing = [];
    foreach (['Посадочный диаметр (дюйм)','Тип шины','Применение','Индекс нагрузки','Индекс категории скорости'] as $field) {
        if (empty($attributes[$field])) $missing[] = $field;
    }
    if (trim((string)$offer->description) === '') $missing[] = 'description';
    foreach ($missing as $field) $result['missing'][$field] = ($result['missing'][$field] ?? 0) + 1;
    $enriched = MerchantTyreAttributes::enrich((string)$offer->name, $attributes);
    $flags = [];
    if ((float)($attributes['Вес, кг'] ?? 0) > 2000) $flags[] = 'Проверить единицы веса';
    if (isset($attributes['Макс. Давление (PSI)']) && (float)$attributes['Макс. Давление (PSI)'] > 300) $flags[] = 'Проверить PSI / кПа';
    $result['items'][] = ['id'=>$id,'name'=>(string)$offer->name,'url'=>(string)$offer->url,
        'size'=>$attributes['Размер шины'] ?? '', 'price'=>(string)$offer->price,
        'missing'=>$missing,'proposed_attributes'=>array_diff_key($enriched,$attributes),'review'=>$flags];
    $result['offers']++;
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
