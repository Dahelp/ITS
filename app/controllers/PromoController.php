<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;

class PromoController extends AppController {

    public function viewAction(){
		
		$alias = $this->route['alias'];
		$up_registr = App::upRegistrLetter($alias);
		$find = \R::findOne('contents', 'alias = ?', [$alias]);
		if(!$find){
            throw new \Exception("Страница не найдена", 404);
        }
		$type = \R::findOne('content_type', 'id = ?', [$find->type_id]);

		// связанные товары
        $related = \R::getAll("SELECT * FROM content_related JOIN product ON product.id = content_related.related_id WHERE content_related.content_id = ?", [$find->id]);
		
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		if($find->img){$find_img = "".PATH."/images/contents/baseimg/".$find->img.""; }else{ $find_img = "".PATH."/images/".App::$app->getProperty('og_logo').""; }
		$this->setMeta($find->title, $find->description, $find->keywords, '' . App::$app->getProperty('shop_name') . '', ''.$find_img.'', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
		
        $this->set(compact('find', 'type', 'related'));
    }
	public function indexAction(){
		$alias = strtok($_SERVER["REQUEST_URI"],'?');
		$alias = str_replace('/', '', $alias);
		$type = \R::findOne('content_type', 'param_url = ?', [$alias]);
		
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perpage = App::$app->getProperty('pagination');
		
		$total = \R::count('contents', "hide = 'show' AND type_id = '$type->id'");
        $pagination = new Pagination($page, $perpage, $total);
        $start = $pagination->getStart();
		
		$conts = \R::findAll('contents', 'type_id = ? ORDER BY date_post DESC LIMIT ?, ?', [$type->id, $start, $perpage]);

		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$this->setMeta($type->title, $type->description, $type->keywords, '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
		
        $this->set(compact('conts', 'type', 'pagination'));
	}

} 