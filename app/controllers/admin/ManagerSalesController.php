<?php

namespace app\controllers\admin;

use app\services\admin\AdminDashboardService;

class ManagerSalesController extends AppController
{
    public function indexAction()
    {
        [$month] = AdminDashboardService::monthBounds($_GET['month'] ?? null);
        $sales = AdminDashboardService::managerSales($month);
        $curr = \R::findOne('currency');

        $this->setMeta('Продажи менеджеров');
        $this->set(compact('sales', 'month', 'curr'));
    }
}
