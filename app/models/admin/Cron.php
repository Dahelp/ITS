<?php

namespace app\models\admin;

use app\models\AppModel;
use ishop\App;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Cron extends AppModel {

    public $attributes = [
        'name' => '',
        'alias' => '',
        'url_params' => '',
        'url_download' => '',
        'hide' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
        ],
    ];

    public function checkUnique() {
        if ($this->attributes['alias'] != "") {
            $cron = \R::findOne('cron', 'alias = ?', [$this->attributes['alias']]);

            if ($cron) {
                if ($cron->alias == $this->attributes['alias']) {
                    $this->errors['unique'][] = 'CRON с таким url уже существует';
                }
                return false;
            }
        }
        return true;
    }

    public static function cron_log($msg) {
        $logDir = ROOT . '/cron/log.txt';
        file_put_contents($logDir, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
    }

    public static function updateIndexNow(string $alias): void {
        $verification_yandex = App::options('option_verification_yandex');
        $verification_bing = App::options('option_verification_bing');
        $client = new Client();

        try {
            $resY = $client->request('GET', 'https://yandex.com/indexnow?url=' . PATH . '/product/' . $alias . '&key=' . $verification_yandex);
            self::cron_log("Yandex IndexNow: " . $resY->getStatusCode());
        } catch (RequestException $e) {
            self::cron_log("Ошибка Yandex IndexNow: " . $e->getMessage());
        }

        try {
            $resB = $client->request('GET', 'https://www.bing.com/indexnow?url=' . PATH . '/product/' . $alias . '&key=' . $verification_bing);
            self::cron_log("Bing IndexNow: " . $resB->getStatusCode());
        } catch (RequestException $e) {
            self::cron_log("Ошибка Bing IndexNow: " . $e->getMessage());
        }
    }

    public static function updateModification(string $article, int $quantity, float $price, string $date_price): void {
        $mod = \R::findOne('modification', 'article = ?', [$article]);
        if (!$mod) {
            self::cron_log("Модификация с артикулом {$article} не найдена.");
            return;
        }

        $update_data = ['quantity' => $quantity];
        $action = \R::findOne('actions', 'product_id = ? AND date_end > ?', [$mod['id'], date('Y-m-d H:i:s')]);
        if (!$action) {
            $update_data['price'] = $price;
        }

        $set_clause = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($update_data)));
        $update_data['id'] = $mod['id'];
        $affected = \R::exec("UPDATE modification SET $set_clause WHERE id = :id", $update_data);

        if ($affected > 0) {
            self::cron_log("Обновлена модификация ID={$mod['id']} (article={$article})");
        } else {
            self::cron_log("Ошибка обновления модификации ID={$mod['id']} (article={$article})");
        }
    }

    public static function updateProduct(array $product, array $res, string $date_price, string $date_update): void {
        $quantity = $res["rest"] + $res["reserve"];
        $stock_status_id = ($res["rest"] != "0") ? "1" : "0";
        $pssql_id = $product["id"];
        $action = \R::findOne('actions', 'product_id = ? AND date_end > ?', [$pssql_id, $date_update]);

        $update_data = [
            'opt_price' => $res["price_opt"],
            'quantity' => $quantity,
            'rest' => $res["rest"],
            'reserve' => $res["reserve"],
            'wait' => $res["wait"],
            'wait_date' => $res["wait_date"]
        ];

        if ($action || $product["stock_status_id"] != "0") {
            if ($product["stock_status_id"] == "2" || $product["stock_status_id"] == "3") {
                $update_data['price'] = $res["price_rozn"];
                $update_data['data_edit_price'] = $date_price;
            }
            if ($product["stock_status_id"] == "0" && $res["wait"] != "") {
                $update_data['stock_status_id'] = "3";
            } else {
                $update_data['stock_status_id'] = $stock_status_id;
            }
        } else {
            $update_data['price'] = $res["price_rozn"];
            $update_data['data_edit_price'] = $date_price;
            $update_data['stock_status_id'] = ($res["wait"] != "") ? "3" : $stock_status_id;
        }

        $set_clause = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($update_data)));
        $update_data['id'] = $pssql_id;
        $affected = \R::exec("UPDATE product SET $set_clause WHERE id = :id", $update_data);

        if ($affected > 0) {
            self::cron_log("Обновлён товар ID={$pssql_id} (article={$product['article']})");
            self::saveStockHistory($pssql_id, $quantity, $res["price_rozn"], $date_price);
            self::updateIndexNow($product['alias']);
        } else {
            self::updateModification($product['article'], $quantity, $res["price_rozn"], $date_price);
        }
    }

    public static function updateProductFromFile(array $fields, string $date_price, string $date_update): void {
        list($article, $price, $opt_price, $svobodno, $reserve) = $fields;
        $article = ltrim(trim($article), '0');
        $quantity = (int)$svobodno + (int)$reserve;

        $product = \R::findOne('product', 'article = ?', [$article]);
        if (!$product) {
            self::updateModification($article, $quantity, (float)$price, $date_price);
            return;
        }

        $action = \R::findOne('actions', 'product_id = ? AND date_end > ?', [$product["id"], $date_update]);
        $update_data = [
            'opt_price' => $opt_price,
            'quantity' => $quantity,
            'stock_status_id' => $quantity ? 1 : 0
        ];

        if (!$action || $product["stock_status_id"] != "0") {
            $update_data['price'] = $price;
            $update_data['data_edit_price'] = $date_price;
        }

        $set_clause = implode(', ', array_map(fn($key) => "$key = :$key", array_keys($update_data)));
        $update_data['id'] = $product["id"];
        $affected = \R::exec("UPDATE product SET $set_clause WHERE id = :id", $update_data);

        if ($affected > 0) {
            self::cron_log("Обновлён товар из файла ID={$product["id"]} (article={$article})");
            self::saveStockHistory($product["id"], $quantity, $price, $date_price);
            self::updateIndexNow($product['alias']);
        } else {
            self::updateModification($article, $quantity, (float)$price, $date_price);
        }
    }

    public static function saveStockHistory(int $product_id, int $quantity, float $price, string $date): void {
        \R::exec("INSERT INTO in_stock_history (product_id, date_ish, qty, price) VALUES (?, ?, ?, ?)", [
            $product_id, $date, $quantity, $price
        ]);
        self::cron_log("История остатков сохранена для ID={$product_id}, qty={$quantity}, price={$price}");
    }
}
