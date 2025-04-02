<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use app\models\Category;
use app\widgets\filter\Filter;
use ishop\App;
use ishop\libs\Pagination;

class CategoryController extends AppController {

    public function viewAction(){
        $alias = $this->route['alias'];
		$up_registr = App::upRegistrLetter($alias);
        $category = \R::findOne('category', 'alias = ?', [$alias]);
        if(!$category){
            throw new \Exception('Страница не найдена', 404);
        }

        $breadcrumbs = Breadcrumbs::getBreadcrumbs($category->id, NULL, $alias, mb_strtolower($this->route["controller"]));

        $cat_model = new Category();
        $ids = $cat_model->getIds($category->id);
        $ids = !$ids ? $category->id : $ids . $category->id;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perpage = App::$app->getProperty('pagination');

        $sql_part = '';
		
        if(!empty($_GET['filter'])){
            /*
            SELECT `product`.*  FROM `product`  WHERE category_id IN (6) AND id IN
            (
            SELECT product_id FROM attribute_product WHERE attr_id IN (1,5) GROUP BY product_id HAVING COUNT(product_id) = 2
            )
            */
            $filter = Filter::getFilter();
			
            if($filter){               
				
				//$cnt = substr_count($filter, ',') + 1; //с перезагрузкой
				$cnt = Filter::getCountGroups($filter); //без перезагрузки
                $sql_part = "AND product.id IN (SELECT product_id FROM attribute_product WHERE attr_id IN ($filter) GROUP BY product_id HAVING COUNT(product_id) = $cnt)";
            }
			
        }

		if(!empty($_GET['sort'])){
			if($_GET['sort'] == "price") { $sql_sort = "ORDER BY price ASC"; }
			if($_GET['sort'] == "nal") { $sql_sort = "ORDER BY stock_status_id DESC"; }
			if($_GET['sort'] == "rate") { $sql_sort = "ORDER BY hit DESC"; }
		}else{
			$sql_sort = "ORDER BY FIELD(`stock_status_id`, 1,3,2,0), name ASC";
		}

        $total = \R::count('product', "hide = 'show' AND category_id IN ($ids) $sql_part $sql_sort");
        $pagination = new Pagination($page, $perpage, $total);
        $start = $pagination->getStart();

        $products = \R::find('product', "hide = 'show' AND category_id IN ($ids) $sql_part $sql_sort LIMIT $start, $perpage");
		
        //InSEO
		$inseo = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", ['category', $category->id]);
		if(!empty($inseo->title)) {
			$title = \ishop\App::seoreplace($inseo->title, $category->id);
		}else{ $title = $category->title; }
		if(!empty($inseo->description)) {
			$description = \ishop\App::seoreplace($inseo->description, $category->id);
		}else{ $description = $category->description; }
		if(!empty($inseo->keywords)) {
			$keywords = \ishop\App::seoreplace($inseo->keywords, $category->id);
		}else{ $keywords = $category->keywords; }
		/*SEO*/
		
        if($this->isAjax()){
            $this->loadView('filter', compact('products', 'total', 'pagination', 'ids', 'inseo', 'filter', 'attr_id', 'cnt'));
        }
		
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }		
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
        $this->set(compact('filter', 'products', 'breadcrumbs', 'pagination', 'total', 'category', 'ids', 'inseo'));
    }

}