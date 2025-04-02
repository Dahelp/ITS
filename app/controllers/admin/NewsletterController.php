<?php

namespace app\controllers\admin;

use app\models\admin\Newsletter;
use app\models\AppModel;
use ishop\App;

class NewsletterController extends AppController{

    public function indexAction(){
		if(!empty($_POST)){
			$newsletter = new Newsletter();
			$data = $_POST;
			$newsletter->newsletterEmail($data);
		}
        
        $this->setMeta('Новостная рассылка');
    }
	
	public function groupsAction(){
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $data['items'] = [];
        $ugroups = \R::getAssoc('SELECT id, name FROM user_groups WHERE name LIKE ?', ["%{$q}%"]);
        if($ugroups){
            $i = 0;
            foreach($ugroups as $id => $name){
                $data['items'][$i]['id'] = $id;
                $data['items'][$i]['text'] = $name;
                $i++;
            }
        }
        echo json_encode($data);
        die;
    }
	
	public function subscriptionAction(){
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $data['items'] = [];
        $subscription = \R::getAssoc('SELECT id, name FROM newsletter WHERE name LIKE ?', ["%{$q}%"]);
        if($subscription){
            $i = 0;
            foreach($subscription as $id => $name){
                $data['items'][$i]['id'] = $id;
                $data['items'][$i]['text'] = $name;
                $i++;
            }
        }
        echo json_encode($data);
        die;
    }
}