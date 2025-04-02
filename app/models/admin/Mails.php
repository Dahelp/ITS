<?php

namespace app\models\admin;

use ishop\App;
use app\models\AppModel;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_Attachment;

class Mails extends AppModel {

    public static function mailboxEmail($data){
        // Create the Transport
        $transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
            ->setUsername(App::$app->getProperty('smtp_login'))
            ->setPassword(App::$app->getProperty('smtp_password'))
        ;
        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);
		$namecomp = App::$app->getProperty('shop_name');
		$subject = $data["subject"];
		$email = $data["email"];
		$content = $data["content"];
		$tell_site = \ishop\App::options('option_telefon');
		ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mailbox.php';
        $body = ob_get_clean();
		
        $message = (new Swift_Message($subject))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
            ->setTo($data["email"])
            ->setBody($body, 'text/html')
        ;
		$msg = $message->toString(); 
		$imap = imap_open("{".App::$app->getProperty('imap_host').":".App::$app->getProperty('imap_port')."/imap/ssl}INBOX.Sent", "".App::$app->getProperty('imap_login')."", "".App::$app->getProperty('imap_password')."");
		$authhost="{".App::$app->getProperty('imap_host').":".App::$app->getProperty('imap_port')."/imap/ssl}INBOX.Sent"; 
		imap_append($imap,$authhost,$msg."\r\n","\\Seen");
		imap_close($imap);
		
        if($_FILES['attachment_file']['tmp_name']){
			$attachment = Swift_Attachment::fromPath($_FILES['attachment_file']['tmp_name']);
			$attachment->setFilename($_FILES['attachment_file']['name']);
			$message->attach($attachment);
		}

        // Send the message
        $result = $mailer->send($message);

        $_SESSION['success'] = 'Письмо отправлено!';
    }
	
	public static function mailboxAnswerEmail($data){
        // Create the Transport
        $transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
            ->setUsername(App::$app->getProperty('smtp_login'))
            ->setPassword(App::$app->getProperty('smtp_password'))
        ;
        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);
		$namecomp = App::$app->getProperty('shop_name');
		$subject = $data["subject"];
		$email = $data["email"];
		$content = $data["content"];
		$tell_site = \ishop\App::options('option_telefon');
		ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mailbox.php';
        $body = ob_get_clean();
		
        $message = (new Swift_Message($subject))
            ->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
			->setReplyTo($data["email"])
            ->setTo($data["email"])
            ->setBody($body, 'text/html')
        ;

		$msg = $message->getHeaders()->get('Message-ID');
		$msg = $message->toString(); 
		$imap = imap_open("{".App::$app->getProperty('imap_host').":".App::$app->getProperty('imap_port')."/imap/ssl}INBOX.Sent", "".App::$app->getProperty('imap_login')."", "".App::$app->getProperty('imap_password')."");
		$authhost="{".App::$app->getProperty('imap_host').":".App::$app->getProperty('imap_port')."/imap/ssl}INBOX.Sent"; 
		imap_append($imap,$authhost,$msg."\r\n","\\Seen");
		imap_close($imap);
		
        if($_FILES['attachment_file']['tmp_name']){
			$attachment = Swift_Attachment::fromPath($_FILES['attachment_file']['tmp_name']);
			$attachment->setFilename($_FILES['attachment_file']['name']);
			$message->attach($attachment);
		}

        // Send the message
        $result = $mailer->send($message);

        $_SESSION['success'] = 'Письмо отправлено!';
    }
}