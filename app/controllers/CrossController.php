<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use app\models\Cross;
use app\services\admin\AdminActivityLogger;
use ishop\App;

class CrossController extends AppController {

    public function viewAction(){
        // alias + проверка регистра
        $alias = rawurldecode((string)$this->route['alias']);
        $up_registr = App::upRegistrLetter($alias);

        // берём cross безопасно
        $cross = \R::findOne('plagins_cross', "cross_abbreviated_name = ?", [$alias]);
        if (!$cross) {
            throw new \Exception('Страница не найдена', 404);
        }

        // связанные сущности
        $crossvendor = \R::findOne('plagins_cross_vendor', "id = ?", [(int)$cross->vendor_id]);
        $product     = \R::findOne('product', "id = ? AND hide != 'hide'", [(int)$cross->product_id]);
        if(!$product){
            throw new \Exception('Страница не найдена', 404);
        }

        // --- безопасные чтения POST/SESSION ---
        $post = $_POST ?? [];
        $oneclickToken = md5(date('Y-m-d'));

        $fio_click   = isset($post['fio_click'])   ? trim((string)$post['fio_click'])   : '';
        $tell_click  = isset($post['tell_click'])  ? preg_replace('~[^0-9+]+~u', '', (string)$post['tell_click']) : '';
        $email_click = isset($post['email_click']) ? trim((string)$post['email_click']) : '';
        $prim_click  = isset($post['prim_click'])  ? trim((string)$post['prim_click'])  : '';

        $name_tovar  = isset($post['name_tovar'])  ? trim((string)$post['name_tovar'])  : '';
        $product_id  = isset($post['product_id'])  ? (int)$post['product_id']          : 0;

        $oneclick    = isset($post['oneclick'])    ? (string)$post['oneclick']          : '';
        $politika    = isset($post['politika'])    ? (string)$post['politika']          : '';

        $sessionUser = $_SESSION['user'] ?? null;
        $user_id     = is_array($sessionUser) ? ($sessionUser['id'] ?? null) : null;

        if ($email_click !== '' && !filter_var($email_click, FILTER_VALIDATE_EMAIL)) {
            $email_click = '';
        }

        $data_create = date('Y-m-d H:i:s');

        // --- обработка OneClick ---
        if ($oneclick === $oneclickToken) {
            if ($politika === 'pk') {
                // упрощённая анти-бот проверка для российских номеров
                $first = substr($tell_click, 0, 3); // "+79", "+7 "
                if ($first !== '+79' && $first !== '+7 ') {
                    $this->errors['unique'][] = "Запрос не обработан! Вы робот? Если нет, попробуйте заполнить форму ещё раз!";
                } else {
                    // письмо
                    Product::mailZakazClick($name_tovar, $fio_click, $tell_click, $email_click, $prim_click);

                    // заявка (параметризованно)
                    \R::exec(
                        "INSERT INTO mail_oneclick
                         (user_id, product_id, name, fio_click, tell_click, email_click, prim_click, data_create, hide_call, data_call, call_uid, hide_order, order_id, hide)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '', '', '0')",
                        [(int)$user_id, (int)$product_id, $name_tovar, $fio_click, $tell_click, $email_click, $prim_click, $data_create]
                    );

                    // история в админку
                    $oneClickId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
                    AdminActivityLogger::incoming(AdminActivityLogger::ACTION_ONECLICK, 'mail_oneclick', $oneClickId, (int)$user_id);

                    setcookie("click-mig", "1house", time()+3600);
                }
            } else {
                $this->errors['unique'][] = "Запрос не обработан! Вы отказались принимать условия политики конфиденциальности.";
            }
            redirect();
        }

        // хлебные крошки
        $breadcrumbs = Breadcrumbs::getBreadcrumbs($product->category_id, $product->name);

        // связанные товары
        $related = \R::getAll("
            SELECT *
            FROM related_product
            JOIN product ON product.id = related_product.related_id
            WHERE related_product.product_id = ?
        ", [$product->id]);

        // похожие товары
        $similar = \R::getAll("
            SELECT *
            FROM similar_product
            JOIN product ON product.id = similar_product.similar_id
            WHERE similar_product.product_id = ? AND product.hide = ?
            ORDER BY product.quantity DESC
        ", [$product->id, 'show']);

        // запись в куки запрошенного товара
        $p_model = new Cross();
        $p_model->setRecentlyViewed($product->id);

        // просмотренные товары
        $r_viewed = $p_model->getRecentlyViewed();
        $recentlyViewed = null;
        if($r_viewed){
            $recentlyViewed = \R::find(
                'product',
                'id IN (' . \R::genSlots($r_viewed) . ') LIMIT 3',
                $r_viewed
            );
        }

        $cat_prod = \R::findOne('category', "id = ?", [$product->category_id]);
        $vendor   = \R::findOne('brand',    "id = ?", [$product->brand_id]);

        // группа атрибутов товаров (исправлен GROUP BY)
        $attribute_group = \R::getAll("
            SELECT *
            FROM attribute
            JOIN product_attribute ON product_attribute.attribute_group_id = attribute.id
            WHERE product_attribute.product_id = ?
            GROUP BY product_attribute.attribute_group_id
        ", [$product->id]);

        // атрибуты товаров
        $attributs = \R::getAll("
            SELECT *
            FROM attribute
            JOIN product_attribute ON product_attribute.attribute_id = attribute.id
            WHERE product_attribute.product_id = ?
            ORDER BY attribute_position
        ", [$product->id]);

        // галерея и модификации
        $gallery = \R::findAll('gallery', 'product_id = ?', [$product->id]);
        $mods    = \R::findAll('modification', 'product_id = ?', [$product->id]);

        // SEO-тексты
        $title = "Купить аналог фильтра " . (string)$cross->cross_name . " " . ($crossvendor ? (string)$crossvendor->name : "") . " по низким ценам | ИТС-Центр";
        $description = $product->name . " является аналогом для фильтра " . ($crossvendor ? (string)$crossvendor->name : "") .
                       " с OEM номером " . (string)$cross->cross_name . " и соответствует всем характеристикам. " .
                       "Купить фильтр с доставкой по России можно в ИТС-Центре по низким ценам.";
        $keywords = "Купить " . ($crossvendor ? (string)$crossvendor->name : "") . ", фильтр " . (string)$cross->cross_name . " цена";

        $date = date("Y-m-d H:i:s");
        $action = \R::findOne('actions', "product_id = ? AND hide = 'show' AND date_end > ?", [$product->id, $date]);

        // InSEO (чтобы не было compact() notice)
        $inseo = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", ['product', $product->category_id]);

        /* SEO мета */
        $path_controller = $this->route["controller"] ? "/".mb_strtolower($this->route["controller"]) : "";
        $path_alias      = $this->route["alias"] ? "/".$this->route["alias"] : "";
        $product_img     = $product->img
                           ? PATH."/images/product/mini/".$product->img
                           : PATH."/images/".App::$app->getProperty('og_logo');

        $this->setMeta(
            $title,
            $description,
            $keywords,
            (string)App::$app->getProperty('shop_name'),
            $product_img,
            PATH.$path_controller.$path_alias
        );
        /* END SEO */

        $this->set(compact(
            'product', 'related', 'similar', 'gallery', 'recentlyViewed', 'breadcrumbs',
            'mods', 'attribute_group', 'attributs', 'cat_prod', 'vendor', 'inseo', 'action',
            'cross', 'crossvendor'
        ));
    }

}
