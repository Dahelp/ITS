<?php

namespace app\controllers\admin;

use app\services\admin\AdminActivityLogger;

class AvailabilityController extends AppController
{
    public function indexAction()
    {
        $where = '';
        $filter = (string)($_GET['filter'] ?? '');
        if ($filter === 'new') {
            $where = "WHERE ma.status_nalichiya = '0'";
        }

        $availability = \R::getAll(
            "SELECT ma.*, p.name AS product_name, p.alias AS product_alias, u.name AS user_name
             FROM mail_availability ma
             LEFT JOIN product p ON p.id = ma.product_id
             LEFT JOIN user u ON u.id = ma.user_id
             {$where}
             ORDER BY ma.data_create DESC"
        );

        $this->setMeta('Заявки о поступлении товара');
        $this->set(compact('availability'));
    }

    public function doneAction()
    {
        $id = $this->getRequestID();
        \R::exec(
            "UPDATE mail_availability
             SET status_nalichiya = '1', status_otpravki = '1', data_mail = ?
             WHERE id = ?",
            [date('Y-m-d H:i:s'), $id]
        );
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_DONE, 'mail_availability', (int)$id);
        $_SESSION['success'] = 'Заявка о поступлении обработана';
        redirect(ADMIN . '/availability');
    }

    public function deleteAction()
    {
        $id = $this->getRequestID();
        \R::exec(
            "UPDATE mail_availability
             SET status_nalichiya = '1'
             WHERE id = ?",
            [$id]
        );
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_LEAD_CLOSE, 'mail_availability', (int)$id);
        $_SESSION['success'] = 'Заявка о поступлении закрыта';
        redirect(ADMIN . '/availability');
    }
}
