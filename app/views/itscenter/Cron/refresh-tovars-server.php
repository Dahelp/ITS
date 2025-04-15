<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use app\models\admin\Cron;

$config = require ROOT . '/config/api.php';
$date_price = date("Y-m-d");
$date_update = date("Y-m-d H:i:s");
$viewcrons = \R::findOne('cron', 'id = ?', [$_GET["id"]]);

$client = new \GuzzleHttp\Client();

try {
    $testserver = $client->request('GET', $config['host'], [
        'auth' => $config['auth'],
        'allow_redirects' => false,
        'timeout' => 5
    ]);

    if ($testserver->getStatusCode() == 200) {
        $category_ids = [];

		if (!empty($_GET['categories'])) {
			$category_ids = explode(',', $_GET['categories']);
		} elseif (!empty($viewcrons['categories'])) {
			$category_ids = explode(',', $viewcrons['categories']);
		}

		if (empty($category_ids)) {
			\app\models\admin\Cron::cron_log("Нет категорий для обновления (id cron: {$viewcrons['id']})");
			exit("Категории не заданы.");
		}

		$placeholders = rtrim(str_repeat('?,', count($category_ids)), ',');
		$products = \R::getAll("SELECT id, article, stock_status_id, alias FROM product WHERE category_id IN ($placeholders)", $category_ids);

        foreach ($products as $product) {
            try {
                $res = $client->request('GET', $config['host'] . '?code=' . $product["article"], [
                    'auth' => $config['auth'],
                    'timeout' => 5
                ]);

                $json = json_decode($res->getBody(), true);
                if (is_array($json)) {
                    foreach ($json as $data) {
                        Cron::updateProduct($product, $data, $date_price, $date_update);
                    }
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Cron::cron_log("Ошибка API для артикула {$product['article']}: " . $e->getMessage());
            }
        }
    }
} catch (\GuzzleHttp\Exception\RequestException $e) {
    Cron::cron_log("Сервер API 1С недоступен: " . $e->getMessage());

    // Резерв: обработка локального CSV
    $fileprod = $viewcrons["alias"] ?: $viewcrons["url_download"];
    $url_download = $viewcrons["url_download"];
    $path = "cron/{$url_download}";

    $html = file_get_contents($fileprod);
    file_put_contents($path, $html);

    $data = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    for ($i = 1; $i < count($data); $i++) {
        $fields = explode(";", $data[$i]);
        Cron::updateProductFromFile($fields, $date_price, $date_update);
    }
}

// Обновление даты и лог в админку
\R::exec("UPDATE cron SET date_update = ? WHERE id = ?", [$date_update, $viewcrons['id']]);

if (!empty($_SESSION['user']['id'])) {
    \R::exec("
        INSERT INTO admin_last_history (gh_id, ah_id, name_tbl, id_tbl, date_modified, customer_id)
        VALUES (2, 49, 'cron', ?, ?, ?)
    ", [$_GET['id'], $date_update, $_SESSION['user']['id']]);
} else {
    \R::exec("
        INSERT INTO admin_last_history (gh_id, ah_id, name_tbl, id_tbl, date_modified, customer_id)
        VALUES (2, 51, 'cron', ?, ?, NULL)
    ", [$_GET['id'], $date_update]);
}

// История total остатков
$countInStock = \R::getCell('SELECT SUM(quantity) FROM in_stock');
if (\R::findOne('in_stock_history_total', 'date_total = ?', [$date_price])) {
    \R::exec("UPDATE in_stock_history_total SET qty_total = ? WHERE date_total = ?", [$countInStock, $date_price]);
} else {
    \R::exec("INSERT INTO in_stock_history_total (date_total, qty_total) VALUES (?, ?)", [$date_price, $countInStock]);
}

$_SESSION['success'] = 'Задание "' . $viewcrons["name"] . '" выполнено!';
redirect(PATH . "/admin/cron");
