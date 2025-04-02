<?php

namespace app\controllers\admin;

use ishop\App;

class CallbackController extends AppController {

	public function indexAction(){
		$callback = \R::getAll("SELECT*FROM callback ORDER BY date_create DESC");
		$this->setMeta('Обратный звонок');
        $this->set(compact('callback'));
	}

} 