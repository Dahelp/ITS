<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;

class ActionController extends AppController {

    public function viewAction(){
		
		$alias = $this->route['alias'];
		$find = \R::findOne('contents', 'alias = ?', [$alias]);
		if(!$find){
            throw new \Exception("Страница не найдена", 404);
        }
		$type = \R::findOne('content_type', 'id = ?', [$find->type_id]);
		$this->setMeta($find->title, $find->description, $find->keywords);
        $this->set(compact('find', 'type'));
    }
	public function indexAction(){
		$alias = $_SERVER['REQUEST_URI'];
		$alias = str_replace('/', '', $alias);
		$type = \R::findOne('content_type', 'param_url = ?', [$alias]);
		$conts = \R::findAll('contents', 'type_id = ?', [$type->id]);
		$this->setMeta($type->title, $type->description, $type->keywords);
        $this->set(compact('conts', 'type'));
	}

} 