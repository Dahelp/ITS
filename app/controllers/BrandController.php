<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;

class BrandController extends AppController {

    public function viewAction(){
		
		$alias = $this->route['alias'];
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
			if($_GET['sort'] == "nal") { $sql_sort = "ORDER BY product.stock_status_id DESC"; }
			if($_GET['sort'] == "rate") { $sql_sort = "ORDER BY product.hit DESC"; }
		}
		
        $total = \R::exec("SELECT product_id FROM attribute_product, product WHERE attribute_product.product_id = product.id AND attribute_product.attr_id = '".$find->id."' $sql_sort");
		$ids = \R::getAll("SELECT product_id FROM attribute_product, product WHERE attribute_product.product_id = product.id AND attribute_product.attr_id = '".$find->id."' $sql_sort");
		if($ids){
			foreach($ids as $ds){
				$prid .= "".$ds["product_id"].",";
			}
			$ids = rtrim($prid, ',');
			$pagination = new Pagination($page, $perpage, $total);
			$start = $pagination->getStart();
			
			$products = \R::find('product', "hide = 'show' AND id IN ($ids) $sql_sort LIMIT $start, $perpage");
		}
        //InSEO
		$params = \R::findOne('attribute_group', "id = ?", [$find["attr_group_id"]]);
		$inseo = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [attribute_group, $find["attr_group_id"]]);
		if($inseo->title) {
			$title = \ishop\App::seoreplacefilter($inseo->title, $find->id);
		}else{ $title = $find->title; }
		if($inseo->description) {
			$description = \ishop\App::seoreplacefilter($inseo->description, $find->id);
		}else{ $description = $find->description; }
		if($inseo->keywords) {
			$keywords = \ishop\App::seoreplacefilter($inseo->keywords, $find->id);
		}else{ $keywords = $find->keywords; }

		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
				

        $this->set(compact('find', 'products', 'breadcrumbs', 'pagination', 'total', 'ids', 'params', 'inseo'));

    }
	public function indexAction(){
		$alias = $_SERVER['REQUEST_URI'];
		$alias = str_replace('/', '', $alias);
		$type = \R::findOne('attribute_group', 'url_params = ?', [$alias]);
		$groups = \R::findAll('attribute_value', 'attr_group_id = ?', [$type->id]);
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$this->setMeta($type->seo_title, $type->seo_description, $type->seo_keywords, '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
        $this->set(compact('groups', 'type'));
	}

} 