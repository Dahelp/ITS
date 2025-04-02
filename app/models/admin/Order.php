<?php

namespace app\models\admin;

use ishop\App;
use app\models\AppModel;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_Attachment;

class Order extends AppModel {
	
	public function addCompany($data){
		$date_create = date('Y-m-d H:i:s');
		\R::exec("INSERT INTO `company`(`comp_name`, `user_id`, `tip`, `url_address`, `postal_address`, `ogrn`, `inn`, `kpp`, `bik`, `raschet`, `korschet`, `bank`, `dir_name`, `nds`, `dogovor`, `data_create`, `hide`)
		VALUES ('".$data['comp_name']."', '".$data['user_id']."', '1', '', '', '', '', '', '', '', '', '', '', '".$data["nds"]."', '', '".$date_create."', 'show')");
	}
	
	public function addUser($data){
		$number = 8;
		$password = \ishop\App::generate_password($number);
		$password = password_hash($password, PASSWORD_DEFAULT);
		$date_create = date('Y-m-d H:i:s');
		\R::exec("INSERT INTO `user`(`password`, `email`, `name`, `telefon`, `role`, `groups`, `admin_id`, `comp_id`, `date_create`, `newsletter`, `uxeh`, `uid_ya`, `uid_gg`, `uid_vk`) VALUES ('".$password."', '".$data['email']."', '".$data['user_name']."', '".$data['telefon']."', 'user', '".$data['vid']."', '".$_SESSION['user']['id']."', '', '".$date_create."', '', '', '', '', '')");
	}
	
	public function addOrder($id, $data){
		$lastorder = $id + 1;
		$order_prefix = \ishop\App::options('order_prefix');
		$inv = "".$order_prefix."".$lastorder."";
		$dostavka_id = $data['dostavka_id'];
		$transport_id = $data['transport_id'];
		$branch_id = $data['branch_id'];
		$city_id = $data['city_id'];
		$address = $data['address'];
		$user_id = $data['user_id'];
		$admin_id = $data['admin_id'];
		$comp_id = $data['comp_id'];
		$seller = $data['seller'];
		$note = $data['note'];
		$data_order = date('Y-m-d H:i:s');
		$curr = \R::findOne('currency');
		\R::exec("INSERT INTO `order`(`inv`, `user_id`, `admin_id`, `comp_id`, `seller`, `status`, `date`, `update_at`, `dostavka_id`, `transport_id`, `branch_id`, `city_id`, `address`, `currency`, `note`) VALUES ('".$inv."', '".$user_id."', '".$admin_id."', '".$comp_id."', '".seller."', '1', '".$data_order."', '', '".$dostavka_id."', '".$transport_id."', '".$branch_id."', '".$city_id."', '".$address."', '".$curr['code']."', '".$note."')"); 
	}
	
	public function addOrderProduct($id, $data){
		$lastorder = $id + 1;
		//запишем новые
        if(!empty($data['order_zakaz'])){
                $sql_part = '';
                foreach($data['order_zakaz'] as $v){
					if($v["product_id"] !="") {
						$product_name = \R::getCell('SELECT `name` FROM product WHERE `id` = ? LIMIT 1', [$v["product_id"]]);
						$sql_part .= "($lastorder, '".$v["product_id"]."', '".$v["article"]."', '".$v["quantity"]."', '".$product_name."', '".$v["price"]."', '".$v["discount_value"]."', '".$v["discount_type"].", '".$v["discount"]."', '".$v["discount_amount"]."'),";
					}
                }
				if($sql_part == ""){ } else {
					$sql_part = rtrim($sql_part, ',');
					\R::exec("INSERT INTO order_product (order_id, product_id, article, qty, name, price, `discount_value`, `discount_type`, discount, discount_amount) VALUES $sql_part");
				}
        }
	}

	public function editOrder($id, $data){
		$dostavka_id = $data['dostavka_id'];
		$transport_id = $data['transport_id'];
		$branch_id = $data['branch_id'];
		$city_id = $data['city_id'];
		$address = $data['address'];
		$comp_id = $data['comp_id'];
		\R::exec("UPDATE `order` SET `comp_id` = '".$comp_id."', `dostavka_id` = '".$dostavka_id."', `transport_id` = '".$transport_id."', `branch_id` = '".$branch_id."', `city_id` = '".$city_id."', `address` = '".$address."' WHERE `id` = ?", [$id]);
	}
	
	public function editOrderProduct($id, $data){
		// удалим все и запишем новые
        if(!empty($data['order_zakaz'])){
            
                \R::exec("DELETE FROM order_product WHERE order_id = ?", [$id]);
                $sql_part = '';
                foreach($data['order_zakaz'] as $v){
					if($v["product_id"] !="") {
						$product_name = \R::getCell('SELECT `name` FROM product WHERE `id` = ? LIMIT 1', [$v["product_id"]]);
						$sql_part .= "($id, '".$v["product_id"]."', '".$v["article"]."', '".$v["quantity"]."', '".$product_name."', '".$v["price"]."', '".$v["discount_value"]."', '".$v["discount_type"]."', '".$v["discount"]."', '".$v["discount_amount"]."'),";
					}
                }
				if($sql_part == ""){ } else {
					$sql_part = rtrim($sql_part, ',');
					\R::exec("INSERT INTO order_product (order_id, product_id, article, qty, name, price, `discount_value`, `discount_type`, discount, discount_amount) VALUES $sql_part");
				}
        }
	}
	
	public function managerEmail($email, $order_id, $user){
		$order = \R::findOne('order', 'id = ?', [$order_id]);
		$order_product = \R::getAll('SELECT * FROM `order_product` WHERE `order_id` = ?', [$order_id]);
		$dost = \R::findOne('dostavka', 'id = ?', [$order["dostavka_id"]]);
		$bran = \R::findOne('branch_office', 'branch_id = ?', [$order["branch_id"]]);
		$trans = \R::findOne('transport_company', 'id = ?', [$order["transport_id"]]);
		$cit = \R::findOne('cities', 'city_id = ?', [$order["city_id"]]);
		if($trans["name"]) { $transport_company = "<b>Название ТК:</b> ".$trans["name"]."<br>"; }
		if($ord["address"] !="") { $address = "<br><b>Адрес:</b> ".$ord["address"]."<br>"; }		
		if($user["groups"] == 3) { $vid = "<b>Вид клиента:</b> Физическое лицо<br>"; }
		if($user["groups"] == 4) {
			$comp = \R::findOne('company', 'user_id = ?', [$user['id']]);
			$vid = "<b>Вид клиента:</b> Юридическое лицо<br>";
			if($comp["nds"] == "1") { $nds = "<b>Налогообложение:</b> c НДС<br>"; }
			if($comp["nds"] == "2") { $nds = "<b>Налогообложение:</b> без НДС<br>"; } 
			if($comp["dogovor"] == "1") { $dogovor = "<b>Условия поставки:</b> Договор<br>"; }
			if($comp["dogovor"] == "2") { $dogovor = "<b>Условия поставки:</b> Счёт-договор<br>"; }																		
		}

		// Create the Transport
        $transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
            ->setUsername(App::$app->getProperty('smtp_login'))
            ->setPassword(App::$app->getProperty('smtp_password'))
        ;
        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);
		$namecomp = App::$app->getProperty('shop_name');
		$tell_site = \ishop\App::options('option_telefon');

		// Create a message
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_manager.php';
        $body = ob_get_clean();

        $message_manager = (new Swift_Message("Сделан заказ №{$order["inv"]} на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo($email)
            ->setBody($body, 'text/html')
		;

        $result = $mailer->send($message_manager);
	}
	
	public function changeEmail($order_id, $user, $template){
		$order = \R::findOne('order', 'id = ?', [$order_id]);
		$order_product = \R::getAll('SELECT `product`.`name`, `product`.`alias` FROM `order_product`, `product` WHERE `order_product`.`product_id` = `product`.`id` AND `order_product`.`order_id` = ?', [$order_id]);
		// Create the Transport
        $transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
            ->setUsername(App::$app->getProperty('smtp_login'))
            ->setPassword(App::$app->getProperty('smtp_password'))
        ;
        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);
		$namecomp = App::$app->getProperty('shop_name');
		$tell_site = \ishop\App::options('option_telefon');

		// Create a message
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/'.$template.'.php';
        $body = ob_get_clean();

        $message_manager = (new Swift_Message("Изменён статус заказа №{$order["inv"]} на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo($user["email"])
            ->setBody($body, 'text/html')
		;

        $result = $mailer->send($message_manager);
	}
	
}