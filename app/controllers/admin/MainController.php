<?php

namespace app\controllers\admin;

class MainController extends AppController {

    public function indexAction(){
		$curr = \R::findOne('currency');
        $countNewOrders = \R::count('order', "status = '1'");
		$countOneClick = \R::count('mail_oneclick', "hide = '0'");		
        $countUsers = \R::count('user', "groups > '2'");
        $countProducts = \R::count('product');
		$countInStock = \R::getCell('SELECT SUM(quantity) FROM in_stock');
        $countCategories = \R::count('category');
		$usersonline = \R::getAll("SELECT user.id, user.name FROM user, user_online WHERE user_online.user_id = user.id AND user.role != 'user' ORDER BY user_online.unix LIMIT 8");
		$qtytotals = \R::getAll("SELECT qty_total FROM (SELECT * FROM in_stock_history_total ORDER BY id DESC LIMIT 7) in_stock_history_total ORDER BY id LIMIT 7");
		$this->setMeta('Панель управления');
        $this->set(compact('countNewOrders', 'countNewMails', 'countCategories', 'countProducts', 'countUsers', 'usersonline', 'countOneClick', 'curr', 'countInStock', 'qtytotals'));
    }

}