<?php

namespace app\models;

use app\services\admin\AdminActivityLogger;
use ishop\App;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class Callback extends AppModel {

    public function addCallback($phone, $user_id = "", $title = ''){
        $res = \R::exec(
            "INSERT INTO `callback`
                (`user_id`, `topic`, `phone`, `date_create`, `date_modified`, `user_modified`, `status`, `hide`)
             VALUES (?, ?, ?, ?, '', '', '0', 'show')",
            [(int)$user_id, $title, $phone, date('Y-m-d H:i:s')]
        );
				if($res){
					
					$callbackId = (int)\R::getCell('SELECT LAST_INSERT_ID()');				
					AdminActivityLogger::incoming(AdminActivityLogger::ACTION_CALLBACK, 'callback', $callbackId, (int)$user_id);
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
					
					$_SESSION['success'] = 'Спасибо за заказ обратного звонка. Наш менеджер обязательно Вам позвонит по указаному номеру который вы указали. Ожидайте звонка в рабочее время с ПН-ПТ 09:00 до 17:00 по МСК.';
					
				}else{
					
				}
    }
	
	public function priceatvCallback($phone, $contact, $email){
        
						
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
					require APP . '/views/'.TEMPLATE.'/mail/mail_priceatv.php';
					$body = ob_get_clean();


					$message_admin = (new Swift_Message("Заказ каталога ATV на сайте " . App::$app->getProperty('shop_name')))
						->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
						->setTo(App::$app->getProperty('admin_email'))
						->setBody($body, 'text/html')
					;
					
					
					$result = $mailer->send($message_admin);
					
					$_SESSION['success'] = 'Спасибо, что заинтересовались нашим каталогом. Мы обязательно проконсультируем по всем вопросам. Поможем подобрать шины на вашу технику.';
					
				
    }

}
