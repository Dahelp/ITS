<?php

namespace app\controllers;

use app\models\User;
use app\widgets\cabinet\Cabinet;
use ishop\App;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_Attachment;

class UserController extends AppController {
	
	public function newsletterAction(){
		if(!User::checkAuth()) redirect('');
		
		$newsletters = \R::getAll("SELECT * FROM newsletter");		
		$user = \R::findOne('user', 'id = ?', [$_SESSION['user']['id']]);
		
		$this->setMeta('Подписки на новости');
		$this->set(compact('newsletters', 'user'));
	}
	
	public function addnewsletterAction(){
		if($_GET) {
			$newsletter_id = $_GET["newsletter_id"];
			$checked = $_GET["checked"];
			
			if($checked==1){
				\R::exec("DELETE FROM `user_newsletter` WHERE `newsletter_id` = '".$newsletter_id."' AND `user_id` = '".$_SESSION['user']['id']."'");
				
			}
			if($checked==0){
				\R::exec("INSERT INTO `user_newsletter`(`user_id`, `newsletter_id`) VALUES ('".$_SESSION['user']['id']."', '".$newsletter_id."')");
				\R::exec("UPDATE `user` SET `newsletter` = '1' WHERE `id` = '".$_SESSION['user']['id']."'");
			}
			
			if($this->isAjax()){
				$this->loadView('newsletter_block');
			}
		}
	}
	
	public function deletenewsletterAction(){
		if($_GET) {
			$checked = $_GET["checked"];
			$id = $_SESSION['user']['id'];
			if($checked==1){
				\R::exec("DELETE FROM `user_newsletter` WHERE `user_id` = '".$_SESSION['user']['id']."'");
				\R::exec("UPDATE `user` SET `newsletter` = '0' WHERE `id` = '".$_SESSION['user']['id']."'");
			}
			if($checked==0){
				$newsletters = \R::getAssoc("SELECT id FROM newsletter");
				$sql_part = '';
				foreach($newsletters as $v) {
					$sql_part .= "($id , $v),";
				}
				$sql_part = rtrim($sql_part, ',');
				\R::exec("INSERT INTO user_newsletter (user_id, newsletter_id) VALUES $sql_part");
				\R::exec("UPDATE `user` SET `newsletter` = '1' WHERE `id` = '".$_SESSION['user']['id']."'");
			}
			
			if($this->isAjax()){
				$this->loadView('newsletter_block');
			}
		}
	}
	
	public function notificationsAction(){
		if(!User::checkAuth()) redirect();
        $this->setMeta('Сообщения');
	}
	
	public function exportAction(){
		if(!User::checkAuth()) redirect();
        $this->setMeta('Экспорт товаров');
	}

	public function wishlistAction(){
		if(!User::checkAuth()) redirect();
        $this->setMeta('Сравнение товаров');
	}
	
    public function signupAction(){
        if(!empty($_POST)){
            $user = new User();
            $data = $_POST;			
            $user->load($data);
            if(!$user->validate($data) || !$user->checkUnique()){
                $user->getErrors();
                $_SESSION['form_data'] = $data;
            }else{				
				$user->attributes['newsletter'] = $user->attributes['newsletter'] ? '1' : '0';
                $user->attributes['password'] = password_hash($user->attributes['password'], PASSWORD_DEFAULT);
                if($id = $user->save('user')){
					\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','42','user','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                    $_SESSION['success'] = 'Пользователь зарегистрирован';
                }else{
                    $_SESSION['error'] = 'Ошибка!';
                }
            }
            redirect();
        }
		
		
		if($_GET['state']) { 
			$state = $_GET['state']; // 123
			if($state == "ya_reg") {
				if (!empty($_GET['code'])) {
					// Отправляем код для получения токена (POST-запрос).
					$params = array(
						'grant_type'    => 'authorization_code',
						'code'          => $_GET['code'],
						'client_id'     => 'ab48bcc31dcd4be3aac787f9f945c7c6',
						'client_secret' => 'cd132d0cf6eb4a04ad7b3c76f13baac4',
					);
				
					$ch = curl_init('https://oauth.yandex.ru/token');
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $params); 
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_HEADER, false);
					$data = curl_exec($ch);
					curl_close($ch);	
						 
					$data = json_decode($data, true);
					if (!empty($data['access_token'])) {
						// Токен получили, получаем данные пользователя.
						$ch = curl_init('https://login.yandex.ru/info');
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, array('format' => 'json')); 
						curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $data['access_token']));
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_HEADER, false);
						$info = curl_exec($ch);
						curl_close($ch);
				 
						$info = json_decode($info, true);
						
					}
					$user = new User();
					
					$pass_random = $user->generate_code();
					$pass = md5($pass_random);
					$name = $info['first_name'];
					$familiya = $info['last_name']."";
					$email = $info['default_email'];					
					$uid_ya = $info["id"];	
				
					// проверяем нет ли такого юзера в БД
					$row = \R::count('user', 'email = ?', [$email]);
					// 1 - такой юзер есть, 0 - нет
					
					if($row==1){
						$res = \R::findOne('user', 'email = ?', [$email]);
						$_SESSION['user']['id'] = $res["id"];
						$_SESSION['user']['name'] = $res["name"];
						$_SESSION['user']['email'] = $res["email"];
						$_SESSION['user']['groups'] = $res["groups"];
						if($res["uid_ya"] == "") {
							$upd = \R::exec("UPDATE user SET uid_ya = '$uid_ya' WHERE id= '".$res["id"]."'");
						}
						
						
					}else{
						$reg = \R::exec("INSERT INTO `user`(`password`, `email`, `name`, `telefon`, `role`, `groups`, `admin_id`, `comp_id`, `date_create`, `newsletter`, `uxeh`, `uid_ya`, `uid_gg`, `uid_vk`)
						VALUES ('".$pass."', '".$email."', '".$familiya." ".$name."', '', 'user', '3', '', '', '".date("Y-m-d H:i:s")."', '0', '', '".$uid_ya."', '', '')");
						$res = \R::findOne('user', 'email = ?', [$email]);
						$_SESSION['user']['id'] = $res["id"];
						$_SESSION['user']['name'] = $res["name"];
						$_SESSION['user']['email'] = $res["email"];
					}
				}
			}
			if($state == "gg_reg"){

				// Отправляем код для получения токена (POST-запрос).
				$client_id = '952884474441-pjrml49bqmfos5g055qmkhdbrrlsp8sh.apps.googleusercontent.com'; // Client ID
				$client_secret = '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'; // Client secret
				$redirect_uri = ''.PATH.'/user/signup'; // Redirect URI


				if (isset($_GET['code'])) {
					$result = false;

					$params = array(
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
						'redirect_uri'  => $redirect_uri,
						'grant_type'    => 'authorization_code',
						'code'          => $_GET['code']
					);

					$url_gg = 'https://accounts.google.com/o/oauth2/token';

					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, $url_gg);
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					$result = curl_exec($curl);
					curl_close($curl);
					$tokenInfo = json_decode($result, true);

					if (isset($tokenInfo['access_token'])) {
						$params['access_token'] = $tokenInfo['access_token'];

						$userInfo = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo' . '?' . urldecode(http_build_query($params))), true);
						if (isset($userInfo['id'])) {
							$userInfo = $userInfo;
							$result = true;
						}
					}				
									
					$user = new User();
						
					$pass_random = $user->generate_code();
					$pass = md5($pass_random);
					$name = $userInfo['given_name'];
					$familiya = $userInfo['family_name']."";
					$email = $userInfo['email'];				
					$uid_gg = $userInfo["id"];	

					// проверяем нет ли такого юзера в БД
					$row = \R::count('user', 'email = ?', [$email]);
					// 1 - такой юзер есть, 0 - нет
					
					if($row==1){
						$res = \R::findOne('user', 'email = ?', [$email]);
						$_SESSION['user']['id'] = $res["id"];
						$_SESSION['user']['name'] = $res["name"];
						$_SESSION['user']['email'] = $res["email"];
						$_SESSION['user']['groups'] = $res["groups"];
						if($res["uid_gg"] == "") {
							$upd = \R::exec("UPDATE user SET uid_gg = '$uid_gg' WHERE id= '".$res["id"]."'");
						}
						
						
					}else{
						$reg = \R::exec("INSERT INTO `user`(`password`, `email`, `name`, `telefon`, `role`, `groups`, `admin_id`, `comp_id`, `date_create`, `date_last_visit`, `newsletter`, `uxeh`, `uid_ya`, `uid_gg`, `uid_vk`)
						VALUES ('".$pass."', '".$email."', '".$familiya." ".$name."', '', 'user', '3', '', '', '".date("Y-m-d H:i:s")."', '0', '', '', '".$uid_gg."', '')");
						$res = \R::findOne('user', 'email = ?', [$email]);
						$_SESSION['user']['id'] = $res["id"];
						$_SESSION['user']['name'] = $res["name"];
						$_SESSION['user']['email'] = $res["email"];
					}				
				}				
			}
			if($state == "vk_reg"){
				
				$client_id = '8097823'; // ID приложения
				$client_secret = 'QUUb6UEcTpHCbEaeC31Y'; // Защищённый ключ
				$redirect_uri = 'https://its-center.ru/user/signup'; // Адрес сайта
				
				if (isset($_GET['code'])) {
					$params = array(
						'client_id' => $client_id,
						'client_secret' => $client_secret,
						'code' => $_GET['code'],
						'redirect_uri' => $redirect_uri
					);

					$token = json_decode(file_get_contents('https://oauth.vk.com/access_token' . '?' . urldecode(http_build_query($params))), true);

					if (isset($token['access_token'])) {
						$params = array(
							'uids'         => $token['user_id'],
							'fields'       => 'notify,email',
							'access_token' => $token['access_token'],
							'v' => '5.81'
						);
						$userInfo = json_decode(file_get_contents('https://api.vk.com/method/users.get' . '?' . urldecode(http_build_query($params))), true);
						if (isset($userInfo['response'][0]['id'])) {
							$userInfo = $userInfo['response'][0];
							$result = true;
						}
					}
						
						$user = new User();
						
						$pass_random = $user->generate_code();
						$pass = md5($pass_random);
						$name = $userInfo['first_name'];
						$familiya = $userInfo['last_name']."";
						$email = $token['email'];
						$uid_vk = $userInfo["id"];
						if($email == "") {							
							$email = "".$uid_vk."@vk.com";
						}
						// проверяем нет ли такого юзера в БД
						$row = \R::count('user', 'email = ?', [$email]);
						// 1 - такой юзер есть, 0 - нет
					
						if($row==1){
							$res = \R::findOne('user', 'email = ?', [$email]);
							$_SESSION['user']['id'] = $res["id"];
							$_SESSION['user']['name'] = $res["name"];
							$_SESSION['user']['email'] = $email;
							$_SESSION['user']['groups'] = $res["groups"];
							
							if($res["uid_vk"] == "") {
								$upd = \R::exec("UPDATE user SET uid_vk = '".$uid_vk."' WHERE id= '".$res["id"]."'");
							}							
						}else{
							$reg = \R::exec("INSERT INTO `user`(`password`, `email`, `name`, `telefon`, `role`, `groups`, `admin_id`, `comp_id`, `date_create`, `date_last_visit`, `newsletter`, `uxeh`, `uid_ya`, `uid_gg`, `uid_vk`)
							VALUES ('".$pass."', '".$email."', '".$familiya." ".$name."', '', 'user', '3', '', '', '".date("Y-m-d H:i:s")."', '0', '', '', '', '".$uid_vk."')");
							$res = \R::findOne('user', 'email = ?', [$email]);
							$_SESSION['user']['id'] = $res["id"];
							$_SESSION['user']['name'] = $res["name"];
							$_SESSION['user']['email'] = $res["email"];
						}						
					
				}
			}
			redirect('/user/cabinet');
		}
        $this->setMeta('Регистрация');
    }

    public function loginAction(){
        if(!empty($_POST)){
            $user = new User();
            if($user->login()){
                $_SESSION['success'] = 'Вы успешно авторизованы';
            }else{
                $_SESSION['error'] = 'Логин/пароль введены неверно';
            }			
            redirect('cabinet');
        }
        $this->setMeta('Вход');
    }

    public function logoutAction(){
        if(isset($_SESSION['user'])) unset($_SESSION['user']);
        redirect('/');
    }
	
	public function cabinetAction(){
        if(!User::checkAuth()) redirect();
        $this->setMeta('Личный кабинет');
    }

    public function editAction(){
        if(!User::checkAuth()) redirect('');
        if(!empty($_POST)){
            $user = new \app\models\admin\User();
            $data = $_POST;
            $data['id'] = $_SESSION['user']['id'];
            $data['role'] = $_SESSION['user']['role'];
            $user->load($data);
            if(!$user->attributes['password']){
                unset($user->attributes['password']);
            }else{
                $user->attributes['password'] = password_hash($user->attributes['password'], PASSWORD_DEFAULT);
            }
            if(!$user->validate($data) || !$user->checkUnique()){
                $user->getErrors();
                redirect();
            }
            if($user->update('user', $_SESSION['user']['id'])){
                foreach($user->attributes as $k => $v){
                    if($k != 'password') $_SESSION['user'][$k] = $v;
                }
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
        $this->setMeta('Изменение личных данных');
    }
	
	public function companyAction(){
        if(!User::checkAuth()) redirect('');
        if(!empty($_POST)){
            $company = new \app\models\admin\Company();
            $data = $_POST;
            $data['user_id'] = $_SESSION['user']['id'];
			$data['tip'] = 1;
            $company->load($data);

            if(!$company->validate($data) || !$company->checkUnique()){
                $company->getErrors();
                redirect();
            }
            if($company->update('company', $data['comp_id'])){
                
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
		$category = \R::getAll("SELECT * FROM category");
		$company = \R::findOne('company', 'user_id = ?', [$_SESSION['user']['id']]);
        $this->setMeta('Компания');
        $this->set(compact('company', 'category'));
        
    }

    public function ordersAction(){
        if(!User::checkAuth()) redirect('');
        $orders = \R::getAll("SELECT order.id, order.inv, order.currency, order.date, order.update_at, order.status, SUM(order_product.price*order_product.qty) as sum FROM `order`, `order_product` WHERE order.id = order_product.order_id AND order.user_id = ? GROUP BY order.id DESC", [$_SESSION['user']['id']]);
        $this->setMeta('История заказов');
        $this->set(compact('orders'));
    }
	
	public function orderAction(){
		$id = $_GET["id"];
        if(!User::checkAuth()) redirect('');
        $order = \R::getAll("SELECT order.id, product.img, product.alias, order_product.name, order_product.qty, order_product.price, order_product.article FROM `order`, `order_product`, `product` WHERE product.id = order_product.product_id AND order.id = order_product.order_id AND order.user_id = ? AND order_product.order_id = ?", [$_SESSION['user']['id'], $id]);
		$order_info = \R::findOne('order', 'order.user_id = ? AND order.id = ?', [$_SESSION['user']['id'], $id]);
        $status = \R::findOne('order_status', 'id = ?', [$order_info->status]);
        $this->setMeta('Просмотр заказа');
        $this->set(compact('order', 'order_info', 'status'));
    }
	
	public function bookmarksAction(){
		if(!User::checkAuth()) redirect('');
		if($_GET) {
			$user_id = $_GET["user_id"];
			$product_id = $_GET["product_id"];
			
			$bookmarks = \R::count('product_bookmarks', 'product_id = ? AND user_id = ?', [$product_id, $user_id]);		
			if($bookmarks==0){
				$reg = \R::exec("INSERT INTO `product_bookmarks`(`product_id`, `user_id`) VALUES ('".$product_id."', '".$user_id."')");
			}			
		}
		$bookmarks = \R::getAll("SELECT product_bookmarks.product_id, product.img, product.article, product.name, product.alias, product.price, product.opt_price, product.category_id, product.quantity, product.stock_status_id, product_bookmarks.id FROM `product`, `product_bookmarks` WHERE product.id = product_bookmarks.product_id AND product_bookmarks.user_id = ?", [$_SESSION['user']['id']]);
        
		$this->setMeta('Закладки');
		$this->set(compact('bookmarks'));
	}
	
	public function pricelistAction(){
		if(!User::checkAuth()) redirect('');
		if(!empty($_POST)){
			$format = $_POST["format"];
			$category_id = $_POST["category_id"];
			$brand_id = $_POST["brand_id"];
			$article = $_POST["article"];
			$actSelect = $_POST["actSelect"];
			if($format == "1") { //PDF
				if($actSelect == "1" OR $actSelect == "4") { //Определённую категорию
					if($brand_id){ $sql_where_brand = " AND product.brand_id = '".$brand_id."'"; } //если есть производитель
					if($category_id == 1) {						
						$product = \R::getAll("SELECT `product`.`category_id`, `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.category_id IN ('9', '18', '19', '20', '21', '22', '23', '24') AND product.hide = ?".$sql_where_brand."", ['show']);
					}
					if($category_id == 2) {
						$product = \R::getAll("SELECT `product`.`category_id`, `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.category_id = ? AND product.hide = ?".$sql_where_brand."", [2, 'show']);
					}
					if($category_id == 25) {
						$product = \R::getAll("SELECT `product`.`category_id`, `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.category_id IN ('31', '32', '33') AND product.hide = ?".$sql_where_brand."", ['show']);
					}
					if($category_id == 4) {
						$product = \R::getAll("SELECT `product`.`category_id`, `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.category_id IN ('26', '27', '28', '29', '30') AND product.hide = ?".$sql_where_brand."", ['show']);
					}
					if($category_id == 3) {
						$product = \R::getAll("SELECT `product`.`category_id`, `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.category_id IN ('10', '11', '12', '13', '14', '15', '16', '17') AND product.hide = ?".$sql_where_brand."", ['show']);
					}
				}
				if($actSelect == "5") { //Все товары					
						$product = \R::getAll("SELECT `product`.`category_id`, `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.hide = ?", ['show']);
				}
				if($actSelect == "2") {
					$product = \R::getAll("SELECT `product`.`category_id`, `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.brand_id = ? AND product.hide = ?", [$brand_id, 'show']);
				}
				if($actSelect == "3") {
					$product = \R::getAll("SELECT `product`.`category_id`, `product`.`article`, `product`.`model`, `product`.`name`, `product`.`quantity`, `product`.`alias`, `product`.`opt_price`, `product`.`price`, `brand`.`name` as vendor FROM `product` JOIN `brand` ON `product`.`brand_id` = `brand`.`id` WHERE product.article = ? AND product.hide = ?", [$article, 'show']);
				}
			}			
		}
		$company = \R::findOne('company', 'user_id = ?', [$_SESSION['user']['id']]);
		$this->setMeta('Прайс-лист');
		$this->set(compact('product', 'company'));
	}
	
	public function pdfcatalogAction(){
		if(!User::checkAuth()) redirect('');
		
		$this->setMeta('Каталог');
		
	}
	
	public function dogovorAction(){
		if(!User::checkAuth()) redirect('');
		$this->setMeta('Договор');
	}
	
	public function bookmarksDeleteAction(){
        $id = $_GET["id"];        
        $bookmarks = \R::load('product_bookmarks', $id);
        \R::trash($bookmarks);
        $_SESSION['success'] = 'Закладка удалена';
        redirect('bookmarks');
    }
	
	public function recoverAction(){
	if($_POST){
        $email = !empty($_POST["email"]) ? trim($_POST["email"]) : ''; 
		$user = \R::findOne('user', 'email = ?', [$email]);
		
		if(!$user) {
			$_SESSION['error'] = 'Email не найден';
			return false;
		}else{
		
		$expire = time() + App::$app->getProperty('time_active_link');
		$hash = md5($expire.$email);
		
		$res = \R::exec("INSERT INTO `recover`(`hash`, `expire`, `email`) VALUES ('".$hash."', '".$expire."', '".$email."')");		
		if($res){
			
			// Create the Transport
			$transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
				->setUsername(App::$app->getProperty('smtp_login'))
				->setPassword(App::$app->getProperty('smtp_password'))
			;
			// Create the Mailer using your created Transport
			$mailer = new Swift_Mailer($transport);
			$namecomp = App::$app->getProperty('shop_name');
			$tell_site = \ishop\App::options('option_telefon');
			$uname = $user["name"];
			// Create a message
			ob_start();
			require APP . '/views/'.TEMPLATE.'/mail/mail_recover.php';
			$body = ob_get_clean();

			$message_client = (new Swift_Message("Восстановление пароля на сайте " . App::$app->getProperty('shop_name')))
				->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
				->setTo($email)
				->setBody($body, 'text/html')
			;

			$message_admin = (new Swift_Message("Восстановление пароля на сайте " . App::$app->getProperty('shop_name')))
				->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
				->setTo(App::$app->getProperty('admin_email'))
				->setBody($body, 'text/html')
			;
			
			// Send the message
			$result = $mailer->send($message_client);
			$result = $mailer->send($message_admin);
			
			$_SESSION['success'] = 'Ссылка на восстановление отправлена на email';
			return true;
		}else{
			return false;
		}
        
        redirect('');
		}
	}
	$this->setMeta('Восстановление пароля');
    }
	
	public function recoverPassAction(){
		if($_POST){
		$hash = $_POST["hash"];
		$user = new User();
		$uhash = $user->get_user_hash($hash);
		
		if($uhash){
			$password = !empty($_POST["password"]) ? trim($_POST["password"]) : '';
			$hash = !empty($_POST["hash"]) ? trim($_POST["hash"]) : '';
			
			if(empty($password)) {
				$_SESSION['error'] = 'Не введён новый пароль';
				return false;
			}else{
				$pass = password_hash($password, PASSWORD_DEFAULT);
				\R::exec("UPDATE user SET password = '".$pass."' WHERE email = '".$uhash["email"]."'");
				\R::exec("DELETE FROM recover WHERE email = '".$uhash["email"]."'");
				$user = \R::findOne('user', 'email = ?', [$uhash["email"]]);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','53','user','".$user["id"]."','".date('Y-m-d H:i:s')."','NULL')");
				$_SESSION['success'] = 'Пароль обновлён';
				return true;
			}
		}else{
			return false;
		}
		}
		$this->setMeta('Создание нового пароля');
	}
	
	public function zvonokAction(){
		if($_POST){
			$phone = $_POST["phone"];
			$title = $_POST["title"];
			$first = substr($phone, "0",5);		
			if($first != "+7 (9") { $this->errors['unique'][] = "Запрос не обработан! Вы робот? Если нет, попробуйте заполнить форму обратной связи еще раз!"; } else {	
				
				$res = \R::exec("INSERT INTO `callback` (`user_id`, `topic`, `phone`, `date_create`, `date_modified`, `user_modified`, `hide`) VALUES ('".$user_id."', '', '".$phone."', '".date('Y-m-d H:i:s')."', '', '', '')");		
				if($res){
					
					$last = \R::findLast('callback');				
					\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('1','2','callback','".$last->id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
					setcookie("request-mig", "1house", time()+3600);
					
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
					require APP . '/views/'.TEMPLATE.'/mail/mail_callback.php';
					$body = ob_get_clean();


					$message_admin = (new Swift_Message("Заказ обратного звонка на сайте " . App::$app->getProperty('shop_name')))
						->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
						->setTo(App::$app->getProperty('admin_email'))
						->setBody($body, 'text/html')
					;
					
					
					$result = $mailer->send($message_admin);
					
					$_SESSION['success'] = 'Спасибо за заказ обратного звонка. Наш менеджер обязательно Вам позвонит по указаному номеру который вы указали. Ожидайте звонка в рабочее время с ПН-ПТ 9:00 до 17:00 по МСК.';
					return true;
				}else{
					return false;
				}							
				             
			}
		}
	}
	
}