<?php

namespace app\controllers\admin;

use app\models\admin\Order;
use app\services\admin\AdminActivityLogger;
use ishop\App;
use ishop\libs\Pagination;

class OrderController extends AppController {

    public function indexAction(){
        $statusFilter = isset($_GET['status']) ? (int)$_GET['status'] : 0;
        $where = $statusFilter > 0 ? 'WHERE `order`.`status` = ?' : '';
        $params = $statusFilter > 0 ? [$statusFilter] : [];
        $count = $statusFilter > 0 ? \R::count('order', 'status = ?', [$statusFilter]) : \R::count('order');
		$curr = \R::findOne('currency');
        $orders = \R::getAll("SELECT `order_status`.`status_name`, `order`.`id`, `order`.`user_id`, `order`.`status`, `order`.`inv`, `order`.`date`, `order`.`update_at`, `order`.`currency`, `user`.`name`, `user`.`admin_id`, `user`.`email`, `order`.`comp_id`, ROUND(SUM(`order_product`.`price` * `order_product`.`qty`), 2) AS `sum` FROM `order`
			JOIN `user` ON `order`.`user_id` = `user`.`id`
			JOIN `order_product` ON `order`.`id` = `order_product`.`order_id`
			JOIN `order_status` ON `order`.`status` = `order_status`.`id`
            {$where}
			GROUP BY `order`.`id`", $params);

        $this->setMeta('Список заказов');
        $this->set(compact('orders', 'count', 'curr'));
    }

	public function statProductAction()
	{
		$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		if ($productId <= 0) {
			throw new \Exception('Некорректный ID товара');
		}

		$product = \R::findOne('product', 'id = ?', [$productId]);
		if (!$product) {
			throw new \Exception('Товар не найден');
		}

		$curr = \R::findOne('currency');

		$orders = \R::getAll("
			SELECT
				o.id,
				o.inv,
				o.user_id,
				o.admin_id,
				o.comp_id,
				o.status,
				os.status_name,
				o.date,

				item.qty_item,
				totals.order_sum,

				u.name  AS user_name,
				u.email AS user_email,
				u.role  AS user_role,

				c.comp_short_name,
				c.inn,

				ua.name AS manager_name
			FROM (
				SELECT order_id, SUM(qty) AS qty_item
				FROM `order_product`
				WHERE product_id = ?
				GROUP BY order_id
			) item
			JOIN `order` o ON o.id = item.order_id
			JOIN (
				SELECT order_id, ROUND(SUM(price * qty), 2) AS order_sum
				FROM `order_product`
				GROUP BY order_id
			) totals ON totals.order_id = o.id
			JOIN `order_status` os ON o.status = os.id
			JOIN `user` u ON o.user_id = u.id
			LEFT JOIN `company` c ON o.comp_id = c.id
			LEFT JOIN `user` ua ON o.admin_id = ua.id
			ORDER BY o.id DESC
		", [$productId]);

		foreach ($orders as &$row) {
			$role = $row['user_role'] ?? 'user';

			$isB2B = ($role === 'b2buser');
			$row['source'] = $isB2B ? 'B2B ИТС-Центр' : 'ИТС-Центр';

			// Контакт (ссылка на редактирование пользователя — одна таблица)
			$row['contact_name']  = $row['user_name'] ?? '';
			$row['contact_email'] = $row['user_email'] ?? '';
			$row['contact_link']  = ADMIN . "/user/edit?id=" . (int)$row['user_id'];

			// Заказ (единый просмотр)
			$row['order_link'] = ADMIN . "/order/view?id=" . (int)$row['id'];
		}
		unset($row);

		$count_sales = array_sum(array_map(static function($row) {
			return (int)($row['qty_item'] ?? 0);
		}, $orders));

		$this->setMeta('Продажи по позиции: ' . ($product['title'] ?? ''));
		$this->set(compact('orders', 'product', 'productId', 'count_sales', 'curr'));
	}

    public function viewAction(){
		if (!empty($_POST)) {
			$id = $this->getRequestID();
			$orderModel = new Order();
			$data = $_POST;

			$orderModel->load($data);
			$orderModel->editOrder($id, $data);
			$orderModel->editOrderProduct($id, $data);

			AdminActivityLogger::admin(44, 'order', (int)$id);

			$_SESSION['success'] = 'Изменения сохранены';
			redirect();
		}

		$namecomp = App::$app->getProperty('shop_name');
		$order_id = $this->getRequestID();
		$order_prefix = \ishop\App::options('order_prefix');
		$curr = \R::findOne('currency');

		$order = \R::getRow("
			SELECT 
				`order_status`.`status_name`,
				`order`.*,
				`user`.`name`,
				`user`.`admin_id`,
				`user`.`telefon`,
				`user`.`email`,
				`user`.`groups`,
				`dostavka`.`name` AS `dostavkaname`,
				`dostavka`.`id` AS `dostavka_id`,
				ROUND(SUM(`order_product`.`price` * `order_product`.`qty`), 2) AS `sum`
			FROM `order`
			JOIN `user` ON `order`.`user_id` = `user`.`id`
			JOIN `order_product` ON `order`.`id` = `order_product`.`order_id`
			JOIN `order_status` ON `order`.`status` = `order_status`.`id`
			JOIN `dostavka` ON `order`.`dostavka_id` = `dostavka`.`id`
			WHERE `order`.`id` = ?
			GROUP BY `order`.`id`
			ORDER BY `order`.`status`, `order`.`id`
			LIMIT 1
		", [$order_id]);

		if (!$order) {
			throw new \Exception('Страница не найдена', 404);
		}

		$order_products = \R::findAll('order_product', "order_id = ?", [$order_id]);

		$comp = null;
		if (!empty($order['comp_id'])) {
			$comp = \R::findOne('company', 'id = ?', [$order['comp_id']]);
		} elseif (!empty($order['user_id'])) {
			$comp = \R::findOne('company', 'user_id = ?', [$order['user_id']]);
		}

		$this->setMeta("Заказ №{$order_prefix}{$order_id}");
		$this->set(compact('order', 'order_products', 'curr', 'namecomp', 'comp'));
	}
	
	public function addAction(){		
		if(!empty($_POST)){
			$order = new Order();
			$data = $_POST;
			$user_name = $data['user_name'];
			if($user_name) {
				$order->addUser($data);
				$user = \R::findLast('user');
				$user_id = $user->id;
				$data['user_id'] = $user_id;
				AdminActivityLogger::admin(36, 'user', (int)$user_id);
			}
			$comp_name = $data['comp_name'];
			if($comp_name) {
				$order->addCompany($data);
				$company = \R::findLast('company');
				$comp_id = $company->id;
				$data['comp_id'] = $comp_id;
				\R::exec("UPDATE user SET comp_id = '".$comp_id."' WHERE id = ?", [$user_id]);
				AdminActivityLogger::admin(33, 'company', (int)$comp_id);
			}
            $order->load($data);
			$last_order = \R::findLast('order');
			$id = $last_order->id;
			$order->addOrder($id, $data);
			$order->addOrderProduct($id, $data);
			
			AdminActivityLogger::admin(43, 'order', (int)$id);
			$_SESSION['success'] = 'Заказ создан';
            redirect();
		}
		
		$curr = \R::findOne('currency');
		$this->set(compact('curr'));
		$this->setMeta("Создать заказ");
	}
	
    public function changeAction(){
        $order_id = $this->getRequestID();
		$orders = new Order();
        $status = $_POST['status'];
        $order = \R::load('order', $order_id);
		$order_status = \R::findOne('order_status', 'id = ?', [$status]);
		$user = \R::load('user', $order->user_id);
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $order->status = $status;
        $order->update_at = date("Y-m-d H:i:s");
		$sending = $order_status["sending"];
		if($sending == "show"){
			$template = $order_status["template"];
			$orders->changeEmail($order_id, $user, $template);
		}
        \R::store($order);
		AdminActivityLogger::admin(46, 'order', (int)$order_id);
        $_SESSION['success'] = 'Изменения сохранены - Присвоен статус '.$order_status->status_name.'';
        redirect(ADMIN . '/order/view?id='.$order_id.'');
    }
	
	public function managerAction(){
        $order_id = $this->getRequestID();
		$orders = new Order();
        $admin_id = $_POST['admin_id'];
        $order = \R::load('order', $order_id);
		$user = \R::load('user', $order->user_id);
		$order_manager = \R::findOne('user', 'id = ?', [$admin_id]);
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $user->admin_id = $admin_id;
		$order->admin_id = $admin_id;
        $order->update_at = date("Y-m-d H:i:s");
		
		$orders->managerEmail($order_manager["email"], $order_id, $user);
        \R::store($order);
		\R::store($user);
		AdminActivityLogger::admin(52, 'order', (int)$order_id);
        $_SESSION['success'] = 'Изменения сохранены - Заказу привязан менеджер '.$order_manager->name.'';		
		
        redirect(ADMIN . '/order/view?id='.$order_id.'');
    }
	
	public function ordermanagerAction(){
        $order_id = $_GET['orderid'];
		$orders = new Order();
        $admin_id = $_GET['id'];
        $order = \R::load('order', $order_id);
		$user = \R::load('user', $order->user_id);
		$order_manager = \R::findOne('user', 'id = ?', [$admin_id]);
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $user->admin_id = $admin_id;
		$order->admin_id = $admin_id;
        $order->update_at = date("Y-m-d H:i:s");		
		
        \R::store($order);
	    \R::store($user);
	   
		AdminActivityLogger::admin(52, 'order', (int)$order_id);
        $_SESSION['success'] = 'Изменения сохранены - Заказу '.$order_id.' привязан менеджер '.$order_manager->name.'';		
		
        redirect(ADMIN . '/order');
    }
	
	public function sellerAction(){
        $order_id = $this->getRequestID();
        $seller = $_POST['seller'];
        $order = \R::load('order', $order_id);
		$company = \R::findOne('company', 'id = ?', [$seller]);
		
        if(!$order){
            throw new \Exception('Страница не найдена', 404);
        }
        $order->seller = $seller;
        $order->update_at = date("Y-m-d H:i:s");
		if($seller == 1){ $order_prefix = "SH"; }
		if($seller == 2){ $order_prefix = "RO"; }
		if($seller == 3){ $order_prefix = "IT"; }		
		$inv = \ishop\App::invoice_num($order_id, 9, $order_prefix);
		$order->inv = $inv;
        \R::store($order);
		AdminActivityLogger::admin(54, 'order', (int)$order_id);
        $_SESSION['success'] = 'Изменения сохранены - Присвоен продавец '.$company->comp_short_name.'';
        redirect(ADMIN . '/order/view?id='.$order_id.'');
    }

    public function deleteAction(){
        $order_id = $this->getRequestID();
        $order = \R::load('order', $order_id);
        \R::trash($order);
		AdminActivityLogger::admin(45, 'order', (int)$order_id);
        $_SESSION['success'] = 'Заказ удален';
        redirect(ADMIN . '/order');
    }
	
	public function searchproductAction(){
		$request = 1;
		$code = $_GET['code'];
		
		if(isset($_POST['request'])){
			$request = $_POST['request'];
		}
		
		// Select2 data
		if($request == 1){
			$q = isset($_GET['q']) ? $_GET['q'] : '';
			$data['items'] = [];
			$searchproduct = \R::getAll('SELECT id, article, name FROM product WHERE name LIKE ? OR article LIKE ? ORDER BY name LIMIT 20', ["%{$q}%", "%{$q}%"]);
			if($searchproduct){
				$i = 0;
				foreach($searchproduct as $product){
					$data['items'][$i]['id'] = $product['id'];
					$data['items'][$i]['text'] = ($product['article'] ? $product['article'].' - ' : '').$product['name'];					
					$i++;
				}
			}
			echo json_encode($data);
			
			die;
		}

		// Add element
		if($request == 2){
			
		   $html = "<div style=\"width: 100%;display:flex\">
						<div style=\"width:50%;padding: 0 0 0 10px;\"><select name=\"order_zakaz[".$code."][product_id]\" class=\"form-control searchproduct_".$code."\" data-id=\"".$code."\" data-placeholder=\"Выберите товар\"></select></div><div style=\"width:15%;padding: 0 0 0 10px;\" class=\"artic_".$code."\"></div><div style=\"width:15%;padding: 0 0 0 10px;\" class=\"price_".$code."\"></div><div style=\"width:10%;padding: 0 0 0 10px;\"><input name=\"order_zakaz[".$code."][quantity]\" type=\"text\" value=\"\" class=\"form-control orderquantity_".$code."\" placeholder=\"введите количество\" /></div><div style=\"width:10%;padding: 0 0 0 10px;\" class=\"itogpriceproduct_".$code."\"><input name=\"order_zakaz[".$code."]itog\" type=\"text\" value=\"\" class=\"form-control itog_".$code."\" placeholder=\"0\" disabled /></div>
					</div>";
		   echo $html;
		   exit;

		}
    }
	
	public function productpriceAction(){
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$comp_id = isset($_GET['comp_id']) ? (int)$_GET['comp_id'] : 0;

		$product = null;
		$stock = [];
		$sale = [];

		if ($id > 0) {
			$product = \R::findOne('product', 'id = ?', [$id]);
			$stock = \R::getRow(
				'SELECT * FROM in_stock WHERE product_id = ? AND branch_id = ? GROUP BY product_id',
				[$id, 9]
			);

			if ($product && $comp_id > 0) {
				$sale = \R::getRow(
					'SELECT * FROM company_typeprice WHERE company_id = ? AND category_id = ?',
					[$comp_id, $product->category_id]
				);
			}
		}

		echo json_encode([
			'result1' => (string)($product->article ?? ''),
			'result2' => (string)($product->price ?? 0),
			'result3' => (string)($product->quantity ?? 0),
			'result4' => (string)($stock['quantity'] ?? 0),
			'result5' => (string)($product->weight ?? 0),
			'result6' => (string)($product->volume ?? 0),
			'result7' => (string)($sale['znachenie'] ?? ''),
		]);
		die;
	}
	
	public function compinfoAction(){
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

		if ($id > 0) {
			$company = \R::getRow(
				"SELECT * FROM company, user WHERE company.user_id = user.id AND company.id = ?",
				[$id]
			);
		} else {
			$company = [];
		}

		echo json_encode([
			'uname' => (string)($company['name'] ?? ''),
			'uid' => (string)($company['user_id'] ?? ''),
			'utelefon' => (string)($company['telefon'] ?? ''),
			'uemail' => (string)($company['email'] ?? ''),
			'cnds' => (string)($company['nds'] ?? ''),
			'tip' => (string)($company['tip'] ?? ''),
			'url_address' => (string)($company['url_address'] ?? ''),
			'postal_address' => (string)($company['postal_address'] ?? ''),
			'ogrn' => (string)($company['ogrn'] ?? ''),
			'inn' => (string)($company['inn'] ?? ''),
			'kpp' => (string)($company['kpp'] ?? ''),
			'bik' => (string)($company['bik'] ?? ''),
			'raschet' => (string)($company['raschet'] ?? ''),
			'korschet' => (string)($company['korschet'] ?? ''),
			'bank' => (string)($company['bank'] ?? ''),
			'dir_name' => (string)($company['dir_name'] ?? ''),
			'dogovor' => (string)($company['dogovor'] ?? ''),
			'hide' => (string)($company['hide'] ?? ''),
		]);
		die;
	}
	
	public function usercontactAction(){
		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

		if ($id > 0) {
			$user = \R::getRow(
				"SELECT user.groups, user.telefon, user.email, user_groups.name as groups_name
				 FROM user, user_groups
				 WHERE user.groups = user_groups.id AND user.id = ?",
				[$id]
			);
			$company = \R::getRow("SELECT * FROM company WHERE user_id = ?", [$id]);
		} else {
			$user = [];
			$company = [];
		}

		echo json_encode([
			'groups' => (string)($user['groups'] ?? ''),
			'groups_name' => (string)($user['groups_name'] ?? ''),
			'utelefon' => (string)($user['telefon'] ?? ''),
			'uemail' => (string)($user['email'] ?? ''),
			'comp_name' => (string)($company['comp_name'] ?? ''),
			'comp_id' => (string)($company['id'] ?? ''),
			'cnds' => (string)($company['nds'] ?? ''),
			'tip' => (string)($company['tip'] ?? ''),
			'url_address' => (string)($company['url_address'] ?? ''),
			'postal_address' => (string)($company['postal_address'] ?? ''),
			'ogrn' => (string)($company['ogrn'] ?? ''),
			'inn' => (string)($company['inn'] ?? ''),
			'kpp' => (string)($company['kpp'] ?? ''),
			'bik' => (string)($company['bik'] ?? ''),
			'raschet' => (string)($company['raschet'] ?? ''),
			'korschet' => (string)($company['korschet'] ?? ''),
			'bank' => (string)($company['bank'] ?? ''),
			'dir_name' => (string)($company['dir_name'] ?? ''),
			'dogovor' => (string)($company['dogovor'] ?? ''),
			'hide' => (string)($company['hide'] ?? ''),
		]);
		die;
	}
	
	public function pdfscoreAction(){
		$order_id = $_GET["id"];		
		$order = \R::findOne("order", "id = ?", [$order_id]);
        $order_products = \R::findAll('order_product', "order_id = ?", [$order_id]);
		$seller = \R::findOne('company', 'id = ?', [$order['seller']]);
		$user = \R::findOne('user', 'id = ?', [$order['user_id']]);
		$comp = \R::findOne('company', 'id = ?', [$user['comp_id']]);
        $this->set(compact('order', 'order_products', 'seller', 'user', 'comp'));
	}
	
}
