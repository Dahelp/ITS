<?php

namespace app\controllers;

use app\models\Callback;
use app\services\admin\AdminActivityLogger;
use ishop\App;

class CallbackController extends AppController
{
    /**
     * Форма "Заказать звонок" на главной
     */
    public function indexAction()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect();
        }

        $data = $_POST;

        // === CSRF-токен ===
        $postToken    = $data['callback_token'] ?? '';
        $sessionToken = $_SESSION['callback_token'] ?? '';

        if (!$postToken || !$sessionToken || !hash_equals($sessionToken, $postToken)) {
            $_SESSION['error'] = 'Форма устарела, попробуйте ещё раз.';
            redirect();
        }

        // одноразовый токен
        unset($_SESSION['callback_token']);

        // === honeypot ===
        if (!empty($data['hp_field'])) {
            // почти наверняка бот — просто уходим без сообщений
            redirect();
        }

        // === Телефон ===
        $phone = isset($data['phone']) ? trim((string)$data['phone']) : '';
        $title = isset($data['title']) ? trim((string)$data['title']) : 'Заказать звонок';
        $agree = !empty($data['agree']);

        if (!$agree) {
            $_SESSION['error'] = 'Необходимо согласие на обработку персональных данных.';
            redirect();
        }

        // Нормализация/проверка телефона (как в других местах)
        $normalizeDigits = static function (string $raw): string {
            return preg_replace('/\D+/', '', $raw);
        };
        $isValidRuMobile = static function (string $raw) use ($normalizeDigits): bool {
            $d = $normalizeDigits($raw);
            if (strlen($d) !== 11) return false;
            if ($d[0] === '8') {
                $d[0] = '7';
            }
            return ($d[0] === '7' && $d[1] === '9'); // российский мобильный 79*********
        };

        if ($phone === '' || !$isValidRuMobile($phone)) {
            $_SESSION['error'] = 'Запрос не обработан! Некорректный номер телефона. Укажите номер мобильного РФ в формате +7 9ХХ ХХХ-ХХ-ХХ.';
            redirect();
        }

        // user_id, если юзер авторизован
        $user_id = $_SESSION['user']['id'] ?? null;

        $callback = new Callback();
        $callback->addCallback($phone, $user_id, $title);

        $_SESSION['success'] = 'Спасибо! Мы перезвоним вам в ближайшее время.';
        redirect();
    }

    /**
     * Форма "Запросить каталог / прайс ATV" (priceatv)
     */
    public function priceatvAction()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect();
        }

        $data = $_POST;

        // Можно использовать отдельный токен, либо тот же.
        // Допустим, отдельный:
        $postToken    = $data['priceatv_token'] ?? '';
        $sessionToken = $_SESSION['priceatv_token'] ?? '';

        if (!$postToken || !$sessionToken || !hash_equals($sessionToken, $postToken)) {
            $_SESSION['error'] = 'Форма устарела, попробуйте ещё раз.';
            redirect();
        }

        unset($_SESSION['priceatv_token']);

        // honeypot
        if (!empty($data['hp_field'])) {
            redirect();
        }

        $phone   = isset($data['phone'])   ? trim((string)$data['phone'])   : '';
        $contact = isset($data['contact']) ? trim((string)$data['contact']) : '';
        $email   = isset($data['email'])   ? trim((string)$data['email'])   : '';

        $normalizeDigits = static function (string $raw): string {
            return preg_replace('/\D+/', '', $raw);
        };
        $isValidRuMobile = static function (string $raw) use ($normalizeDigits): bool {
            $d = $normalizeDigits($raw);
            if (strlen($d) !== 11) return false;
            if ($d[0] === '8') {
                $d[0] = '7';
            }
            return ($d[0] === '7' && $d[1] === '9');
        };

        if ($phone === '' || !$isValidRuMobile($phone)) {
            $_SESSION['error'] = 'Запрос не обработан! Некорректный номер телефона. Укажите номер мобильного РФ в формате +7 9ХХ ХХХ-ХХ-ХХ.';
            redirect();
        }

        // Email — не обязателен, но если заполнен — проверим
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Укажите корректный e-mail (или оставьте поле пустым).';
            redirect();
        }

        $callback = new Callback();
        $userId = isset($_SESSION['user']['id']) && is_numeric($_SESSION['user']['id'])
            ? (int)$_SESSION['user']['id']
            : 0;
        $request = \R::dispense('callback');
        $request->user_id = $userId;
        $request->topic = 'Запрос каталога ATV';
        $request->phone = $phone;
        $request->date_create = date('Y-m-d H:i:s');
        $request->date_modified = '';
        $request->user_modified = '';
        $request->status = '0';
        $request->hide = 'show';
        $requestId = (int)\R::store($request);
        AdminActivityLogger::incoming(AdminActivityLogger::ACTION_CATALOG_REQUEST, 'callback', $requestId, $userId);
        $callback->priceatvCallback($phone, $contact, $email);

        $_SESSION['success'] = 'Спасибо! Ваш запрос принят, мы свяжемся с вами.';
        redirect();
    }
}
