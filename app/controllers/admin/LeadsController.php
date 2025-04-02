<?php
namespace app\controllers\admin;

use app\models\admin\Leads;
use app\models\AppModel;
use ishop\App;
use app\services\imap\Imap;
use app\services\LeadProfileBadges;
use app\services\leads\LeadsKanban;
use app\services\imap\ConnectionErrorException;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;
use app\models\admin\SSP;

header('Content-Type: text/html; charset=utf-8');
defined('BASEPATH') or exit('No direct script access allowed');

class LeadsController extends AppController{

    /* List all leads */
    public function indexAction()
    {		
		$leads = \R::getAll("SELECT * FROM tblleads ORDER BY id");
        $this->setMeta('Список лидов');
        $this->set(compact('leads'));
    }
	
	public function serverProcessingAction(){		
		//datatables server-side

		$status = $_GET["status"];
		if($_GET["status"]){ $where = " WHERE a.status = '".$status."'"; }
		$table = <<<EOT
		 (
			SELECT a.id, a.name, a.email, a.phonenumber, a.assigned, a.status, a.source, a.company, a.dateadded, a.lastcontact, a.lead_value, b.profile_image FROM tblleads a LEFT JOIN tblstaff b ON a.assigned = b.staffid$where 
		 ) temp
		EOT;
		$primaryKey = 'id';

		$columns = array(
			array( 'db' => 'id', 'dt' => 0),
			array( 'db' => 'name',  'dt' => 1 ),
			array( 'db' => 'company',   'dt' => 2,
				   'formatter' => function( $d, $row ) {						
						return '<a href="'.ADMIN.'/company/edit?id='.$row[0].'">'.$d.'</a>';
					} ),
			array( 'db' => 'email',   'dt' => 3 ),
			array( 'db' => 'phonenumber',   'dt' => 4),
			array( 'db' => 'lead_value', 'dt' => 5,
				   'formatter' => function( $d, $row ) {
						$curr = \R::findOne('currency');
						if($d>0){
							return ''.$curr['symbol_left'].' '.$d.' '.$curr['symbol_right'].'';
						}
					} ),
			array( 'db' => 'id', 'dt' => 6,
				   'formatter' => function( $d, $row ) {
						$tags = \R::getRow('SELECT tbltags.name FROM tbltaggables, tbltags WHERE tbltaggables.tag_id = tbltags.id AND tbltaggables.rel_id = ? AND tbltaggables.rel_type = ?', [$d, 'lead']);
						return ''.$tags["name"].'';
					} ),
			array( 'db' => 'assigned',   'dt' => 7 ),
			array( 'db' => 'profile_image', 'dt' => 8,
				   'formatter' => function( $d, $row ) {						
						$staff_assigned = '<a data-toggle="tooltip" data-title="Дмитрий Куликов" href="'.PATH.'/crm/admin/profile/'.$row[7].'" data-original-title="" title="">';
							if($d !='') { $staff_assigned .= '<img src="'.PATH.'/crm/uploads/staff_profile_images/'.$row[7].'/small_'.$d.'" class="staff-profile-image-small">'; }
							else { $staff_assigned .= '<img src="'.PATH.'/crm/assets/images/user-placeholder.jpg" class="staff-profile-image-small">'; }
						$staff_assigned .= '</a>';
						return ''.$staff_assigned.'';
					} ),
			array( 'db' => 'status',   'dt' => 9,
				   'formatter' => function( $d, $row ) {
						$status = \R::findOne('tblleads_status', 'statusorder=?', [$d]);						
						return ''.$status['name'].'';
						
					}),
			array( 'db' => 'source',   'dt' => 10,
				   'formatter' => function( $d, $row ) {
						$sources = \R::findOne('tblleads_sources', 'id=?', [$d]);						
						return ''.$sources['name'].'';
						
					}),
			array( 'db' => 'lastcontact',   'dt' => 11),
			array( 'db' => 'dateadded',   'dt' => 12),
			array( 'db' => 'id', 'dt' => 13, 
					'formatter' => function( $d, $row ) {
						return '<a href="'.ADMIN.'/leads/edit?id='.$row[0].'"><i class="fas fa-pencil-alt"></i></a> <a class="delete" href="'.ADMIN.'/leads/delete?id='.$row[0].'"><i class="fas fa-times-circle text-danger"></i></a>'; 
					}),
		);
		 
		// SQL server connection information
		$sql_details = array(
			'user' => 'shinaspec_its',
			'pass' => 'r*cS8hTq',
			'db'   => 'shinaspec_its',
			'host' => 'localhost'
		);
		$spp = new SSP();
		echo json_encode(
			$spp::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
		);
		die;
	}
}
