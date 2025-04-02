<?php

namespace app\controllers\admin;

use ishop\App;

class OneclickController extends AppController {

	public function indexAction(){
		$clicks = \R::getAll("SELECT*FROM mail_oneclick ORDER BY data_create DESC");
		$namecomp = App::$app->getProperty('shop_name');
		$this->setMeta('Заказы в 1 клик');
        $this->set(compact('clicks', 'namecomp'));
	}

} 