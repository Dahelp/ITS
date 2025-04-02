<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use app\models\Category;
use app\widgets\filter\Filter;
use ishop\App;
use ishop\libs\Pagination;

class PodborController extends AppController {

    public function indexAction(){
        $alias = $this->route['alias'];
        $category = \R::findOne('category', 'alias = ?', [$alias]);
        if(!$category){
            throw new \Exception('Страница не найдена', 404);
        }

        $breadcrumbs = Breadcrumbs::getBreadcrumbs($category->id);
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
                $cnt = Filter::getCountGroups($filter);
                $sql_part = "AND id IN (SELECT product_id FROM attribute_product WHERE attr_id IN ($filter) GROUP BY product_id HAVING COUNT(product_id) = $cnt)";
            }
        }

		if(!empty($_GET['sort'])){
			if($_GET['sort'] == "price") { $sql_sort = "ORDER BY price ASC"; }
			if($_GET['sort'] == "nal") { $sql_sort = "ORDER BY stock_status_id DESC"; }
			if($_GET['sort'] == "rate") { $sql_sort = "ORDER BY hit DESC"; }
		}

        $total = \R::count('product', "hide = 'show' AND category_id IN ($ids) $sql_part $sql_sort");
        $pagination = new Pagination($page, $perpage, $total);
        $start = $pagination->getStart();

        $products = \R::find('product', "hide = 'show' AND category_id IN ($ids) $sql_part $sql_sort LIMIT $start, $perpage");
		
        if($this->isAjax()){
            $this->loadView('filter', compact('products', 'total', 'pagination', 'ids'));
        }
		
		//InSEO
		
		if($category->id == "3") { 
			$pdr_name = "Подбор дисков на спецтехнику по параметрам";
			$title = "Подбор дисков на спецтехнику по параметрам";
			$description = "Подбор дисков на спецтехнику по параметрам";
			$keywords = "";
		}
		if($category->id == "31") {
			$pdr_name = "Подбор камер на спецтехнику по параметрам";
			$title = "Подбор камер на спецтехнику по параметрам";
			$description = "Подбор камер на спецтехнику по параметрам";
			$keywords = "";
		}
		if($category->id == "34") {
			$pdr_name = "Подбор шин на спецтехнику по параметрам";
			$title = "Подбор шин на спецтехнику по параметрам";
			$description = "Подбор шин на спецтехнику по параметрам";
			$keywords = "";
		}
		
		
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }		
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
        $this->set(compact('products', 'breadcrumbs', 'pagination', 'total', 'url_alias', 'category', 'ids', 'pdr_name'));
    }
	
}