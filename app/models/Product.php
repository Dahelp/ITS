<?php

namespace app\models;

use ishop\App;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class Product extends AppModel {

    public function setRecentlyViewed($id){
        $recentlyViewed = $this->getAllRecentlyViewed();
        if(!$recentlyViewed){
            setcookie('recentlyViewed', $id, time() + 3600*24, '/');
        }else{
            $recentlyViewed = explode('.', $recentlyViewed);
            if(!in_array($id, $recentlyViewed)){
                $recentlyViewed[] = $id;
                $recentlyViewed = implode('.', $recentlyViewed);
                setcookie('recentlyViewed', $recentlyViewed, time() + 3600*24, '/');
            }
        }
    }

    public function getRecentlyViewed(){
        if(!empty($_COOKIE['recentlyViewed'])){
            $recentlyViewed = $_COOKIE['recentlyViewed'];
            $recentlyViewed = explode('.', $recentlyViewed);
            return array_slice($recentlyViewed, -3);
        }
        return false;
    }

    public function getAllRecentlyViewed(){
        if(!empty($_COOKIE['recentlyViewed'])){
            return $_COOKIE['recentlyViewed'];
        }
        return false;
    }
	
	public static function mailZakazClick($name_tovar, $fio, $tell, $email, $note){
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
        require APP . '/views/'.TEMPLATE.'/mail/mail_oneclick_user.php';
        $body_user = ob_get_clean();

        $message_client = (new Swift_Message("Вы совершили заказ в 1 клик на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo($email_click)
            ->setBody($body_user, 'text/html')
        ;
		
		// Create a message admin
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_oneclick.php';
        $body = ob_get_clean();

        $message_admin = (new Swift_Message("Заказ товара в 1 клик на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo(App::$app->getProperty('admin_email'))
            ->setBody($body, 'text/html')
        ;

        // Send the message
        $result = $mailer->send($message_client);
        $result = $mailer->send($message_admin);
        $_SESSION['success'] = 'Спасибо за Ваш заказ. В ближайшее время с Вами свяжется менеджер для согласования заказа';
    }
	
	public static function mailRequest($name_tovar, $fio, $tell, $email, $note){
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
        require APP . '/views/'.TEMPLATE.'/mail/mail_request_user.php';
        $body_user = ob_get_clean();

        $message_client = (new Swift_Message("Заявка на товар под заказ на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo($email)
            ->setBody($body_user, 'text/html')
        ;
		
		// Create a message admin
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_request.php';
        $body = ob_get_clean();

        $message_admin = (new Swift_Message("Заявка на товар под заказ на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo(App::$app->getProperty('admin_email'))
            ->setBody($body, 'text/html')
        ;

        // Send the message
        $result = $mailer->send($message_client);
        $result = $mailer->send($message_admin);
        $_SESSION['success'] = 'Спасибо за Вашу заявку товара под заказ. В ближайшее время с Вами свяжется менеджер для согласования заказа';
    }

	public static function mailAvailability($name_tovar, $email_modal, $user_id){
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
        require APP . '/views/'.TEMPLATE.'/mail/mail_availability.php';
        $body_user = ob_get_clean();

        $message_client = (new Swift_Message("Заявка о поступлении товара на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo($email_modal)
            ->setBody($body_user, 'text/html')
        ;
		
		// Create a message admin
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_availability.php';
        $body = ob_get_clean();

        $message_admin = (new Swift_Message("Заявка о поступлении товара на сайте " . App::$app->getProperty('shop_name')))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo(App::$app->getProperty('admin_email'))
            ->setBody($body, 'text/html')
        ;

        // Send the message
        $result = $mailer->send($message_client);
        $result = $mailer->send($message_admin);
        $_SESSION['success'] = 'Заявка о поступлении товара на сайте принята. При поступлении товара на склад, мы оповестим Вас по почте '.$email_modal.'';
	}
}