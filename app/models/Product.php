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

    /** Отправка "1 клик" */
    public static function mailZakazClick($name_tovar, $fio, $tell, $email, $note){
        $transport = (new Swift_SmtpTransport(
            App::$app->getProperty('smtp_host'),
            App::$app->getProperty('smtp_port'),
            App::$app->getProperty('smtp_protocol')
        ))
        ->setUsername(App::$app->getProperty('smtp_login'))
        ->setPassword(App::$app->getProperty('smtp_password'));

        $mailer = new Swift_Mailer($transport);
        $namecomp  = App::$app->getProperty('shop_name');
        $tell_site = \ishop\App::options('option_telefon');

        // письмо клиенту
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_oneclick_user.php';
        $body_user = ob_get_clean();

        $message_client = (new Swift_Message("Вы совершили заказ в 1 клик на сайте {$namecomp}"))
            ->setFrom([App::$app->getProperty('smtp_login') => $namecomp])
            ->setTo($email)
            ->setBody($body_user, 'text/html')
            ->setReplyTo([$email => $fio ?: 'Клиент']);

        // письмо админу
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_oneclick.php';
        $body_admin = ob_get_clean();

        $message_admin = (new Swift_Message("Заказ товара в 1 клик на сайте {$namecomp}"))
            ->setFrom([App::$app->getProperty('smtp_login') => $namecomp])
            ->setTo(App::$app->getProperty('admin_email'))
            ->setBody($body_admin, 'text/html')
            ->setReplyTo([$email => $fio ?: 'Клиент']);

        try {
            $s1 = $mailer->send($message_client);
            $s2 = $mailer->send($message_admin);
            if (!$s1 || !$s2) {
                throw new \RuntimeException('SwiftMailer вернул 0 получателей (oneclick).');
            }
            $_SESSION['success'] = 'Спасибо за Ваш заказ. В ближайшее время с Вами свяжется менеджер для согласования заказа';
            return true;
        } catch (\Throwable $e) {
            error_log("[".date('Y-m-d H:i:s')."] mailZakazClick error: ".$e->getMessage()."\n", 3, ROOT.'/tmp/mail_errors.log');
            $_SESSION['errors']['unique'][] = 'Не удалось отправить письмо по «1 клик». Попробуйте ещё раз или свяжитесь по телефону.';
            return false;
        }
    }

    /** Отправка "под заказ" */
    public static function mailRequest($name_tovar, $fio, $tell, $email, $note){
        $transport = (new Swift_SmtpTransport(
            App::$app->getProperty('smtp_host'),
            App::$app->getProperty('smtp_port'),
            App::$app->getProperty('smtp_protocol')
        ))
        ->setUsername(App::$app->getProperty('smtp_login'))
        ->setPassword(App::$app->getProperty('smtp_password'));

        $mailer = new Swift_Mailer($transport);
        $namecomp  = App::$app->getProperty('shop_name');
        $tell_site = \ishop\App::options('option_telefon');

        // письмо клиенту
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_request_user.php';
        $body_user = ob_get_clean();

        $message_client = (new Swift_Message("Заявка на товар под заказ на сайте {$namecomp}"))
            ->setFrom([App::$app->getProperty('smtp_login') => $namecomp])
            ->setTo($email)
            ->setBody($body_user, 'text/html')
            ->setReplyTo([$email => $fio ?: 'Клиент']);

        // письмо админу
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_request.php';
        $body_admin = ob_get_clean();

        $message_admin = (new Swift_Message("Заявка на товар под заказ на сайте {$namecomp}"))
            ->setFrom([App::$app->getProperty('smtp_login') => $namecomp])
            ->setTo(App::$app->getProperty('admin_email'))
            ->setBody($body_admin, 'text/html')
            ->setReplyTo([$email => $fio ?: 'Клиент']);

        try {
            $s1 = $mailer->send($message_client);
            $s2 = $mailer->send($message_admin);
            if (!$s1 || !$s2) {
                throw new \RuntimeException('SwiftMailer вернул 0 получателей (request).');
            }
            $_SESSION['success'] = 'Спасибо за Вашу заявку товара под заказ. В ближайшее время с Вами свяжется менеджер для согласования заказа';
            return true;
        } catch (\Throwable $e) {
            error_log("[".date('Y-m-d H:i:s')."] mailRequest error: ".$e->getMessage()."\n", 3, ROOT.'/tmp/mail_errors.log');
            $_SESSION['errors']['unique'][] = 'Не удалось отправить письмо по заявке «под заказ». Попробуйте ещё раз или свяжитесь по телефону.';
            return false;
        }
    }

    /** Отправка "уведомить о поступлении" */
    public static function mailAvailability($name_tovar, $email_modal, $user_id){
        $transport = (new Swift_SmtpTransport(
            App::$app->getProperty('smtp_host'),
            App::$app->getProperty('smtp_port'),
            App::$app->getProperty('smtp_protocol')
        ))
        ->setUsername(App::$app->getProperty('smtp_login'))
        ->setPassword(App::$app->getProperty('smtp_password'));

        $mailer = new Swift_Mailer($transport);
        $namecomp  = App::$app->getProperty('shop_name');
        $tell_site = \ishop\App::options('option_telefon');

        // клиенту
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_availability.php';
        $body_user = ob_get_clean();

        $message_client = (new Swift_Message("Заявка о поступлении товара на сайте {$namecomp}"))
            ->setFrom([App::$app->getProperty('smtp_login') => $namecomp])
            ->setTo($email_modal)
            ->setBody($body_user, 'text/html');

        // админу (можно другую вёрстку, но сейчас одинаковая)
        ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_availability.php';
        $body_admin = ob_get_clean();

        $message_admin = (new Swift_Message("Заявка о поступлении товара на сайте {$namecomp}"))
            ->setFrom([App::$app->getProperty('smtp_login') => $namecomp])
            ->setTo(App::$app->getProperty('admin_email'))
            ->setBody($body_admin, 'text/html')
            ->setReplyTo([$email_modal => 'Клиент']);

        try {
            $s1 = $mailer->send($message_client);
            $s2 = $mailer->send($message_admin);
            if (!$s1 || !$s2) {
                throw new \RuntimeException('SwiftMailer вернул 0 получателей (availability).');
            }
            $_SESSION['success'] = 'Заявка о поступлении товара принята. При поступлении товара на склад мы оповестим Вас по почте '.$email_modal.'.';
            return true;
        } catch (\Throwable $e) {
            error_log("[".date('Y-m-d H:i:s')."] mailAvailability error: ".$e->getMessage()."\n", 3, ROOT.'/tmp/mail_errors.log');
            $_SESSION['errors']['unique'][] = 'Не удалось отправить письмо о поступлении. Попробуйте ещё раз или свяжитесь по телефону.';
            return false;
        }
    }
}
