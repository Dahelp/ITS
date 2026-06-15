<?php

namespace app\controllers;

use app\models\User;
use app\services\admin\AdminActivityLogger;
use app\widgets\cabinet\Cabinet;
use ishop\App;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_Attachment;
use ishop\libs\Pagination;

class UserController extends AppController {

	/** Открыть сессию для чтения (без записи) */
	private function sessReadOpen(): void {
		if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
	}

	/** Открыть сессию для записи (сет флешей и т.п.) */
	private function sessWriteOpen(): void {
		if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
	}

	/** Безопасно закрыть сессию (снять блокировку) */
	private function sessClose(): void {
		if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }
	}
	
	public function newsletterAction(){
		if(!User::checkAuth()) redirect('');

		$this->sessReadOpen();
		$uid = $_SESSION['user']['id'] ?? null;
		$this->sessClose();

		if (!$uid) redirect('');

		$newsletters = \R::getAll("SELECT * FROM newsletter");
		$user = \R::findOne('user', 'id = ?', [$uid]);

		$this->setMeta('Подписки на новости');
		$this->set(compact('newsletters', 'user'));
	}
	
	public function addnewsletterAction(){
		if($_GET) {
			$this->sessReadOpen();
			$uid = $_SESSION['user']['id'] ?? null;
			$this->sessClose();
			if (!$uid) redirect('');

			$newsletter_id = (int)($_GET["newsletter_id"] ?? 0);
			$checked = (int)($_GET["checked"] ?? 0);

			if($checked === 1){
				\R::exec("DELETE FROM user_newsletter WHERE newsletter_id = ? AND user_id = ?", [$newsletter_id, $uid]);
			} else {
				\R::exec("INSERT INTO user_newsletter (user_id, newsletter_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id=user_id", [$uid, $newsletter_id]);
				\R::exec("UPDATE user SET newsletter = 1 WHERE id = ?", [$uid]);
			}

			if($this->isAjax()){
				$this->loadView('newsletter_block');
			}
		}
	}
	
	public function deletenewsletterAction(){
		if($_GET) {
			$this->sessReadOpen();
			$uid = $_SESSION['user']['id'] ?? null;
			$this->sessClose();
			if (!$uid) redirect('');

			$checked = (int)($_GET["checked"] ?? 0);

			if($checked === 1){
				\R::exec("DELETE FROM user_newsletter WHERE user_id = ?", [$uid]);
				\R::exec("UPDATE user SET newsletter = 0 WHERE id = ?", [$uid]);
			} else {
				$newsletters = \R::getCol("SELECT id FROM newsletter");
				if ($newsletters) {
					$vals = [];
					foreach($newsletters as $nid) { $vals[] = '('.(int)$uid.','.(int)$nid.')'; }
					\R::exec("INSERT INTO user_newsletter (user_id, newsletter_id) VALUES ".implode(',', $vals));
					\R::exec("UPDATE user SET newsletter = 1 WHERE id = ?", [$uid]);
				}
			}

			if($this->isAjax()){
				$this->loadView('newsletter_block');
			}
		}
	}
	
	public function notificationsAction(){
		if(!User::checkAuth()) redirect();
        $this->setMeta('Сообщения');
	}
	
	public function exportAction(){
		if(!User::checkAuth()) redirect();
        $this->setMeta('Экспорт товаров');
	}	
	
    public function signupAction()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		if (empty($_POST)) {
			if (empty($_SESSION['signup_token'])) {
				$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			}
			$this->setMeta('Регистрация');
			return;
		}

		$data = $_POST;

		// === CSRF ===
		$postToken    = (string)($data['signup_token'] ?? '');
		$sessionToken = (string)($_SESSION['signup_token'] ?? '');

		if ($postToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postToken)) {
			$this->sessWriteOpen();
			$_SESSION['error'] = 'Форма устарела, обновите страницу и попробуйте ещё раз.';
			$fd = $data;
			unset($fd['password'], $fd['signup_token'], $fd['hp_field']);
			$_SESSION['form_data'] = $fd;
			$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			$this->sessClose();
			redirect();
		}

		// одноразовый токен
		unset($_SESSION['signup_token']);

		// === honeypot ===
		if (!empty($data['hp_field'])) {
			redirect();
		}

		// === нормализация ===
		$name = trim((string)($data['name'] ?? ''));
		$email = trim((string)($data['email'] ?? ''));
		$email = mb_strtolower($email, 'UTF-8');
		$password = (string)($data['password'] ?? '');
		$agreePersonalData = !empty($data['agree_personal_data']) ? 1 : 0;

		$data['name'] = $name;
		$data['email'] = $email;
		$data['agree_personal_data'] = $agreePersonalData;

		// === проверки ===
		if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
			$this->sessWriteOpen();
			$_SESSION['error'] = 'Укажите корректное имя.';
			$fd = $data;
			unset($fd['password'], $fd['signup_token'], $fd['hp_field']);
			$_SESSION['form_data'] = $fd;
			$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			$this->sessClose();
			redirect();
		}

		if ($email === '' || mb_strlen($email, 'UTF-8') > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->sessWriteOpen();
			$_SESSION['error'] = 'Укажите корректный e-mail.';
			$fd = $data;
			unset($fd['password'], $fd['signup_token'], $fd['hp_field']);
			$_SESSION['form_data'] = $fd;
			$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			$this->sessClose();
			redirect();
		}

		if (mb_strlen($password, 'UTF-8') < 6 || mb_strlen($password, 'UTF-8') > 255) {
			$this->sessWriteOpen();
			$_SESSION['error'] = 'Пароль должен включать не менее 6 символов.';
			$fd = $data;
			unset($fd['password'], $fd['signup_token'], $fd['hp_field']);
			$_SESSION['form_data'] = $fd;
			$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			$this->sessClose();
			redirect();
		}

		if ($agreePersonalData !== 1) {
			$this->sessWriteOpen();
			$_SESSION['error'] = 'Вы должны дать согласие на обработку персональных данных.';
			$fd = $data;
			unset($fd['password'], $fd['signup_token'], $fd['hp_field']);
			$_SESSION['form_data'] = $fd;
			$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			$this->sessClose();
			redirect();
		}

		// === регистрация ===
		$user = new User();
		$user->load($data);

		if (!$user->validate($data) || !$user->checkUnique()) {
			$this->sessWriteOpen();
			$user->getErrors();
			$fd = $data;
			unset($fd['password'], $fd['signup_token'], $fd['hp_field']);
			$_SESSION['form_data'] = $fd;
			$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			$this->sessClose();
			redirect();
		}

		$newsletter = !empty($user->attributes['newsletter']) ? '1' : '0';
		$user->attributes['newsletter'] = $newsletter;

		$user->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
		$user->attributes['agree_personal_data'] = 1;
		$user->attributes['agree_date'] = date('Y-m-d H:i:s');

		// cookie accepted
		$cookieId = $_COOKIE['cookie_session_id'] ?? null;
		$cookieLogPath = ROOT . '/storage/logs/cookie_log.txt';
		$cookieAccepted = false;

		if ($cookieId && is_string($cookieId) && file_exists($cookieLogPath) && is_readable($cookieLogPath)) {
			$logContent = file_get_contents($cookieLogPath);
			if ($logContent !== false && strpos($logContent, $cookieId) !== false) {
				$cookieAccepted = true;
			}
		} elseif (isset($_COOKIE['cookie_accepted']) && $_COOKIE['cookie_accepted'] === 'true') {
			$cookieAccepted = true;
		}

		if ($cookieAccepted) {
			$user->attributes['cookie_accepted'] = 1;
			$user->attributes['cookie_agree_date'] = date('Y-m-d H:i:s');
		}

		if ($id = $user->save('user')) {
			$this->sessReadOpen();
			$actorId = $_SESSION['user']['id'] ?? null;
			$this->sessClose();

			AdminActivityLogger::log(
				AdminActivityLogger::GROUP_CLIENT,
				AdminActivityLogger::ACTION_CLIENT_SIGNUP,
				'user',
				(int)$id,
				$actorId ? (int)$actorId : (int)$id
			);

			$this->sessWriteOpen();
			unset($_SESSION['form_data']);
			$_SESSION['success'] = 'Пользователь зарегистрирован';
			$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			$this->sessClose();
		} else {
			$this->sessWriteOpen();
			$_SESSION['error'] = 'Ошибка регистрации.';
			$_SESSION['form_data'] = [
				'name' => $name,
				'email' => $email,
			];
			$_SESSION['signup_token'] = bin2hex(random_bytes(32));
			$this->sessClose();
		}

		redirect();
	}


	public function acceptCookieAction(){
		$this->sessReadOpen();
		$uid = $_SESSION['user']['id'] ?? null;
		$this->sessClose();

		if (!empty($uid)) {
			\R::exec("UPDATE user SET cookie_accepted = 1, cookie_agree_date = NOW() WHERE id = ?", [$uid]);
		}
		exit;
	}


    public function loginAction()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		if (empty($_POST)) {
			$this->setMeta('Вход');
			return;
		}

		$data = $_POST;

		// === honeypot (антибот) ===
		if (!empty($data['hp_field'])) {
			redirect();
		}

		// === доп. проверка loginok ===
		if (empty($data['loginok']) || $data['loginok'] !== md5(date('Y-m-d'))) {
			$this->sessWriteOpen();
			$_SESSION['error'] = 'Некорректный запрос. Обновите страницу и попробуйте ещё раз.';
			$this->sessClose();
			redirect();
		}

		// === базовая проверка email ===
		$email = isset($data['email']) ? trim((string)$data['email']) : '';
		$email = mb_strtolower($email, 'UTF-8');

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->sessWriteOpen();
			$_SESSION['error'] = 'Укажите корректный e-mail.';
			$this->sessClose();
			redirect();
		}

		$_POST['email'] = $email;

		// === логин ===
		$user = new User();
		$ok   = $user->login();

		$this->sessWriteOpen();
		if ($ok) {
			$_SESSION['success'] = 'Вы успешно авторизованы';
		} else {
			$_SESSION['error'] = 'Логин/пароль введены неверно';
		}
		$this->sessClose();

		redirect('cabinet');
	}

    public function logoutAction(){
		$this->sessWriteOpen();
		unset($_SESSION['user'], $_SESSION['cart']);
		$this->sessClose();
		redirect('/');
	}
	
	public function cabinetAction(){
        if(!User::checkAuth()) redirect();
        $this->setMeta('Личный кабинет');
    }

    public function editAction(){
        if(!User::checkAuth()) redirect('');
        if(!empty($_POST)){
            $user = new \app\models\admin\User();
            $data = $_POST;
            $data['id'] = $_SESSION['user']['id'];
            $data['role'] = $_SESSION['user']['role'];
            $user->load($data);
            if(!$user->attributes['password']){
                unset($user->attributes['password']);
            }else{
                $user->attributes['password'] = password_hash($user->attributes['password'], PASSWORD_DEFAULT);
            }
            if(!$user->validate($data) || !$user->checkUnique()){
                $user->getErrors();
                redirect();
            }
            if($user->update('user', $_SESSION['user']['id'])){
                foreach($user->attributes as $k => $v){
                    if($k != 'password') $_SESSION['user'][$k] = $v;
                }
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
        $this->setMeta('Изменение личных данных');
    }
	
	public function companyAction(){
        if(!User::checkAuth()) redirect('');
        if(!empty($_POST)){
            $company = new \app\models\admin\Company();
            $data = $_POST;
			$this->sessReadOpen();
            $data['user_id'] = $_SESSION['user']['id'];
			$this->sessClose();
			$data['tip'] = 1;
            $company->load($data);

            if(!$company->validate($data) || !$company->checkUnique()){
                $company->getErrors();
                redirect();
            }
            if($company->update('company', $data['comp_id'])){
                $this->sessReadOpen();
                $_SESSION['success'] = 'Изменения сохранены';
				$this->sessClose();
            }
            redirect();
        }
		$category = \R::getAll("SELECT * FROM category");
		$company = \R::findOne('company', 'user_id = ?', [$_SESSION['user']['id']]);
        $this->setMeta('Компания');
        $this->set(compact('company', 'category'));
        
    }

	public function ordersAction(){
		if (!User::checkAuth()) redirect('');

		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		$perpage = \ishop\App::$app->getProperty('pagination');

		$userId = (int)$_SESSION['user']['id'];

		$total = \R::count('order', "user_id = ?", [$userId]);

		$pagination = new \ishop\libs\Pagination($page, $perpage, $total);
		$start = $pagination->getStart();

		$orders = \R::getAll(
			"SELECT 
				o.id,
				o.inv,
				o.currency,
				o.date,
				o.update_at,
				o.status,
				COALESCE(SUM(op.price * op.qty), 0) AS sum,
				os.status_name
			FROM `order` o
			LEFT JOIN `order_product` op ON op.order_id = o.id
			LEFT JOIN `order_status` os ON os.id = o.status
			WHERE o.user_id = ?
			GROUP BY o.id, o.inv, o.currency, o.date, o.update_at, o.status, os.status_name
			ORDER BY o.id DESC
			LIMIT $start, $perpage",
			[$userId]
		);

		$this->setMeta('История заказов');
		$this->set(compact('orders', 'pagination', 'total', 'page'));
	}
	
	public function orderAction(){
		if (!User::checkAuth()) redirect('');

		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		if ($id <= 0) {
			redirect(PATH . '/user/orders');
		}

		$userId = (int)$_SESSION['user']['id'];

		$order = \R::getAll(
			"SELECT 
				o.id,
				p.img,
				p.alias,
				op.name,
				op.qty,
				op.price,
				op.article
			FROM `order` o
			JOIN `order_product` op ON o.id = op.order_id
			LEFT JOIN `product` p ON p.id = op.product_id
			WHERE o.user_id = ? AND op.order_id = ?",
			[$userId, $id]
		);

		$order_info = \R::findOne('order', 'user_id = ? AND id = ?', [$userId, $id]);

		if (!$order_info) {
			$this->setMeta('Просмотр заказа');
			$this->set(compact('order', 'order_info'));
			return;
		}

		$status = \R::findOne('order_status', 'id = ?', [$order_info->status]);

		$this->setMeta('Просмотр заказа');
		$this->set(compact('order', 'order_info', 'status', 'id'));
	}
	
	public function wishlistAction() {
        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }
		$this->sessReadOpen();
        $user_id = $_SESSION['user']['id'];
		$this->sessClose();
       
        // Пагинация
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perpage = App::$app->getProperty('pagination') ?? 20;

        $total = \R::count('product_wishlists', 'user_id = ?', [$user_id]);
        $pagination = new Pagination($page, $perpage, $total);
        $start = $pagination->getStart();

        // Выборка закладок с LIMIT
        $wishlists = \R::getAll("
            SELECT 
                pb.product_id, p.img, p.article, p.name, p.alias, p.price, 
                p.opt_price, p.category_id, p.quantity, p.stock_status_id, pb.id 
            FROM product_wishlists pb
            JOIN product p ON p.id = pb.product_id
            WHERE pb.user_id = ?
            ORDER BY pb.id DESC
            LIMIT ?, ?
        ", [$user_id, $start, $perpage]);

        $this->setMeta('Закладки');
        $this->set(compact('wishlists', 'pagination'));
    }


	public function pricelistAction(){
		if (!User::checkAuth()) redirect('');

		$product = [];
		$company = \R::findOne('company', 'user_id = ?', [$_SESSION['user']['id']]);

		if (!empty($_POST)) {
			$format     = trim($_POST['format'] ?? '');
			$actSelect  = trim($_POST['actSelect'] ?? '');
			$categoryId = (int)($_POST['category_id'] ?? 0);
			$brandId    = (int)($_POST['brand_id'] ?? 0);
			$article    = trim($_POST['article'] ?? '');

			if ($format === '1') {
				$categoryMap = [
					1  => [9, 18, 19, 20, 21, 22, 23, 24], // Индустриальные шины
					2  => [2],                             // Шины для квадроциклов
					25 => [31, 32, 33],                    // Камеры, ленты, кольца
					3  => [10, 11, 12, 13, 14, 15, 16, 17],// Фильтры
					4  => [26, 27, 28, 29, 30],            // Диски
				];

				$sql = "
					SELECT 
						p.category_id,
						p.article,
						p.model,
						p.name,
						p.quantity,
						p.alias,
						p.opt_price,
						p.price,
						b.name AS vendor
					FROM product p
					JOIN brand b ON p.brand_id = b.id
					WHERE p.hide = ?
				";

				$params = ['show'];

				switch ($actSelect) {
					case '1': // категория
					case '4': // категория + производитель
						if ($categoryId > 0 && isset($categoryMap[$categoryId])) {
							$catIds = $categoryMap[$categoryId];
							$placeholders = implode(',', array_fill(0, count($catIds), '?'));
							$sql .= " AND p.category_id IN ($placeholders)";
							$params = array_merge($params, $catIds);

							if ($actSelect === '4' && $brandId > 0) {
								$sql .= " AND p.brand_id = ?";
								$params[] = $brandId;
							}

							$sql .= " ORDER BY b.name ASC, p.name ASC";
							$product = \R::getAll($sql, $params);
						}
						break;

					case '2': // по производителю
						if ($brandId > 0) {
							$sql .= " AND p.brand_id = ? ORDER BY b.name ASC, p.name ASC";
							$params[] = $brandId;
							$product = \R::getAll($sql, $params);
						}
						break;

					case '3': // по артикулу
						if ($article !== '') {
							$sql .= " AND p.article = ? ORDER BY b.name ASC, p.name ASC";
							$params[] = $article;
							$product = \R::getAll($sql, $params);
						}
						break;

					case '5': // все товары
						$sql .= " ORDER BY b.name ASC, p.name ASC";
						$product = \R::getAll($sql, $params);
						break;
				}
			}
		}

		$this->setMeta('Прайс-лист');
		$this->set(compact('product', 'company'));
	}
	
	public function pdfcatalogAction(){
		if(!User::checkAuth()) redirect('');
		
		$this->setMeta('Каталог');
		
	}
	
	public function dogovorAction(){
		if(!User::checkAuth()) redirect('');
		$this->setMeta('Договор');
	}

	public function wishlistsAddAction()
	{
		if (!User::checkAuth()) {
			http_response_code(401);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['ok' => false, 'error' => 'not_auth']);
			exit;
		}

		$this->sessReadOpen();
		$user_id = (int)($_SESSION['user']['id'] ?? 0);
		$this->sessClose();

		$product_id = (int)($_GET['product_id'] ?? 0);
		if ($user_id <= 0 || $product_id <= 0) {
			http_response_code(400);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['ok' => false, 'error' => 'bad_params']);
			exit;
		}

		$exists = (int)\R::count('product_wishlists', 'product_id = ? AND user_id = ?', [$product_id, $user_id]);
		$added = false;

		if ($exists === 0) {
			\R::exec("INSERT INTO product_wishlists (product_id, user_id) VALUES (?, ?)", [$product_id, $user_id]);
			$added = true;
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['ok' => true, 'product_id' => $product_id, 'added' => $added]);
		exit;
	}

	public function wishlistsToggleAction()
	{
		if (!User::checkAuth()) {
			http_response_code(401);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['ok' => false, 'error' => 'not_auth']);
			exit;
		}

		$this->sessReadOpen();
		$user_id = (int)($_SESSION['user']['id'] ?? 0);
		$this->sessClose();

		$product_id = (int)($_REQUEST['product_id'] ?? 0);
		if ($user_id <= 0 || $product_id <= 0) {
			http_response_code(400);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['ok' => false, 'error' => 'bad_params']);
			exit;
		}

		$bm = \R::findOne('product_wishlists', 'product_id = ? AND user_id = ?', [$product_id, $user_id]);

		if ($bm) {
			\R::trash($bm);
			$state = 'removed';
		} else {
			\R::exec(
				"INSERT INTO product_wishlists (product_id, user_id) VALUES (?, ?)",
				[$product_id, $user_id]
			);
			$state = 'added';
		}

		$count = (int)\R::count('product_wishlists', 'user_id = ?', [$user_id]);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'ok' => true,
			'product_id' => $product_id,
			'state' => $state,
			'count' => $count,
		]);
		exit;
	}
	
	public function wishlistsDeleteAction()
	{
		if (!User::checkAuth()) { redirect('/'); return; }

		$this->sessReadOpen();
		$user_id = (int)($_SESSION['user']['id'] ?? 0);
		$this->sessClose();

		$id = (int)($_GET['id'] ?? 0);
		if ($id <= 0) { redirect('wishlists'); return; }

		$bm = \R::findOne('product_wishlists', 'id = ? AND user_id = ?', [$id, $user_id]);
		if ($bm) \R::trash($bm);

		$_SESSION['success'] = 'Закладка удалена';
		redirect('wishlists');
	}
	
	public function recoverAction()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->setMeta('Восстановление пароля');
			return;
		}

		// CSRF-токен
		$postToken    = $_POST['recover_token'] ?? '';
		$sessionToken = $_SESSION['recover_token'] ?? '';

		if (!$postToken || !$sessionToken || !hash_equals($sessionToken, $postToken)) {
			$_SESSION['error'] = 'Форма устарела, обновите страницу и попробуйте ещё раз.';
			$_SESSION['form_data'] = $_POST;
			redirect('user/recover');
		}

		// одноразовый токен
		unset($_SESSION['recover_token']);

		// honeypot
		if (!empty($_POST['hp_field'])) {
			// бот — молча уходим
			redirect('user/recover');
		}

		// E-mail
		$email = !empty($_POST['email']) ? trim($_POST['email']) : '';
		$email = mb_strtolower($email, 'UTF-8');

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$_SESSION['error'] = 'Укажите корректный e-mail.';
			$_SESSION['form_data'] = $_POST;
			redirect('user/recover');
		}

		// Ищем пользователя (но не палим, найден ли он пользователю)
		$user = \R::findOne('user', 'email = ?', [$email]);

		// Если такого пользователя нет — ничего страшного: просто покажем
		// "если такой email есть, мы отправили письмо" (анти user-enumeration)
		if ($user) {

			// Ограничение по частоте: не чаще, чем раз в N минут (например, 5)
			$now = time();
			$minInterval = 5 * 60; // 5 минут

			$last = \R::findOne('recover', 'email = ? ORDER BY id DESC', [$email]);
			if ($last && !empty($last['expire'])) {
				// expire ты используешь как время "смерти ссылки", просто проверим,
				// что сейчас не случилось слишком рано после последней заявки
				$secondsSinceLast = $now - ((int)$last['expire'] - (int)App::$app->getProperty('time_active_link'));
				if ($secondsSinceLast < $minInterval) {
					// Не шлём повторно письмо, но сообщение пользователю всё равно успех
					$_SESSION['success'] = 'Если такой e-mail зарегистрирован, ссылка на восстановление пароля уже отправлена.';
					redirect('user/recover');
				}
			}

			// Генерируем криптостойкий токен
			$token  = bin2hex(random_bytes(32)); // 64 символа
			$expire = $now + (int)App::$app->getProperty('time_active_link');

			// Вставка через плейсхолдеры
			\R::exec(
				"INSERT INTO recover (hash, expire, email) VALUES (?, ?, ?)",
				[$token, $expire, $email]
			);

			// Отправка писем
			$transport = (new Swift_SmtpTransport(
				App::$app->getProperty('smtp_host'),
				App::$app->getProperty('smtp_port'),
				App::$app->getProperty('smtp_protocol')
			))
				->setUsername(App::$app->getProperty('smtp_login'))
				->setPassword(App::$app->getProperty('smtp_password'));

			$mailer = new Swift_Mailer($transport);

			$namecomp  = App::$app->getProperty('shop_name');
			$tell_site = \ishop\App::options('option_telefon');
			$uname     = $user['name'];

			ob_start();
			require APP . '/views/' . TEMPLATE . '/mail/mail_recover.php';
			$body = ob_get_clean();

			$message_client = (new Swift_Message("Восстановление пароля на сайте " . $namecomp))
				->setFrom([App::$app->getProperty('smtp_login') => $namecomp])
				->setTo($email)
				->setBody($body, 'text/html');

			$message_admin = (new Swift_Message("Восстановление пароля на сайте " . $namecomp))
				->setFrom([App::$app->getProperty('smtp_login') => $namecomp])
				->setTo(App::$app->getProperty('admin_email'))
				->setBody($body, 'text/html');

			$mailer->send($message_client);
			$mailer->send($message_admin);
		}

		// В любом случае (есть пользователь или нет) — одно и то же сообщение
		$_SESSION['success'] = 'Если такой e-mail зарегистрирован, ссылка на восстановление пароля отправлена.';
		redirect('user/recover');
	}

	
	public function recoverPassAction()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->setMeta('Создание нового пароля');
			return;
		}

		$hash = !empty($_POST['hash']) ? trim($_POST['hash']) : '';
		$password = !empty($_POST['password']) ? trim($_POST['password']) : '';

		if ($hash === '') {
			$_SESSION['error'] = 'Некорректная ссылка для восстановления пароля.';
			redirect('user/recover');
		}

		$userModel = new User();
		$uhash = $userModel->get_user_hash($hash);

		if (!$uhash) {
			$_SESSION['error'] = 'Ссылка для восстановления пароля недействительна или устарела.';
			redirect('user/recover');
		}

		if ($password === '') {
			$_SESSION['error'] = 'Не введён новый пароль.';
			redirect('user/recover-pass?key=' . urlencode($hash));
		}

		// Минимальная проверка сложности пароля (можешь усилить по желанию)
		if (mb_strlen($password, 'UTF-8') < 6) {
			$_SESSION['error'] = 'Пароль должен быть не короче 6 символов.';
			redirect('user/recover-pass?key=' . urlencode($hash));
		}

		$passHash = password_hash($password, PASSWORD_DEFAULT);

		// Обновляем пароль и чистим recover через плейсхолдеры
		\R::exec("UPDATE user SET password = ? WHERE email = ?", [$passHash, $uhash['email']]);
		\R::exec("DELETE FROM recover WHERE email = ?", [$uhash['email']]);

		$user = \R::findOne('user', 'email = ?', [$uhash['email']]);
		if ($user) {
			AdminActivityLogger::log(AdminActivityLogger::GROUP_SYSTEM, AdminActivityLogger::ACTION_PASSWORD_RECOVER, 'user', (int)$user['id']);
		}

		$_SESSION['success'] = 'Пароль обновлён. Теперь вы можете войти в личный кабинет с новым паролем.';
		redirect('user/login');
	}
	
	public function zvonokAction(){
		if (!empty($_POST)) {
			$name    = trim($_POST['name'] ?? '');
			$phone   = trim($_POST['phone'] ?? '');
			$email   = trim($_POST['email'] ?? '');
			$comment = trim($_POST['comment'] ?? '');
			$title   = trim($_POST['title'] ?? 'Заказ обратного звонка');
			$agree   = !empty($_POST['agree']);

			if (!$agree) {
				$this->sessWriteOpen();
				$_SESSION['error'] = 'Необходимо согласие на обработку персональных данных.';
				$this->sessClose();
				redirect();
			}

			$this->sessReadOpen();
			$user_id = $_SESSION['user']['id'] ?? 0;
			$this->sessClose();

			$first = substr($phone, 0, 5);
			if ($first != "+7 (9") {
				$this->sessWriteOpen();
				$_SESSION['error'] = 'Запрос не обработан! Вы робот? Если нет, попробуйте заполнить форму обратной связи еще раз!';
				$this->sessClose();
				redirect();
			}

			$callback = \R::dispense('callback');
			$callback->user_id = (int)$user_id;
			$callback->topic = $title;
			$callback->phone = $phone;
			$callback->date_create = date('Y-m-d H:i:s');
			$callback->date_modified = '';
			$callback->user_modified = '';
			$callback->hide = '';

			// Если в таблице есть такие поля — раскомментируйте:
			// $callback->name = $name;
			// $callback->email = $email;
			// $callback->comment = $comment;

			$id = \R::store($callback);

			if ($id) {
				AdminActivityLogger::incoming(AdminActivityLogger::ACTION_CALLBACK, 'callback', (int)$id, (int)$user_id);

				setcookie("request-mig", "1house", time() + 3600, "/");

				$transport = (new Swift_SmtpTransport(
					App::$app->getProperty('smtp_host'),
					App::$app->getProperty('smtp_port'),
					App::$app->getProperty('smtp_protocol')
				))
					->setUsername(App::$app->getProperty('smtp_login'))
					->setPassword(App::$app->getProperty('smtp_password'));

				$mailer = new Swift_Mailer($transport);

				$namecomp = App::$app->getProperty('shop_name');
				$tell_site = \ishop\App::options('option_telefon');

				ob_start();
				require APP . '/views/' . TEMPLATE . '/mail/mail_callback.php';
				$body = ob_get_clean();

				$message_admin = (new Swift_Message("Заказ обратного звонка на сайте " . App::$app->getProperty('shop_name')))
					->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
					->setTo(App::$app->getProperty('admin_email'))
					->setBody($body, 'text/html');

				$mailer->send($message_admin);

				$this->sessWriteOpen();
				$_SESSION['success'] = 'Спасибо за заказ обратного звонка. Наш менеджер обязательно Вам позвонит по указаному номеру. Ожидайте звонка в рабочее время с ПН-ПТ 9:00 до 17:00 по МСК.';
				$this->sessClose();

				redirect();
			} else {
				$this->sessWriteOpen();
				$_SESSION['error'] = 'Ошибка отправки формы. Попробуйте ещё раз.';
				$this->sessClose();
				redirect();
			}
		}

		redirect();
	}

	public function catalogAction(){
		if (!empty($_POST)) {
			$name    = trim($_POST['name'] ?? '');
			$phone   = trim($_POST['phone'] ?? '');
			$email   = trim($_POST['email'] ?? '');
			$comment = trim($_POST['comment'] ?? '');
			$title   = trim($_POST['title'] ?? 'Запрос каталога');
			$agree   = !empty($_POST['agree']);

			if (!$agree) {
				$this->sessWriteOpen();
				$_SESSION['error'] = 'Необходимо согласие на обработку персональных данных.';
				$this->sessClose();
				redirect();
			}

			if (empty($phone) || empty($email)) {
				$this->sessWriteOpen();
				$_SESSION['error'] = 'Заполните обязательные поля.';
				$this->sessClose();
				redirect();
			}

			$first = substr($phone, 0, 5);
			if ($first != "+7 (9") {
				$this->sessWriteOpen();
				$_SESSION['error'] = 'Запрос не обработан! Вы робот? Если нет, попробуйте заполнить форму ещё раз.';
				$this->sessClose();
				redirect();
			}

			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->sessWriteOpen();
				$_SESSION['error'] = 'Укажите корректный email.';
				$this->sessClose();
				redirect();
			}

			$this->sessReadOpen();
			$user_id = $_SESSION['user']['id'] ?? 0;
			$this->sessClose();

			// Если хочешь хранить такие заявки в БД, лучше завести отдельную таблицу.
			// Пока делаем без записи в БД — только письмо админу.

			$transport = (new Swift_SmtpTransport(
				App::$app->getProperty('smtp_host'),
				App::$app->getProperty('smtp_port'),
				App::$app->getProperty('smtp_protocol')
			))
				->setUsername(App::$app->getProperty('smtp_login'))
				->setPassword(App::$app->getProperty('smtp_password'));

			$mailer = new Swift_Mailer($transport);

			$namecomp = App::$app->getProperty('shop_name');
			$tell_site = \ishop\App::options('option_telefon');

			$callback = \R::dispense('callback');
			$callback->user_id = (int)$user_id;
			$callback->topic = $title;
			$callback->phone = $phone;
			$callback->date_create = date('Y-m-d H:i:s');
			$callback->date_modified = '';
			$callback->user_modified = '';
			$callback->status = '0';
			$callback->hide = 'show';
			$catalogRequestId = (int)\R::store($callback);
			AdminActivityLogger::incoming(AdminActivityLogger::ACTION_CATALOG_REQUEST, 'callback', $catalogRequestId, (int)$user_id);

			ob_start();
			require APP . '/views/' . TEMPLATE . '/mail/mail_catalog.php';
			$body = ob_get_clean();

			$message_admin = (new Swift_Message("Запрос каталога на сайте " . App::$app->getProperty('shop_name')))
				->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
				->setTo(App::$app->getProperty('admin_email'))
				->setBody($body, 'text/html');

			$mailer->send($message_admin);

			// Если нужно сразу отправлять каталог клиенту на email —
			// раскомментируй и подставь ссылку на файл каталога.
			/*
			$catalogUrl = PATH . '/files/catalog.pdf';

			$message_user = (new Swift_Message("Каталог компании " . App::$app->getProperty('shop_name')))
				->setFrom([App::$app->getProperty('smtp_login') => App::$app->getProperty('shop_name')])
				->setTo($email)
				->setBody(
					'Спасибо за обращение! Каталог доступен по ссылке: <a href="' . $catalogUrl . '">' . $catalogUrl . '</a>',
					'text/html'
				);

			$mailer->send($message_user);
			*/

			$this->sessWriteOpen();
			$_SESSION['success'] = 'Спасибо! Ваша заявка на получение каталога отправлена. Наш менеджер свяжется с вами или отправит каталог на указанный email.';
			$this->sessClose();

			redirect();
		}

		redirect();
	}
	
}
