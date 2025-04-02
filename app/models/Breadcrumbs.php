<?php

namespace app\models;

use ishop\App;

class Breadcrumbs{

    public static function getBreadcrumbs($category_id, $bname = '', $alias_active = '', $controller = ''){
        $cats = App::$app->getProperty('cats');
        $breadcrumbs_array = self::getParts($cats, $category_id);

        if($breadcrumbs_array){			
			$breadcrumbs = "<li class='breadcrumb-item'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'><a itemprop='item' class='text-nowrap' href='" . PATH . "'><meta itemprop='name' content='Главная'><i class='fas fa-home'></i><meta itemprop='position' content='1'></a></span></li><li class='breadcrumb-item'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'><a itemprop='item' class='text-nowrap' href='" . PATH . "/catalog'>Каталог</a><meta itemprop='name' content='Каталог'><link itemprop='item' href='" . PATH . "/catalog'><meta itemprop='position' content='2'></span></li>";
            $i=2;
			foreach($breadcrumbs_array as $alias => $name){
				$position = $i+1;
				$pos = $i+1;
				$caturl = \R::findOne('category', 'alias = ?', [$alias]);
				if($caturl["type_id"] == "1"){					
					if($alias_active != $alias){
						$breadcrumbs .= "<li class='breadcrumb-item' data-id='1'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'><a itemprop='item' href='" . PATH . "/catalog/{$alias}'><span itemprop='name'>{$name}</span><meta itemprop='position' content='{$position}'></a></span></li>";
					}else{
						$breadcrumbs .= "<li class='breadcrumb-item text-nowrap active' data-id='1'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'>{$name}<meta itemprop='position' content='{$position}'></span></li>";
					}
				}else{
					if($alias_active != $alias){
						$breadcrumbs .= "<li class='breadcrumb-item' data-id='3'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'><a itemprop='item' href='" . PATH . "/category/{$alias}'><span itemprop='name'>{$name}</span><meta itemprop='position' content='{$position}'></a></span></li>";
					}else{
						$breadcrumbs .= "<li class='breadcrumb-item text-nowrap active' data-id='4'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'>{$name}<meta itemprop='name' content='{$name}'><link itemprop='item' href='" . PATH . "/category/{$alias}'><meta itemprop='position' content='{$position}'></span></li>";
					}
				}
				$i++;
            }
        }else{
			if($bname){
				$breadcrumbs = "<li class='breadcrumb-item' data-id='5'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'><a itemprop='item' class='text-nowrap' href='" . PATH . "'><meta itemprop='name' content='Главная'><i class='fas fa-home'></i><meta itemprop='position' content='1'></a></span></li><li class='breadcrumb-item'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'><a itemprop='item' class='text-nowrap' href='" . PATH . "/catalog'>Каталог</a><meta itemprop='name' content='Каталог'><link itemprop='item' href='" . PATH . "/catalog'><meta itemprop='position' content='2'></span></li>";
			}else{
				$breadcrumbs = "<li class='breadcrumb-item' data-id='6'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'><a itemprop='item' class='text-nowrap' href='" . PATH . "'><meta itemprop='name' content='Главная'><i class='fas fa-home'></i><meta itemprop='position' content='1'></a></span></li><li class='breadcrumb-item text-nowrap active'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'>Каталог<meta itemprop='name' content='Каталог'><link itemprop='item' href='" . PATH . "/catalog'><meta itemprop='position' content='2'></span></li>";
			}
		}
        if($bname){
			$pos = $pos+1;
            $breadcrumbs .= "<li class='breadcrumb-item text-nowrap active' data-id='7'><span itemscope='' itemprop='itemListElement' itemtype='http://schema.org/ListItem'>$bname<meta itemprop='name' content='{$bname}'><link itemprop='item' href='".PATH."/{$controller}/{$alias_active}'><meta itemprop='position' content='{$pos}'></span></li>";
        }
        return $breadcrumbs;
    }

    public static function getParts($cats, $id){
        if(!$id) return false;
        $breadcrumbs = [];
        foreach($cats as $k => $v){
            if(isset($cats[$id])){
                $breadcrumbs[$cats[$id]['alias']] = $cats[$id]['name'];
                $id = $cats[$id]['parent_id'];
            }else break;
        }
        return array_reverse($breadcrumbs, true);
    }

}