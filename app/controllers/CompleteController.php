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
	
	public function viewAction(){
		
		$alias = $this->route['alias'];
        $complete = \R::findOne('plagins_complete', "alias = ? AND hide != 'hide'", [$alias]);
        if(!$complete){
            throw new \Exception('Страница не найдена', 404);
        }

		$cat_prod = \R::findOne('category', "id = ?", [$complete->category_id]);
		
		// галерея
        $gallery = \R::findAll('plagins_complete_gallery', 'complete_id = ?', [$complete->id]);
		
		//InSEO
		$inseo = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [complete, $complete->category_id]);
		if($complete->title) { $title = $complete->title; }else{ $title = \ishop\App::seoreplace($inseo->title, $complete->id); }
		if($complete->description) { $description = $complete->description; }else{ $description = \ishop\App::seoreplace($inseo->description, $complete->id); }
		if($complete->keywords) { $keywords = $complete->keywords; }else{ $keywords = \ishop\App::seoreplace($inseo->keywords, $complete->id); }
		$date = date("Y-m-d H:i:s");
		$action = \R::findOne('actions', "product_id = ? AND hide = 'show' AND date_end > '".$date."'", [$complete->id]);
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		if($complete->img){$product_img = "".PATH."/images/complete/mini/".$complete->img.""; }else{ $product_img = "".PATH."/images/".App::$app->getProperty('og_logo').""; }
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.$product_img.'', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
		
        $this->set(compact('complete', 'gallery', 'cat_prod'));
	}

}