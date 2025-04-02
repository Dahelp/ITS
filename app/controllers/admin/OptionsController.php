<?php

namespace app\controllers\admin;

class OptionsController extends AppController{

    public function indexAction(){
		if(!empty($_POST)){
			foreach ($_POST['altname'] as $altname => $attribute) {
				\R::exec("UPDATE options SET `znachenie` = '".$attribute['znachenie']."'  WHERE `option_id` = '".$altname."'");
			}
			
			$_SESSION['success'] = "Изменения сохранены";
            redirect();			
		}
        $options = \R::getAll("SELECT*FROM options GROUP BY tip");
        $this->setMeta('Основные настройки');
        $this->set(compact('options'));
    }
	
}