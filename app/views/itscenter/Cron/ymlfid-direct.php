<?php

$xml = static fn($v) => htmlspecialchars((string)$v, ENT_XML1 | ENT_COMPAT, 'UTF-8');

$cronId = (int)($_GET['id'] ?? 0);
$catId = (int)($_GET['cat_id'] ?? 0);
$tiposize = trim((string)($_GET['tiposize'] ?? ''));

if ($cronId <= 0 || $catId <= 0 || $tiposize === '') {
    if (PHP_SAPI === 'cli') {
        echo "SKIP ymlfid-direct: id, cat_id or tiposize is empty\n";
        return;
    }
    redirect(PATH . '/admin/cron');
}

$date = date('Y-m-d H:i:s');
$datetime = date('c', strtotime($date));
$dateUpdate = date('Y-m-d H:i:s');
$viewcrons = \R::findOne('cron', 'id = ?', [$cronId]);

if (!$viewcrons) {
    throw new \RuntimeException('Cron task not found: ' . $cronId);
}

$sizeName = str_replace([',', '.', '/', '*', '-', ' ', '  '], '', $tiposize);
$fileName = trim((string)$viewcrons['url_download']) . '-' . $sizeName . '.xml';
$filePath = 'cron/' . $fileName;

$fd = fopen($filePath, 'w+');
if (!$fd) {
    throw new \RuntimeException('Cannot create file: ' . $filePath);
}

$text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$text .= "<yml_catalog date=\"" . $xml($datetime) . "\">\n";
$text .= "<shop>\n";
$text .= "<name>ИТС-Центр</name>\n";
$text .= "<company>ООО ИТС-Центр</company>\n";
$text .= "<url>" . $xml(PATH) . "</url>\n";
$text .= "<currencies><currency id=\"RUR\" rate=\"1\"/></currencies>\n";
$text .= "<categories><category id=\"1\">Шины для спецтехники</category>";

$categories = \R::getAll(
    "SELECT id, name, parent_id FROM category WHERE id = ? AND hide = 'show'",
    [$catId]
);

foreach ($categories as $cat) {
    $parent = (int)$cat['parent_id'] === 0 ? '' : " parentId=\"" . (int)$cat['parent_id'] . "\"";
    $text .= "<category id=\"" . (int)$cat['id'] . "\"{$parent}>" . $xml($cat['name']) . "</category>";
}

$text .= "</categories>\n<offers>";

$offers = \R::getAll(
    "SELECT product.*, product.id AS prod_id, brand.name AS vendor
     FROM product
     JOIN brand ON brand.id = product.brand_id
     JOIN category ON product.category_id = category.id
     WHERE product.name LIKE ?
       AND category.id = ?
       AND product.hide = 'show'
       AND product.sku != ''
       AND product.img != ''
       AND product.price != '0'
       AND product.stock_status_id = '1'",
    ['%' . $tiposize . '%', $catId]
);

foreach ($offers as $offer) {
    $available = (int)$offer['quantity'] === 0 ? 'false' : 'true';
    $img = !empty($offer['img']) ? PATH . '/images/product/unload/' . $offer['unload_img'] : '';

    $text .= "<offer id=\"" . $xml($offer['sku']) . "\" available=\"" . $available . "\">";
    $text .= "<url>" . $xml(PATH . '/product/' . $offer['alias']) . "</url>";

    $action = \R::findOne('actions', 'product_id = ?', [(int)$offer['id']]);
    if ($action && isset($action->znachenie)) {
        $price = (float)$offer['price'] - (float)$action->znachenie;
        $text .= "<price>" . $xml($price) . "</price>";
        $text .= "<oldprice>" . $xml($offer['price']) . "</oldprice>";
    } else {
        $text .= "<price>" . $xml($offer['price']) . "</price>";
        if (!empty($offer['price_rrs']) && (float)$offer['price_rrs'] > 0) {
            $text .= "<oldprice>" . $xml($offer['price_rrs']) . "</oldprice>";
        }
    }

    $text .= "<currencyId>RUR</currencyId>";
    $text .= "<categoryId>" . (int)$offer['category_id'] . "</categoryId>";
    $text .= "<picture>" . $xml($img) . "</picture>";
    $text .= "<name>" . $xml($offer['name']) . "</name>";
    $text .= "<model>" . $xml($offer['model']) . "</model>";
    $text .= "<vendor>" . $xml($offer['vendor']) . "</vendor>";

    $params = \R::getAll(
        "SELECT attribute.attribute_name, product_attribute.attribute_text
         FROM attribute
         JOIN product_attribute ON attribute.id = product_attribute.attribute_id
         WHERE product_attribute.product_id = ?
         ORDER BY attribute.attribute_name",
        [(int)$offer['prod_id']]
    );

    foreach ($params as $param) {
        $text .= "<param name=\"" . $xml($param['attribute_name']) . "\">" . $xml($param['attribute_text']) . "</param>";
    }

    $text .= "</offer>";
}

$text .= "</offers>\n</shop>\n</yml_catalog>";

fwrite($fd, $text);
fclose($fd);

\R::exec('UPDATE cron SET date_update = ? WHERE id = ?', [$dateUpdate, $cronId]);
\app\services\admin\AdminActivityLogger::cron($cronId, PHP_SAPI !== 'cli', $_SESSION['user']['id'] ?? null, $dateUpdate);

if (PHP_SAPI !== 'cli') {
    $_SESSION['success'] = 'Задание "' . $viewcrons['name'] . '" выполнено!';
    redirect(PATH . '/admin/cron');
}

echo "DONE ymlfid-direct file={$fileName} offers=" . count($offers) . PHP_EOL;
