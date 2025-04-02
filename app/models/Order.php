<?php

namespace app\models;

use ishop\App;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_Attachment;

class Order extends AppModel {

    public static function saveOrder($data){
        $order = \R::dispense('order');
        $order->user_id = $data['user_id'];
        $order->note = $data['note'];
		$order->dostavka_id = $data['dostavka_id'];
		$order->status = 1;
		$last_order = \R::findLast('order');
		$lastorder = $last_order->id + 1;
		if($data['groups'] == 3) { // Физлицо
			if($data['dostavka_id'] == 2) { // если выбрана транспортная компания
				if($data['city_id'] !=""){ // если выбран город
					$city = \R::findOne('cities', 'city_id = ?', [$data['city_id']]); // находим его регион
					if($city['region_id'] == 50 OR $city['region_id'] == 77) { $order_prefix = "SH"; $order->seller = "1"; } // если выбран Московский регион
					else{ $order_prefix = "RO"; $order->seller = "2"; } // если выбран регион Россия без Московского региона
				}else{
					$order_prefix = "SH"; $order->seller = "1"; // если не выбран город, выбераем по умолчанию
				}
			}else{
				$order_prefix = "SH"; $order->seller = "1"; // если выбран курьер или самовывоз, выбераем по умолчанию
			}			
		}
		if($data['groups'] == 4) { // Юрлицо
			if($data['nds'] == 1) { $order_prefix = "IT"; $order->seller = "3"; } // если с НДС
			if($data['nds'] == 2) { // если без НДС
				if($data['dostavka_id'] == 2) { // если выбрана транспортная компания
					if($data['city_id'] !=""){ // если выбран город
						$city = \R::findOne('cities', 'city_id = ?', [$data['city_id']]); // находим его регион
						if($city['region_id'] == 50 OR $city['region_id'] == 77) { $order_prefix = "SH"; $order->seller = "1"; } // если выбран Московский регион
						else{ $order_prefix = "RO"; $order->seller = "2"; } // если выбран регион Россия без Московского региона
					}else{
						$order_prefix = "SH"; $order->seller = "1"; // если не выбран город, выбераем по умолчанию
					}
				}else{
					$order_prefix = "SH"; $order->seller = "1"; // если выбран курьер или самовывоз, выбераем по умолчанию
				}
			}
		}
		$order->inv = \ishop\App::invoice_num($lastorder, 9, $order_prefix);
		if($data['address'] !="") { $order->address = $data['address']; }
		if($data['transport_id'] !="") { $order->transport_id = $data['transport_id']; }
		if($data['city_id'] !="") { $order->city_id = $data['city_id']; }
		if($data['branch_id'] !="") { $order->branch_id = $data['branch_id']; }
		if($data['comp_id'] !="") { $order->comp_id = $data['comp_id']; }
		$order->admin_id = $data['admin_id'];
		$order->user_id = isset($data['user_id']) ? $data['user_id'] : $_SESSION['user']['id'];
        $order->currency = $_SESSION['cart.currency']['code'];
        $order_id = \R::store($order);
        self::saveOrderProduct($order_id);
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','32','order','".$order_id."','".date('Y-m-d H:i:s')."','".$order->user_id."')");
        return $order_id;
    }

    public static function saveOrderProduct($order_id){
        $sql_part = '';
        foreach($_SESSION['cart'] as $product_id => $product){
            $product_id = (int)$product_id;
			if($_SESSION['promocart']){
				$prods = \R::getRow('SELECT * FROM product WHERE id = ?', [$product_id]);
				$promo = \R::getRow('SELECT * FROM plagins_promocode WHERE promocode = ?', [$_SESSION['promocart']]);
				$discount = ($prods['price']/100)*$promo["value"];
				$discount_amount = $discount * $product['qty'];
				$sql_part .= "('".$order_id."', '".$product_id."', '".$product['article']."', '".$product['qty']."', '".$product['unit']."', '".$product['name']."', '".$prods['price']."', '".$promo["value"]."', '3', '".$discount."', '".$product['price']."', '".$discount."'),";
			}else{
				$sql_part .= "('".$order_id."', '".$product_id."', '".$product['article']."', '".$product['qty']."', '".$product['unit']."', '".$product['name']."', '".$product['price']."', '', '', '', '', ''),";
			}
        }
        $sql_part = rtrim($sql_part, ',');
        \R::exec("INSERT INTO order_product (`order_id`, `product_id`, `article`, `qty`, `unit`, `name`, `price`, `discount_value`, `discount_type`, `discount`, `price_discount`, `discount_amount`) VALUES $sql_part");
    }

    public static function mailOrder($order_id, $user_email, $uname, $telefon, $admin_id, $note, $date, $dostavka_name, $branch_name, $address, $transport_company, $city_name, $vid, $compname, $nds, $dogovor){
        // Create the Transport
        $transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
            ->setUsername(App::$app->getProperty('smtp_login'))
            ->setPassword(App::$app->getProperty('smtp_password'))
        ;
        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);
		$namecomp = App::$app->getProperty('shop_name');
		$tell_site = \ishop\App::options('option_telefon');
		$order_prefix = \ishop\App::options('order_prefix');
		// Create a message
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_order.php';
        $body = ob_get_clean();
		$ord = \R::findOne('order', 'id = ?', [$order_id]);
        $message_client = (new Swift_Message("Вы совершили заказ №{$ord["inv"]} на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo($user_email)
            ->setBody($body, 'text/html')
        ;

        $message_admin = (new Swift_Message("Сделан заказ №{$ord["inv"]} на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo(App::$app->getProperty('admin_email'))
            ->setBody($body, 'text/html')
		;
		
		
		if($_FILES['rekvizity']['tmp_name']){
			$attachment = Swift_Attachment::fromPath($_FILES['rekvizity']['tmp_name']);
			$attachment->setFilename($_FILES['rekvizity']['name']);
			$message_admin->attach($attachment);
		}
		
        if($admin_id !="0"){
			$adm = \R::findOne('user', 'id = ?', [$admin_id]);
			$message_manager = (new Swift_Message("Сделан заказ №{$ord["inv"]} на сайте " . App::$app->getProperty('shop_name')))
				->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
				->setTo($adm["email"])
				->setBody($body, 'text/html')
			;
			$result = $mailer->send($message_manager);
		}

        // Send the message
        $result = $mailer->send($message_client);
        $result = $mailer->send($message_admin);
		
		
        unset($_SESSION['cart']);
        unset($_SESSION['cart.qty']);
        unset($_SESSION['cart.sum']);
		unset($_SESSION['cart.weight']);
		unset($_SESSION['cart.volume']);
        unset($_SESSION['cart.currency']);
		unset($_SESSION['promocart']);
		
		$dayofweek = date('w', strtotime($date));
		if($dayofweek > 0 && $dayofweek < 6){		
			$_SESSION['success'] = 'Спасибо за Ваш заказ. Для согласования заказа с Вами свяжется менеджер в рабочее время ПН-ПТ с 09:00 до 17:00';		
		}
		if($dayofweek > 5 && $dayofweek < 8){		
			$_SESSION['success'] = 'Спасибо за Ваш заказ. Для согласования заказа с Вами свяжется менеджер в понедельник в рабочее время с 09:00 до 17:00';		
		}
    }

}