<?php

$date_update = date('Y-m-d H:i:s');
$cronId = !empty($_GET['id']) ? (int)$_GET['id'] : 2;

$viewcrons = \R::findOne('cron', 'id = ?', [$cronId]);
if (!$viewcrons) {
    http_response_code(500);
    exit('Cron not found');
}

$fileName = trim($viewcrons['url_download']);
if ($fileName === '') {
    http_response_code(500);
    exit('Empty cron file name');
}

/*
|--------------------------------------------------------------------------
| Пишем сначала во временный файл, потом подменяем основной
|--------------------------------------------------------------------------
*/
$targetDir = WWW . '/cron';
$targetFile = $targetDir . '/' . ltrim(str_replace('\\', '/', $fileName), '/');
$tmpFile = $targetFile . '.tmp';

$urls = [];

/*
|--------------------------------------------------------------------------
| Маленький helper без лишней сложности
|--------------------------------------------------------------------------
*/
$addUrl = function($url) use (&$urls) {
    $url = trim($url);
    if ($url === '') {
        return;
    }
    $urls[$url] = $url;
};

$filterLandingRobotsWhere = '';
try {
    if (\R::getCell("SHOW COLUMNS FROM attribute_value_category_canonical LIKE 'robots'")) {
        $filterLandingRobotsWhere = " AND (avcc.robots IS NULL OR avcc.robots = '' OR avcc.robots NOT LIKE 'noindex%')";
    }
} catch (\Throwable $e) {
    $filterLandingRobotsWhere = '';
}

/*
|--------------------------------------------------------------------------
| 1. Главная
|--------------------------------------------------------------------------
*/
$addUrl(PATH . '/');

/*
|--------------------------------------------------------------------------
| 2. Фильтр-лендинги НОВОЙ структуры:
|    /category/{category_alias}/{value_alias}
|--------------------------------------------------------------------------
|
| Берём:
| - только активные записи
| - только mode = landing
| - если есть redirect_category_id, используем его
| - иначе category_id
|--------------------------------------------------------------------------
*/
$sm_filter_landings = \R::getAll("
    SELECT DISTINCT
        c.id AS category_id,
        c.alias AS category_alias,
        av.id AS value_id,
        av.alias AS value_alias
    FROM attribute_value_category_canonical avcc
    INNER JOIN category c
        ON c.id = IFNULL(avcc.redirect_category_id, avcc.category_id)
       AND c.hide = 'show'
    INNER JOIN attribute_value av
        ON av.id = avcc.attr_value_id
       AND av.hide = 'show'
    WHERE avcc.is_active = 1
      AND avcc.mode = 'landing'
      {$filterLandingRobotsWhere}
      AND c.alias <> ''
      AND av.alias <> ''
");

if ($sm_filter_landings) {
    $catModel = new \app\models\Category();

    foreach ($sm_filter_landings as $row) {
        $categoryId = (int)$row['category_id'];
        $valueId = (int)$row['value_id'];

        if ($categoryId <= 0 || $valueId <= 0) {
            continue;
        }

        $categoryIds = $catModel->getIds($categoryId);
        $categoryIds = !$categoryIds ? (string)$categoryId : $categoryIds . $categoryId;

        $hasProducts = (int)\R::getCell(
            "SELECT COUNT(*)
             FROM product p
             INNER JOIN attribute_product ap ON ap.product_id = p.id
             WHERE p.hide = 'show'
               AND p.category_id IN ($categoryIds)
               AND ap.attr_id = ?",
            [$valueId]
        );

        if ($hasProducts < 1) {
            continue;
        }

        $addUrl(\app\services\filters\FilterUrlHelper::buildCategoryFilterUrl(
            (string)$row['category_alias'],
            (string)$row['value_alias']
        ));
    }
}

/*
|--------------------------------------------------------------------------
| 3. Товары
|--------------------------------------------------------------------------
*/
$sm_product = \R::getAll("SELECT alias FROM product WHERE hide = 'show' AND alias <> ''");
if ($sm_product) {
    foreach ($sm_product as $smp) {
        $addUrl(PATH . '/product/' . $smp['alias']);
    }
}

/*
|--------------------------------------------------------------------------
| 4. Контентные страницы
|--------------------------------------------------------------------------
*/
$sm_content_type = \R::getAll("SELECT id, param_url FROM content_type WHERE hide = 'show' AND param_url <> ''");
if ($sm_content_type) {
    foreach ($sm_content_type as $type) {
        $sm_content = \R::getAll("
            SELECT alias
            FROM contents
            WHERE type_id = ?
              AND hide = 'show'
              AND alias <> ''
        ", [$type['id']]);

        if ($sm_content) {
            foreach ($sm_content as $cont) {
                $addUrl(PATH . '/' . $type['param_url'] . '/' . $cont['alias']);
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| 5. Категории
|--------------------------------------------------------------------------
*/
$sm_category = \R::getAll("SELECT alias FROM category WHERE hide = 'show' AND alias <> ''");
if ($sm_category) {
    foreach ($sm_category as $smc) {
        $addUrl(PATH . '/category/' . $smc['alias']);
    }
}

/*
|--------------------------------------------------------------------------
| 6. Cross
|--------------------------------------------------------------------------
*/
$sm_cross = \R::getAll("SELECT cross_abbreviated_name FROM plagins_cross WHERE cross_abbreviated_name <> ''");
if ($sm_cross) {
    foreach ($sm_cross as $cross) {
        $cross_abbreviated_name = mb_strtolower(trim($cross['cross_abbreviated_name']));
        if ($cross_abbreviated_name !== '') {
            $addUrl(PATH . '/cross/' . rawurlencode($cross_abbreviated_name));
        }
    }
}

/*
|--------------------------------------------------------------------------
| 7. Техника
|--------------------------------------------------------------------------
*/
$sm_technics = \R::getAll("SELECT alias FROM technics WHERE alias <> ''");
if ($sm_technics) {
    foreach ($sm_technics as $technics) {
        $addUrl(PATH . '/technics/' . $technics['alias']);
    }
}

/*
|--------------------------------------------------------------------------
| 8. Генерация XML
|--------------------------------------------------------------------------
*/
$text = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$text .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
$text .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
$text .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 ';
$text .= 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;

foreach ($urls as $url) {
    $text .= '  <url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc></url>' . PHP_EOL;
}

$text .= '</urlset>';

/*
|--------------------------------------------------------------------------
| 9. Запись во временный файл
|--------------------------------------------------------------------------
*/
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    http_response_code(500);
    exit('Cannot create sitemap directory');
}

if (file_put_contents($tmpFile, $text, LOCK_EX) === false) {
    http_response_code(500);
    exit('Cannot write tmp sitemap');
}

if (!file_exists($tmpFile) || filesize($tmpFile) <= 0) {
    @unlink($tmpFile);
    http_response_code(500);
    exit('Tmp sitemap is empty');
}

/*
|--------------------------------------------------------------------------
| 10. Подмена основного файла
|--------------------------------------------------------------------------
*/
if (!@rename($tmpFile, $targetFile)) {
    @unlink($tmpFile);
    http_response_code(500);
    exit('Cannot replace sitemap');
}

/*
|--------------------------------------------------------------------------
| 11. Обновление cron + история
|--------------------------------------------------------------------------
*/
\R::exec("UPDATE cron SET date_update = ? WHERE id = ?", [$date_update, $cronId]);

if (!empty($_SESSION['user']['id'])) {
    \R::exec("
        INSERT INTO admin_last_history
            (gh_id, ah_id, name_tbl, id_tbl, date_modified, customer_id)
        VALUES
            ('2','49','cron', ?, ?, ?)
    ", [$cronId, date('Y-m-d H:i:s'), (int)$_SESSION['user']['id']]);
} else {
    \R::exec("
        INSERT INTO admin_last_history
            (gh_id, ah_id, name_tbl, id_tbl, date_modified, customer_id)
        VALUES
            ('2','51','cron', ?, ?, NULL)
    ", [$cronId, date('Y-m-d H:i:s')]);
}

$_SESSION['success'] = 'Задание "' . $viewcrons['name'] . '" выполнено!';
redirect(PATH . '/admin/cron');
?>
