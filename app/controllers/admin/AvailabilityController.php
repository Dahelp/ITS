<?php

namespace app\controllers\admin;

use ishop\App;

class AvailabilityController extends AppController {

	public function indexAction(){
		$availability = \R::getAll("SELECT*FROM mail_availability ORDER BY data_create DESC");
		$this->setMeta('Заявки о поступлении товара');
        $this->set(compact('availability'));
	}

} 