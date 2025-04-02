<?php

namespace app\models;

use ishop\App;

class Cart extends AppModel {
	
	public $rules = [
        'required' => [            
            ['name'],
            ['email'],
			['telefon'],
        ],
        'email' => [
            ['email'],
        ],
		'telefon' => [
            ['telefon'],
        ]
    ];

    public function addToCart($product, $qty = 1, $max, $mod){
        if(!isset($_SESSION['cart.currency'])){
            $_SESSION['cart.currency'] = App::$app->getProperty('currency');
        }
        if($mod){
            $ID = "{$product->id}-{$mod->id}";
            $name = "{$product->name} ({$mod->name_modification})";
            $price = $mod->price;
			$article = $mod->article;
			$unit = $mod->unit;
			$weight = $product->weight;
			$volume = $product->volume;
        }else{
            $ID = $product->id;
            $name = $product->name;			
            $price = $product->price;
			$article = $product->article;
			$unit = $product->unit;
			$weight = $product->weight;
			$volume = $product->volume;
        }
		$uprice = \R::getRow('SELECT company.tip, company_typeprice.znachenie FROM company, company_typeprice WHERE company.id = company_typeprice.company_id AND company.user_id = ? AND company_typeprice.category_id = ?', [$_SESSION['user']['id'], $product->category_id]);
		if($uprice["tip"] == 2) { 
			if($uprice["znachenie"] == "") {
				$price = $product->opt_price;
			}else{				
				$price_nds = round($product->price - ($product->price/1.2), 0) * 6; $price_opt = $price_nds - (($price_nds/100) * $uprice["znachenie"]); $price = ceil($price_opt / 6) * 6;
			}
		} 
        if(isset($_SESSION['cart'][$ID])){
            $_SESSION['cart'][$ID]['qty'] += $qty;
        }else{
            $_SESSION['cart'][$ID] = [
                'qty' => $qty,
				'unit' => $unit,
				'weight' => $weight,
				'volume' => $volume,
				'max' => $max,
                'name' => $name,
				'article' => $article,
                'alias' => $product->alias,
                'price' => $price * $_SESSION['cart.currency']['value'],
                'img' => $product->img,
            ];
        }
        $_SESSION['cart.qty'] = isset($_SESSION['cart.qty']) ? $_SESSION['cart.qty'] + $qty : $qty;
        $_SESSION['cart.sum'] = isset($_SESSION['cart.sum']) ? $_SESSION['cart.sum'] + $qty * ($price * $_SESSION['cart.currency']['value']) : $qty * ($price * $_SESSION['cart.currency']['value']);
		$_SESSION['cart.weight'] = isset($_SESSION['cart.weight']) ? $_SESSION['cart.weight'] + $qty * $weight : $qty * $weight;
		$_SESSION['cart.volume'] = isset($_SESSION['cart.volume']) ? $_SESSION['cart.volume'] + $qty * $volume : $qty * $volume;
    }
	
	public function addToCartComplete($product, $qty, $max, $min, $mod = null, $set = null){
        if(!isset($_SESSION['cart.currency'])){
            $_SESSION['cart.currency'] = App::$app->getProperty('currency');
        }
        if($mod){
            $ID = "{$product->id}-{$mod->id}";
            $name = "{$product->name} ({$mod->name_modification})";
            $price = $mod->price;
			$article = $mod->article;
			$unit = $mod->unit;
			$weight = $product->weight;
			$volume = $product->volume;
        }else{
            $ID = $product->id;
            $name = $product->name;			
            $price = $product->price;
			$price_complete = $product->price_complete;
			$price_discount = $product->price_discount;
			$article = $product->article;
			$unit = $product->unit;
			$weight = $product->weight;
			$volume = $product->volume;
        }
		$uprice = \R::getRow('SELECT company.tip FROM company, company_typeprice WHERE company.id = company_typeprice.company_id AND company.user_id = ? AND company_typeprice.category_id = ?', [$_SESSION['user']['id'], $product->category_id]);
		if($uprice["tip"] == 2) { $price = $product->opt_price; }
		
        if(isset($_SESSION['cart'][$ID])){
            $_SESSION['cart'][$ID]['qty'] += $qty;
        }else{
            $_SESSION['cart'][$ID] = [
                'qty' => $qty,
				'unit' => $unit,
				'weight' => $weight,
				'volume' => $volume,
				'max' => $max,
				'min' => $min,
                'name' => $name,
				'article' => $article,
                'alias' => $product->alias,
                'price' => $price * $_SESSION['cart.currency']['value'],
				'price_complete' => $price_complete * $_SESSION['cart.currency']['value'],
				'price_discount' => $price_discount,
                'img' => $product->img,
				'set' => $set,
            ];
        }
		if($set){
			if($qty < $min){
				$price = $price_complete;
			}else{
				$price = $price_complete - $price_discount;
			}
		}
		$_SESSION['cart.qty'] = isset($_SESSION['cart.qty']) ? $_SESSION['cart.qty'] + $qty : $qty;        
        $_SESSION['cart.sum'] = isset($_SESSION['cart.sum']) ? $_SESSION['cart.sum'] + $qty * ($price * $_SESSION['cart.currency']['value']) : $qty * ($price * $_SESSION['cart.currency']['value']);
		$_SESSION['cart.weight'] = isset($_SESSION['cart.weight']) ? $_SESSION['cart.weight'] + $qty * $weight : $qty * $weight;
		$_SESSION['cart.volume'] = isset($_SESSION['cart.volume']) ? $_SESSION['cart.volume'] + $qty * $volume : $qty * $volume;
    }

    public function deleteItem($id){
        $qtyMinus = $_SESSION['cart'][$id]['qty'];
		$sumWeight = $_SESSION['cart'][$id]['weight'];
		$sumVolume = $_SESSION['cart'][$id]['volume'];
        $sumMinus = $_SESSION['cart'][$id]['qty'] * $_SESSION['cart'][$id]['price'];
        $_SESSION['cart.qty'] -= $qtyMinus;
        $_SESSION['cart.sum'] -= $sumMinus;
		$_SESSION['cart.weight'] -= $sumWeight;
		$_SESSION['cart.volume'] -= $sumVolume;
        unset($_SESSION['cart'][$id]);
    }
	
	public function deletecompleteItem($id, $min, $set){
		
		unset($_SESSION['cart'][$id]);
		
		foreach($_SESSION['cart'] as $pid => $item){ 
			if($item["set"] == $set) {
				$quantity[$set] = 1;
				unset($_SESSION['cart'][$pid]['set']);			
			}else{
				if($_SESSION['cart'][$pid]['set']) {
					$qty[$pid] = $_SESSION['cart'][$pid]['qty'];
					if($qty[$pid] < $_SESSION['cart'][$pid]['min']) {						
						$quantity[$_SESSION['cart'][$pid]['set']] = 1;											
					}
					if($qty[$pid] == $_SESSION['cart'][$pid]['min']) {
						
						$quantity[$_SESSION['cart'][$pid]['set']] = 0;
					}
					if($qty[$pid] > $_SESSION['cart'][$pid]['min']) {
						$quantity[$_SESSION['cart'][$pid]['set']] = 0;
					}
				}
			}			
			if($item["set"]) { $kolko[$item["set"]] += $quantity[$item["set"]]; }			
		} 
		
		foreach($_SESSION['cart'] as $rid => $item){
			if($item["set"]) {
				if($kolko[$item["set"]] > 0){						
					$_SESSION['cart'][$rid]['price_discount'] = 0;
					$sum[$rid] = $item["price_complete"] * $qty[$rid];
					$kolvo[$rid] = $qty[$rid];					
				}else{						
					$complete_set = \R::getRow('SELECT plagins_complete_product.discount FROM plagins_complete_product, product WHERE plagins_complete_product.product_id = product.id AND plagins_complete_product.product_id = ? AND plagins_complete_product.complete_id = ?', [$rid, $item['set']]);
					$_SESSION['cart'][$rid]['price_discount'] = $complete_set["discount"];
					$sum[$rid] = ($item["price_complete"] - $complete_set["discount"]) * $qty[$rid];
					$kolvo[$rid] = $qty[$rid];					
				}
			}else{
				$sum[$rid] = $item["price"] * $item["qty"];
				$kolvo[$rid] = $item['qty'];
			}
		}

		$sumWeight = $_SESSION['cart'][$id]['weight'];
		$sumVolume = $_SESSION['cart'][$id]['volume'];
        $_SESSION['cart.qty'] = array_sum($kolvo);
        $_SESSION['cart.sum'] = array_sum($sum);
		$_SESSION['cart.weight'] -= $sumWeight;
		$_SESSION['cart.volume'] -= $sumVolume;        
    }
	
	public function pluscartItem($id){
        $qtyPlus = $_SESSION['cart'][$id]['qty'];
        $sumPlus = $_SESSION['cart'][$id]['price'];
		$weightPlus = $_SESSION['cart'][$id]['weight'];
		$volumePlus = $_SESSION['cart'][$id]['volume'];
        $_SESSION['cart.qty'] = $_SESSION['cart.qty'] + 1;
        $_SESSION['cart.sum'] += $sumPlus;
		$_SESSION['cart.weight'] += $weightPlus;
		$_SESSION['cart.volume'] += $volumePlus;
		$_SESSION['cart'][$id]['qty'] = $_SESSION['cart'][$id]['qty'] + 1;
		
    }
	
	public function promocartItem($val){
		$promo = \R::getRow('SELECT * FROM plagins_promocode WHERE promocode = ?', [$val]);
		if($promo){
			foreach($_SESSION['cart'] as $k => $v) {
				$_SESSION['cart'][$k]['price'] = $_SESSION['cart'][$k]['price'] - (($_SESSION['cart'][$k]['price']/100)*2);
				$qty[$k] = $_SESSION['cart'][$k]['qty'];
				$sum[$k] = $_SESSION['cart'][$k]['price'] * $_SESSION['cart'][$k]["qty"];
						
			}
			$_SESSION['cart.qty'] = array_sum($qty);
			$_SESSION['cart.sum'] = array_sum($sum);
			$_SESSION['promocart'] = $val;
		}
    }
	
	public function clearpromoItem(){
		
			foreach($_SESSION['cart'] as $k => $v) {
				
				$prods = \R::getRow('SELECT * FROM product WHERE id = ?', [$k]);
				$_SESSION['cart'][$k]['price'] = $prods['price'];
				$qty[$k] = $_SESSION['cart'][$k]['qty'];
				$sum[$k] = $_SESSION['cart'][$k]['price'] * $_SESSION['cart'][$k]["qty"];
						
			}
			$_SESSION['cart.qty'] = array_sum($qty);
			$_SESSION['cart.sum'] = array_sum($sum);
			
	}
	
	public function pluscartcompleteItem($id, $min, $set){
		foreach($_SESSION['cart'] as $pid => $item){ 
			if($item["set"] == $set) {
				if($id == $pid) { 
					$qty[$id] = $_SESSION['cart'][$id]['qty'] + 1;
					if($qty[$id] < $min) { 						
						$quantity[$set] = 1;											
					}
					if($qty[$id] == $min) {
						
						$quantity[$set] = 0;
					}
					if($qty[$id] > $min) {
						$quantity[$set] = 0;
					}					
				}
				else{ 
					$qty[$pid] = $_SESSION['cart'][$pid]['qty'];
					if($qty[$pid] < $_SESSION['cart'][$pid]['min']) {						
						$quantity[$set] = 1;											
					}
					if($qty[$pid] == $_SESSION['cart'][$pid]['min']) {
						
						$quantity[$set] = 0;
					}
					if($qty[$pid] > $_SESSION['cart'][$pid]['min']) {
						$quantity[$set] = 0;
					}					
				}				
				
			}else{
				if($_SESSION['cart'][$pid]['set']) {
					$qty[$pid] = $_SESSION['cart'][$pid]['qty'];
					if($qty[$pid] < $_SESSION['cart'][$pid]['min']) {						
						$quantity[$_SESSION['cart'][$pid]['set']] = 1;											
					}
					if($qty[$pid] == $_SESSION['cart'][$pid]['min']) {
						
						$quantity[$_SESSION['cart'][$pid]['set']] = 0;
					}
					if($qty[$pid] > $_SESSION['cart'][$pid]['min']) {
						$quantity[$_SESSION['cart'][$pid]['set']] = 0;
					}
				}
			}
			
			if($item["set"]) { $kolko[$item["set"]] += $quantity[$item["set"]]; }
			
			
		} 
		
		foreach($_SESSION['cart'] as $rid => $item){
			if($item["set"]) {
				if($kolko[$item["set"]] > 0){						
					$_SESSION['cart'][$rid]['price_discount'] = 0;
					$sum[$rid] = $item["price_complete"] * $qty[$rid];						
				}else{						
					$complete_set = \R::getRow('SELECT plagins_complete_product.discount FROM plagins_complete_product, product WHERE plagins_complete_product.product_id = product.id AND plagins_complete_product.product_id = ? AND plagins_complete_product.complete_id = ?', [$rid, $item['set']]);
					$_SESSION['cart'][$rid]['price_discount'] = $complete_set["discount"];
					$sum[$rid] = ($item["price_complete"] - $complete_set["discount"]) * $qty[$rid];						
				}
			}else{
				$sum[$rid] = $item["price"] * $item["qty"];
			}
		}
		
		$weightPlus = $_SESSION['cart'][$id]['weight'];
		$volumePlus = $_SESSION['cart'][$id]['volume'];
        $_SESSION['cart.qty'] = $_SESSION['cart.qty'] + 1;
        $_SESSION['cart.sum'] = array_sum($sum);
        
		$_SESSION['cart.weight'] += $weightPlus;
		$_SESSION['cart.volume'] += $volumePlus;
		$_SESSION['cart'][$id]['qty'] = $_SESSION['cart'][$id]['qty'] + 1;
		
    }
	
	public function minuscartItem($id){
        $qtyMinus = $_SESSION['cart'][$id]['qty'];
        $sumMinus = $_SESSION['cart'][$id]['price'];
		$weightMinus = $_SESSION['cart'][$id]['weight'];
		$volumeMinus = $_SESSION['cart'][$id]['volume'];
        $_SESSION['cart.qty'] = $_SESSION['cart.qty'] - 1;
        $_SESSION['cart.sum'] -= $sumMinus;
		$_SESSION['cart.weight'] -= $weightMinus;
		$_SESSION['cart.volume'] -= $volumeMinus;
		$_SESSION['cart'][$id]['qty'] = $_SESSION['cart'][$id]['qty'] - 1;		
    }
	
	public function minuscartcompleteItem($id, $min, $set){	
		
		foreach($_SESSION['cart'] as $pid => $item){ 
			if($item["set"] == $set) {
				if($id == $pid) { 
					$qty[$id] = $_SESSION['cart'][$id]['qty'] - 1;
					if($qty[$id] < $min) { 						
						$quantity[$set] = 1;											
					}
					if($qty[$id] == $min) {
						
						$quantity[$set] = 0;
					}
					if($qty[$id] > $min) {
						$quantity[$set] = 0;
					}					
				}
				else{ 
					$qty[$pid] = $_SESSION['cart'][$pid]['qty'];
					if($qty[$pid] < $_SESSION['cart'][$pid]['min']) {						
						$quantity[$set] = 1;											
					}
					if($qty[$pid] == $_SESSION['cart'][$pid]['min']) {
						
						$quantity[$set] = 0;
					}
					if($qty[$pid] > $_SESSION['cart'][$pid]['min']) {
						$quantity[$set] = 0;
					}					
				}				
				
			}else{
				if($_SESSION['cart'][$pid]['set']) {
					$qty[$pid] = $_SESSION['cart'][$pid]['qty'];
					if($qty[$pid] < $_SESSION['cart'][$pid]['min']) {						
						$quantity[$_SESSION['cart'][$pid]['set']] = 1;											
					}
					if($qty[$pid] == $_SESSION['cart'][$pid]['min']) {
						
						$quantity[$_SESSION['cart'][$pid]['set']] = 0;
					}
					if($qty[$pid] > $_SESSION['cart'][$pid]['min']) {
						$quantity[$_SESSION['cart'][$pid]['set']] = 0;
					}
				}
			}
			
			if($item["set"]) { $kolko[$item["set"]] += $quantity[$item["set"]]; }
			
			
		} 
		
		foreach($_SESSION['cart'] as $rid => $item){
			if($item["set"]) {
				if($kolko[$item["set"]] > 0){						
					$_SESSION['cart'][$rid]['price_discount'] = 0;
					$sum[$rid] = $item["price_complete"] * $qty[$rid];						
				}else{						
					$complete_set = \R::getRow('SELECT plagins_complete_product.discount FROM plagins_complete_product, product WHERE plagins_complete_product.product_id = product.id AND plagins_complete_product.product_id = ? AND plagins_complete_product.complete_id = ?', [$rid, $item['set']]);
					$_SESSION['cart'][$rid]['price_discount'] = $complete_set["discount"];
					$sum[$rid] = ($item["price_complete"] - $complete_set["discount"]) * $qty[$rid];						
				}
			}else{
				$sum[$rid] = $item["price"] * $item["qty"];
			}
		}
	
		$weightMinus = $_SESSION['cart'][$id]['weight'];
		$volumeMinus = $_SESSION['cart'][$id]['volume'];
        $_SESSION['cart.qty'] = $_SESSION['cart.qty'] - 1;
        $_SESSION['cart.sum'] = array_sum($sum);
		$_SESSION['cart.weight'] -= $weightMinus;
		$_SESSION['cart.volume'] -= $volumeMinus;
		$_SESSION['cart'][$id]['qty'] = $_SESSION['cart'][$id]['qty'] - 1;		
    }
	
    public static function recalc($curr){
        if(isset($_SESSION['cart.currency'])){
            if($_SESSION['cart.currency']['base']){
                $_SESSION['cart.sum'] *= $curr->value;
            }else{
                $_SESSION['cart.sum'] = $_SESSION['cart.sum'] / $_SESSION['cart.currency']['value'] * $curr->value;
            }
            foreach($_SESSION['cart'] as $k => $v){
                if($_SESSION['cart.currency']['base']){
                    $_SESSION['cart'][$k]['price'] *= $curr->value;
                }else{
                    $_SESSION['cart'][$k]['price'] = $_SESSION['cart'][$k]['price'] / $_SESSION['cart.currency']['value'] * $curr->value;
                }
            }
            foreach($curr as $k => $v){
                $_SESSION['cart.currency'][$k] = $v;
            }
        }
    }

}