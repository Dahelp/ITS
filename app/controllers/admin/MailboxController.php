<?php

namespace app\controllers\admin;

use app\models\admin\Mails;
use app\models\admin\SSP;
use app\models\AppModel;
use ishop\App;
use DataTables\Database;
use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;
use PhpImap\Imap;

class MailboxController extends AppController {
	
	public function indexAction(){	
		if($_GET["folder"]) { $folder = ".".$_GET["folder"].""; }		
		$this->setMeta('Входящие');		
	}
	
	public function readAction(){	
		
		$id = $this->getRequestID();
        $message = \R::findOne('mails_imap', 'message_id = ?', [$id]);
		\R::exec("UPDATE mails_imap SET is_seen = '1' WHERE message_id = '".$id."'");
		$mailbox = new Mailbox(
			'{'.App::$app->getProperty('imap_host').':'.App::$app->getProperty('imap_port').'/imap/ssl}INBOX', // IMAP server and mailbox folder
			''.App::$app->getProperty('imap_login').'', // Username for the before configured mailbox
			''.App::$app->getProperty('imap_password').'', // Password for the before configured username
			false, // Directory, where attachments will be saved (optional)
			'UTF-8' // Server encoding (optional)
		);
		
		$this->setMeta('Чтение почты');
		$this->set(compact('message', 'mailbox'));		
	}
	
	public function testAction(){		
		$this->setMeta('Входящие');	
	}
	
	public function composeAction(){
		if(!empty($_POST)){
			$mailbox = new Mails();
			$data = $_POST;
            $mailbox->load($data);
			
		    $mailbox->mailboxEmail($data);
		}		

		$this->setMeta('Написать новое письмо');

	}
	
	public function answerAction(){
		if(!empty($_POST)){
			$mailbox = new Mails();
			$data = $_POST;
            $mailbox->load($data);
			
		    $mailbox->mailboxAnswerEmail($data);
		}
		$id = $_GET["id"];
		if($id){
			$message = \R::findOne('mails_imap', 'message_id = ?', [$id]);
		}
		$namecomp = App::$app->getProperty('shop_name');
		$this->setMeta('Ответить на письмо');
		if($id){
			$this->set(compact('namecomp', 'message'));
		}else{
			$this->set(compact('namecomp'));
		}
	}
	
	public function serverProcessingAction(){		
		//datatables server-side

		if($_GET["folder"]){ $where = " WHERE folder = '".$_GET["folder"]."'"; }
		$table = 'mails_imap';
		$primaryKey = 'id';	 

		$columns = array(
			array( 'db' => 'message_id', 'dt' => 0),
			array( 'db' => 'from_mail',  'dt' => 1 ),
			array( 'db' => 'subject',   'dt' => 2,
				   'formatter' => function( $d, $row ) {
						return '<a href="'.ADMIN.'/mailbox/read?id='.$row[0].'">'.$d.'</a>';
					} ),
			array( 'db' => 'attachments',   'dt' => 3,
				   'formatter' => function( $d, $row ) {
						if($d == "1") {
							return '<i class="fas fa-paperclip"></i>';
						}
					} ),
			array( 'db' => 'is_seen',   'dt' => 4,
				   'formatter' => function( $d, $row ) {
						if($d == "1") {
							return '1';
						}else{
							return '0';
						}
					} ),
			array( 'db' => 'date_dispatch', 'dt' => 5,
				   'formatter' => function( $d, $row ) {
						$data_YH = date("Y-m-d H:i:s");
						$date = \ishop\App::getPeriodMailbox($d, $data_YH);
						if($date>0) {
							$date_mail = \ishop\App::abbreviateddate(date('Y-m-d', strtotime($d)));
						}else{
							$data_Y = date("Y-m-d");
							$date_E = date('Y-m-d', strtotime($d));
							if($data_Y != $date_E) { $date_mail = \ishop\App::abbreviateddate(date('Y-m-d', strtotime($d))); }
							else { $date_mail = date('H:i', strtotime($d)); }
						}
						return ''.$date_mail.'';
					} ),
			array( 'db' => 'message_id', 'dt' => 6, 
					'formatter' => function( $d, $row ) {
						return '<a class="delete" href="'.ADMIN.'/mailbox/delete?id='.$row[0].'"><i class="fas fa-times-circle text-danger"></i></a>'; 
					}),
		);
		 
		// SQL server connection information
		$sql_details = array(
			'user' => App::$app->getProperty('sql_user'),
			'pass' => App::$app->getProperty('sql_pass'),
			'db'   => App::$app->getProperty('sql_db'),
			'host' => App::$app->getProperty('sql_host')
		);
		$spp = new SSP();
		echo json_encode(
			$spp::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
		);
		die;
	}
	
	public function deleteAction(){
        $id = $this->getRequestID();        
        $mailbox = \R::findOne('mails_imap', 'message_id = ?', [$id]);
		
		\R::trash($mailbox);
		
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','61','mails_imap','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                
        $_SESSION['success'] = 'Письмо ID '.$id.' удалено';
        redirect();
    }

} 