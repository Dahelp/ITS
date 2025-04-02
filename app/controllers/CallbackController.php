<?php

namespace app\controllers;

use app\models\Callback;
use ishop\App;

class CallbackController extends AppController {	
	
	public function indexAction(){
		if($_POST){
			$phone = $_POST["phone"];
			$title = $_POST["title"];
			$callback = new Callback();
			$first = substr($phone, "0",5);		
			if($first != "+7 (9") { $this->errors['unique'][] = "Запрос не обработан! Вы робот? Если нет, попробуйте заполнить форму обратной связи еще раз!"; } else {					
				$callback -> addCallback($phone, $user_id, $title);			            
			}			 
		}
		redirect();
	}
	
	public function priceatvAction(){
		if($_POST){
			$phone = $_POST["phone"];
			$contact = $_POST["contact"];
			$email = $_POST["email"];
			$callback = new Callback();
			$first = substr($phone, "0",5);		
			if($first != "+7 (9") { $this->errors['unique'][] = "Запрос не обработан! Вы робот? Если нет, попробуйте запросить каталог еще раз!"; } else {					
				$callback -> priceatvCallback($phone, $contact, $email);			            
			}			 
		}
		redirect();
	}
}