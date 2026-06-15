<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;
use app\helpers\SchemaHelper;

class Controller extends AppController {

    public function viewAction(){

		$alias = rawurldecode($this->route['alias']);
		$up_registr = App::upRegistrLetter($alias);

		$find = \R::findOne('attribute_value', 'alias = ?', [$alias]);
		if(!$find){
			throw new \Exception('Страница не найдена', 404);
		}

		$breadcrumbs = Breadcrumbs::getBreadcrumbs($find->id);
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		$perpage = App::$app->getProperty('pagination');

		if(!empty($_GET['sort'])){
			if($_GET['sort'] == "price") { $sql_sort = "ORDER BY product.price ASC"; }
			if($_GET['sort'] == "nal")   { $sql_sort = "ORDER BY product.stock_status_id DESC"; }
			if($_GET['sort'] == "rate")  { $sql_sort = "ORDER BY product.hit DESC"; }
		}else{
			$sql_sort = "ORDER BY FIELD(`stock_status_id`, 1,3,2,0), name ASC";
		}

		// Считаем только ВИДИМЫЕ товары, иначе пагинация будет вести на пустые страницы
		$total = (int)\R::getCell(
			"SELECT COUNT(*)
			FROM attribute_product ap
			JOIN product p ON p.id = ap.product_id
			WHERE ap.attr_id = ? AND p.hide = 'show'",
			[$find->id]
		);

		// Получаем ids только видимых товаров с сортировкой
		$ids = \R::getAll(
			"SELECT ap.product_id
			FROM attribute_product ap
			JOIN product p ON p.id = ap.product_id
			WHERE ap.attr_id = ? AND p.hide = 'show'
			$sql_sort",
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

		if($ids){
			$prid = '';
			foreach($ids as $ds){
				$prid .= $ds["product_id"] . ",";
			}
			$idsStr = rtrim($prid, ',');

			$pagination = new Pagination($page, $perpage, $total);
			$start = $pagination->getStart();

			$products = \R::find('product', "hide = 'show' AND id IN ($idsStr) $sql_sort LIMIT $start, $perpage");
		}

		// InSEO (ручное важнее шаблона)
		$params = \R::findOne('attribute_group', 'id = ?', [$find->attr_group_id]);
		$inseo = \R::findOne(
			'plagins_inseo',
			"tip = ? AND category_id = ? AND hide = 'show'",
			['attribute_group', $find->attr_group_id]
		);

		// Title: сначала ручной, потом InSEO
		if (!empty($find->title)) {
			$title = $find->title;
		} elseif ($inseo && !empty($inseo->title)) {
			$title = \ishop\App::seoreplacefilter($inseo->title, $find->id);
		} else {
			$title = '';
		}

		// Description (meta страницы фильтра): сначала ручное, потом InSEO
		if (!empty($find->description)) {
			$description = $find->description;
		} elseif ($inseo && !empty($inseo->description)) {
			$description = \ishop\App::seoreplacefilter($inseo->description, $find->id);
		} else {
			$description = '';
		}

		// Keywords
		if (!empty($find->keywords)) {
			$keywords = $find->keywords;
		} elseif ($inseo && !empty($inseo->keywords)) {
			$keywords = \ishop\App::seoreplacefilter($inseo->keywords, $find->id);
		} else {
			$keywords = '';
		}

		/*SEO meta*/
		$canonical = rtrim(PATH, '/') . '/'
			. trim($params->url_params, '/') . '/'
			. ltrim($find->alias, '/');

		$this->setMeta(
			$title,
			$description,
			$keywords,
			'' . App::$app->getProperty('shop_name') . '',
			''.PATH.'/images/' . App::$app->getProperty('og_logo') . '',
			$canonical
		);


		// ---------- JSON-LD для страницы фильтра (CollectionPage + ItemList) ----------
		$itemUrls = [];
		if (!empty($products)) {
			foreach ($products as $p) {
				$itemUrls[] = '/product/' . $p->alias; // поправьте, если у вас другой роут товара
			}
		}

		// правильный URL: /{group}/{alias}
		$pagePath = '/' . trim($params->url_params ?? '', '/') . '/' . ltrim($find->alias, '/');
		$pageUrl  = rtrim(PATH, '/') . $pagePath;

		// name как H1-логика: seo_h1 -> InSEO name -> value
		$pageName = !empty($find->seo_h1)
			? $find->seo_h1
			: (($inseo && !empty($inseo->name)) ? \ishop\App::seoreplacefilter($inseo->name, $find->id) : $find->value);

		// description для schema: meta description страницы фильтра, либо фолбэк
		$pageDesc = $description ?: ($find->description ?: $find->value);

		$jsonLdCollection = SchemaHelper::renderCollectionPageJsonLd($pageUrl, $pageName, $pageDesc, $itemUrls, $pageUrl);

        // ---------- JSON-LD для страницы FAQ ----------
        $jsonLdFaq = '';
        if (!empty($faqRows)) {
            $jsonLdFaq = SchemaHelper::renderFaqPageJsonLd($faqRows);
        }

		$this->set(compact('find', 'products', 'breadcrumbs', 'pagination', 'total', 'params', 'inseo', 'jsonLdCollection', 'relatedSizes', 'technicsLinks', 'faqRows', 'jsonLdFaq'));
	}

    public function indexAction(){
        $alias = $_SERVER['REQUEST_URI'];
        $alias = str_replace('/', '', $alias);

        $type = \R::findOne('attribute_group', 'url_params = ?', [$alias]);
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

        /*SEO*/
		$canonical = rtrim(PATH, '/') . '/' . trim($type->url_params, '/');

		$this->setMeta(
			$type->seo_title,
			$type->seo_description,
			$type->seo_keywords,
			App::$app->getProperty('shop_name'),
			PATH . '/images/' . App::$app->getProperty('og_logo'),
			$canonical
		);
		/*SEO*/

        $this->set(compact('groups', 'type'));
    }

}
