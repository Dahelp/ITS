<?php 

use ishop\App;
use Guzzlehttp\Guzzle;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

$date_price = date("Y-m-d");
$date_update = date("Y-m-d H:i:s");
$viewcrons = \R::findOne('cron', 'id = ?', [$_GET["id"]]);
$client = new \GuzzleHttp\Client();

//проверка ответа сервера
$testserver = $client->request('GET', 'http://84.47.156.246:8082/trade/hs/goods/tovars',

	[
		'auth' => [
			'GetCompanyByINN',
			'GetCompanyByINN'
		]
	], 
	[
		'allow_redirects' => false
	]

);

if($testserver->getStatusCode() == 200){	
	
	$cron_id = $viewcrons["id"];
	
	$products = \R::getAll("SELECT product.id, product.article, product.stock_status_id FROM product, category WHERE product.category_id = category.id AND category.id IN ('26', '27', '28', '29', '30', '35')");
	
	$cnt = count($products);
	foreach($products as $pssql){	
		
		$response = $client->request('GET', 'http://84.47.156.246:8082/trade/hs/goods/tovars?code='.$pssql["article"].'',

		[
			'auth' => [
				'GetCompanyByINN',
				'GetCompanyByINN'
			]
		]

		);
		$str = iconv("CP1252", "UTF-8", $response->getBody());

		$response = json_decode($response->getBody(), true);
		
		foreach($response as $res){
			
			if($res["rest"] !="0") { $stock_status_id = "1"; }
			else {$stock_status_id = "0";}
			
			$action = \R::findOne('actions', 'product_id = ? AND date_end > ?', [$pssql["id"], $date_update]);
			if($action){
				
				if($pssql["stock_status_id"]==2){
					if($stock_status_id == 1){
						$updt[] = \R::exec("UPDATE product SET opt_price = '".$res["price_opt"]."', stock_status_id = '".$stock_status_id."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");					
					}
					if($stock_status_id == 0){
						$updt[] = \R::exec("UPDATE product SET opt_price = '".$res["price_opt"]."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
				}
				if($pssql["stock_status_id"]==3){
					if($stock_status_id == 1){
						$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', stock_status_id = '".$stock_status_id."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
					if($stock_status_id == 0){
						$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
				}
				if($pssql["stock_status_id"]==0){
					if($t == "") {
						$updt[] = \R::exec("UPDATE product SET opt_price = '".$res["price_opt"]."', stock_status_id = '".$stock_status_id."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}else{
						$updt[] = \R::exec("UPDATE product SET opt_price = '".$res["price_opt"]."', stock_status_id = '3', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
				}
				if($pssql["stock_status_id"]==1){						
						$updt[] = \R::exec("UPDATE product SET opt_price = '".$res["price_opt"]."', stock_status_id = '".$stock_status_id."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");						
				}
			}else{
				
				if($pssql["stock_status_id"]==2){
					if($stock_status_id == 1){
						$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', stock_status_id = '".$stock_status_id."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
					if($stock_status_id == 0){
						$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
				}
				if($pssql["stock_status_id"]==3){
					if($stock_status_id == 1){
						$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', stock_status_id = '".$stock_status_id."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
					if($stock_status_id == 0){
						$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
				}
				if($pssql["stock_status_id"]==0){
					if($t == "") {
						$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', stock_status_id = '".$stock_status_id."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}else{
						$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', stock_status_id = '3', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");
					}
				}
				if($pssql["stock_status_id"]==1){						
					$updt[] = \R::exec("UPDATE product SET price = '".$res["price_rozn"]."', data_edit_price = '".$date_price."', opt_price = '".$res["price_opt"]."', stock_status_id = '".$stock_status_id."', quantity = '".$res["rest"]."' WHERE id = '".$pssql["id"]."'");						
				}
				
			}
			if($stock_status_id == 1){
			//Заявки о поступлении товара на склад
				$availability = \R::getAll("SELECT id FROM mail_availability WHERE status_nalichiya = '0' AND product_id = '".$pssql["id"]."'");
				if($availability){
					foreach($availability as $item){
						$updtp = \R::exec("UPDATE mail_availability SET status_nalichiya = '1', data_postupleniya = '".$date_price."' WHERE id = '".$item["id"]."'");					
					}					
				}
			}
		}
		
	}

}else{ //если testserver нет ответа, подключаем файл и отправляем письмо на почту admin об отсутствии ответа от сервера

	// Create the Transport
	$transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
		->setUsername(App::$app->getProperty('smtp_login'))
		->setPassword(App::$app->getProperty('smtp_password'))
	;
	// Create the Mailer using your created Transport
	$mailer = new Swift_Mailer($transport);
	$namecomp = App::$app->getProperty('shop_name');
	$tell_site = \ishop\App::options('option_telefon');
	$name_file = "Обновление товаров (категория Шины) через сервер 1С";
	// Create a message admin
	ob_start();
	require APP . '/views/'.TEMPLATE.'/mail/mail_server_otvet.php';
	$body = ob_get_clean();

	$message_admin = (new Swift_Message("Оповещение об отсутствии ответа сервера на сайте " . App::$app->getProperty('shop_name')))
		->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
		->setTo(App::$app->getProperty('admin_email'))
		->setBody($body, 'text/html')
	;

	// Send the message
	$result = $mailer->send($message_admin);

	if($viewcrons["alias"]==""){ $fileprod = "".$crons["alias"].""; $cron_id = $crons["id"]; }
	else { $fileprod = "".$viewcrons["alias"].""; $cron_id = $viewcrons["id"]; }

	$exp = explode("/", $fileprod);
	$file_name = end($exp); //myimage.jpg
	$url_download = $viewcrons["url_download"];
	$path = "cron/$url_download";

	$ch = curl_init($fileprod);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$html = curl_exec($ch);
	curl_close($ch); 

	file_put_contents($path, $html);

	$data = File("cron/$url_download");
	$cnt = count($data);
	for ($i=1;$i<count($data);$i++) {
	 
		list($c, $e, $o, $d, $r, $f, $rkl, $g, $rkr, $k, $rv, $l, $rspb, $ek, $ekr, $t, $p) = explode(";", $data[$i]);  
	  
		$c = ltrim("$c", '0');
		$c = trim($c); //article
		$e = trim($e); //tcena  
		$o = trim($o); //tcena opt
		$d = trim($d); //svobodnoe_kolichestvo
		$r = trim($r); //obshiy_rezerv
		$f = trim($f); //klimovsk
		$rkl = trim($rkl); //rezerv_klimovsk
		$g = trim($g); //krasnodar
		$rkr = trim($rkr); //rezerv_krasnodar
		$k = trim($k); //voronezh
		$rv = trim($rv); //rezerv_voronezh
		$l = trim($l); //sankt-peterburg
		$rspb = trim($rspb); //rezerv_sankt-peterburg
		$ek = trim($ek); //ekaterinburg
		$ekr = trim($ekr); //rezerv ekaterinburg
		$t = trim($t); //kol_postupleniya
		$p = trim($p); //data_postupleniya
		if($p == ""){ $p = "0000-00-00"; }
		if($c !="") {		  
			  
			$article = $c;
			$quantity = $d+$r;
			$price = $e;
			$opt_price =$o;
			if($quantity !="0") { $stock_status_id = "1"; }
			else {$stock_status_id = "0";}

			$pssql = \R::findOne('product', 'article = ?', [$article]);				
			
			if($pssql["id"]) {
				
				$action = \R::findOne('actions', 'product_id = ? AND date_end > ?', [$pssql["id"], $date_update]);
				if($action){
					if($pssql["stock_status_id"]==2){
						if($stock_status_id == 1){
							$updt[] = \R::exec("UPDATE product SET opt_price = '".$opt_price."', stock_status_id = '".$stock_status_id."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
						if($stock_status_id == 0){
							$updt[] = \R::exec("UPDATE product SET opt_price = '".$opt_price."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
					}
					if($pssql["stock_status_id"]==3){
						if($stock_status_id == 1){
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', stock_status_id = '".$stock_status_id."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
						if($stock_status_id == 0){
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
					}
					if($pssql["stock_status_id"]==0){
						if($t == "") {
							$updt[] = \R::exec("UPDATE product SET opt_price = '".$opt_price."', stock_status_id = '".$stock_status_id."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}else{
							$updt[] = \R::exec("UPDATE product SET opt_price = '".$opt_price."', stock_status_id = '3', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
					}
					if($pssql["stock_status_id"]==1){						
							$updt[] = \R::exec("UPDATE product SET opt_price = '".$opt_price."', stock_status_id = '".$stock_status_id."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");						
					}
				}
				else{
					if($pssql["stock_status_id"]==2){
						if($stock_status_id == 1){
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', stock_status_id = '".$stock_status_id."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
						if($stock_status_id == 0){
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
					}
					if($pssql["stock_status_id"]==3){
						if($stock_status_id == 1){
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', stock_status_id = '".$stock_status_id."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
						if($stock_status_id == 0){
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
					}
					if($pssql["stock_status_id"]==0){
						if($t == "") {
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', stock_status_id = '".$stock_status_id."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}else{
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', stock_status_id = '3', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");
						}
					}
					if($pssql["stock_status_id"]==1){						
							$updt[] = \R::exec("UPDATE product SET price = '".$price."', data_edit_price = '".$date_price."', opt_price = '".$opt_price."', stock_status_id = '".$stock_status_id."', quantity = '".$quantity."' WHERE id = '".$pssql["id"]."'");						
					}
				}
				$branch = \R::getAll("SELECT * FROM branch_office");
				foreach($branch as $br){
					
					$stock = \R::findOne('in_stock', 'product_id = ? AND branch_id = ?', [$pssql["id"], $br["branch_id"]]);
					if($stock){
						$updatestock = \R::exec("UPDATE in_stock SET `quantity` = '".${$br["tbl"]}."', `date_scheduling` = '".$p."' WHERE `product_id` = '".$pssql["id"]."' AND `branch_id` = '".$br["branch_id"]."'");
					}else{		
						$insertstock = \R::exec("INSERT INTO `in_stock`(`branch_id`, `product_id`, `quantity`, `date_scheduling`) VALUES ('".$br["branch_id"]."','".$pssql["id"]."','".${$br["tbl"]}."','".$p."')");					
					}
				}

				/*History in_stock*/
					$total_history = \R::exec("INSERT INTO `in_stock_history`(`product_id`, `date_ish`, `qty`, `price`) VALUES ('".$pssql["id"]."', '".$date_price."', '".$quantity."', '".$price."')");
				/*History in_stock*/
				
				if($price != $pssql["price"] or $quantity != $pssql["quantity"] or $stock_status_id != $pssql["stock_status_id"]) {
				
					// Yandex API IndexNow
					$verification_yandex = \ishop\App::options('option_verification_yandex');
					$client_yandex = new \GuzzleHttp\Client();
					$response_yandex = $client_yandex->request('GET', 'https://yandex.com/indexnow?url='.PATH.'/product/'.$pssql["alias"].'&key='.$verification_yandex.'');
					if($response_yandex->getStatusCode() == "200") { $status_code_yandex = "OK"; }else{ $status_code_yandex = $response_yandex->getBody(); }
					$yandex = "<br>Yandex IndexNow: ".$status_code_yandex."";
					
					// Bing API IndexNow
					$verification_bing = \ishop\App::options('option_verification_bing');
					$client_bing = new \GuzzleHttp\Client();
					$response_bing = $client_bing->request('GET', 'https://www.bing.com/indexnow?url='.PATH.'/product/'.$pssql["alias"].'&key='.$verification_bing.'');
					if($response_bing->getStatusCode() == "200") { $status_code_bing = "OK"; }else{ $status_code_bing = $response_bing->getBody(); }
					$bing = "<br>Bing IndexNow: ".$status_code_bing."";
									
				}
			
			}else{				
				$mdsql = \R::findOne('modification', 'article = ?', [$article]);
				
				if($mdsql["id"]) {
							
					$action = \R::findOne('actions', 'product_id = ? AND date_end > ?', [$mdsql["id"], $date_update]);
					if($action){
						$updtmd[] = \R::exec("UPDATE modification SET quantity = '".$quantity."' WHERE id = '".$mdsql["id"]."'");
					}
					else{
						$updtmd[] = \R::exec("UPDATE modification SET price = '".$price."', quantity = '".$quantity."' WHERE id = '".$mdsql["id"]."'");			
					}
					
					$branch = \R::getAll("SELECT * FROM branch_office");
					foreach($branch as $br){
						
						$stock = \R::findOne('in_stock', 'product_id = ? AND branch_id = ?', [$mdsql["id"], $br["branch_id"]]);
						if($stock){
							$updatestock = \R::exec("UPDATE in_stock SET `quantity` = '".${$br["tbl"]}."', `date_scheduling` = '".$p."' WHERE `product_id` = '".$mdsql["id"]."' AND `branch_id` = '".$br["branch_id"]."'");
						}else{		
							$insertstock = \R::exec("INSERT INTO `in_stock`(`branch_id`, `product_id`, `quantity`, `date_scheduling`) VALUES ('".$br["branch_id"]."','".$mdsql["id"]."','".${$br["tbl"]}."','".$p."')");					
						}
					}					
				}				
			}			
		}	  
	}	
}

$xcol = array_key_last($updt);
if($xcol <= $cnt ){ 
	\R::exec("UPDATE cron SET date_update = '".$date_update."' WHERE id = '".$cron_id."'");
	if($_SESSION['user']['id']) { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','49','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')"); }
	else { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','51','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','NULL')");  }
	
	/*History total*/
		$InStockDate = \R::findOne('in_stock_history_total', 'date_total = ?', [$date_price]);
		$countInStock = \R::getCell('SELECT SUM(quantity) FROM in_stock');
		if($InStockDate){
			$total_history = \R::exec("UPDATE in_stock_history_total SET `qty_total` = '".$countInStock."' WHERE `date_total` = '".$date_price."'");
		}else{			
			$total_history = \R::exec("INSERT INTO `in_stock_history_total`(`date_total`, `qty_total`) VALUES ('".$date_price."','".$countInStock."')");
		}
	/*History total*/
}
$_SESSION['success'] = 'Задание "'.$viewcrons["name"].'" выполнено!';
redirect("".PATH."/admin/cron");