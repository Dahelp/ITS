<?php

namespace app\controllers\admin;

use app\services\admin\AdminDashboardService;

class NotificationsController extends AppController
{
    public function indexAction()
    {
        $items = AdminDashboardService::notificationItems();
        $total = AdminDashboardService::notificationsCount();

        $this->setMeta('Уведомления');
        $this->set(compact('items', 'total'));
    }
}
