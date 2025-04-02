<?php

namespace app\controllers\admin;

use app\models\admin\Action;
use app\models\AppModel;
use ishop\App;

class ActionController extends AppController {
   
	public function indexAction() {
	
	    $actions = \R::getAll("SELECT product.name, product.price, product.quantity, product.alias, actions.znachenie, actions.date_start, actions.date_end, actions.id, actions.product_id, actions_type.type FROM actions, actions_type, product WHERE actions.type_id = actions_type.id AND actions.product_id = product.id ORDER BY actions.date_start DESC");
        $this->setMeta('Список товаров по акции');
        $this->set(compact('actions')); 
	}
	
	public function addAction(){
		if(!empty($_POST)){
            $act = new Action();
            $data = $_POST;
            $act->load($data);
			$act->attributes['date_create'] = date("Y-m-d H:i:s");
			$act->attributes['user_id'] = $_SESSION['user']['id'];
            if(!$act->validate($data) || !$act->checkUnique()){
                $act->getErrors();
                redirect();
            }
            if($act->save('actions', false)){
                $_SESSION['success'] = 'Акция добавлена';
                redirect();
            }
        }
		$this->setMeta('Добавить правило');
		
		$types = \R::findAll('actions_type');
		$this->setMeta('Добавить товар по акции');
		$this->set(compact('types'));
	}
	
	public function EditAction(){
		if(!empty($_POST)){
			$id = $this->getRequestID(false);
            $act = new Action();
            $data = $_POST;
            $act->load($data);
			$act->attributes['date_modification'] = date("Y-m-d H:i:s");
			$act->attributes['user_modification'] = $_SESSION['user']['id'];
            if(!$act->validate($data)){
                $act->getErrors();
                redirect();
            }
			if($act->update('actions', $id)){
                $_SESSION['success'] = 'Изменения сохранены';
                redirect();
            }

        }
		$id = $this->getRequestID();
        $action = \R::load('actions', $id);		
		$product = \R::findOne('product', 'id = ?', [$action["product_id"]]);
		$types = \R::getAll("SELECT*FROM actions_type");
        $this->setMeta('Редактировать акцию');
        $this->set(compact('action', 'product', 'types'));
		
	}
	
	public function deleteAction(){
        $id = $this->getRequestID();        
        $act = \R::load('actions', $id);
        \R::trash($act);
        $_SESSION['success'] = 'Акция удалена';
        redirect();
    }
	
}