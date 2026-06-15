<?php

namespace app\models;

use ishop\App;

class Breadcrumbs{

    public static function getBreadcrumbs(
    $category_id,
    $bname = '',
    $alias_active = '',
    $controller = '',
    $lastCategoryAsLink = false
) {
    $cats = App::$app->getProperty('cats');
    $breadcrumbs_array = self::getParts($cats, $category_id);

    $e = function ($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    };

    if ($breadcrumbs_array) {
        $breadcrumbs = "<li class='breadcrumb-item'><a class='text-nowrap' href='" . PATH . "'><i class='fas fa-home'></i><span class='visually-hidden'>Главная</span></a></li>";
        $breadcrumbs .= "<li class='breadcrumb-item'><a class='text-nowrap' href='" . PATH . "/catalog'>Каталог</a></li>";

        foreach ($breadcrumbs_array as $alias => $name) {
            $aliasEsc = $e($alias);
            $nameEsc = $e($name);

            $caturl = \R::findOne('category', 'alias = ?', [$alias]);

            if (!$caturl) {
                continue;
            }

            $isActive = ($alias_active == $alias);

            if ((string)$caturl['type_id'] === '1') {
                $url = PATH . "/catalog/{$aliasEsc}";

                if (!$isActive || $lastCategoryAsLink) {
                    $breadcrumbs .= "<li class='breadcrumb-item' data-id='1'><a href='{$url}'>{$nameEsc}</a></li>";
                } else {
                    $breadcrumbs .= "<li class='breadcrumb-item text-nowrap active' data-id='1' aria-current='page'>{$nameEsc}</li>";
                }
            } else {
                $url = PATH . "/category/{$aliasEsc}";

                if (!$isActive || $lastCategoryAsLink) {
                    $breadcrumbs .= "<li class='breadcrumb-item' data-id='3'><a href='{$url}'>{$nameEsc}</a></li>";
                } else {
                    $breadcrumbs .= "<li class='breadcrumb-item text-nowrap active' data-id='4' aria-current='page'>{$nameEsc}</li>";
                }
            }
        }
    } else {
        if ($bname) {
            $breadcrumbs = "<li class='breadcrumb-item' data-id='5'><a class='text-nowrap' href='" . PATH . "'><i class='fas fa-home'></i><span class='visually-hidden'>Главная</span></a></li>";
            $breadcrumbs .= "<li class='breadcrumb-item'><a class='text-nowrap' href='" . PATH . "/catalog'>Каталог</a></li>";
        } else {
            $breadcrumbs = "<li class='breadcrumb-item' data-id='6'><a class='text-nowrap' href='" . PATH . "'><i class='fas fa-home'></i><span class='visually-hidden'>Главная</span></a></li>";
            $breadcrumbs .= "<li class='breadcrumb-item text-nowrap active' aria-current='page'>Каталог</li>";
        }
    }

    if ($bname) {
        $breadcrumbs .= "<li class='breadcrumb-item text-nowrap active' data-id='7' aria-current='page'>" . $e($bname) . "</li>";
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