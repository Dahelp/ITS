<?php
// Idempotent installation; use --push only for the specifically linked set.
require dirname(__DIR__).'/config/init.php';
\ishop\App::$app = \ishop\Registry::instance();
foreach ((require CONF.'/params.php') as $key => $value) { \ishop\App::$app->setProperty($key, $value); }
\ishop\Db::instance();
$backup = ROOT.'/storage/p350-complete-backup-20261016.json';
if (!is_file($backup)) {
    file_put_contents($backup, json_encode([
        'ads' => \R::getAll('SELECT * FROM avito_ad'),
        'sets' => \R::getAll('SELECT * FROM plagins_complete'),
        'items' => \R::getAll('SELECT * FROM plagins_complete_product'),
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}
\app\services\CompleteAvito::ensureSchema();
$name = 'Комплект шин 29х9-14/29х11-14 P350 EKKA для квадроциклов';
$alias = 'komplekt-shin-29x9-14-29x11-14-p350-ekka-dlya-kvadrociklov';
$products = \R::getAll('SELECT id,price FROM product WHERE alias IN (?,?) ORDER BY id', [
    '29x9-14-ekka-p350-shiny-dlya-kvadrociklov', '29x11-14-ekka-p350-shiny-dlya-kvadrociklov']);
if (count($products) !== 2) { throw new RuntimeException('Не найдены обе исходные шины'); }
$id = (int)\R::getCell('SELECT id FROM plagins_complete WHERE alias = ? OR name = ?', [$alias, $name]);
$model = new \app\models\admin\PlaginsComplete();
$data = [
 'name' => $name, 'category_id' => 2, 'hide' => 'show', 'alias' => $alias,
 'title' => 'Комплект шин 29х9-14/29х11-14 P350 EKKA — купить в ИТС-Центре',
 'description' => 'Разноширокий комплект EKKA P350 для квадроцикла: 2 шины 29х9-14 и 2 шины 29х11-14. Посадочный диаметр 14 дюймов. Цена за 4 шины. Самовывоз и доставка по России.',
 'keywords' => 'комплект шин EKKA P350, 29х9-14, 29х11-14, шины для квадроцикла, разноширокие шины, комплект 4 шины',
 'content' => '<p>Разноширокий комплект шин EKKA с протектором P350 для квадроциклов и ATV. В комплект входят четыре шины с посадочным диаметром 14 дюймов.</p><ul><li>29х9-14 EKKA P350 — 2 шт.</li><li>29х11-14 EKKA P350 — 2 шт.</li></ul><p>Узкие и широкие шины имеют одинаковую номинальную высоту 29 дюймов. Перед покупкой проверьте размеры, рекомендованные производителем вашей техники. Цена указана за полный комплект из четырёх шин. Колёсные диски в стоимость не входят.</p><p>Самовывоз со склада ИТС-Центра и отправка транспортными компаниями по России. Актуальная цена и наличие зависят от всех товарных позиций комплекта.</p>',
];
$model->load($data);
$model->attributes['img'] = 'ekka-p350-29x9-29x11-14-set.png';
if (!is_file(WWW.'/images/complete/baseimg/'.$model->attributes['img'])) { throw new RuntimeException('Сначала установите изображение комплекта'); }
if (!$id) {
 $id = (int)$model->save('plagins_complete');
 $model->editProductComplete($id, ['complete' => array_map(static function ($p) {
    return ['product_id'=>$p['id'], 'quantity'=>2, 'price'=>$p['price'], 'discount'=>0];
 }, $products)]);
}
foreach (['p350-29x9-14-original.jpg', 'p350-29x11-14-original.jpg'] as $img) {
    if (!\R::getCell('SELECT id FROM plagins_complete_gallery WHERE complete_id = ? AND img = ?', [$id, $img])) {
        \R::exec('INSERT INTO plagins_complete_gallery (complete_id, img) VALUES (?, ?)', [$id, $img]);
    }
}
// Description explicitly specifies both P350 sizes and a set of four.
\app\services\CompleteAvito::saveLinks($id, '2444178586');
\app\services\CompleteAvito::sync();
echo json_encode(['id'=>$id,'url'=>'https://its-center.ru/complete/'.$alias,'totals'=>\app\services\CompleteAvito::totals($id)],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
if (in_array('--push', $argv, true)) {
 echo json_encode(\app\models\admin\Avito::pushLinkedAdsToApi($id), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
}
