<?php

namespace app\controllers\admin;

use app\models\admin\Cron;
use app\models\AppModel;
use ishop\App;

class CronController extends AppController {

    public function indexAction(){
		
		$crons = \R::getAll("SELECT * FROM cron ORDER BY name");
		
		$this->set(compact('crons'));
		
		$this->setMeta('CRON файлы');
	}
	
	public function addAction() {
		
		if(!empty($_POST)){
            $cron = new Cron();
            $data = $_POST;
            $cron->load($data);

            if(!$cron->validate($data) || !$cron->checkUnique()){
                $cron->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($cron->save('cron', false)){
                $_SESSION['success'] = 'CRON добавлен';
            }
            redirect();
        }

        $this->setMeta('Добавить CRON задание');
		
	}
	
	public function editAction() {
		
		if(!empty($_POST)){
			$id = $this->getRequestID(false);
            $cron = new Cron();
            $data = $_POST;
            $cron->load($data);

            if(!$cron->validate($data)){
                $cron->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($cron->update('cron', $id)){
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
		$id = $this->getRequestID();
        $cron = \R::load('cron', $id);
        $this->setMeta('Редактировать CRON задание');
		$this->set(compact('cron'));
	}
	
	public function deleteAction(){
        $id = $this->getRequestID();        
        $cron = \R::load('cron', $id);
        \R::trash($cron);
        $_SESSION['success'] = 'Задание удалено';
        redirect();
    }
}