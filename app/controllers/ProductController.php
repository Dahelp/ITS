<?php

namespace app\controllers;

use app\models\admin\Review;
use app\models\Breadcrumbs;
use app\models\Product;
use app\services\admin\AdminActivityLogger;
use ishop\App;
use app\helpers\SchemaHelper;

class ProductController extends AppController
{
    public function viewAction()
    {
        $post = $_POST ?? [];
        $dtmd = md5(date('Y-m-d'));

        $addReview    = !empty($post['addreview']);
        $productId    = isset($post['product_id']) ? (int)$post['product_id'] : 0;
        $content      = isset($post['content']) ? trim((string)$post['content']) : '';
        $point        = isset($post['point']) ? (int)$post['point'] : 0;

        $fio_modal    = isset($post['fio_modal']) ? trim((string)$post['fio_modal']) : '';
        $tell_modal   = isset($post['tell_modal']) ? trim((string)$post['tell_modal']) : '';
        $email_modal  = isset($post['email_modal']) ? trim((string)$post['email_modal']) : '';
        $prim_modal   = isset($post['prim_modal']) ? trim((string)$post['prim_modal']) : '';
        $name_tovar   = isset($post['name_tovar']) ? trim((string)$post['name_tovar']) : '';

        $oneclick     = isset($post['oneclick']) ? (string)$post['oneclick'] : '';
        $request      = isset($post['request']) ? (string)$post['request'] : '';
        $availability = isset($post['availability']) ? (string)$post['availability'] : '';
        $politika     = isset($post['politika']) ? (string)$post['politika'] : '';

        $sessionUser = $_SESSION['user'] ?? null;
        $userId = is_array($sessionUser) ? (int)($sessionUser['id'] ?? 0) : 0;

        if ($email_modal !== '' && !filter_var($email_modal, FILTER_VALIDATE_EMAIL)) {
            $email_modal = '';
        }

        $isValidRuMobile = static function (string $raw): bool {
            $d = preg_replace('/\D+/', '', $raw);
            if (strlen($d) !== 11) {
                return false;
            }
            if ($d[0] === '8') {
                $d[0] = '7';
            }
            return ($d[0] === '7' && $d[1] === '9');
        };

        if ($addReview) {
            $uid = $userId > 0 ? $userId : 0;

            $review = new Review();
            $data = [
                'product_id'   => $productId,
                'content'      => $content,
                'point'        => $point,
                'date_post'    => date('Y-m-d'),
                'hide'         => 'show',
                'finger_up'    => null,
                'finger_down'  => null,
                'user_id'      => $uid,
                'uname'        => '',
            ];

            if ($uid > 0) {
                $user = \R::load('user', $uid);
                $data['uname'] = (string)$user->name;
            }

            $review->load($data);
            if (!$review->validate($data)) {
                $review->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }
            if ($review->save('review')) {
                $_SESSION['success'] = 'Отзыв добавлен';
            }
            redirect();
        }

        $alias = $this->route['alias'];
        App::upRegistrLetter($alias);

        $product = \R::findOne('product', "alias = ? AND hide != 'hide'", [$alias]);
        if (!$product) {
            throw new \Exception('Страница не найдена', 404);
        }

        $data_create = date('Y-m-d H:i:s');
        if (!isset($this->errors['unique'])) {
            $this->errors['unique'] = [];
        }

        if ($oneclick === $dtmd) {
            if ($politika === 'pk') {
                if (!$isValidRuMobile($tell_modal)) {
                    $this->errors['unique'][] = "Запрос не обработан! Некорректный номер телефона. Укажите номер мобильного РФ в формате +7 9ХХ ХХХ-ХХ-ХХ.";
                } else {
                    Product::mailZakazClick($name_tovar, $fio_modal, $tell_modal, $email_modal, $prim_modal);

                    \R::exec(
                        "INSERT INTO mail_oneclick
                        (user_id, product_id, name, fio_click, tell_click, email_click, prim_click, data_create, hide_call, data_call, call_uid, hide_order, order_id, hide)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '', '', '0')",
                        [$userId, $productId, $name_tovar, $fio_modal, $tell_modal, $email_modal, $prim_modal, $data_create]
                    );
                    $oneClickId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
                    AdminActivityLogger::incoming(AdminActivityLogger::ACTION_ONECLICK, 'mail_oneclick', $oneClickId, $userId);

                    setcookie("click-mig", "1house", time() + 3600);
                }
            } else {
                $this->errors['unique'][] = "Запрос не обработан! Вы отказались принимать условия политики конфиденциальности на сайте.";
            }
            redirect();
        }

        if ($request === $dtmd) {
            if ($politika === 'pk') {
                if (!$isValidRuMobile($tell_modal)) {
                    $this->errors['unique'][] = "Запрос не обработан! Некорректный номер телефона. Укажите номер мобильного РФ в формате +7 9ХХ ХХХ-ХХ-ХХ.";
                } else {
                    Product::mailRequest($name_tovar, $fio_modal, $tell_modal, $email_modal, $prim_modal);

                    \R::exec(
                        "INSERT INTO mail_request
                        (user_id, product_id, name, fio, tell, email, note, data_create, hide_call, data_call, call_uid, hide_order, order_id, hide)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '', '', '0')",
                        [$userId, $productId, $name_tovar, $fio_modal, $tell_modal, $email_modal, $prim_modal, $data_create]
                    );
                    $requestId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
                    AdminActivityLogger::incoming(AdminActivityLogger::ACTION_PRODUCT_REQUEST, 'mail_request', $requestId, $userId);

                    setcookie("request-mig", "1house", time() + 3600);
                }
            } else {
                $this->errors['unique'][] = "Запрос не обработан! Вы отказались принимать условия политики конфиденциальности на сайте.";
            }
            redirect();
        }

        if ($availability === $dtmd) {
            if ($politika === 'pk') {
                Product::mailAvailability($name_tovar, $email_modal, $userId);

                \R::exec(
                    "INSERT INTO mail_availability
                    (user_id, email, product_id, data_create, status_nalichiya, data_postupleniya, status_otpravki, data_mail)
                    VALUES (?, ?, ?, ?, '0', '', '0', '')",
                    [$userId, $email_modal, $productId, $data_create]
                );
                $availabilityId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
                AdminActivityLogger::incoming(AdminActivityLogger::ACTION_AVAILABILITY, 'mail_availability', $availabilityId, $userId);
            } else {
                $this->errors['unique'][] = "Запрос не обработан! Вы отказались принимать условия политики конфиденциальности на сайте.";
            }
            redirect();
        }

        $breadcrumbs = Breadcrumbs::getBreadcrumbs(
            $product->category_id,
            $product->name,
            $alias,
            mb_strtolower($this->route["controller"])
        );

        $related = \R::findAll(
            'product',
            "id IN (
                SELECT related_id
                FROM related_product
                WHERE product_id = ?
            ) AND hide = ?
            ORDER BY quantity DESC",
            [$product->id, 'show']
        );

        $similar = \R::findAll(
            'product',
            "id IN (
                SELECT similar_id
                FROM similar_product
                WHERE product_id = ?
            ) AND hide = ?
            ORDER BY quantity DESC",
            [$product->id, 'show']
        );

        $review = \R::getAll(
            "SELECT *
             FROM review_product
             JOIN review ON review.id = review_product.review_id
             WHERE review_product.product_id = ?
             ORDER BY review.date_post DESC",
            [$product->id]
        );

        $p_model = new Product();
        $p_model->setRecentlyViewed($product->id);

        $r_viewed = $p_model->getRecentlyViewed();
        $recentlyViewed = null;
        if ($r_viewed) {
            $recentlyViewed = \R::find('product', 'id IN (' . \R::genSlots($r_viewed) . ') LIMIT 3', $r_viewed);
        }

        $cat_prod = \R::findOne('category', "id = ?", [$product->category_id]);
        $vendor = \R::findOne('brand', "id = ?", [$product->brand_id]);

        $attribute_group = \R::getAll(
            "SELECT *
             FROM attribute
             JOIN product_attribute ON product_attribute.attribute_group_id = attribute.id
             WHERE product_attribute.product_id = ?
             GROUP BY product_attribute.attribute_group_id",
            [$product->id]
        );

        $allAttributes = \R::getAll(
            "SELECT
                pa.attribute_group_id,
                pa.attribute_id,
                a.attribute_name,
                a.attribute_position,
                pa.attribute_text
             FROM product_attribute pa
             JOIN attribute a ON pa.attribute_id = a.id
             WHERE pa.product_id = ?
             ORDER BY pa.attribute_group_id, a.attribute_position",
            [$product->id]
        );

        $attributesByGroup = [];
        $attributeValueMap = [];
        foreach ($allAttributes as $att) {
            $groupId = (int)$att['attribute_group_id'];
            $attributesByGroup[$groupId][] = $att;
            $attributeValueMap[(int)$att['attribute_id']] = (string)$att['attribute_text'];
        }

        $gallery = \R::findAll('gallery', 'product_id = ?', [$product->id]);
        $mods = \R::findAll('modification', 'product_id = ?', [$product->id]);

        $inseo = \R::findOne(
            'plagins_inseo',
            "tip = ? AND category_id = ? AND hide = 'show'",
            ['product', $product->category_id]
        );

        $dateNow = date("Y-m-d H:i:s");
        $action = \R::findOne(
            'actions',
            "product_id = ? AND hide = 'show' AND date_end > ?",
            [$product->id, $dateNow]
        );

        $administr = null;
        if ($userId > 0) {
            $administr = \R::findOne('user', 'id = ?', [$userId]);
        }

        $productWishlisted = false;
        if ($userId > 0) {
            $productWishlisted = \R::count(
                'product_wishlists',
                'product_id = ? AND user_id = ?',
                [$product->id, $userId]
            ) > 0;
        }

        $reviewStat = [
            'bal' => 0.0,
            'cnt' => 0,
            'rating' => 0.0,
        ];
        if (!empty($review)) {
            $sumBal = 0.0;
            $cnt = 0;
            foreach ($review as $rw) {
                $sumBal += (float)($rw['point'] ?? 0);
                $cnt++;
            }
            $reviewStat['bal'] = $sumBal;
            $reviewStat['cnt'] = $cnt;
            $reviewStat['rating'] = $cnt > 0 ? ($sumBal / $cnt) : 0.0;
        }

        $quickFilters = \R::getAll(
            "SELECT ag.title, ag.url_params, av.value, av.alias
             FROM attribute_group ag
             JOIN attribute_value av ON av.attr_group_id = ag.id
             JOIN attribute_product ap ON ap.attr_id = av.id
             WHERE ap.product_id = ?
             GROUP BY ag.title, ag.url_params, av.value, av.alias
             ORDER BY ag.title, av.value",
            [$product->id]
        );

        $technics = [];
        if (!empty($attributeValueMap[4])) {
            $technics = \R::getAll(
                "SELECT technics.model, technics_manufacturer.name, technics.alias
                 FROM technics_tiposize
                 JOIN attribute_value ON technics_tiposize.value_id = attribute_value.id
                 JOIN technics ON technics.id = technics_tiposize.technics_id
                 JOIN technics_manufacturer ON technics_manufacturer.id = technics.manufacturer_id
                 WHERE attribute_value.value = ?",
                [$attributeValueMap[4]]
            );
        }

        $complete = \R::getAll(
            "SELECT DISTINCT pc.complete_id AS id, c.name, c.alias, c.img, c.description
             FROM plagins_complete_product pc
             JOIN plagins_complete c ON pc.complete_id = c.id
             WHERE pc.product_id = ?",
            [$product->id]
        );

        $completeItemsById = [];
        if (!empty($complete)) {
            $completeIds = array_values(array_filter(array_map(static fn($row) => (int)$row['id'], $complete)));
            if (!empty($completeIds)) {
                $slots = implode(',', array_fill(0, count($completeIds), '?'));

                $completeItems = \R::getAll(
                    "SELECT
                        pcp.complete_id,
                        p.name,
                        p.price AS price,
                        p.quantity,
                        pcp.product_id,
                        pcp.qty,
                        pcp.price AS price_complete,
                        pcp.discount
                     FROM plagins_complete_product pcp
                     JOIN product p ON pcp.product_id = p.id
                     WHERE pcp.complete_id IN ($slots)
                     ORDER BY pcp.complete_id, p.name",
                    $completeIds
                );

                foreach ($completeItems as $item) {
                    $cid = (int)$item['complete_id'];
                    $completeItemsById[$cid][] = $item;
                }
            }
        }

        $cross = \R::getAll(
            "SELECT
                pcv.name,
                pc.cross_name,
                pc.cross_abbreviated_name,
                pc.tip_cross,
                pc.equipment_vendor
             FROM plagins_cross pc
             JOIN plagins_cross_vendor pcv ON pc.vendor_id = pcv.id
             WHERE pc.product_id = ?",
            [$product->id]
        );

        $crossAnalog = \R::getAll(
            "SELECT
                pcv.name,
                pc.cross_name,
                pc.cross_abbreviated_name,
                pc.tip_cross,
                pc.equipment_vendor
             FROM plagins_cross pc
             JOIN plagins_cross_vendor pcv ON pc.vendor_id = pcv.id
             WHERE pc.equipment_vendor = ? AND pc.product_id = ?",
            [2, $product->id]
        );

        $crossOem = \R::getAll(
            "SELECT
                pcv.name,
                pc.cross_name,
                pc.cross_abbreviated_name,
                pc.tip_cross,
                pc.equipment_vendor
             FROM plagins_cross pc
             JOIN plagins_cross_vendor pcv ON pc.vendor_id = pcv.id
             WHERE pc.equipment_vendor = ? AND pc.product_id = ?",
            [1, $product->id]
        );

        $services = [];
        $servicesWidgetContext = [];

        if ((int)$product->category_id !== 38) {
            $services = \R::getAll(
                "SELECT p.*
                FROM service_product sp
                JOIN product p ON p.id = sp.service_id
                WHERE sp.product_id = ?
                AND p.category_id = 38
                AND p.hide = 'show'
                ORDER BY sp.id ASC",
                [(int)$product->id]
            );

            $servicesWidgetContext = !empty($services)
                ? \app\widgets\product\Product::buildContext($services)
                : [];
        }

        $reviewGalleryByReviewId = [];
        if (!empty($review)) {
            $reviewIds = array_values(array_filter(array_map(static fn($row) => (int)$row['id'], $review)));
            if (!empty($reviewIds)) {
                $slots = implode(',', array_fill(0, count($reviewIds), '?'));

                $reviewGalleryRows = \R::getAll(
                    "SELECT id, review_id, img
                     FROM review_gallery
                     WHERE review_id IN ($slots)
                     ORDER BY id",
                    $reviewIds
                );

                foreach ($reviewGalleryRows as $row) {
                    $rid = (int)$row['review_id'];
                    $reviewGalleryByReviewId[$rid][] = $row;
                }
            }
        }

        $relatedWidgetContext = \app\widgets\product\Product::buildContext(array_values($related ?: []));
        $similarWidgetContext = \app\widgets\product\Product::buildContext(array_values($similar ?: []));

        $title = $product->title ?: \ishop\App::seoreplace($inseo->title ?? '', $product->id);
        $description = $product->description ?: \ishop\App::seoreplace($inseo->description ?? '', $product->id);
        $keywords = $product->keywords ?: \ishop\App::seoreplace($inseo->keywords ?? '', $product->id);

        $path_controller = $this->route["controller"] ? "/" . mb_strtolower($this->route["controller"]) : "";
        $path_alias = $this->route["alias"] ? "/" . $this->route["alias"] : "";

        if ($product->img) {
            $product_img = PATH . "/images/product/mini/" . $product->img;
        } else {
            $product_img = PATH . "/images/" . App::$app->getProperty('og_logo');
        }

        $this->setMeta(
            $title,
            $description,
            $keywords,
            (string)App::$app->getProperty('shop_name'),
            $product_img,
            PATH . $path_controller . $path_alias
        );

        $reviewCount = is_array($review) ? count($review) : 0;
        $ratingValue = $reviewStat['rating'];

        $curr = App::$app->getProperty('currency');

        $priceForJsonLd = (float)$product->price;
        if ($action) {
            if ((string)$action->type_id === "1") {
                $priceForJsonLd = $product->price - ($product->price / 100 * (float)$action->znachenie);
            } elseif ((string)$action->type_id === "2") {
                $priceForJsonLd = $product->price - (float)$action->znachenie;
            }
        }
        if ($priceForJsonLd < 0) {
            $priceForJsonLd = 0.0;
        }

        $jsonLdProduct = SchemaHelper::renderProductJsonLd(
            $product,
            $vendor,
            $gallery ? (array)$gallery : [],
            $mods ? (array)$mods : [],
            $curr,
            (float)$priceForJsonLd,
            $inseo ?? null,
            $ratingValue,
            $reviewCount,
            true,
            $review ?? [],
            20
        );

        $breadcrumbsArr = [];
        $breadcrumbsArr[] = ['name' => 'Главная', 'link' => PATH];
        $breadcrumbsArr[] = ['name' => 'Каталог', 'link' => PATH . '/catalog'];

        $cats = App::$app->getProperty('cats');
        $parts = \app\models\Breadcrumbs::getParts($cats, $product->category_id);
        if ($parts) {
            foreach ($parts as $catAlias => $namePart) {
                $caturl = \R::findOne('category', 'alias = ?', [$catAlias]);
                $link = (string)PATH . (($caturl && (string)$caturl['type_id'] === "1")
                    ? "/catalog/{$catAlias}"
                    : "/category/{$catAlias}");
                $breadcrumbsArr[] = ['name' => (string)$namePart, 'link' => $link];
            }
        }
        $breadcrumbsArr[] = ['name' => (string)$product->name, 'link' => (string)PATH . '/product/' . (string)$product->alias];
        $jsonLdBreadcrumbs = SchemaHelper::renderBreadcrumbsJsonLd($breadcrumbsArr);

        $ratingDistribution = [1=>0,2=>0,3=>0,4=>0,5=>0];

        foreach ($review as $rw) {
            $point = (int)$rw['point'];
            if ($point >= 1 && $point <= 5) {
                $ratingDistribution[$point]++;
            }
        }

        $totalReviews = count($review);

        $needFancybox = true;

        $this->set(compact(
            'product',
            'related',
            'similar',
            'gallery',
            'recentlyViewed',
            'breadcrumbs',
            'mods',
            'attribute_group',
            'attributesByGroup',
            'attributeValueMap',
            'cat_prod',
            'vendor',
            'inseo',
            'action',
            'review',
            'reviewStat',
            'quickFilters',
            'technics',
            'complete',
            'completeItemsById',
            'cross',
            'crossAnalog',
            'crossOem',
            'reviewGalleryByReviewId',
            'administr',
            'productWishlisted',
            'relatedWidgetContext',
            'similarWidgetContext',
            'jsonLdProduct',
            'jsonLdBreadcrumbs',
            'ratingDistribution',
            'totalReviews',
            'services',
            'servicesWidgetContext',
            'needFancybox'
        ));


    }

    public function comparisonAction()
    {
        if ($_GET) {
            $product_id = $_GET["product_id"];
            $_SESSION['comparison'][$product_id] = $product_id;
        }
        $this->setMeta('Сравнение товаров');
    }

    public function addReviewAction()
    {
    }
}
