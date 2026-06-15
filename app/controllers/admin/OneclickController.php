<?php

namespace app\controllers\admin;

use app\services\admin\AdminActivityLogger;
use app\services\admin\AdminLeadOrderService;
use ishop\App;

class OneclickController extends AppController
{
    public function indexAction()
    {
        $where = '';
        $filter = (string)($_GET['filter'] ?? '');
        if ($filter === 'new') {
            $where = "WHERE mo.hide = '0'";
        }

        $clicks = \R::getAll(
            "SELECT mo.*, p.name AS product_name, p.alias AS product_alias, u.name AS user_name
             FROM mail_oneclick mo
             LEFT JOIN product p ON p.id = mo.product_id
             LEFT JOIN user u ON u.id = mo.user_id
             {$where}
             ORDER BY mo.data_create DESC"
        );
        $namecomp = App::$app->getProperty('shop_name');

        $this->setMeta('Заказы в 1 клик');
        $this->set(compact('clicks', 'namecomp'));
    }

    public function processAction()
    {
        $id = $this->getRequestID();
        \R::exec(
            "UPDATE mail_oneclick SET hide = '1', hide_call = '1', data_call = ?, call_uid = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), $_SESSION['user']['id'] ?? null, $id]
        );
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_PROCESS, 'mail_oneclick', (int)$id);
        $_SESSION['success'] = 'Заказ в 1 клик взят в обработку';
        redirect(ADMIN . '/oneclick');
    }

    public function doneAction()
    {
        $id = $this->getRequestID();
        \R::exec("UPDATE mail_oneclick SET hide = '2' WHERE id = ?", [$id]);
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_DONE, 'mail_oneclick', (int)$id);
        $_SESSION['success'] = 'Заказ в 1 клик обработан';
        redirect(ADMIN . '/oneclick');
    }

    public function deleteAction()
    {
        $id = $this->getRequestID();
        \R::exec("UPDATE mail_oneclick SET hide = '2' WHERE id = ?", [$id]);
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_CLOSE, 'mail_oneclick', (int)$id);
        $_SESSION['success'] = 'Заказ в 1 клик закрыт';
        redirect(ADMIN . '/oneclick');
    }

    public function createOrderAction()
    {
        $id = $this->getRequestID();
        $orderId = AdminLeadOrderService::createFromOneClick((int)$id);
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_DONE, 'mail_oneclick', (int)$id);
        $_SESSION['success'] = 'Создан заказ #' . $orderId . ' из заявки в 1 клик';
        redirect(ADMIN . '/order/view?id=' . $orderId);
    }
}
