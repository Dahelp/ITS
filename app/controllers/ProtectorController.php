<?php

namespace app\controllers;

use app\helpers\SchemaHelper;
use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;

class ProtectorController extends AppController
{
    public function viewAction()
    {
        $alias = rawurldecode($this->route['alias'] ?? '');
        $alias = trim((string)$alias);

        if ($alias === '') {
            throw new \Exception('Страница не найдена', 404);
        }

        $params = \R::findOne(
            'attribute_group',
            'url_params = ?',
            ['protector']
        );

        if (!$params) {
            throw new \Exception('Группа фильтров не найдена', 404);
        }

        $find = \R::findOne(
            'attribute_value',
            "attr_group_id = ? AND alias = ? AND hide = 'show'",
            [(int)$params->id, $alias]
        );

        if (!$find) {
            throw new \Exception('Страница не найдена', 404);
        }

         /**
         * Прямой 301-редирект на каноническую category-страницу,
         * если он включён у группы фильтров.
         *
         * Условия:
         * - redirect_to_category = 1
         * - canonical_source = manual_map
         * - в attribute_value_category_canonical есть активная привязка
         */
        if (
            (int)$params->redirect_to_category === 1
            && trim((string)($params->canonical_source ?? 'none')) === 'manual_map'
        ) {
            $row = \R::getRow(
                "SELECT c.alias
                 FROM attribute_value_category_canonical avcc
                 INNER JOIN category c ON c.id = avcc.category_id
                 WHERE avcc.attr_value_id = ?
                   AND avcc.is_active = 1
                 LIMIT 1",
                [(int)$find->id]
            );

            if (!empty($row['alias'])) {
                $categoryAlias = trim((string)$row['alias'], '/');
                $valueAlias = trim((string)$find->alias, '/');

                if ($categoryAlias !== '' && $valueAlias !== '') {
                    $targetPath = '/category/' . $categoryAlias . '/' . $valueAlias;
                    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

                    if ($currentPath !== $targetPath) {
                        $target = rtrim(PATH, '/') . $targetPath;
                        header('Location: ' . $target, true, 301);
                        exit;
                    }
                }
            }
        }

        $breadcrumbs = Breadcrumbs::getBreadcrumbs($find->id);

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perpage = (int)App::$app->getProperty('pagination');

        $sqlSort = "ORDER BY FIELD(`stock_status_id`, 1,3,2,0), name ASC";
        if (!empty($_GET['sort'])) {
            if ($_GET['sort'] === 'price') {
                $sqlSort = "ORDER BY product.price ASC";
            } elseif ($_GET['sort'] === 'nal') {
                $sqlSort = "ORDER BY product.stock_status_id DESC";
            } elseif ($_GET['sort'] === 'rate') {
                $sqlSort = "ORDER BY product.hit DESC";
            }
        }

        $total = (int)\R::getCell(
            "SELECT COUNT(*)
             FROM attribute_product ap
             JOIN product p ON p.id = ap.product_id
             WHERE ap.attr_id = ? AND p.hide = 'show'",
            [$find->id]
        );

        $ids = \R::getAll(
            "SELECT ap.product_id
             FROM attribute_product ap
             JOIN product p ON p.id = ap.product_id
             WHERE ap.attr_id = ? AND p.hide = 'show'
             $sqlSort",
            [$find->id]
        );

        $relatedSizes = \R::getAll(
            "SELECT av.id, av.value, av.alias, ag.url_params
             FROM attribute_value_related r
             JOIN attribute_value av ON av.id = r.related_attr_value_id
             JOIN attribute_group ag ON ag.id = av.attr_group_id
             WHERE r.attr_value_id = ?
             ORDER BY r.sort, av.value",
            [$find->id]
        );

        $technicsLinks = \R::getAll(
            "SELECT t.id, t.name, t.alias
             FROM attribute_value_technic at
             JOIN technics_type t ON t.id = at.technic_id
             WHERE at.attr_value_id = ? AND t.hide = 'show'
             ORDER BY at.sort, t.name",
            [$find->id]
        );

        $faqRows = \R::getAll(
            "SELECT question, answer
             FROM attribute_value_faq
             WHERE attr_value_id = ? AND hide = 'show'
             ORDER BY sort, id
             LIMIT 20",
            [$find->id]
        );

        $products = [];
        $pagination = null;

        if (!empty($ids)) {
            $productIds = [];
            foreach ($ids as $row) {
                if (!empty($row['product_id'])) {
                    $productIds[] = (int)$row['product_id'];
                }
            }

            $productIds = array_values(array_unique(array_filter($productIds)));

            if (!empty($productIds)) {
                $idsStr = implode(',', $productIds);
                $pagination = new Pagination($page, $perpage, $total);
                $start = (int)$pagination->getStart();

                $products = \R::find(
                    'product',
                    "hide = 'show' AND id IN ($idsStr) $sqlSort LIMIT $start, $perpage"
                );
            }
        }

        $inseo = \R::findOne(
            'plagins_inseo',
            "tip = ? AND category_id = ? AND hide = 'show'",
            ['attribute_group', $find->attr_group_id]
        );

        if (!empty($find->title)) {
            $title = $find->title;
        } elseif ($inseo && !empty($inseo->title)) {
            $title = \ishop\App::seoreplacefilter($inseo->title, $find->id);
        } else {
            $title = '';
        }

        if (!empty($find->description)) {
            $description = $find->description;
        } elseif ($inseo && !empty($inseo->description)) {
            $description = \ishop\App::seoreplacefilter($inseo->description, $find->id);
        } else {
            $description = '';
        }

        if (!empty($find->keywords)) {
            $keywords = $find->keywords;
        } elseif ($inseo && !empty($inseo->keywords)) {
            $keywords = \ishop\App::seoreplacefilter($inseo->keywords, $find->id);
        } else {
            $keywords = '';
        }

        $canonical = rtrim(PATH, '/') . '/'
            . trim($params->url_params, '/') . '/'
            . ltrim($find->alias, '/');

        $this->setMeta(
            $title,
            $description,
            $keywords,
            App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            $canonical
        );

        $itemUrls = [];
        if (!empty($products)) {
            foreach ($products as $p) {
                $itemUrls[] = '/product/' . $p->alias;
            }
        }

        $pagePath = '/' . trim($params->url_params ?? '', '/') . '/' . ltrim($find->alias, '/');
        $pageUrl  = rtrim(PATH, '/') . $pagePath;

        $pageName = !empty($find->seo_h1)
            ? $find->seo_h1
            : (($inseo && !empty($inseo->name)) ? \ishop\App::seoreplacefilter($inseo->name, $find->id) : $find->value);

        $pageDesc = $description ?: ($find->description ?: $find->value);

        $jsonLdCollection = SchemaHelper::renderCollectionPageJsonLd(
            $pageUrl,
            $pageName,
            $pageDesc,
            $itemUrls,
            $pageUrl
        );

        $jsonLdFaq = '';
        if (!empty($faqRows)) {
            $jsonLdFaq = SchemaHelper::renderFaqPageJsonLd($faqRows);
        }

        $this->set(compact(
            'find',
            'products',
            'breadcrumbs',
            'pagination',
            'total',
            'params',
            'inseo',
            'jsonLdCollection',
            'relatedSizes',
            'technicsLinks',
            'faqRows',
            'jsonLdFaq'
        ));
    }

    public function indexAction()
    {
        $alias = trim((string)($_SERVER['REQUEST_URI'] ?? ''), '/');
        $alias = strtok($alias, '?');

        $type = \R::findOne('attribute_group', 'url_params = ?', [$alias]);
        if (!$type) {
            throw new \Exception('Страница не найдена', 404);
        }

        $groups = \R::findAll(
            'attribute_value',
            "attr_group_id = ?
             AND hide = 'show'
             AND id IN (
                SELECT ap.attr_id
                FROM attribute_product ap
                INNER JOIN product p ON p.id = ap.product_id
                WHERE p.hide = 'show'
             )
             ORDER BY value ASC",
            [(int)$type->id]
        );

        $canonical = rtrim(PATH, '/') . '/' . trim($type->url_params, '/');

        $this->setMeta(
            $type->seo_title,
            $type->seo_description,
            $type->seo_keywords,
            App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            $canonical
        );

        $this->set(compact('groups', 'type'));
    }
}
