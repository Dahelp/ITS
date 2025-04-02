<?php

namespace app\controllers;

use app\models\admin\Product;
use ishop\App;

class ComparisonController extends AppController {

	public function indexAction(){		
        
		$cat_id = isset($_GET['cat_id']) ? $_GET['cat_id'] : '';
		$this->setMeta('Сравнение товаров');
		$this->set(compact('cat_id'));
	}
	
	public function addcomparisonAction(){
		if($_GET) {
			$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
			$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
			$kolcompar = isset($_GET['kolcompar']) ? $_GET['kolcompar'] : 0;

			$kolcompar = $kolcompar + 1;
			$_SESSION['comparison'][$product_id] = $product_id;			
			$_SESSION['comparison_category'][$category_id][$product_id] = $category_id;
			$_SESSION['comparison_count'] = $kolcompar;
			
			echo json_encode(array('result'=>''.$kolcompar.''));		
			die;
		}


	}
	
	public function deletecomparisonAction(){
		if($_GET) {
			$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
			$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';
			$kolcompar = isset($_GET['kolcompar']) ? $_GET['kolcompar'] : 0;

			$kolcompar = $kolcompar - 1;
			unset($_SESSION['comparison'][$product_id]);
			if(count($_SESSION['comparison_category'][$category_id])==1) {
				unset($_SESSION['comparison_category'][$category_id]);
			}
			if(count($_SESSION['comparison_category'][$category_id])>1) {
				unset($_SESSION['comparison_category'][$category_id][$product_id]);
			}
			$_SESSION['comparison_count'] = $kolcompar;
			
			foreach($_SESSION['comparison_category'] as $category => $key) { 
				if(!empty($category)) { 
					$ncat = \R::findOne('category', 'id = ?', [$category]);			
					$catcompar .= "<div class=\"compar_category catcamp-".$category."\"><a href=\"comparison?cat_id=".$category."\">".$ncat["name"]." (".count($_SESSION['comparison_category'][$category]).")</a></div>";
					$keys .= "$category,";
				}
			}
			echo json_encode(array('result'=>''.$kolcompar.'', 'result2'=>''.$catcompar.''));		
			die;
		}
	}
	
	public function deletevseAction(){		
		unset($_SESSION['comparison']);
		unset($_SESSION['comparison_category']);
		unset($_SESSION['comparison_count']);
		redirect();		
	}

}