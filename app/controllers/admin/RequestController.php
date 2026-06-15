<?php

namespace app\controllers\admin;

use app\services\admin\AdminActivityLogger;
use app\services\admin\AdminLeadOrderService;

class RequestController extends AppController
{
    public function indexAction()
    {
        $where = '';
        $filter = (string)($_GET['filter'] ?? '');
        if ($filter === 'new') {
            $where = "WHERE mr.hide = '0'";
        }

        $requests = \R::getAll(
            "SELECT
                mr.*,
                p.name AS product_name,
                p.alias AS product_alias,
                u.name AS user_name,
                u.email AS user_email
             FROM mail_request mr
             LEFT JOIN product p ON p.id = mr.product_id
             LEFT JOIN user u ON u.id = mr.user_id
             {$where}
             ORDER BY mr.data_create DESC"
        );

        $this->setMeta('Заявки о товаре');
        $this->set(compact('requests'));
    }

    public function processAction()
    {
        $id = $this->getRequestID();
        \R::exec(
            "UPDATE mail_request SET hide = '1', hide_call = '1', data_call = ?, call_uid = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), $_SESSION['user']['id'] ?? null, $id]
        );
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_PROCESS, 'mail_request', (int)$id);
        $_SESSION['success'] = 'Заявка взята в обработку';
        redirect(ADMIN . '/request');
    }

    public function doneAction()
    {
        $id = $this->getRequestID();
        \R::exec("UPDATE mail_request SET hide = '2' WHERE id = ?", [$id]);
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_DONE, 'mail_request', (int)$id);
        $_SESSION['success'] = 'Заявка обработана';
        redirect(ADMIN . '/request');
    }

    public function deleteAction()
    {
        $id = $this->getRequestID();
        \R::exec("UPDATE mail_request SET hide = '2' WHERE id = ?", [$id]);
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_CLOSE, 'mail_request', (int)$id);
        $_SESSION['success'] = 'Заявка закрыта';
        redirect(ADMIN . '/request');
    }

    public function createOrderAction()
    {
        $id = $this->getRequestID();
        $orderId = AdminLeadOrderService::createFromRequest((int)$id);
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_DONE, 'mail_request', (int)$id);
        $_SESSION['success'] = 'Создан заказ #' . $orderId . ' из заявки о товаре';
        redirect(ADMIN . '/order/view?id=' . $orderId);
    }
}
