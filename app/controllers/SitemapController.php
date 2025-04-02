<?php

namespace app\controllers;
use ishop\App;

class SitemapController extends AppController {

    public function indexAction(){
		
		$sm_category = \R::getAll("SELECT alias, name, id FROM `category` WHERE hide='show' AND parent_id = '0'");
		$sm_atgroup = \R::getAll("SELECT title, url_params FROM `attribute_group` WHERE url_params !=''");
					
		$this->setMeta('Карта сайта', 'Карта сайта its-center.ru');
		$this->set(compact('sm_category', 'sm_atgroup'));
    }
}