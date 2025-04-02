<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;

class CatalogController extends AppController {

	public function indexAction(){
		$alias = $this->route['alias'];
		$up_registr = App::upRegistrLetter($alias);
		if($alias) {
			$cats = \R::findOne('category', 'alias = ?', [$alias]);
			$category = \R::find('category', 'parent_id = ?', [$cats["id"]]);
		}else{
			$category = \R::find('category', 'parent_id = ?', [0]);
		}
        if(!$category){
            throw new \Exception('Страница не найдена', 404);
        }

        $breadcrumbs = Breadcrumbs::getBreadcrumbs($cats->id, NULL, $alias, mb_strtolower($this->route["controller"]));

		
		/*SEO*/
		//InSEO
		$inseo = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", ['category', $cats->id]);
		if(!empty($inseo->title)) {
			$title = \ishop\App::seoreplace($inseo->title, $cats->id);
		}else{ 
			if($this->route["controller"] == "category"){
				$title = $cats->title;
			}else{
				$title = "Каталог ".\ishop\App::downFirstLetter($cats->name)." в интернет-магазине ИТС-Центр";
			}
		}
		if(!empty($inseo->description)) {
			$description = \ishop\App::seoreplace($inseo->description, $cats->id);
		}else{ 
			if($this->route["controller"] == "category"){
				$description = $cats->description;
			}else{
				$description = "В каталоге ".\ishop\App::downFirstLetter($cats->name)." в интернет магазине ИТС-Центр можно подобрать и купить товары с доставкой до транспортной компании.";
			}
		}
		if(!empty($inseo->keywords)) {
			$keywords = \ishop\App::seoreplace($inseo->keywords, $cats->id);
		}else{ 
			if($this->route["controller"] == "category"){
				$keywords = $cats->keywords;
			}else{
				$keywords = "".\ishop\App::downFirstLetter($cats->name)." для спецтехники, ".\ishop\App::downFirstLetter($cats->name)." для погрузчиков";
			}
		}

		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$this->setMeta(''.$title.'', ''.$description.'', ''.$keywords.'', '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
        $this->set(compact('category', 'breadcrumbs', 'cats'));
    }
}