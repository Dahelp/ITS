<?php

namespace app\controllers;

use app\models\admin\Company;
use app\models\Cart;
use app\models\Order;
use app\models\User;
use ishop\App;

class CartController extends AppController {

    public function addAction(){
        $id = !empty($_GET['id']) ? (int)$_GET['id'] : null;
        $qty = !empty($_GET['qty']) ? (int)$_GET['qty'] : null;
        $mod_id = !empty($_GET['mod']) ? (int)$_GET['mod'] : null;
		$modification = !empty($_GET['modification']) ? (int)$_GET['modification'] : null;
        $mod = null;
		$max = !empty($_GET['max']) ? (int)$_GET['max'] : null;

        $cart = new Cart();
		
		if($modification){
			
			if($id){
				$product = \R::findOne('product', 'id = ?', [$id]);
				if(!$product){
					return false;
				}
				
				$mods = \R::findAll('modification', 'product_id = ?', [$product->id]);
				foreach($mods as $modi):
					$sum_mods += $modi->quantity;
					$modprice .= "".$modi->price.", ";
				endforeach;
					$max = $product->quantity + $sum_mods;								
					$sql_modprice = "".$product->price.", ".$modprice."";
					$sql_modprice = rtrim($sql_modprice, ', ');								
					$maxs=[];
					$max_price=max($maxs=explode(",", $sql_modprice));
					
					$mod->id = $mod_id;
					$mod->name_modification = "unified";
					$mod->price = $max_price;
					$mod->article = $product->article;
					$mod->unit = "шт";
			}
				
			$cart->addToCart($product, $qty, $max, $mod);
			
		}else{
			
			if($id){
				$product = \R::findOne('product', 'id = ?', [$id]);
				if(!$product){
					return false;
				}
				if($mod_id){
					$mod = \R::findOne('modification', 'id = ? AND product_id = ?', [$mod_id, $id]);
				}
			}
				
			$cart->addToCart($product, $qty, $max, $mod);
			
		}
		if($this->isAjax()){
            $this->loadView('cart_modal');
        }
        redirect();
    }
	
	public function addcompleteAction(){
        $pid = !empty($_GET['id']) ? $_GET['id'] : null;
        $qty = !empty($_GET['qty']) ? (int)$_GET['qty'] : null;
        $mod_id = !empty($_GET['mod']) ? (int)$_GET['mod'] : null;
		$complete_prod = !empty($_GET['complete']) ? $_GET['complete'] : null;
		$set = !empty($_GET['set']) ? $_GET['set'] : null;
        $mod = null;
		$multiple_id = explode('-', $pid);
        $cart = new Cart();
		foreach($multiple_id as $id) {
			
			if($id){
				$product = \R::findOne('product', 'id = ?', [$id]);
				$complete = \R::findOne('plagins_complete_product', 'product_id = ?', [$id]);
				if(!$product){
					return false;
				}
				if($mod_id){
					$mod = \R::findOne('modification', 'id = ? AND product_id = ?', [$mod_id, $id]);
				}
			}
			if($product->quantity < $qty){ $quantity = $product->quantity; }
			if($product->quantity > $qty){ $quantity = $qty;}
			if($complete_prod == 1) { $product->price_complete = $complete->price; $product->price_discount = $complete->discount; }
			
			
			
			$cart->addToCartComplete($product, $quantity, $product->quantity, $complete->qty, $mod, $set);
		}
        if($this->isAjax()){
            $this->loadView('cart_modal');
        }
        redirect();
    }

    public function showAction(){
        $this->loadView('cart_modal');
    }	
	
// delete product id modal
    public function deleteAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
            $cart->deleteItem($id);
        }
        if($this->isAjax()){
            $this->loadView('cart_modal');
        }
        redirect();
    }
// delete product id modal to complete	
	public function deletecompleteAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
		$min = !empty($_GET['min']) ? $_GET['min'] : null;
		$set = !empty($_GET['set']) ? $_GET['set'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
            $cart->deletecompleteItem($id, $min, $set);
        }
        if($this->isAjax()){
            $this->loadView('cart_modal');
        }
        redirect();
    }
	
	// promo id cart	
	public function promocartAction(){
        $val = !empty($_GET['val']) ? $_GET['val'] : null;
        if($val){
            $cart = new Cart();
			$cart->promocartItem($val);			
        }
        if($this->isAjax()){
            $this->loadView('cart_table');
		}
        redirect();
    }
	
	public function clearpromoAction(){
		unset($_SESSION['promocart']);
		$cart = new Cart();
		$cart->clearpromoItem();
        $this->loadView('cart_table');
    }
	
// delete product id cart	
	public function deletecartAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
            $cart->deleteItem($id);
        }
        if($this->isAjax()){
            $this->loadView('cart_table');
        }
        redirect();
    }
	
// delete product id cart to complete	
	public function deletecartcompleteAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
		$min = !empty($_GET['min']) ? $_GET['min'] : null;
		$set = !empty($_GET['set']) ? $_GET['set'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
            $cart->deletecompleteItem($id, $min, $set);
        }
        if($this->isAjax()){
            $this->loadView('cart_table');
        }
        redirect();
    }
// increase product id modal	
	public function plusmodalAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
			$cart->pluscartItem($id);			
        }
        if($this->isAjax()){
            $this->loadView('cart_modal');
		}
        redirect();
    }
// increase product id modal to complete	
	public function plusmodalcompleteAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
		$min = !empty($_GET['min']) ? $_GET['min'] : null;
		$set = !empty($_GET['set']) ? $_GET['set'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
			$cart->pluscartcompleteItem($id, $min, $set);			
        }
        if($this->isAjax()){
            $this->loadView('cart_modal');
		}
        redirect();
    }
// increase product id cart	
		public function pluscartAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
			$cart->pluscartItem($id);			
        }
        if($this->isAjax()){
            $this->loadView('cart_table');
		}
        redirect();
    }
// increase product id cart to complete	
	public function pluscartcompleteAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
		$min = !empty($_GET['min']) ? $_GET['min'] : null;
		$set = !empty($_GET['set']) ? $_GET['set'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
			$cart->pluscartcompleteItem($id, $min, $set);			
        }
        if($this->isAjax()){
            $this->loadView('cart_table');
		}
        redirect();
    }
	
// reduce product id modal	
	public function minusmodalAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
			$cart->minuscartItem($id);			
        }
        if($this->isAjax()){
            $this->loadView('cart_modal');
		}
        redirect();
    }
// reduce product id modal to complete	
	public function minusmodalcompleteAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
		$min = !empty($_GET['min']) ? $_GET['min'] : null;
		$set = !empty($_GET['set']) ? $_GET['set'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
			$cart->minuscartcompleteItem($id, $min, $set);			
        }
        if($this->isAjax()){
            $this->loadView('cart_modal');
		}
        redirect();
    }
// reduce product id cart	
	public function minuscartAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
			$cart->minuscartItem($id);			
        }
        if($this->isAjax()){
            $this->loadView('cart_table');
		}
        redirect();
    }
// reduce product id cart to complete	
	public function minuscartcompleteAction(){
        $id = !empty($_GET['id']) ? $_GET['id'] : null;
		$min = !empty($_GET['min']) ? $_GET['min'] : null;
		$set = !empty($_GET['set']) ? $_GET['set'] : null;
        if(isset($_SESSION['cart'][$id])){
            $cart = new Cart();
			$cart->minuscartcompleteItem($id, $min, $set);			
        }
        if($this->isAjax()){
            $this->loadView('cart_table');
		}
        redirect();
    }

    public function clearAction(){
        unset($_SESSION['cart']);
        unset($_SESSION['cart.qty']);
        unset($_SESSION['cart.sum']);
		unset($_SESSION['cart.weight']);
		unset($_SESSION['cart.volume']);
        unset($_SESSION['cart.currency']);
		unset($_SESSION['promocart']);
        $this->loadView('cart_modal');
    }

    public function viewAction(){
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$this->setMeta('Корзина', 'Корзина', '', '' . App::$app->getProperty('shop_name') . '', ''.PATH.'/images/' . App::$app->getProperty('og_logo') . '', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
    }
	
	public function innAction(){
		if($_GET) {
			
			$client = new \GuzzleHttp\Client();
			$inn= isset($_GET['inn']) ? $_GET['inn'] : '';	
			$response = $client->request('GET', 'http://84.47.156.246:8082/itscentr/hs/CompanyListFrom1C/getCompanyFrom1C?inn='.$inn.'',

			 [
				'auth' => [
					'GetCompanyByINN',
					'GetCompanyByINN'
				]
			]

			);
			$str = iconv("CP1252", "UTF-8", $response->getBody());

			$response = json_decode($response->getBody(), true);
				
			echo "<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"comp_name\">Название компании</label>
					<input type=\"text\" name=\"comp_name\" class=\"form-control\" id=\"comp_name\" value=\"".htmlspecialchars($response["company"]["companyFields"]["Fullname"])."\">
					<input type=\"hidden\" name=\"comp_short_name\" class=\"form-control\" id=\"comp_short_name\" value=\"".htmlspecialchars($response["company"]["companyFields"]["shortName"])."\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"url_address\">Юр.адрес</label>
					<input type=\"text\" name=\"url_address\" class=\"form-control\" id=\"url_address\" value=\"".htmlspecialchars($response["company"]["companyAddresses"][0]["printForm"])."\">
					<input type=\"hidden\" name=\"postal_address\" class=\"form-control\" id=\"postal_address\" value=\"".htmlspecialchars($response["company"]["companyAddresses"][0]["printForm"])."\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"dir_name\">".htmlspecialchars($response["company"]["companyContacts"][0]["contact"]["jobName"])."</label>
					<input type=\"text\" name=\"dir_name\" class=\"form-control\" id=\"dir_name\" value=\"".htmlspecialchars($response["company"]["companyContacts"][0]["contact"]["f"])." ".htmlspecialchars($response["company"]["companyContacts"][0]["contact"]["i"])." ".htmlspecialchars($response["company"]["companyContacts"][0]["contact"]["o"])."\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"kpp\">КПП</label>
					<input type=\"text\" name=\"kpp\" class=\"form-control\" id=\"kpp\" value=\"".htmlspecialchars($response["company"]["companyFields"]["CodeKPP"])."\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"ogrn\">ОГРН</label>
					<input type=\"text\" name=\"ogrn\" class=\"form-control\" id=\"ogrn\" value=\"".htmlspecialchars($response["company"]["companyFields"]["CodeOGRN"])."\">
				</div>
				<p></p>			
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"bank\">Наименование банка</label>
					<input type=\"text\" name=\"bank\" class=\"form-control\" id=\"bank\" value=\"\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"raschet\">Расчётный счёт</label>
					<input type=\"text\" name=\"raschet\" class=\"form-control\" id=\"raschet\" value=\"\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"korschet\">Кор. счёт</label>
					<input type=\"text\" name=\"korschet\" class=\"form-control\" id=\"korschet\" value=\"\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"bik\">БИК</label>
					<input type=\"text\" name=\"bik\" class=\"form-control\" id=\"bik\" value=\"\">
				</div>";							
			
			die;
		}
	}
	
    public function checkoutAction(){
        if(!empty($_POST)){
			$usok = \R::findOne('user', 'email = ?', [$_POST['email']]);			
				if($usok["id"]) {
					$user_id = $usok["id"];
					$comp_id = $usok["comp_id"];
					$groups = $usok["groups"];
					$data = $_POST;				
				}else{
					// регистрация пользователя
					if(!User::checkAuth()){
						$user = new User();
						$data = $_POST;
						$first = substr($data["telefon"], 4,1);
						$first = (int)$first;
						if(!$first) { 
							$data["telefon"] = "";
							$_SESSION['error'] = 'Проверьте правильно введённый номер телефона!';
							$_SESSION['form_data'] = $data;
							redirect(); 
						}
						$user->load($data);
						if(!$user->validate($data)){
							$user->getErrors();
							$_SESSION['form_data'] = $data;
							redirect();
						}else{
							$user->attributes['password'] = password_hash($user->attributes['password'], PASSWORD_DEFAULT);
							if(!$user_id = $user->save('user')){
								$_SESSION['error'] = 'Ошибка!';
								redirect();
							}
						}
						/*if($_FILES['rekvizity']){}else{
							if($data['groups'] == 4){
								$company = new Company();						
								$company->load($data);
								
								if(!$company->validate($data) || !$company->checkUnique()){
									$company->getErrors();
									$_SESSION['form_data'] = $data;
									redirect();
								}
								$data['tip'] = 1;
								$data['user_id'] = $user_id;
								if($id = $company->save('company')){							
									\R::exec("UPDATE user SET comp_id = '".$id."' WHERE id = ?", [$user_id]);
									\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','33','company','".$id."','".date('Y-m-d H:i:s')."','".$user_id."')");
								
								}
								
							}
						}
						*/
					}
				}		
				// сохранение заказа
				$data['user_id'] = isset($user_id) ? $user_id : $_SESSION['user']['id'];
				$data['comp_id'] = !empty($comp_id) ? $comp_id : $id;
				$data['comp_short_name'] = !empty($_POST['comp_short_name']) ? $_POST['comp_short_name'] : '';
				$data['inn'] = !empty($_POST['inn']) ? $_POST['inn'] : '';
				$data['note'] = !empty($_POST['note']) ? $_POST['note'] : '';
				$data['dostavka_id'] = !empty($_POST['dostavka_id']) ? $_POST['dostavka_id'] : '';
				$data['address'] = !empty($_POST['address']) ? $_POST['address'] : '';
				$data['transport_id'] = !empty($_POST['transport_id']) ? $_POST['transport_id'] : '';
				$city_name = !empty($_POST['city_name']) ? $_POST['city_name'] : '';
				$cit = \R::findOne('cities', 'city_name = ?', [$city_name]);
				$data['city_id'] = !empty($cit['city_id']) ? $cit['city_id'] : '';
				$data['branch_id'] = !empty($_POST['branch_id']) ? $_POST['branch_id'] : '';
				$data['groups'] = !empty($_SESSION['user']['groups']) ? $_SESSION['user']['groups'] : $_POST['groups'];
				$user_email = isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : $_POST['email'];
				if($_FILES['rekvizity']) { $rekvizity = '1'; }else{ $rekvizity = '0'; }
				$usm = \R::findOne('user', 'email = ?', [$user_email]);
				if($data['groups'] == 4){
					$comp = \R::findOne('company', 'user_id = ?', [$data['user_id']]);
					if($comp['nds']){
						$data['nds'] = $comp['nds'];
					}
				}
				if($usm["admin_id"] !="0"){
					$data['admin_id'] = $usm["admin_id"];
				}else{
					$data['admin_id'] = 0;
				}
				$order_id = Order::saveOrder($data);
				$ord = \R::findOne('order', 'id = ?', [$order_id]);			
				$dost = \R::findOne('dostavka', 'id = ?', [$ord["dostavka_id"]]);
				$bran = \R::findOne('branch_office', 'branch_id = ?', [$ord["branch_id"]]);
				$trans = \R::findOne('transport_company', 'id = ?', [$ord["transport_id"]]);
				
				if($trans["name"]) { $transport_company = "<b>Название ТК:</b> ".$trans["name"]."<br>"; }
				if($ord["address"] !="") { $address = "<br><b>Адрес:</b> ".$ord["address"]."<br>"; }
				if($data['user_id']) {
					if($usm["groups"] == 3) { $vid = "<b>Вид клиента:</b> Физическое лицо<br>"; }
					if($usm["groups"] == 4) { 				
						$vid = "<b>Вид клиента:</b> Юридическое лицо<br>";
						if($comp) {
							$compname = "<b>Компания (зарегистрирована):</b> ".$comp['comp_short_name']." (".$comp['inn'].")<br>";						
							if($comp["nds"] == "1") { $nds = "<b>Налогообложение:</b> c НДС<br>"; }
							if($comp["nds"] == "2") { $nds = "<b>Налогообложение:</b> без НДС<br>"; } 
							if($comp["dogovor"] == "1") { $dogovor = "<b>Условия поставки:</b> Договор<br>"; }
							if($comp["dogovor"] == "2") { $dogovor = "<b>Условия поставки:</b> Счёт-договор<br>"; }
						}else{
							if($data['inn']) {
								$compname = "<b>Компания:</b> ".$data['comp_short_name']." (".$data['inn'].")<br>";
							}
							if($_POST["nds"] == "1") { $nds = "<b>Налогообложение:</b> c НДС<br>"; }
							if($_POST["nds"] == "2") { $nds = "<b>Налогообложение:</b> без НДС<br>"; } 
							if($_POST["dogovor"] == "1") { $dogovor = "<b>Условия поставки:</b> Договор<br>"; }
							if($_POST["dogovor"] == "2") { $dogovor = "<b>Условия поставки:</b> Счёт-договор<br>"; }
						}							
					}
				}
				Order::mailOrder($order_id, $user_email, $usm["name"], $usm["telefon"], $usm["admin_id"], $ord["note"], $ord["date"], $dost["name"], $bran["branch_name"], $address, $transport_company, $city_name, $vid, $compname, $nds, $dogovor);
				
        }
        redirect();
    }
	
	public function dostavkaAction(){
		if($_GET) {
			$dostavka_id = isset($_GET['dostavka_id']) ? $_GET['dostavka_id'] : '';			
			$dos = \R::findOne('dostavka', 'id = ?', [$dostavka_id]);			

			echo json_encode(array('result'=>''.$dos.''));		
			die;
		}
	}

}