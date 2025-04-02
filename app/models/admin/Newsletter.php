<?php

namespace app\models\admin;

use app\models\AppModel;
use ishop\App;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_Plugins_AntiFloodPlugin;

class Newsletter extends AppModel {

    public static function newsletterEmail($data){
        // Create the Transport
        $transport = (new Swift_SmtpTransport(App::$app->getProperty('smtp_host'), App::$app->getProperty('smtp_port'), App::$app->getProperty('smtp_protocol')))
            ->setUsername(App::$app->getProperty('smtp_login'))
            ->setPassword(App::$app->getProperty('smtp_password'))
        ;
        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);
		// Use AntiFlood to re-connect after 100 emails
		$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(100));
		// And specify a time in seconds to pause for (30 secs)
		$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin(100, 30));
	
		$namecomp = App::$app->getProperty('shop_name');
		$tell_site = \ishop\App::options('option_telefon');
		ob_start();
        require APP . '/views/'.TEMPLATE.'/mail/mail_newsletter.php';
        $body = ob_get_clean();
		/*массив группы*/
		$sql_groups = '';
        foreach($data["ugroups"] as $g){
            $g = (int)$g;
            $sql_groups .= "'$g',";
        }
        $sql_groups = rtrim($sql_groups, ',');
		/*массив рассылки*/
		$sql_newslet = '';
        foreach($data["unewslet"] as $f){
            $f = (int)$f;
            $sql_newslet .= "'$f',";
        }
        $sql_newslet = rtrim($sql_newslet, ',');
		
		$newsusers = \R::getAll("SELECT user.email, user.name FROM user, user_groups, user_newsletter WHERE user.id = user_newsletter.user_id AND user.groups = user_groups.id AND user.groups IN (".$sql_groups.") AND user_newsletter.newsletter_id IN (".$sql_newslet.") AND user.newsletter !='0' GROUP BY user.email");
		
		$message = (new Swift_Message("".$data["subject"].""))
				->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
				->setBody($body, 'text/html')
			;
		foreach($newsusers as $item) {		
			$message->setTo([$item["email"] => $item["name"]]);		
			// Send the message
			$result = $mailer->send($message);
		}

        $_SESSION['success'] = 'Рассылка отправлена!';
    }

}