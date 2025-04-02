<?php

namespace app\controllers\admin;
use ishop\App;
use ishop\libs\Pagination;

class SearchController extends AppController{

    public function typeaheadAction(){
        if($this->isAjax()){
            $query = !empty(trim($_GET['query'])) ? trim($_GET['query']) : null;
            if($query){
                //$products = \R::getAll("SELECT id, name FROM product WHERE concat(name,article) LIKE ? AND hide = 'show' LIMIT 15", ["%{$query}%"]);
                $products = \R::getAll("SELECT id, name FROM (SELECT id, name FROM product WHERE concat(name,article) LIKE '%{$query}%' UNION SELECT product.id, product.name FROM product, plagins_cross WHERE product.id = plagins_cross.product_id AND (concat(plagins_cross.cross_name,plagins_cross.cross_abbreviated_name) LIKE '%{$query}%')) product LIMIT 15");
				if($products) { echo json_encode($products); }

            }
        }
        die;
    }
}