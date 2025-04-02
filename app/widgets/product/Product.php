<?php

namespace app\widgets\product;

use ishop\App;

class Product{
	
	public $product;
	public $tpl;
	public $curr;
	
    public function __construct($product, $curr, $tpl = ''){

		$this->tpl = $tpl ?: __DIR__ . '/product_tpl.php';
        $this->run($product, $curr);
		
    }
	
	protected function run($product, $curr){

        require $this->tpl;

    }

}