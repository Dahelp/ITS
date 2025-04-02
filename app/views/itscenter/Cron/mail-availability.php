<?php 

use ishop\App;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

$date_update = date("Y-m-d H:i:s");

$availability = \R::getAll("SELECT id, product_id, email FROM mail_availability WHERE status_nalichiya = '1' AND status_otpravki = '0'");
if($availability){
	foreach($availability as $item){
		$updtp = \R::exec("UPDATE mail_availability SET status_otpravki = '1', data_mail = '".$date_update."' WHERE id = '".$item["id"]."'");	
		
		if($updtp){
			
			$product = \R::findOne('product', 'id = ?', [$item["product_id"]]);
			
			// Create the Transport
			$transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
				->setUsername(App::$app->getProperty('smtp_login'))
				->setPassword(App::$app->getProperty('smtp_password'))
			;
			// Create the Mailer using your created Transport
			$mailer = new Swift_Mailer($transport);
			$namecomp = App::$app->getProperty('shop_name');
			$tell_site = \ishop\App::options('option_telefon');
			// Create a message user
			ob_start();
			require APP . '/views/'.TEMPLATE.'/mail/mail_availability_otpravka.php';
			$body_user = ob_get_clean();

			$message_client = (new Swift_Message("Оповещение о поступлении товара на сайте " . App::$app->getProperty('shop_name')))
				->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
				->setTo($item["email"])
				->setBody($body_user, 'text/html')
			;
			
			// Create a message admin
			ob_start();
			require APP . '/views/'.TEMPLATE.'/mail/mail_availability_otpravka.php';
			$body = ob_get_clean();

			$message_admin = (new Swift_Message("Оповещение о поступлении товара на сайте " . App::$app->getProperty('shop_name')))
				->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
				->setTo(App::$app->getProperty('admin_email'))
				->setBody($body, 'text/html')
			;

			// Send the message
			$result = $mailer->send($message_client);
			$result = $mailer->send($message_admin);
		}	
		
	}					
}		