<?php 

use ishop\App;
use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;
use PhpImap\Imap;
$viewcrons = \R::findOne('cron', 'id = ?', [$_GET["id"]]);
$mailbox = new Mailbox(
	'{'.App::$app->getProperty('imap_host').':'.App::$app->getProperty('imap_port').'/imap/ssl}', // IMAP server and mailbox folder
	''.App::$app->getProperty('imap_login').'', // Username for the before configured mailbox
	''.App::$app->getProperty('imap_password').'', // Password for the before configured username
	false, // Directory, where attachments will be saved (optional)
	'UTF-8' // Server encoding (optional)
);

try {
	$mail_ids = $mailbox->searchMailbox('ALL');
} catch (ConnectionException $ex) {
	exit('IMAP connection failed: '.$ex->getMessage());
} catch (Exception $ex) {
	exit('An error occured: '.$ex->getMessage());
}
$last = R::findLast('mails_imap');

foreach ($mail_ids as $mail_id) {
	if($mail_id > $last->message_id) {
        	
		$email = $mailbox->getMail(
			$mail_id, // ID of the email, you want to get
			false // Do NOT mark emails as seen (optional)
		);
		if($email->textHtml){
			$content = "".$email->textHtml."";
		}else {
			$content = "".$email->textPlain."";
		}
		if($email->isSeen) { $seen = 'read'; }else{ $seen = 'unread'; }

		$umail = \R::findOne('user', 'email = ?', [$email->fromAddress]);		
		$date_email = date('Y-m-d H:i:s', strtotime($email->date));
		if($email->getAttachments){ $attachments = "1"; }else{ $attachments = "0"; }
		if($email->isFlagged){ $flagged = "1"; }else{ $flagged = "0"; }		
			
		\R::exec("INSERT INTO `mails_imap`(`message_id`, `folder`, `from_mail`, `from_name`, `user_id`, `subject`, `content`, `date_dispatch`, `date_last_modified`, `id_flagged`, `is_seen`, `attachments`)
		VALUES ('".$mail_id."','".$email->mailboxFolder."','".$email->fromAddress."','".h($email->fromName)."','".$umail["id"]."','".h($email->subject)."','".base64_encode($content)."','".$date_email."','','".$flagged."','".$email->isSeen."','".$attachments."')");
		
	}
}

$mailbox->disconnect();

$date_update = date("Y-m-d H:i");
\R::exec("UPDATE cron SET date_update = '".$date_update."' WHERE id = '".$_GET["id"]."'");
if($_SESSION['user']['id']) { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','59','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')"); }
else { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','51','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','NULL')");  }
$_SESSION['success'] = 'Задание "'.$viewcrons["name"].'" выполнено!';
redirect("".PATH."/admin/cron");
