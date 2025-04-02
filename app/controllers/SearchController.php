<?php

namespace app\controllers;

use ishop\App;
use ishop\libs\Pagination;

class SearchController extends AppController{

    public function typeaheadAction(){
        if($this->isAjax()){
            $query = !empty(trim($_GET['query'])) ? trim($_GET['query']) : null;
            if($query){
                //$products = \R::getAll("SELECT id, name FROM product WHERE concat(name,article) LIKE ? AND hide = 'show' LIMIT 15", ["%{$query}%"]);
                $products = \R::getAll("SELECT id, name, img, price, alias FROM (SELECT id, name, img, price, alias FROM product WHERE hide = 'show' AND concat(name,article) LIKE '%{$query}%' UNION SELECT product.id, product.name, product.img, product.price, product.alias FROM product, plagins_cross WHERE product.id = plagins_cross.product_id AND (concat(plagins_cross.cross_name,plagins_cross.cross_abbreviated_name) LIKE '%{$query}%')) product LIMIT 15");
				if($products) { echo json_encode($products); }

            }
        }
        die;
    }

    public function indexAction(){
		
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perpage = App::$app->getProperty('pagination');	
		if(!$_GET['s']){
            throw new \Exception('Страница не найдена', 404);
        }
        $query = !empty(trim($_GET['s'])) ? trim($_GET['s']) : null;
		
		$total = \R::getAll("SELECT * FROM (SELECT id, name, price, opt_price, alias, hit, new_product, sale, img, category_id, article, quantity, price_rrs FROM product WHERE hide = 'show' AND concat(name,article) LIKE '%{$query}%' UNION SELECT product.id, product.name, product.price, product.opt_price, product.alias, product.hit, product.new_product, product.sale, product.img, product.category_id, product.article, product.quantity, product.price_rrs FROM product, plagins_cross WHERE product.id = plagins_cross.product_id AND (concat(plagins_cross.cross_name,plagins_cross.cross_abbreviated_name) LIKE '%{$query}%')) product");
        $total = count($total);
		$pagination = new Pagination($page, $perpage, $total);
        $start = $pagination->getStart();
		
        if($query){
            //$products = \R::find('product', "concat(name,article) LIKE ? AND hide = 'show'", ["%{$query}%"]);
			$products = \R::getAll("SELECT * FROM (SELECT id, name, price, alias, hit, new_product, sale, img, category_id, article, quantity, stock_status_id FROM product WHERE hide = 'show' AND concat(name,article) LIKE '%{$query}%' UNION SELECT product.id, product.name, product.price, product.alias, product.hit, product.new_product, product.sale, product.img, product.category_id, product.article, product.quantity, product.stock_status_id FROM product, plagins_cross WHERE product.id = plagins_cross.product_id AND (concat(plagins_cross.cross_name,plagins_cross.cross_abbreviated_name) LIKE '%{$query}%')) product ORDER BY FIELD(`stock_status_id`, 1,3,2,0), name ASC LIMIT $start, $perpage");
        
        }
        $this->setMeta('Поиск по: ' . h($query));
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$this->setMeta('Поиск по: ' . h($query), '', '', '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
        $this->set(compact('products', 'query', 'pagination', 'total'));
    }

}