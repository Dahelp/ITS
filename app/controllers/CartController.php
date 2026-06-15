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
                $this->loadView('cart_modal');
                $this->sessClose();
                return;
            }

            $this->gone410();
        }

        $product = \R::findOne('product', 'id = ?', [$id]);

        if (!$product) {
            if ($this->isAjax()) {
                $this->loadView('cart_modal');
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

        $this->loadView('cart_modal');
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
            $this->loadView('cart_modal');
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

        $this->loadView('cart_modal');
        $this->sessClose();
        return;
    }

    public function showAction(): void
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        $this->loadView('cart_modal');
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

        $this->loadView('cart_modal');
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

        $this->loadView('cart_modal');
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

    public function deletecartAction()
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
            $cart->deletecompleteItem($id, $completeId);
        }

        $this->loadView('cart_table');
        $this->sessClose();
        return;
    }

    public function plusmodalAction()
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

        $this->loadView('cart_modal');
        $this->sessClose();
        return;
    }

    public function plusmodalcompleteAction()
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

        $this->loadView('cart_modal');
        $this->sessClose();
        return;
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

    public function minusmodalAction()
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

        $this->loadView('cart_modal');
        $this->sessClose();
        return;
    }

    public function minusmodalcompleteAction()
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

        $this->loadView('cart_modal');
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

    public function clearAction(): void
    {
        $this->sessOpenRW();

        if (!$this->isAjax()) {
            $this->redirectCartView(302);
        }

        unset(
            $_SESSION['cart'],
            $_SESSION['cart.qty'],
            $_SESSION['cart.sum'],
            $_SESSION['cart.weight'],
            $_SESSION['cart.volume'],
            $_SESSION['cart.currency'],
            $_SESSION['promocart'],
            $_SESSION['promo_state'],
            $_SESSION['cart.notice']
        );

        $ctx = $_GET['ctx'] ?? 'page';

        if ($ctx === 'modal') {
            $this->loadView('cart_modal');
        } else {
            $this->loadView('cart_table');
        }

        $this->sessClose();
        return;
    }

    public function viewAction()
    {
        $this->sessOpenRW();

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
			$inn= isset($_GET['inn']) ? $_GET['inn'] : '';	
			$response = $client->request('GET', 'http://84.47.156.246:8082/itscentr/hs/CompanyListFrom1C/getCompanyFrom1C?inn='.$inn.'',

			 [
				'auth' => [
					'GetCompanyByINN',
					'GetCompanyByINN'
				]
			]

			);
			$str = iconv("CP1252", "UTF-8", $response->getBody());

			$response = json_decode($response->getBody(), true);
				
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
    unset($_SESSION['checkout_token']);

    // ========= ANTI-BOT: honeypot =========
    if (!empty($_POST['hp_field'])) {
        // 99% бот – тихо уходим
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
    $email = isset($data['email']) ? trim((string)$data['email']) : '';
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

    // ====== Получаем / создаём пользователя ======
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
            $comp_id = $usok['comp_id'] ?? null;
            $groups  = $usok['groups'] ?? null;
        } else {
            // Регистрация нового пользователя (кабинет создаётся автоматически)
            if (!$isAuth) {
                $user = new \app\models\User();
                $data = $_POST;

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

    // comp_id — либо из найденного пользователя, либо null
    $data['comp_id'] = !empty($comp_id) ? $comp_id : null;

    $data['comp_short_name'] = !empty($_POST['comp_short_name']) ? $_POST['comp_short_name'] : '';
    $data['inn']             = !empty($_POST['inn']) ? $_POST['inn'] : '';
    $data['note']            = !empty($_POST['note']) ? $_POST['note'] : '';
    $data['dostavka_id']     = !empty($_POST['dostavka_id']) ? $_POST['dostavka_id'] : '';
    $data['address']         = !empty($_POST['address']) ? $_POST['address'] : '';
    $data['transport_id']    = !empty($_POST['transport_id']) ? $_POST['transport_id'] : '';

    $city_name = !empty($_POST['city_name']) ? $_POST['city_name'] : '';
    $cit       = \R::findOne('cities', 'city_name = ?', [$city_name]);
    $data['city_id'] = !empty($cit['city_id']) ? $cit['city_id'] : '';

    $data['branch_id'] = !empty($_POST['branch_id']) ? $_POST['branch_id'] : '';

    $data['groups'] = !empty($_SESSION['user']['groups'])
        ? $_SESSION['user']['groups']
        : ($data['groups'] ?? ($_POST['groups'] ?? null));

    $user_email = $_SESSION['user']['email'] ?? $email;

    $data['form_callback'] = $formCallback;
    $promo = trim((string)($_SESSION['promocart'] ?? ''));

    // Флаг наличия прикреплённых реквизитов
    if (!empty($_FILES['rekvizity']) && $_FILES['rekvizity']['error'] === UPLOAD_ERR_OK) {
        $rekvizity = '1';
    } else {
        $rekvizity = '0';
    }

    $usm = \R::findOne('user', 'email = ?', [$user_email]);

    // Если это юр. лицо
    if (!empty($data['groups']) && (int)$data['groups'] === 4) {
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

    // ========= Формирование данных для письма =========
    $ord   = \R::findOne('order', 'id = ?', [$order_id]);
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

    if (!empty($data['user_id']) && $usm) {
        if ((int)$usm['groups'] === 3) {
            $vid = "<b>Вид клиента:</b> Физическое лицо<br>";
        }

        if ((int)$usm['groups'] === 4) {
            $vid = "<b>Вид клиента:</b> Юридическое лицо<br>";

            $comp = \R::findOne('company', 'user_id = ?', [$data['user_id']]);
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
                if (!empty($data['inn'])) {
                    $compname = "<b>Компания:</b> " . $data['comp_short_name'] . " (" . $data['inn'] . ")<br>";
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

		\app\models\Order::mailOrder(
		$order_id,
		$user_email,
		$usm['name']    ?? '',
		$usm['telefon'] ?? '',
		$usm['admin_id'] ?? 0,
		$ord['note']    ?? '',
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
		$promo
	);

	unset(
		$_SESSION['cart'],
		$_SESSION['cart.qty'],
		$_SESSION['cart.sum'],
		$_SESSION['cart.weight'],
		$_SESSION['cart.volume'],
		$_SESSION['cart.currency'],
		$_SESSION['promocart'],
		$_SESSION['promo_state'],
		$_SESSION['cart.notice']
	);

	session_write_close();
	redirect();
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
