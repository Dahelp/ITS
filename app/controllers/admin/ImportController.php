<?php

namespace app\controllers\admin;

use app\models\admin\Product;
use ishop\App;
use app\models\AppModel;

class ImportController extends AppController {

    public function indexAction(){
		$ftp_server = App::$app->getProperty('ftp_server');
		$ftp_login = App::$app->getProperty('ftp_login');
		$ftp_pass = App::$app->getProperty('ftp_pass');
		$ftp_port = App::$app->getProperty('ftp_port');
		if(!empty($_POST)){
			$conn_id = @ftp_connect(''.$ftp_server.'', ''.$ftp_port.'', 5); // коннектимся к серверу FTP
			if($conn_id) // если соединение с сервером прошло удачно, продолжаем
			{
				$login_result = @ftp_login($conn_id, ''.$ftp_login.'', ''.$ftp_pass.''); // вводим свои логин и пароль для FTP
				if($login_result) // если сервер принял логин пароль, идем дальше
				{
					// теперь нужно поиграть с пассивным режимом, включить его или выключить(TRUE, FALSE)
					// если дальнейшие функции ftp будут работать не правильно, пробуйте менять этот параметр (TRUE или FALE)
                   
					ftp_pasv ($conn_id, TRUE); // в данном случае пассивный режим включен
					
					$fileprod = $_POST["url_file"];
					$exp = explode("/", $fileprod);
					$file_name = end($exp); //myimage.jpg
					
					$path = "xml/$file_name";

					$ch = curl_init($fileprod);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_HEADER, false);
					$html = curl_exec($ch);
					curl_close($ch); 

					file_put_contents($path, $html);

					$xml = simplexml_load_file("xml/$file_name");
					
					if ( $_POST['url_file'])
					{ 

						foreach ( $xml->shop->offers->offer as $row )  
						{
							$product = new Product();
						
							$product->attributes['article'] = $row[id];
							$product->attributes['price'] = $row->price;
							$product->attributes['category_id'] = $_POST["category_id"];	
							$product->attributes['name'] = $row->name;    
							$product->attributes['model'] = $row->model;
							$brand_id = \R::findOne('brand', 'name = ?', [$row->vendor]);
							$product->attributes['brand_id'] = $brand_id['id'];
							$product->attributes['quantity'] = $row->quantity;
							$product->attributes['hide'] = 'show';
							$product->attributes['hit'] = '0';
							$product->attributes['new_product'] = '0';
							$product->attributes['sale'] = '0';
							$product->attributes['opt_price'] = '0';
							$product->attributes['date'] = date("Y-m-d");							
							if($row->quantity>0) { $product->attributes['stock_status_id'] = "1"; } else { $product->attributes['stock_status_id'] = "0"; }
							if(!$product->checkUniqueArticle()){}
							else{													
								$wmax = App::$app->getProperty('img_width');
								$hmax = App::$app->getProperty('img_height');
								$wmaxmini = App::$app->getProperty('mini_img_width');
								$hmaxmini = App::$app->getProperty('mini_img_height');
								$product->uploadImgXml($row->picture, $row->name, $wmax, $hmax, $wmaxmini, $hmaxmini);
								$product->getImg();
								if($id = $product->save('product')){
									$alias = AppModel::createAlias('product', 'alias', ''.$row->name.'', $id);
									$p = \R::load('product', $id);
									$p->alias = $alias;	
									\R::store($p);
									$isNonEmptyArray = $product->traverseArray($row->param);
									if($isNonEmptyArray){ 
										$sql_part = '';
										foreach ( $row->param as $params ) {							

											$att = \R::findOne('attribute', 'attribute_name = ?', [$params["name"]]);
											$sql_part .= "($id, '".$att['id']."', '".$att['attribute_group_id']."', '".$params."'),";              
											
										}
										$sql_part = rtrim($sql_part, ',');
									
									\R::exec("INSERT IGNORE INTO product_attribute (product_id, attribute_id, attribute_group_id, attribute_text) VALUES $sql_part");
									}
									$_SESSION['success'] = 'Товар добавлен';
								}
							}
						}
					}
				}
				else { $_SESSION['error'] = 'Пароль или логин не подошли!'; }
			}
			else { $_SESSION['error'] = 'Не подключились'; }
			ftp_close($conn_id); // и закрываем коннект с FTP 
		}
		
        $this->setMeta('Импорт товаров');

    }
	
}