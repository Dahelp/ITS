<?php
/** Campaign-specific selection; prices and free stock always come from the live catalogue. */
namespace app\services;
final class DirectGallerySelection
{
    public const GROUPS = [
        'skid' => ['Шины для минипогрузчиков', [7094,7113]],
        'backhoe' => ['Шины для экскаваторов-погрузчиков', [7103,9932,7104,10270,7073,10212]],
        'loader' => ['Шины для фронтальных погрузчиков', [10003,9523,11368,9322,9526,10687]],
        'forklift' => ['Шины для вилочных погрузчиков', [9247,11953,7081,12032]],
        'rims' => ['Диски для погрузчиков', [10437,10438,10439,10680,12058,12059]],
        'tubes' => ['Камеры для шин', [12097,8142,11012,8132,11007,11009]],
    ];
    public static function generate(): array
    {
        $ids = array_merge(...array_values(array_map(static fn($g) => $g[1], self::GROUPS)));
        $stock = CatalogSelection::stock();
        $price = CatalogSelection::price();
        $rows = \R::getAll('SELECT product.*, brand.name AS vendor, ('.$stock.') AS feed_stock, ('.$price.') AS feed_price FROM product LEFT JOIN brand ON brand.id=product.brand_id WHERE product.article IN ('.\R::genSlots($ids).") AND product.hide='show' AND product.stock_status_id=1", $ids);
        $params = \R::getAll('SELECT pa.product_id,a.attribute_name,pa.attribute_text FROM product_attribute pa JOIN attribute a ON a.id=pa.attribute_id JOIN product p ON p.id=pa.product_id WHERE p.article IN ('.\R::genSlots($ids).')', $ids);
        $bySku = []; foreach ($rows as $row) $bySku[(int)$row['article']] = $row;
        $categories = $offers = $groupOffers = [];
        foreach (self::GROUPS as $key => [$name,$skus]) {
            $category = 101 + count($categories);
            $categories[] = ['id'=>$category,'name'=>$name];
            $groupOffers[$key] = [];
            foreach ($skus as $sku) {
                if (!isset($bySku[$sku])) continue;
                $row = $bySku[$sku]; $row['category_id'] = $category;
                $groupOffers[$key][] = $row; $offers[] = $row;
                $known = [];
                foreach ($params as $param) {
                    if ((int)$param['product_id'] === (int)$row['id']) $known[$param['attribute_name']] = true;
                }
                $extra = [];
                if (preg_match('/\b(\d{2,3})(A[1-8]|[B-Z])\b(?![-\/])/u', $row['name'], $load)) {
                    $extra['Индекс нагрузки'] = $load[1];
                    $extra['Индекс категории скорости'] = $load[2];
                }
                if (preg_match('/(?:^|Камера\s+)(\d+(?:\.\d+)?)(?:\/\d+)?-(\d+(?:\.\d+)?)(?:\s|$)/u', $row['name'], $size)) {
                    $extra['Посадочный диаметр (дюйм)'] = $size[2];
                    $extra['Ширина профиля (дюйм)'] = $size[1];
                }
                if ($key === 'rims' && preg_match('/(?:^|Диск\s+)(\d+(?:\.\d+)?)[A-Z]*[-x](\d+(?:\.\d+)?)/u', $row['name'], $rim)) {
                    $extra['Посадочный диаметр (дюйм)'] = $rim[2];
                    $extra['Ширина диска (дюйм)'] = $rim[1];
                }
                foreach ($extra as $name=>$value) {
                    if (!isset($known[$name])) $params[] = ['product_id'=>$row['id'],'attribute_name'=>$name,'attribute_text'=>$value];
                }
                $params[] = ['product_id'=>$row['id'],'attribute_name'=>'Тип техники','attribute_text'=> match($key) {
                    'skid'=>'Минипогрузчик','backhoe'=>'Экскаватор-погрузчик','loader'=>'Фронтальный погрузчик','forklift'=>'Вилочный погрузчик',default=>'Спецтехника'
                }];
            }
        }
        $renderer = new SpecialTyreFeedService(WWW, 'https://its-center.ru');
        $result = [];
        foreach (['all'=>$offers] + $groupOffers as $key=>$items) {
            $feed = $renderer->render($categories,$items,$params);
            $feed['xml'] = str_replace('>Шины для спецтехники<','>Товары для спецтехники<',$feed['xml']);
            $doc = new \DOMDocument(); $doc->loadXML($feed['xml'],LIBXML_NONET);
            $feed['skus'] = []; foreach ($doc->getElementsByTagName('offer') as $offer) $feed['skus'][] = $offer->getAttribute('id');
            $result[$key] = $feed;
        }
        return $result;
    }
}


