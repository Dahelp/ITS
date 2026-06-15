<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use app\models\Category;
use app\widgets\filter\Filter;
use ishop\App;
use ishop\libs\Pagination;

class CompleteController extends AppController {

    public function indexAction(){
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perpage = App::$app->getProperty('pagination');

        $sql_part = '';

		if(!empty($_GET['sort'])){
			if($_GET['sort'] == "price") { $sql_sort = "ORDER BY price ASC"; }
			if($_GET['sort'] == "nal") { $sql_sort = "ORDER BY stock_status_id DESC"; }
			if($_GET['sort'] == "rate") { $sql_sort = "ORDER BY hit DESC"; }
		}else{
			$sql_sort = "ORDER BY name ASC";
		}

        $total = \R::count('plagins_complete', "hide = 'show' $sql_part $sql_sort");
        $pagination = new Pagination($page, $perpage, $total);
        $start = $pagination->getStart();

        $completes = \R::find('plagins_complete', "hide = 'show' $sql_part $sql_sort LIMIT $start, $perpage");
		$title = "Комплекты шин, фильтров, дисков. Купить комплекты для ТО";
		$description = "Ищите комплект шин для своей колёсной техники? Хотите подобрать комплект для ТО? Нужна консультация? Вы там, где надо. На этих страницах вы сможете выбрать комплект товаров для своего квадроцикла, купить комплект для ТО на спецтехнику, найти аналоги фильтров и дисков.";
		$keywords = "";

		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }		
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
        $this->set(compact('completes', 'pagination'));
    }
	
	public function viewAction()
	{
		$alias = $this->route['alias'];
		$complete = \R::findOne('plagins_complete', "alias = ? AND hide != 'hide'", [$alias]);

		if (!$complete) {
			throw new \Exception('Страница не найдена', 404);
		}

		$cat_prod = \R::findOne('category', "id = ?", [$complete->category_id]);

		// Галерея
		$gallery = \R::findAll('plagins_complete_gallery', 'complete_id = ?', [$complete->id]);

		// InSEO
		$inseo = \R::findOne(
			'plagins_inseo',
			"tip = ? AND category_id = ? AND hide = 'show'",
			['complete', $complete->category_id]
		);

		$title = $complete->title ?: (!empty($inseo->title) ? \ishop\App::seoreplace($inseo->title, $complete->id) : $complete->name);
		$description = $complete->description ?: (!empty($inseo->description) ? \ishop\App::seoreplace($inseo->description, $complete->id) : $complete->name);
		$keywords = $complete->keywords ?: (!empty($inseo->keywords) ? \ishop\App::seoreplace($inseo->keywords, $complete->id) : '');

		if ($this->route["controller"]) {
			$path_controller = "/" . mb_strtolower($this->route["controller"]);
		} else {
			$path_controller = "";
		}

		if ($this->route["alias"]) {
			$path_alias = "/" . $this->route["alias"];
		} else {
			$path_alias = "";
		}

		$product_img = $complete->img
			? PATH . "/images/complete/mini/" . $complete->img
			: PATH . "/images/" . App::$app->getProperty('og_logo');

		$this->setMeta(
			$title,
			$description,
			$keywords,
			App::$app->getProperty('shop_name'),
			$product_img,
			PATH . $path_controller . $path_alias
		);

		// Бренд / vendor — безопасно, если у комплекта нет vendor_id
		$vendor = null;
		if (!empty($complete->vendor_id)) {
			$vendor = \R::findOne('vendor', 'id = ?', [$complete->vendor_id]);
		}

		// Состав комплекта
		$prods = \R::getAll("
			SELECT 
				p.id,
				p.name,
				p.alias,
				p.img,
				p.price,
				p.price_rrs,
				p.quantity,
				p.article,
				p.sku,
				p.hit,
				p.new_product,
				p.sale,
				pcp.product_id,
				pcp.qty,
				pcp.price AS price_complete,
				pcp.discount
			FROM plagins_complete_product pcp
			INNER JOIN product p ON pcp.product_id = p.id
			WHERE pcp.complete_id = ?
			ORDER BY pcp.id ASC
		", [$complete->id]);

		$price_complete = 0;
		$discount_complete = 0;
		$itg_qty = 0;
		$prod_id = [];
		$prod_qty = [];
		$prodid = [];
		$quantity_state = 0;

		foreach ($prods as &$prod) {
			$row_price = (float)$prod['price_complete'] * (int)$prod['qty'];
			$row_discount = (float)$prod['discount'] * (int)$prod['qty'];

			$price_complete += $row_price;
			$discount_complete += $row_discount;

			$prod_id[] = $prod['product_id'];
			$prod_qty[] = $prod['qty'];

			if ((int)$prod['quantity'] >= (int)$prod['qty']) {
				$quantity_state = 1;
				$prodid[] = $prod['product_id'];
			} elseif ((int)$prod['quantity'] > 0 && (int)$prod['quantity'] < (int)$prod['qty']) {
				$quantity_state = 0;
				$prodid[] = $prod['product_id'];
			} else {
				$quantity_state = 0;
			}

			$itg_qty += $quantity_state;
		}
		unset($prod);

		$prod_id = implode(',', $prod_id);
		$prod_qty = implode(',', $prod_qty);
		$prodid = implode('-', $prodid);

		$itog_price_complete = $price_complete - $discount_complete;

		// Группы атрибутов для верхнего блока
		$filters = \R::getAll("
			SELECT ag.title, ag.id
			FROM attribute_group ag
			INNER JOIN attribute_category ac ON ac.group_id = ag.id
			WHERE ac.category_id = ?
			ORDER BY ag.title ASC
		", [$complete->category_id]);

		// Значения атрибутов по группам
		$filterValues = [];
		foreach ($filters as $filter) {
			$fv = \R::getAll("
				SELECT DISTINCT av.value, av.alias
				FROM plagins_complete_product pcp
				INNER JOIN attribute_product ap ON ap.product_id = pcp.product_id
				INNER JOIN attribute_value av ON av.id = ap.attr_id
				WHERE pcp.complete_id = ?
				AND av.attr_group_id = ?
				ORDER BY av.value ASC
			", [$complete->id, $filter['id']]);

			$vals = [];
			foreach ($fv as $value) {
				$vals[] = $value['value'];
			}
			$filterValues[$filter['id']] = implode(', ', $vals);
		}

		// Атрибуты для таблицы характеристик
		$attr_names = \R::getAll("
			SELECT a.id, a.attribute_name
			FROM attribute a
			INNER JOIN attribute_comparison ac ON ac.attribute_id = a.id
			WHERE ac.category_id = ?
			ORDER BY a.attribute_name ASC
		", [$complete->category_id]);

		$productAttributes = [];
		foreach ($prods as $prod) {
			$rows = \R::getAll("
				SELECT pa.attribute_id, pa.attribute_text
				FROM product_attribute pa
				WHERE pa.product_id = ?
			", [$prod['product_id']]);

			foreach ($rows as $row) {
				$productAttributes[$prod['product_id']][(int)$row['attribute_id']] = $row['attribute_text'];
			}
		}

		// Применяемость
		$technics = [];

		$this->set(compact(
			'complete',
			'gallery',
			'cat_prod',
			'vendor',
			'prods',
			'price_complete',
			'discount_complete',
			'itg_qty',
			'prod_id',
			'prod_qty',
			'prodid',
			'itog_price_complete',
			'filters',
			'filterValues',
			'attr_names',
			'productAttributes',
			'technics',
			'inseo'
		));
	}

}