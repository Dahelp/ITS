<?php

namespace app\controllers;

use app\models\admin\Company;
use app\models\Cart;
use app\models\Order;
use app\models\User;
use app\services\admin\AdminActivityLogger;
use ishop\App;

class CartController extends AppController {

	private function sessOpenRW()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private function sessOpenRO()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private function sessClose()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    private function redirectCartView(int $code = 302): void
    {
        $this->sessClose();
        header('Location: ' . PATH . '/cart/view', true, $code);
        exit;
    }

    private function redirectProduct($product, int $code = 302): void
    {
        $alias = !empty($product->alias) ? (string)$product->alias : (string)$product->id;

        $this->sessClose();
        header('Location: ' . PATH . '/product/' . $alias, true, $code);
        exit;
    }

    private function gone410(): void
    {
        $this->sessClose();
        http_response_code(410);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow', true);
        echo '410 Gone';
        exit;
    }

    public function indexAction(): void
    {
        header('Location: ' . PATH . '/cart/view', true, 301);
        exit;
    }

    public function addAction()
    {
        $this->sessOpenRW();

        $id           = !empty($_GET['id']) ? (int)$_GET['id'] : 0;
        $qty          = !empty($_GET['qty']) ? (int)$_GET['qty'] : 1;
        $mod_id       = !empty($_GET['mod']) ? (int)$_GET['mod'] : 0;
        $modification = !empty($_GET['modification']) ? (int)$_GET['modification'] : 0;
        $max          = isset($_GET['max']) ? (int)$_GET['max'] : null;

        if ($id <= 0) {
            if ($this->isAjax()) {
                $this->loadView('cart_state');
                $this->sessClose();
                return;
            }

            $this->gone410();
        }

        $product = \R::findOne('product', 'id = ?', [$id]);

        if (!$product) {
            if ($this->isAjax()) {
                $this->loadView('cart_state');
                $this->sessClose();
                return;
            }

            $this->gone410();
        }

        if (!$this->isAjax()) {
            $this->redirectProduct($product, 302);
        }

        $cart = new Cart();
        $mod = null;

        if ($modification) {
            $sum_mods = 0;
            $modprice = '';

            $mods = \R::findAll('modification', 'product_id = ?', [$product->id]);

            foreach ($mods as $modi) {
                $sum_mods += (int)$modi->quantity;
                $modprice .= (string)$modi->price . ',';
            }

            $max = (int)$product->quantity + $sum_mods;

            $pricesStr = (string)$product->price . ',' . rtrim($modprice, ',');
            $pricesArr = preg_split('/\s*,\s*/', trim($pricesStr), -1, PREG_SPLIT_NO_EMPTY);
            $pricesArr = array_map('floatval', $pricesArr);

            $max_price = !empty($pricesArr) ? max($pricesArr) : (float)$product->price;

            $mod = (object)[
                'id'                => $mod_id ?: 0,
                'name_modification' => 'unified',
                'price'             => $max_price,
                'article'           => (string)$product->article,
                'unit'              => 'шт',
            ];

            $cart->addToCart($product, $qty, $max, $mod);
        } else {
            if ($mod_id > 0) {
                $mod = \R::findOne(
                    'modification',
                    'id = ? AND product_id = ?',
                    [$mod_id, $id]
                );

                if (!$mod) {
                    $mod = null;
                }
            }

            $cart->addToCart($product, $qty, $max, $mod);
        }

        $this->loadView('cart_state');
        $this->sessClose();
        return;
    }

    public function addcompleteAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $productIdsRaw   = !empty($_GET['id']) ? trim($_GET['id']) : '';
        $completeCount   = !empty($_GET['qty']) ? (int)$_GET['qty'] : 1;
        $modId           = !empty($_GET['mod']) ? (int)$_GET['mod'] : 0;
        $isCompletePrice = !empty($_GET['complete']) ? (int)$_GET['complete'] : 0;
        $completeId      = !empty($_GET['complete_id']) ? (int)$_GET['complete_id'] : 0;

        if ($productIdsRaw === '' || $completeCount < 1) {
            $this->loadView('cart_state');
            $this->sessClose();
            return;
        }

        $productIds = array_filter(array_map('intval', explode('-', $productIdsRaw)));
        $cart = new Cart();

        foreach ($productIds as $productId) {
            $product = \R::findOne('product', 'id = ?', [$productId]);
            if (!$product) {
                continue;
            }

            $mod = null;
            if ($modId > 0) {
                $mod = \R::findOne(
                    'modification',
                    'id = ? AND product_id = ?',
                    [$modId, $productId]
                );
            }

            $completeRow = null;

            if ($completeId > 0) {
                $completeRow = \R::findOne(
                    'plagins_complete_product',
                    'product_id = ? AND complete_id = ?',
                    [$productId, $completeId]
                );
            }

            if (!$completeRow) {
                $completeRow = \R::findOne(
                    'plagins_complete_product',
                    'product_id = ?',
                    [$productId]
                );
            }

            $itemsPerComplete = 1;
            if ($completeRow && !empty($completeRow->qty) && (int)$completeRow->qty > 0) {
                $itemsPerComplete = (int)$completeRow->qty;
            }

            $requestedQty = $completeCount * $itemsPerComplete;
            $availableQty = (int)$product->quantity;
            $finalQty = min($requestedQty, $availableQty);

            if ($finalQty < 1) {
                continue;
            }

            if ($isCompletePrice === 1 && $completeRow) {
                $product->price_complete = $completeRow->price;
                $product->price_discount = $completeRow->discount;
            }

            $cart->addToCartComplete(
                $product,
                $finalQty,
                $itemsPerComplete,
                $mod,
                $completeId,
                $isCompletePrice === 1
            );
        }

        if ($completeId > 0) {
            $cart->refreshCompleteState($completeId);
        }

        $this->loadView('cart_state');
        $this->sessClose();
        return;
    }

    public function showAction(): void
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        (new Cart())->reconcileAvailability();
        $this->loadView('cart_state');
        $this->sessClose();
        return;
    }

    public function deleteAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $id = !empty($_GET['id']) ? $_GET['id'] : null;

        if ($id !== null && isset($_SESSION['cart'][$id])) {
            $cart = new Cart();
            $cart->deleteItem($id);
        }

        $this->loadView('cart_state');
        $this->sessClose();
        return;
    }

    public function deletecompleteAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        $completeId = !empty($_GET['complete_id']) ? (int)$_GET['complete_id'] : 0;

        if ($id !== null && isset($_SESSION['cart'][$id])) {
            $cart = new Cart();
            $cart->deletecompleteItem($id, $completeId);
        }

        $this->loadView('cart_state');
        $this->sessClose();
        return;
    }

    public function promocartAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $val = !empty($_GET['val']) ? $_GET['val'] : null;

        if ($val) {
            $cart = new Cart();
            $res = $cart->promocartItem($val);
            $_SESSION['promo_state'] = $res ?: ['ok' => 0, 'msg' => ''];
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    public function clearpromoAction(): void
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        unset($_SESSION['promocart']);

        $cart = new Cart();
        if (method_exists($cart, 'clearpromoItem')) {
            $cart->clearpromoItem();
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    public function restoreAction(): void
    {
        $this->sessOpenRW();
        if (!$this->isAjax()) $this->redirectCartView(302);
        $restored = (new Cart())->restoreRemoved((string)($_GET['token'] ?? ''));
        if (!$restored) $_SESSION['cart.quantity_notice'] = 'Не удалось восстановить товар. Проверьте наличие и добавьте его из каталога.';
        $this->loadView('cart_table');
        $this->sessClose();
    }

    public function deletecartAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $id = !empty($_GET['id']) ? $_GET['id'] : null;

        if ($id !== null && isset($_SESSION['cart'][$id])) {
            $cart = new Cart();
            $cart->rememberRemoved((string)$id);
            $cart->deleteItem($id);
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    public function deletecartcompleteAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        $completeId = !empty($_GET['complete_id']) ? (int)$_GET['complete_id'] : 0;

        if ($id !== null && isset($_SESSION['cart'][$id])) {
            $cart = new Cart();
            $cart->rememberRemoved((string)$id);
            $cart->deletecompleteItem($id, $completeId);
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    public function quantityAction(): void
    {
        $this->sessOpenRW();
        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }
        $id = (string)($_GET['id'] ?? '');
        $qty = filter_var($_GET['qty'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($qty !== false && isset($_SESSION['cart'][$id])) {
            (new Cart())->setQuantity($id, $qty);
        }
        $this->loadView(($_GET['ctx'] ?? '') === 'product' ? 'cart_state' : 'cart_table');
        $this->sessClose();
    }

    public function pluscartAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $id = !empty($_GET['id']) ? $_GET['id'] : null;

        if ($id !== null && isset($_SESSION['cart'][$id])) {
            $cart = new Cart();
            $cart->pluscartItem($id);
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    public function pluscartcompleteAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        $completeId = !empty($_GET['complete_id']) ? (int)$_GET['complete_id'] : 0;

        if ($id !== null && isset($_SESSION['cart'][$id])) {
            $cart = new Cart();
            $cart->pluscartcompleteItem($id, $completeId);
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    public function minuscartAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $id = !empty($_GET['id']) ? $_GET['id'] : null;

        if ($id !== null && isset($_SESSION['cart'][$id])) {
            $cart = new Cart();
            $cart->minuscartItem($id);
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    public function minuscartcompleteAction()
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $id = !empty($_GET['id']) ? $_GET['id'] : null;
        $completeId = !empty($_GET['complete_id']) ? (int)$_GET['complete_id'] : 0;

        if ($id !== null && isset($_SESSION['cart'][$id])) {
            $cart = new Cart();
            $cart->minuscartcompleteItem($id, $completeId);
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    private function requireCartActionUser(): int
    {
        $this->sessOpenRW();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$userId ||
            empty($_SESSION['checkout_token']) ||
            !hash_equals((string)$_SESSION['checkout_token'], (string)($_POST['checkout_token'] ?? ''))) {
            $_SESSION['cart.action_notice'] = 'Войдите в личный кабинет и обновите страницу корзины перед действием.';
            $this->redirectCartView(303);
        }
        return $userId;
    }

    public function saveLaterAction(): void
    {
        $userId = $this->requireCartActionUser();
        $ids = [];
        foreach ($_SESSION['cart'] ?? [] as $row) {
            $id = (int)($row['product_id'] ?? 0);
            if ($id > 0) $ids[$id] = $id;
        }
        if (!$ids) $this->redirectCartView(303);
        try {
            \R::begin();
            foreach ($ids as $id) {
                if (!\R::count('product_wishlists', 'product_id = ? AND user_id = ?', [$id, $userId])) {
                    \R::exec('INSERT INTO product_wishlists (product_id, user_id) VALUES (?, ?)', [$id, $userId]);
                }
            }
            \R::commit();
        } catch (\Throwable $e) {
            \R::rollback();
            error_log('Cart save later failed: ' . $e->getMessage());
            $_SESSION['cart.action_notice'] = 'Не удалось сохранить товары. Корзина сохранена, попробуйте позже.';
            $this->redirectCartView(303);
        }
        $this->emptyCart();
        $_SESSION['cart.action_notice'] = 'Товары перенесены в избранное. Количество и состав комплектов нужно будет выбрать заново.';
        $this->redirectCartView(303);
    }

    public function repeatAction(): void
    {
        $userId = $this->requireCartActionUser();
        // Accept a selected order only after checking ownership; prices remain current.
        $selectedId = (int)($_POST['order_id'] ?? 0);
        $orderId = $selectedId > 0 ? (int)\R::getCell(
            'SELECT id FROM `order` WHERE user_id = ? AND id = ?', [$userId, $selectedId]
        ) : 0;
        if (!$orderId) {
            $_SESSION['cart.action_notice'] = 'Заказ не найден или недоступен для повторения.';
            $this->redirectCartView(303);
        }
        $snapshot = $_SESSION;
        try {
            $rows = \R::getAll('SELECT product_id, article, SUM(qty) AS qty FROM order_product WHERE order_id = ? GROUP BY product_id, article', [$orderId]);
            $result = (new \app\services\CartRepeat())->addRows($rows, new Cart());
            unset($_SESSION['cart.quantity_notice']);
            $_SESSION['cart.action_notice'] = 'Заказ №' . $orderId . ': добавлено ' . $result['added'] . ' шт. по текущим ценам. ' .
                'Пропущено позиций: ' . $result['skipped'] . '; ограничено остатком: ' . $result['limited'] . '. Проверьте корзину перед оформлением.';
        } catch (\Throwable $e) {
            $_SESSION = $snapshot;
            error_log('Cart repeat failed: ' . $e->getMessage());
            $_SESSION['cart.action_notice'] = 'Не удалось повторить заказ. Исходная корзина сохранена, попробуйте позже.';
        }
        $this->redirectCartView(303);
    }

    private function emptyCart(): void
    {
        foreach (['cart','cart.qty','cart.sum','cart.weight','cart.volume','cart.currency',
                  'promocart','promo_state','cart.notice','cart.quantity_notice','cart.undo'] as $key) {
            unset($_SESSION[$key]);
        }
        if (isset($_SESSION['form_data']['checkout_token']) || isset($_SESSION['form_data']['dostavka_id'])) {
            unset($_SESSION['form_data']);
        }
        $_SESSION['checkout_draft_generation'] = bin2hex(random_bytes(8));
    }

    public function clearAction(): void
    {
        $this->sessOpenRW();
        if (!$this->isAjax()) $this->redirectCartView(302);
        $this->emptyCart();
        $this->loadView('cart_table');
        $this->sessClose();
    }

    public function viewAction()
    {
        $this->sessOpenRW();
        (new Cart())->reconcileAvailability();

        if (empty($_SESSION['checkout_token'])) {
            $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
        }

        $this->setMeta(
            'Корзина',
            'Корзина',
            '',
            (string)App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            PATH . '/cart/view'
        );

        $this->data['checkoutLoginResume'] = null;
        if (!empty($_SESSION['checkout_login_resume'])) {
            $this->data['checkoutLoginResume'] = $_SESSION['checkout_login_resume'];
            unset($_SESSION['checkout_login_resume']);
        }
        $this->data['checkoutFormData'] = [];
        if (isset($_SESSION['form_data']['checkout_token']) || isset($_SESSION['form_data']['dostavka_id'])) {
            $this->data['checkoutFormData'] = $_SESSION['form_data'];
            unset($_SESSION['form_data']);
        }

        $accountGroup = !empty($_SESSION['user']['id']) ? \R::getCell('SELECT `groups` FROM user WHERE id=?', [(int)$_SESSION['user']['id']]) : 0;
        $this->data['checkoutCustomerGroup'] = \app\services\CheckoutPayment::group($accountGroup, 0);

        $this->sessClose();
    }

    public function itemsAction(): void
    {
        $this->layout = false;
        $this->loadView('cart_items_readonly');
    }
        
	public function innAction(){
		if($_GET) {
			
			if (session_status() === PHP_SESSION_ACTIVE) {
				session_write_close();
			}

			$client = new \GuzzleHttp\Client();
			$inn = is_string($_GET['inn'] ?? null) ? trim($_GET['inn']) : '';
            if (!preg_match('/^(?:[0-9]{10}|[0-9]{12})$/D', $inn)) { http_response_code(400); die; }
            try {	
			$response = $client->request('GET', 'http://84.47.156.246:8082/itscentr/hs/CompanyListFrom1C/getCompanyFrom1C?inn='.$inn.'',

			 [
				'connect_timeout' => 3, 'timeout' => 10,
                'auth' => [
					'GetCompanyByINN',
					'GetCompanyByINN'
				]
			]

			);
            $response = json_decode((string)$response->getBody(), true);
            if (empty($response['company']['companyFields']['Fullname'])) {
                http_response_code(404); die;
            }
            } catch (\Throwable $e) { http_response_code(503); die; }
            $response['company']['companyFields'] += ['shortName'=>'','CodeKPP'=>'','CodeOGRN'=>''];
            $response['company']['companyAddresses'][0]['printForm'] = $response['company']['companyAddresses'][0]['printForm'] ?? '';
            $response['company']['companyContacts'][0]['contact'] = ($response['company']['companyContacts'][0]['contact'] ?? []) + ['jobName'=>'Руководитель','f'=>'','i'=>'','o'=>''];
            header('Cache-Control: no-store');

			echo "<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"comp_name\">Название компании</label>
					<input type=\"text\" name=\"comp_name\" class=\"form-control\" id=\"comp_name\" value=\"".htmlspecialchars($response["company"]["companyFields"]["Fullname"])."\">
					<input type=\"hidden\" name=\"comp_short_name\" class=\"form-control\" id=\"comp_short_name\" value=\"".htmlspecialchars($response["company"]["companyFields"]["shortName"])."\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"url_address\">Юр.адрес</label>
					<input type=\"text\" name=\"url_address\" class=\"form-control\" id=\"url_address\" value=\"".htmlspecialchars($response["company"]["companyAddresses"][0]["printForm"])."\">
					<input type=\"hidden\" name=\"postal_address\" class=\"form-control\" id=\"postal_address\" value=\"".htmlspecialchars($response["company"]["companyAddresses"][0]["printForm"])."\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"dir_name\">".htmlspecialchars($response["company"]["companyContacts"][0]["contact"]["jobName"])."</label>
					<input type=\"text\" name=\"dir_name\" class=\"form-control\" id=\"dir_name\" value=\"".htmlspecialchars($response["company"]["companyContacts"][0]["contact"]["f"])." ".htmlspecialchars($response["company"]["companyContacts"][0]["contact"]["i"])." ".htmlspecialchars($response["company"]["companyContacts"][0]["contact"]["o"])."\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"kpp\">КПП</label>
					<input type=\"text\" name=\"kpp\" class=\"form-control\" id=\"kpp\" value=\"".htmlspecialchars($response["company"]["companyFields"]["CodeKPP"])."\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"ogrn\">ОГРН</label>
					<input type=\"text\" name=\"ogrn\" class=\"form-control\" id=\"ogrn\" value=\"".htmlspecialchars($response["company"]["companyFields"]["CodeOGRN"])."\">
				</div>
				<p></p>			
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"bank\">Наименование банка</label>
					<input type=\"text\" name=\"bank\" class=\"form-control\" id=\"bank\" value=\"\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"raschet\">Расчётный счёт</label>
					<input type=\"text\" name=\"raschet\" class=\"form-control\" id=\"raschet\" value=\"\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"korschet\">Кор. счёт</label>
					<input type=\"text\" name=\"korschet\" class=\"form-control\" id=\"korschet\" value=\"\">
				</div>
				<p></p>
				<div class=\"col-sm-12\">
					<label class=\"form-label\" for=\"bik\">БИК</label>
					<input type=\"text\" name=\"bik\" class=\"form-control\" id=\"bik\" value=\"\">
				</div>";							
			
			die;
		}
	}
	
    public function loginCheckoutAction(): void
    {
        $this->sessOpenRW();
        $token = (string)($_POST['checkout_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$token || !hash_equals((string)($_SESSION['checkout_token'] ?? ''), $token)) $this->redirectCartView(303);
        $_SESSION['checkout_login'] = ['fields'=>\app\services\CheckoutAccount::fields($_POST), 'step'=>max(1,min(3,(int)($_POST['checkout_step'] ?? 1))), 'time'=>time(), 'attachment'=>!empty($_POST['checkout_attachment'])];
        $this->sessClose();
        redirect(PATH . '/user/login?checkout=1');
    }

    public function returnCheckoutAction(): void
    {
        $this->sessOpenRW();
        if (!empty($_SESSION['checkout_login']) && $_SESSION['checkout_login']['time'] > time()-3600) {
            $_SESSION['checkout_login_resume'] = $_SESSION['checkout_login'];
        }
        unset($_SESSION['checkout_login']);
        $this->redirectCartView(303);
    }

    public function completeAction(): void
    {
        $this->sessOpenRW();
        if (empty($_SESSION['checkout_result']) || $_SESSION['checkout_result']['time'] < time()-86400) $this->redirectCartView();
        $this->data['result'] = $_SESSION['checkout_result'];
        if (empty($_SESSION['setup_resend_token'])) $_SESSION['setup_resend_token']=bin2hex(random_bytes(32));
        $this->setMeta('Заказ принят');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        $this->sessClose();
    }

    public function checkoutAction()
{
    // 1) Сессия
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Разрешаем только POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect();
    }

    // ========= ANTI-BOT: CSRF / checkout token =========
    $postToken    = $_POST['checkout_token'] ?? '';
    $sessionToken = $_SESSION['checkout_token'] ?? '';

    if (!$postToken || !$sessionToken || !hash_equals($sessionToken, $postToken)) {
		$_SESSION['error'] = 'Форма устарела, обновите страницу и попробуйте ещё раз.';
		$_SESSION['form_data'] = $_POST;
		$_SESSION['checkout_token'] = bin2hex(random_bytes(32));
		session_write_close();
		redirect();
	}

    // Токен одноразовый
    if ((new Cart())->reconcileAvailability()) {
        $_SESSION['error'] = 'Наличие или условия скидки изменились. Стоимость и количество обновлены. Проверьте корзину перед оформлением.';
        $_SESSION['form_data'] = $_POST;
        $this->redirectCartView(303);
    }
    unset($_SESSION['checkout_token']);

    // ========= ANTI-BOT: honeypot =========
    if (!empty($_POST['hp_field'])) {
        // 99% бот – тихо уходим
        session_write_close();
        redirect();
    }

    if (empty($_POST['checkout_legal_agree'])) {
        $_SESSION['error'] = 'Вы должны принять условия и дать согласие на обработку персональных данных.';
        $_SESSION['form_data'] = $_POST;
        $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
        session_write_close();
        redirect();
    }

    // ========= Проверка корзины =========
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['error']     = 'Корзина пуста. Добавьте товары и оформите заказ заново.';
        $_SESSION['form_data'] = $_POST;
        session_write_close();
        redirect();
    }

    // ========= Авторизация / пользователь из сессии =========
    $isAuth      = \app\models\User::checkAuth();
    $sessionUser = $isAuth ? ($_SESSION['user'] ?? []) : [];

    // ========= Базовые данные из формы + fallback к сессии =========
    $data    = $_POST;

    // e-mail: сначала из формы, если пусто или некорректен — берём из сессии авторизованного
    $email = isset($data['email']) ? mb_strtolower(trim((string)$data['email']), 'UTF-8') : '';
    if ($isAuth) $email = (string)$sessionUser['email'];
    if (($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) && !empty($sessionUser['email'])) {
        $email = trim((string)$sessionUser['email']);
    }

    // телефон: сначала из формы, если пусто — берём из профиля
    $telefon = isset($data['telefon']) ? trim((string)$data['telefon']) : '';
    if ($telefon === '' && !empty($sessionUser['telefon'])) {
        $telefon = trim((string)$sessionUser['telefon']);
    }

    // --- функции нормализации/проверки телефона (как в ProductController) ---
    $normalizeDigits = static function (string $raw): string {
        // только цифры
        return preg_replace('/\D+/', '', $raw);
    };
    $isValidRuMobile = static function (string $raw) use ($normalizeDigits): bool {
        $d = $normalizeDigits($raw);
        // допускаем 8XXXXXXXXXX -> логически считаем как 7XXXXXXXXXX
        if (strlen($d) !== 11) return false;
        if ($d[0] === '8') {
            $d[0] = '7';
        }
        return ($d[0] === '7' && $d[1] === '9'); // российский мобильный 79*********
    };

    // ========= Итоговая проверка e-mail =========
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error']     = 'Укажите корректный e-mail.';
        $_SESSION['form_data'] = $data;
        session_write_close();
        redirect();
    }

    // ========= Проверка телефона =========
    if (!$isValidRuMobile($telefon)) {
        $_SESSION['error']     = 'Укажите корректный номер мобильного телефона РФ в формате +7 9ХХ ХХХ-ХХ-ХХ.';
        $_SESSION['form_data'] = $data;
        session_write_close();
        redirect();
    }

    // ========= Проверка способа обратной связи =========
    $formCallback = isset($_POST['form_callback']) ? trim((string)$_POST['form_callback']) : '';
    $allowedCallback = ['MAX', 'E-mail', 'Обратный звонок'];

    if ($formCallback === '' || !in_array($formCallback, $allowedCallback, true)) {
        $_SESSION['error']     = 'Выберите способ обратной связи.';
        $_SESSION['form_data'] = $data;
        $_SESSION['form_data']['form_callback'] = $formCallback;
        session_write_close();
        redirect();
    }

    if (!$isAuth && (trim((string)($_POST['name'] ?? '')) === '' || mb_strlen((string)($_POST['name'] ?? '')) > 255 || mb_strlen($email)>150)) {
        $_SESSION['error']='Укажите ФИО (до 255 символов) и email (до 150 символов).';
        $_SESSION['form_data']=\app\services\CheckoutAccount::fields($_POST);
        $_SESSION['checkout_token']=bin2hex(random_bytes(32));
        $this->redirectCartView(303);
    }

    // Validate payment before creating a customer or an order.
    $accountGroup = $isAuth && !empty($sessionUser['id'])
        ? \R::getCell('SELECT `groups` FROM user WHERE id=?', [(int)$sessionUser['id']]) : 0;
    $paymentGroup = \app\services\CheckoutPayment::group($accountGroup, $_POST['groups'] ?? null);
    $paymentDelivery = \R::getRow('SELECT id,name FROM dostavka WHERE id=?', [(int)($_POST['dostavka_id'] ?? 0)]);
    try {
        $checkoutMode=$isAuth ? 'account' : \app\services\CheckoutAccount::mode($_POST['checkout_mode']??'guest');
        if (!$paymentDelivery) throw new \DomainException('Выберите способ получения.');
        $paymentMethod = \app\services\CheckoutPayment::validate($_POST['payment_method'] ?? '', $paymentGroup, \app\services\CheckoutPayment::pickup((string)$paymentDelivery['name']));
        $companyFields = \app\services\CheckoutCompany::fields($_POST);
        if ($paymentGroup===4 && $isAuth) {
            $savedCompany=\R::findOne('company','user_id = ?',[(int)$sessionUser['id']]);
            if ($savedCompany) foreach (array_keys(\app\services\CheckoutCompany::LABELS) as $key) {
                if ($companyFields[$key]==='') $companyFields[$key]=(string)($savedCompany[$key]??'');
            }
        }
        $hasCompanyFile=!empty($_FILES['rekvizity']) && (int)$_FILES['rekvizity']['error']===UPLOAD_ERR_OK;
        if (isset($_FILES['rekvizity']['error']) && !in_array((int)$_FILES['rekvizity']['error'],[UPLOAD_ERR_OK,UPLOAD_ERR_NO_FILE],true)) throw new \DomainException('Не удалось загрузить файл реквизитов. Прикрепите его повторно.');
        \app\services\CheckoutCompany::validate($companyFields,$paymentGroup===4,$hasCompanyFile);
        $pickupBranch = \app\services\CheckoutPayment::pickup((string)$paymentDelivery['name'])
            ? \app\services\CheckoutPickup::resolve(\R::getAll("SELECT branch_id,branch_name FROM branch_office WHERE hide='show'"), $_POST['branch_id'] ?? '') : null;
    } catch (\DomainException $e) {
        $_SESSION['error'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST;
        $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
        $this->redirectCartView(303);
    }

    // ====== Получаем / создаём пользователя ======
    \app\services\CheckoutAccount::ensureSchema();
    // Run compatibility DDL before the order transaction (MySQL DDL commits).
    \app\services\CompleteEngagement::ensureSchema();
    \R::begin();
    try {
    $newAccount = false;
    $user_id = null;
    $comp_id = null;
    $groups  = null;

    if ($isAuth && !empty($sessionUser['id'])) {
        // Уже авторизован — используем данные из сессии
        $user_id = (int)$sessionUser['id'];

        // Подтянем полную запись пользователя
        $usok = \R::load('user', $user_id);
        if ($usok && $usok->id) {
            $comp_id = $usok->comp_id ?? null;
            $groups  = $usok->groups ?? null;
        }
    } else {
        // Не авторизован — ищем по e-mail или создаём нового
        $usok = \R::findOne('user', 'email = ?', [$email]);

        if ($usok && !empty($usok['id'])) {
            // Пользователь найден по e-mail
            $user_id = (int)$usok['id'];
            // Email routes the order to its mailbox owner; it grants no profile access.
            // Never inherit company, customer terms or manager from an unauthenticated match.
            $comp_id = null;
            $groups = $paymentGroup;
        } else {
            // Регистрация нового пользователя (кабинет создаётся автоматически)
            if (!$isAuth) {
                $user = new \app\models\User();
                $data = $_POST;
                $data['email'] = $email;
                $data['password'] = bin2hex(random_bytes(32));
                $data['role'] = 'user';
                $data['groups'] = $paymentGroup;
                $data['newsletter'] = '0';
                $user->load($data);
                if (!$user->validate($data)) {
                    $user->getErrors();
                    $_SESSION['form_data'] = $data;
                    session_write_close();
                    redirect();
                } else {
                    $user->attributes['password'] = password_hash($user->attributes['password'], PASSWORD_DEFAULT);

                    // Согласие на обработку персональных данных
                    $user->attributes['agree_personal_data'] = 1;
                    $user->attributes['agree_date'] = date('Y-m-d H:i:s');

                    // Согласие на cookie
                    if (isset($_COOKIE['cookie_accepted']) && $_COOKIE['cookie_accepted'] === 'true') {
                        $user->attributes['cookie_accepted'] = 1;
                        $user->attributes['cookie_agree_date'] = date('Y-m-d H:i:s');
                    } else {
                        $cookieId      = $_COOKIE['cookie_session_id'] ?? null;
                        $cookieLogPath = ROOT . '/storage/logs/cookie_log.txt';
                        if ($cookieId && file_exists($cookieLogPath)) {
                            $logContent = file_get_contents($cookieLogPath);
                            if (strpos($logContent, $cookieId) !== false) {
                                $user->attributes['cookie_accepted'] = 1;
                                $user->attributes['cookie_agree_date'] = date('Y-m-d H:i:s');
                            }
                        }
                    }

                    if (!$user_id = $user->save('user')) {
                        $_SESSION['error']     = 'Ошибка при регистрации пользователя.';
                        $_SESSION['form_data'] = $data;
                        session_write_close();
                        redirect();
                    }
                    $newAccount = true;
                    \R::exec('INSERT INTO checkout_account_access (user_id) VALUES (?)', [(int)$user_id]);
                    AdminActivityLogger::incoming(
                        AdminActivityLogger::ACTION_CLIENT_SIGNUP,
                        'user',
                        (int)$user_id,
                        (int)$user_id
                    );
                }
            }
			}
    }

    // ========= Сохранение заказа =========
    $data['user_id'] = isset($user_id)
        ? $user_id
        : ($_SESSION['user']['id'] ?? null);

    // A guest email match cannot change the mailbox owner's company.
    if ($paymentGroup===4 && ($isAuth || $newAccount)) {
        $savedCompanyId=\app\services\CheckoutCompany::saveForOwner((int)$data['user_id'],$companyFields,$_POST);
        if ($savedCompanyId) $comp_id=$savedCompanyId;
    }
    // comp_id — либо из найденного пользователя, либо null
    $data['comp_id'] = !empty($comp_id) ? $comp_id : null;

    $data['comp_short_name'] = trim((string)($_POST['comp_name'] ?? $_POST['comp_short_name'] ?? ''));
    $data['inn']             = !empty($_POST['inn']) ? $_POST['inn'] : '';
    $customerNote = is_string($_POST['note'] ?? null) ? trim($_POST['note']) : '';
    $data['note'] = $customerNote;
    $companySummary=\app\services\CheckoutCompany::text($companyFields);
    if ($companySummary!=='') $data['note'] .= ($data['note']!=='' ? "\n\n" : '')."Реквизиты и цель покупки:\n".$companySummary;
    $data['dostavka_id']     = !empty($_POST['dostavka_id']) ? $_POST['dostavka_id'] : '';
    $data['address']         = !empty($_POST['address']) ? $_POST['address'] : '';
    $data['transport_id']    = !empty($_POST['transport_id']) ? $_POST['transport_id'] : '';

    $city_name = !empty($_POST['city_name']) ? $_POST['city_name'] : '';
    $cit       = \R::findOne('cities', 'city_name = ?', [$city_name]);
    $data['city_id'] = !empty($cit['city_id']) ? $cit['city_id'] : '';

    $data['branch_id'] = $pickupBranch !== null ? (string)$pickupBranch['branch_id'] : '';

    $data['groups'] = $paymentGroup;
    $data['payment_method'] = $paymentMethod;

    $user_email = $_SESSION['user']['email'] ?? $email;

    $data['form_callback'] = $formCallback;
    $promo = trim((string)($_SESSION['promocart'] ?? ''));

    // Флаг наличия прикреплённых реквизитов
    if (!empty($_FILES['rekvizity']) && $_FILES['rekvizity']['error'] === UPLOAD_ERR_OK) {
        $rekvizity = '1';
    } else {
        $rekvizity = '0';
    }

    $usm = $isAuth ? \R::load('user', (int)$user_id) : [
        'name'=>(string)($_POST['name'] ?? ''), 'telefon'=>$telefon,
        'groups'=>$paymentGroup, 'admin_id'=>0
    ];

    // Если это юр. лицо
    if ($isAuth && !empty($data['groups']) && (int)$data['groups'] === 4) {
        $comp = \R::findOne('company', 'user_id = ?', [$data['user_id']]);
        if ($comp && !empty($comp['nds'])) {
            $data['nds'] = $comp['nds'];
        }
    }

    if (!empty($usm['admin_id']) && $usm['admin_id'] != '0') {
        $data['admin_id'] = $usm['admin_id'];
    } else {
        $data['admin_id'] = 0;
    }

    // Непосредственно сохранение заказа
    $order_id = \app\models\Order::saveOrder($data);

    \R::exec('INSERT INTO checkout_order_contact (order_id,email,name,telefon) VALUES (?,?,?,?)', [$order_id,$user_email,(string)$usm['name'],(string)$usm['telefon']]);
    \app\services\CompanyCardData::store((int)$order_id,$companyFields,['name'=>(string)$usm['name'],'telefon'=>(string)$usm['telefon'],'email'=>(string)$user_email,'has_attachment'=>$hasCompanyFile]);
    \R::commit();
    } catch (\Throwable $e) {
        \R::rollback();
        error_log('CR10: checkout persistence failed ('.get_class($e).' at '.$e->getFile().':'.$e->getLine().')');
        $_SESSION['error']='Не удалось сохранить заказ. Попробуйте ещё раз.';
        $_SESSION['form_data']=\app\services\CheckoutAccount::fields($_POST);
        $_SESSION['checkout_token']=bin2hex(random_bytes(32));
        $this->redirectCartView(303);
    }
    $setupUrl = null;
    $registerAccount=!$isAuth && $checkoutMode==='register';
    $setupPending=$registerAccount && ($newAccount || (int)\R::getCell('SELECT pending FROM checkout_account_access WHERE user_id=?',[(int)$user_id])===1);
    if ($setupPending) {
        try { $setupUrl = \app\services\CheckoutAccount::issue((int)$user_id); }
        catch (\Throwable $e) { error_log('CR10: cannot issue setup link for order '.(int)$order_id); }
    }
    $_SESSION['checkout_result'] = ['order_id'=>(int)$order_id,'email'=>$user_email,'guest'=>!$isAuth,'new'=>$setupPending,'user_id'=>$setupPending?(int)$user_id:0,'checkout_mode'=>$checkoutMode,'sent'=>false,'time'=>time()];

    // ========= Формирование данных для письма =========
    $ord   = \R::findOne('order', 'id = ?', [$order_id]);
    $_SESSION['checkout_result']['number'] = (string)$ord['inv'];
    $dost  = \R::findOne('dostavka', 'id = ?', [$ord['dostavka_id']]);
    $bran  = \R::findOne('branch_office', 'branch_id = ?', [$ord['branch_id']]);
    $trans = \R::findOne('transport_company', 'id = ?', [$ord['transport_id']]);

    $transport_company = '';
    if (!empty($trans['name'])) {
        $transport_company = "<b>Название ТК:</b> " . $trans['name'] . "<br>";
    }

    $addressHtml = '';
    if (!empty($ord['address'])) {
        $addressHtml = "<br><b>Адрес:</b> " . $ord['address'] . "<br>";
    }

    $vid      = '';
    $compname = '';
    $nds      = '';
    $dogovor  = '';

    if ($usm) {
        if ((int)$usm['groups'] === 3) {
            $vid = "<b>Вид клиента:</b> Физическое лицо<br>";
        }

        if ((int)$usm['groups'] === 4) {
            $vid = "<b>Вид клиента:</b> Юридическое лицо<br>";

            $comp = $isAuth ? \R::findOne('company', 'user_id = ?', [$data['user_id']]) : null;
            if ($comp) {
                $compname = "<b>Компания (зарегистрирована):</b> " . $comp['comp_short_name'] . " (" . $comp['inn'] . ")<br>";

                if ($comp['nds'] == '1') {
                    $nds = "<b>Налогообложение:</b> c НДС<br>";
                } elseif ($comp['nds'] == '2') {
                    $nds = "<b>Налогообложение:</b> без НДС<br>";
                }

                if ($comp['dogovor'] == '1') {
                    $dogovor = "<b>Условия поставки:</b> Договор<br>";
                } elseif ($comp['dogovor'] == '2') {
                    $dogovor = "<b>Условия поставки:</b> Счёт-договор<br>";
                }
            } else {
                // компания не зарегистрирована, берём из формы
                if (!empty($data['inn']) || !empty($data['comp_short_name'])) {
                    $compname = "<b>Компания:</b> " . htmlspecialchars($data['comp_short_name'], ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($data['inn'], ENT_QUOTES, 'UTF-8') . ")<br>";
                    foreach (['kpp'=>'КПП','url_address'=>'Юридический адрес'] as $key=>$label) {
                        $compname .= '<b>'.$label.':</b> '.htmlspecialchars((string)($_POST[$key] ?? ''), ENT_QUOTES, 'UTF-8').'<br>';
                    }
                }
                if (!empty($_POST['nds'])) {
                    if ($_POST['nds'] == '1') {
                        $nds = "<b>Налогообложение:</b> c НДС<br>";
                    } elseif ($_POST['nds'] == '2') {
                        $nds = "<b>Налогообложение:</b> без НДС<br>";
                    }
                }
                if (!empty($_POST['dogovor'])) {
                    if ($_POST['dogovor'] == '1') {
                        $dogovor = "<b>Условия поставки:</b> Договор<br>";
                    } elseif ($_POST['dogovor'] == '2') {
                        $dogovor = "<b>Условия поставки:</b> Счёт-договор<br>";
                    }
                }
            }
        }
    }

    $compname=\app\services\CheckoutCompany::html($companyFields);
	try {
		$mailSent = \app\models\Order::mailOrder(
		$order_id,
		$user_email,
		$usm['name']    ?? '',
		$usm['telefon'] ?? '',
		$usm['admin_id'] ?? 0,
		$customerNote,
		$ord['date']    ?? '',
		$dost['name']   ?? '',
		$bran['branch_name'] ?? '',
		$addressHtml,
		$transport_company,
		$city_name,
		$vid,
		$compname,
		$nds,
		$dogovor,
		$formCallback,
		$promo,
        ['guest'=>$registerAccount,'setup_url'=>$setupUrl,'company_fields'=>$companyFields,'company_card'=>$paymentGroup===4||$paymentMethod==='bank_transfer']
	);
        $_SESSION['checkout_result']['sent'] = $mailSent;
    } catch (\Throwable $e) {
        error_log('CR10: order saved, notification failed for order '.(int)$order_id);
    }
    $_SESSION['checkout_result']['setup_sent'] = !empty($_SESSION['checkout_result']['sent']) && !empty($setupUrl);

	$this->emptyCart();

	session_write_close();
	redirect(PATH . '/cart/complete');
}
	
	public function dostavkaAction(){
		if($_GET) {

			if (session_status() === PHP_SESSION_ACTIVE) {
				session_write_close();
			}

			$dostavka_id = isset($_GET['dostavka_id']) ? $_GET['dostavka_id'] : '';			
			$dos = \R::findOne('dostavka', 'id = ?', [$dostavka_id]);			

			echo json_encode(array('result'=>''.$dos.''));		
			die;
		}
	}

}
