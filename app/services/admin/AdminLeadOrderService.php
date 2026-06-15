<?php

namespace app\services\admin;

use ishop\App;

class AdminLeadOrderService
{
    public static function createFromOneClick(int $leadId): int
    {
        $lead = \R::getRow(
            "SELECT mo.*, p.article, p.name AS product_name, p.price, p.unit
             FROM mail_oneclick mo
             LEFT JOIN product p ON p.id = mo.product_id
             WHERE mo.id = ?
             LIMIT 1",
            [$leadId]
        );

        if (!$lead) {
            throw new \Exception('Заявка не найдена', 404);
        }

        if (!empty($lead['order_id'])) {
            return (int)$lead['order_id'];
        }

        $userId = self::resolveUser(
            (int)($lead['user_id'] ?? 0),
            (string)($lead['email_click'] ?? ''),
            (string)($lead['fio_click'] ?? ''),
            (string)($lead['tell_click'] ?? '')
        );

        $orderId = self::createOrder($userId, (string)($lead['prim_click'] ?? ''));
        self::createOrderProduct($orderId, $lead);

        \R::exec(
            "UPDATE mail_oneclick
             SET hide = '2', hide_order = '1', order_id = ?
             WHERE id = ?",
            [$orderId, $leadId]
        );

        return $orderId;
    }

    public static function createFromRequest(int $leadId): int
    {
        $lead = \R::getRow(
            "SELECT mr.*, p.article, p.name AS product_name, p.price, p.unit
             FROM mail_request mr
             LEFT JOIN product p ON p.id = mr.product_id
             WHERE mr.id = ?
             LIMIT 1",
            [$leadId]
        );

        if (!$lead) {
            throw new \Exception('Заявка не найдена', 404);
        }

        if (!empty($lead['order_id'])) {
            return (int)$lead['order_id'];
        }

        $userId = self::resolveUser(
            (int)($lead['user_id'] ?? 0),
            (string)($lead['email'] ?? ''),
            (string)($lead['fio'] ?? ''),
            (string)($lead['tell'] ?? '')
        );

        $orderId = self::createOrder($userId, (string)($lead['note'] ?? ''));
        self::createOrderProduct($orderId, $lead);

        \R::exec(
            "UPDATE mail_request
             SET hide = '2', hide_order = '1', order_id = ?
             WHERE id = ?",
            [$orderId, $leadId]
        );

        return $orderId;
    }

    private static function resolveUser(int $userId, string $email, string $name, string $phone): int
    {
        if ($userId > 0 && \R::getCell('SELECT id FROM user WHERE id = ?', [$userId])) {
            return $userId;
        }

        $email = trim($email);
        if ($email !== '') {
            $found = (int)\R::getCell('SELECT id FROM user WHERE email = ? LIMIT 1', [$email]);
            if ($found > 0) {
                return $found;
            }
        }

        $user = \R::dispense('user');
        $user->password = password_hash(App::generate_password(8), PASSWORD_DEFAULT);
        $user->email = $email ?: 'lead-' . date('YmdHis') . '-' . random_int(1000, 9999) . '@example.local';
        $user->name = trim($name) ?: 'Клиент из заявки';
        $user->telefon = trim($phone);
        $user->role = 'user';
        $user->groups = 3;
        $user->admin_id = $_SESSION['user']['id'] ?? 0;
        $user->comp_id = '';
        $user->date_create = date('Y-m-d H:i:s');
        $user->newsletter = '';
        $user->uxeh = '';
        $user->uid_ya = '';
        $user->uid_gg = '';
        $user->uid_vk = '';

        $newUserId = (int)\R::store($user);
        AdminActivityLogger::admin(36, 'user', $newUserId);

        return $newUserId;
    }

    private static function createOrder(int $userId, string $note): int
    {
        $currency = \R::getRow('SELECT code FROM currency LIMIT 1');
        $lastId = (int)\R::getCell('SELECT MAX(id) FROM `order`');
        $nextId = $lastId + 1;
        $prefix = App::options('order_prefix') ?: 'IT';

        $order = \R::dispense('order');
        $order->inv = App::invoice_num($nextId, 9, $prefix);
        $order->user_id = $userId;
        $order->admin_id = $_SESSION['user']['id'] ?? 0;
        $order->comp_id = '';
        $order->seller = 1;
        $order->status = 1;
        $order->status_1c = '';
        $order->end_buyer = '';
        $order->date = date('Y-m-d H:i:s');
        $order->update_at = '';
        $order->dostavka_id = 1;
        $order->transport_id = '';
        $order->branch_id = '';
        $order->city_id = '';
        $order->city_text = '';
        $order->address = '';
        $order->currency = $currency['code'] ?? 'RUB';
        $order->note = $note;
        $order->guid_1c = '';
        $order->status_shipment_id = '';
        $order->data_shipment = '';
        $order->status_payment_id = '';

        $orderId = (int)\R::store($order);
        AdminActivityLogger::admin(43, 'order', $orderId);

        return $orderId;
    }

    private static function createOrderProduct(int $orderId, array $lead): void
    {
        $productId = (int)($lead['product_id'] ?? 0);
        if ($productId <= 0) {
            return;
        }

        \R::exec(
            "INSERT INTO order_product
                (order_id, product_id, article, qty, unit, name, price, discount_value, discount_type, discount, price_discount, discount_amount, external)
             VALUES (?, ?, ?, 1, ?, ?, ?, '', '', '', '', '', '')",
            [
                $orderId,
                $productId,
                (string)($lead['article'] ?? ''),
                (string)($lead['unit'] ?? 'шт'),
                (string)($lead['product_name'] ?: ($lead['name'] ?? '')),
                (float)($lead['price'] ?? 0),
            ]
        );
    }
}
