<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;

class TechnicsController extends AppController {

    public function viewAction(){
		
		$alias = $this->route['alias'];
		$technics = \R::findOne('technics', 'alias = ?', [$alias]);
		$type = \R::findOne('technics_type', 'id = ?', [$technics->type_id]);
		$manufacturer = \R::findOne('technics_manufacturer', 'id = ?', [$technics->manufacturer_id]);
		if(!$technics){
            throw new \Exception('Страница не найдена', 404);
        }
		
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perpage = App::$app->getProperty('pagination');
				
		$title = "Шины на ".$type->name." ".$manufacturer->name." ".$technics->model."";
		$description = "Компания «ИТС-Центр» предлагает купить новую высококачественную и надежную резину на спецтехнику по выгодным ценам. ".$type->name." ".$manufacturer->name." ".$technics->model." отличается удобным управлением, простотой обслуживания и долговечностью.";
		$keywords = "купить шины, на ".$type->name.", ".$manufacturer->name." ".$technics->model."";
		
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		if($technics->img){$product_img = "".PATH."/images/technics/mini/".$technics->img.""; }else{ $technics_img = "".PATH."/images/".App::$app->getProperty('og_logo').""; }
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.$technics_img.'', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/		
		
        $this->set(compact('technics', 'type', 'manufacturer'));

    }
	public function indexAction(){

		$technics = \R::getAll("SELECT * FROM technics_type WHERE hide = 'show'");
		
		$title = "Подбор шин по типу техники";
		$description = "Компания «ИТС-Центр» предлагает возпользоваться подбором шин по типу техники. Найти и купить шины можно у нас, большой ассортимент шин на различную технику.";
		$keywords = "Подбор шин, найти шины по типу техники, каталог техники";
		
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$technics_img = "".PATH."/images/".App::$app->getProperty('og_logo')."";
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.$technics_img.'', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
		
        $this->set(compact('technics'));
	}
	public function typeAction(){
		$alias = $this->route['alias'];
		$type = \R::findOne('technics_type', 'alias = ?', [$alias]);
		$manufacturers = \R::getAll("SELECT technics_manufacturer.name, technics_manufacturer.img, technics_manufacturer.alias FROM technics, technics_manufacturer, technics_type WHERE technics_type.id = technics.type_id AND technics_manufacturer.id = technics.manufacturer_id AND technics.type_id = '".$type["id"]."' GROUP BY technics_manufacturer.name ORDER BY technics_manufacturer.name");
		
		$title = "Подбор шин для ".$type["seoname_2"]." по модели и производителю техники";
		$description = "Подобрать шины для ".$type["seoname_2"]." по производителю техники, модели. Купить шины в ИТС-Центр с доставкой по всей России";
		$keywords = "Подбор шин, шины по производителю, каталог производителей техники";
		
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$technics_img = "".PATH."/images/".App::$app->getProperty('og_logo')."";
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.$technics_img.'', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
		
		$this->set(compact('type', 'manufacturers'));
	}
	
	public function manufacturerAction(){
		$alias = $this->route['alias'];		
		$type_alias = $this->route['type'];
		$manufacturer = \R::findOne('technics_manufacturer', 'alias = ?', [$alias]);
		$type = \R::findOne('technics_type', 'alias = ?', [$type_alias]);
		
		/*pagination*/
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perpage = App::$app->getProperty('pagination');
		$total = \R::count('technics', "manufacturer_id = '".$manufacturer["id"]."' AND type_id = '".$type["id"]."'");
        $pagination = new Pagination($page, $perpage, $total);
        $start = $pagination->getStart();
		/*pagination*/
		
		$technics = \R::getAll("SELECT technics.model, technics.img, technics.alias FROM technics, technics_manufacturer, technics_type WHERE technics_type.id = technics.type_id AND technics_manufacturer.id = technics.manufacturer_id AND technics.manufacturer_id = '".$manufacturer["id"]."' AND technics_type.alias = '".$type_alias."' ORDER BY technics.model ASC LIMIT $start, $perpage");
		
		$title = "Подбор шин на ".$type["seoname_3"]." ".$manufacturer["name"]."";
		$description = "Компания «ИТС-Центр» предлагает возпользоваться подбором шин по марке техники. Найти и купить шины на ".$type["seoname_3"]." ".$manufacturer["name"]." можно у нас, большой ассортимент шин на различную технику.";
		$keywords = "Подбор шин, найти шины по технике, каталог техники, шины на ".$type["seoname_3"]." ".$manufacturer["name"]."";
		
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$technics_img = "".PATH."/images/".App::$app->getProperty('og_logo')."";
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.$technics_img.'', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
		
		$this->set(compact('manufacturer', 'technics', 'type', 'pagination', 'total'));
	}

} 