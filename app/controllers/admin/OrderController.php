<?php

namespace app\controllers\admin;

use app\models\admin\Order;
use ishop\App;
use ishop\libs\Pagination;

class OrderController extends AppController {

    public function indexAction(){
        $count = \R::count('order');
		$curr = \R::findOne('currency');
        $orders = \R::getAll("SELECT `order_status`.`status_name`, `order`.`id`, `order`.`user_id`, `order`.`status`, `order`.`inv`, `order`.`date`, `order`.`update_at`, `order`.`currency`, `user`.`name`, `user`.`admin_id`, `user`.`email`, `order`.`comp_id`, ROUND(SUM(`order_product`.`price` * `order_product`.`qty`), 2) AS `sum` FROM `order`
			JOIN `user` ON `order`.`user_id` = `user`.`id`
			JOIN `order_product` ON `order`.`id` = `order_product`.`order_id`
			JOIN `order_status` ON `order`.`status` = `order_status`.`id`
			GROUP BY `order`.`id`");

        $this->setMeta('Список заказов');
        $this->set(compact('orders', 'count', 'curr'));
    }

    public function viewAction(){
		if(!empty($_POST)){
			$id = $this->getRequestID();
			$order = new Order();
			$data = $_POST;
            $order->load($data);			
			$order->editOrder($id, $data);
			$order->editOrderProduct($id, $data);
			
			\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','44','order','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
			$_SESSION['success'] = 'Изменения сохранены';
            redirect();
		}
		$namecomp = App::$app->getProperty('shop_name');
        $order_id = $this->getRequestID();
		$order_prefix = \ishop\App::options('order_prefix');
		$curr = \R::findOne('currency');
        $order = \R::getRow("SELECT `order_status`.`status_name`, `order`.*, `user`.`name`, `user`.`admin_id`, `user`.`telefon`, `user`.`email`, `user`.`groups`, `dostavka`.`name` AS dostavkaname, `dostavka`.`id` AS dostavka_id, `order_product`.`discount`, `order_product`.`discount_amount`, ROUND(SUM(`order_product`.`price` * `order_product`.`qty`), 2) AS `sum` FROM `order`
  JOIN `user` ON `order`.`user_id` = `user`.`id`
  JOIN `order_product` ON `order`.`id` = `order_product`.`order_id`
  JOIN `order_status` ON `order`.`status` = `order_status`.`id`
  JOIN `dostavka` ON `order`.`dostavka_id` = `dostavka`.`id`
  WHERE `order`.`id` = ?
  GROUP BY `order`.`id` ORDER BY `order`.`status`, `order`.`id` LIMIT 1", [$order_id]);
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $order_products = \R::findAll('order_product', "order_id = ?", [$order_id]);
        $this->setMeta("Заказ №{$order_prefix}{$order_id}");
        $this->set(compact('order', 'order_products', 'curr', 'namecomp'));
    }
	
	public function addAction(){		
		if(!empty($_POST)){
			$order = new Order();
			$data = $_POST;
			$user_name = $data['user_name'];
			if($user_name) {
				$order->addUser($data);
				$user = \R::findLast('user');
				$user_id = $user->id;
				$data['user_id'] = $user_id;
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','36','user','".$user_id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
			}
			$comp_name = $data['comp_name'];
			if($comp_name) {
				$order->addCompany($data);
				$company = \R::findLast('company');
				$comp_id = $company->id;
				$data['comp_id'] = $comp_id;
				\R::exec("UPDATE user SET comp_id = '".$comp_id."' WHERE id = ?", [$user_id]);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','33','company','".$comp_id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
			}
            $order->load($data);
			$last_order = \R::findLast('order');
			$id = $last_order->id;
			$order->addOrder($id, $data);
			$order->addOrderProduct($id, $data);
			
			\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','43','order','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
			$_SESSION['success'] = 'Заказ создан';
            redirect();
		}
		
		$curr = \R::findOne('currency');
		$this->set(compact('curr'));
		$this->setMeta("Создать заказ");
	}
	
    public function changeAction(){
        $order_id = $this->getRequestID();
		$orders = new Order();
        $status = $_POST['status'];
        $order = \R::load('order', $order_id);
		$order_status = \R::findOne('order_status', 'id = ?', [$status]);
		$user = \R::load('user', $order->user_id);
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $order->status = $status;
        $order->update_at = date("Y-m-d H:i:s");
		$sending = $order_status["sending"];
		if($sending == "show"){
			$template = $order_status["template"];
			$orders->changeEmail($order_id, $user, $template);
		}
        \R::store($order);
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','46','order','".$order_id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
        $_SESSION['success'] = 'Изменения сохранены - Присвоен статус '.$order_status->status_name.'';
        redirect(ADMIN . '/order/view?id='.$order_id.'');
    }
	
	public function managerAction(){
        $order_id = $this->getRequestID();
		$orders = new Order();
        $admin_id = $_POST['admin_id'];
        $order = \R::load('order', $order_id);
		$user = \R::load('user', $order->user_id);
		$order_manager = \R::findOne('user', 'id = ?', [$admin_id]);
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $user->admin_id = $admin_id;
		$order->admin_id = $admin_id;
        $order->update_at = date("Y-m-d H:i:s");
		
		$orders->managerEmail($order_manager["email"], $order_id, $user);
        \R::store($order);
		\R::store($user);
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','52','order','".$order_id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
        $_SESSION['success'] = 'Изменения сохранены - Заказу привязан менеджер '.$order_manager->name.'';		
		
        redirect(ADMIN . '/order/view?id='.$order_id.'');
    }
	
	public function ordermanagerAction(){
        $order_id = $_GET['orderid'];
		$orders = new Order();
        $admin_id = $_GET['id'];
        $order = \R::load('order', $order_id);
		$user = \R::load('user', $order->user_id);
		$order_manager = \R::findOne('user', 'id = ?', [$admin_id]);
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $user->admin_id = $admin_id;
		$order->admin_id = $admin_id;
        $order->update_at = date("Y-m-d H:i:s");		
		
        \R::store($order);
	    \R::store($user);
	   
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','52','order','".$order_id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
        $_SESSION['success'] = 'Изменения сохранены - Заказу '.$order_id.' привязан менеджер '.$order_manager->name.'';		
		
        redirect(ADMIN . '/order');
    }
	
	public function sellerAction(){
        $order_id = $this->getRequestID();
        $seller = $_POST['seller'];
        $order = \R::load('order', $order_id);
		$company = \R::findOne('company', 'id = ?', [$seller]);
		
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $order->seller = $seller;
        $order->update_at = date("Y-m-d H:i:s");
		if($seller == 1){ $order_prefix = "SH"; }
		if($seller == 2){ $order_prefix = "RO"; }
		if($seller == 3){ $order_prefix = "IT"; }		
		$inv = \ishop\App::invoice_num($order_id, 9, $order_prefix);
		$order->inv = $inv;
        \R::store($order);
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','54','order','".$order_id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
        $_SESSION['success'] = 'Изменения сохранены - Присвоен продавец '.$company->comp_short_name.'';
        redirect(ADMIN . '/order/view?id='.$order_id.'');
    }

    public function deleteAction(){
        $order_id = $this->getRequestID();
        $order = \R::load('order', $order_id);
        \R::trash($order);
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','45','order','".$order_id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
        $_SESSION['success'] = 'Заказ удален';
        redirect(ADMIN . '/order');
    }
	
	public function searchproductAction(){
		$request = 1;
		$code = $_GET['code'];
		
		if(isset($_POST['request'])){
			$request = $_POST['request'];
		}
		
		// Select2 data
		if($request == 1){
			$q = isset($_GET['q']) ? $_GET['q'] : '';
			$data['items'] = [];
			$searchproduct = \R::getAssoc('SELECT id, name FROM product WHERE name LIKE ? LIMIT 10', ["%{$q}%"]);
			if($searchproduct){
				$i = 0;
				foreach($searchproduct as $id => $name){
					$data['items'][$i]['id'] = $id;
					$data['items'][$i]['text'] = $name;					
					$i++;
				}
			}
			echo json_encode($data);
			
			die;
		}

		// Add element
		if($request == 2){
			
		   $html = "<div style=\"width: 100%;display:flex\">
						<div style=\"width:50%;padding: 0 0 0 10px;\"><select name=\"order_zakaz[".$code."][product_id]\" class=\"form-control searchproduct_".$code."\" data-id=\"".$code."\" data-placeholder=\"Выберите товар\"></select></div><div style=\"width:15%;padding: 0 0 0 10px;\" class=\"artic_".$code."\"></div><div style=\"width:15%;padding: 0 0 0 10px;\" class=\"price_".$code."\"></div><div style=\"width:10%;padding: 0 0 0 10px;\"><input name=\"order_zakaz[".$code."][quantity]\" type=\"text\" value=\"\" class=\"form-control orderquantity_".$code."\" placeholder=\"введите количество\" /></div><div style=\"width:10%;padding: 0 0 0 10px;\" class=\"itogpriceproduct_".$code."\"><input name=\"order_zakaz[".$code."]itog\" type=\"text\" value=\"\" class=\"form-control itog_".$code."\" placeholder=\"0\" disabled /></div>
					</div>";
		   echo $html;
		   exit;

		}
    }
	
	public function productpriceAction(){
		$id = isset($_GET['id']) ? $_GET['id'] : '';
		$comp_id = isset($_GET['comp_id']) ? $_GET['comp_id'] : 'no';
		
		if($id) {
			$product = \R::findOne('product', 'id = ?', [$id]);
			$stock = \R::getRow('SELECT * FROM in_stock WHERE product_id = ? AND branch_id = ? GROUP BY product_id', [$id, 9]);
			if($comp_id !="no") {
				$sale = \R::getRow('SELECT * FROM company_typeprice WHERE company_id = ? AND category_id = ?', [$comp_id, $product->category_id]);
			}else{
				$sale->znachenie = "";
			}
		}else{
			$product->article = "";
		}
		echo json_encode(array('result1'=>''.$product->article.'', 'result2'=>''.$product->price.'', 'result3'=>''.$product->quantity.'', 'result4'=>''.$stock["quantity"].'', 'result5'=>''.$product->weight.'', 'result6'=>''.$product->volume.'', 'result7'=>''.$sale["znachenie"].''));		
		die;
    }
	
	public function compinfoAction(){
		$id = isset($_GET['id']) ? $_GET['id'] : '';
		if($id) {
			$company = \R::getRow("SELECT * FROM company, user WHERE company.user_id = user.id AND company.id = ?", [$id]);
			
		}else{
			$company->name = "";
			$company->telefon = "";
			$company->email = "";
			$company->nds = "";
			$company->tip = "";
			$company->url_address = "";
			$company->postal_address = "";
			$company->ogrn = "";
			$company->inn = "";
			$company->kpp = "";
			$company->bik = "";
			$company->raschet = "";
			$company->korschet = "";
			$company->bank = "";
			$company->dir_name = "";
			$company->dogovor = "";
			$company->hide = "";
		}
		echo json_encode(array('uname'=>''.$company["name"].'', 'uid'=>''.$company["user_id"].'', 'utelefon'=>''.$company["telefon"].'', 'uemail'=>''.$company["email"].'', 'cnds'=>''.$company["nds"].'', 'tip'=>''.$company["tip"].'', 'url_address'=>''.$company["url_address"].'', 'postal_address'=>''.$company["postal_address"].'', 'ogrn'=>''.$company["ogrn"].'', 'inn'=>''.$company["inn"].'', 'kpp'=>''.$company["kpp"].'', 'bik'=>''.$company["bik"].'', 'raschet'=>''.$company["raschet"].'', 'korschet'=>''.$company["korschet"].'', 'bank'=>''.$company["bank"].'', 'dir_name'=>''.$company["dir_name"].'', 'dogovor'=>''.$company["dogovor"].'', 'hide'=>''.$company["hide"].''));
		die;
    }
	
	public function usercontactAction(){
		$id = isset($_GET['id']) ? $_GET['id'] : '';
		if($id) {
			$user = \R::getRow("SELECT user.groups, user.telefon, user.email, user_groups.name as groups_name FROM user, user_groups WHERE user.groups = user_groups.id AND user.id = ?", [$id]);
			$company = \R::getRow("SELECT * FROM company WHERE user_id = ?", [$id]);
		}else{
			$user->telefon = "";
			$user->email = "";
			$user->groups = "";
			$user->groups_name = "";
			$company->nds = "";
			$company->tip = "";
			$company->url_address = "";
			$company->postal_address = "";
			$company->ogrn = "";
			$company->inn = "";
			$company->kpp = "";
			$company->bik = "";
			$company->raschet = "";
			$company->korschet = "";
			$company->bank = "";
			$company->dir_name = "";
			$company->dogovor = "";
			$company->hide = "";
			
		}
		echo json_encode(array('groups'=>''.$user["groups"].'', 'groups_name'=>''.$user["groups_name"].'', 'utelefon'=>''.$user["telefon"].'', 'uemail'=>''.$user["email"].'', 'tip'=>''.$company["tip"].'', 'comp_name'=>''.$company["comp_name"].'', 'comp_id'=>''.$company["id"].'', 'cnds'=>''.$company["nds"].'', 'tip'=>''.$company["tip"].'', 'url_address'=>''.$company["url_address"].'', 'postal_address'=>''.$company["postal_address"].'', 'ogrn'=>''.$company["ogrn"].'', 'inn'=>''.$company["inn"].'', 'kpp'=>''.$company["kpp"].'', 'bik'=>''.$company["bik"].'', 'raschet'=>''.$company["raschet"].'', 'korschet'=>''.$company["korschet"].'', 'bank'=>''.$company["bank"].'', 'dir_name'=>''.$company["dir_name"].'', 'dogovor'=>''.$company["dogovor"].'', 'hide'=>''.$company["hide"].''));
		die;
    }
	
	public function pdfscoreAction(){
		$order_id = $_GET["id"];		
		$order = \R::findOne("order", "id = ?", [$order_id]);
        $order_products = \R::findAll('order_product', "order_id = ?", [$order_id]);
		$seller = \R::findOne('company', 'id = ?', [$order['seller']]);
		$user = \R::findOne('user', 'id = ?', [$order['user_id']]);
		$comp = \R::findOne('company', 'id = ?', [$user['comp_id']]);
        $this->set(compact('order', 'order_products', 'seller', 'user', 'comp'));
	}
	
}