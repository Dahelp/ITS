<?php

namespace app\controllers\admin;

use app\services\admin\AdminDashboardService;

class MainController extends AppController
{
    public function indexAction()
    {
        [$salesMonth] = AdminDashboardService::monthBounds($_GET['month'] ?? null);

        $curr = \R::findOne('currency');
        $countNewOrders = \R::count('order', "status = '1'");
        $countOneClick = \R::count('mail_oneclick', "hide = '0'");
        $countUsers = \R::count('user', "groups > '2'");
        $countProducts = \R::count('product');
        $countCategories = \R::count('category');

        $usersonline = AdminDashboardService::onlineAdmins();
        $countOnlineUsers = AdminDashboardService::onlineAdminsCount();
        $managerSales = AdminDashboardService::managerSales($salesMonth);
        $recentActivity = AdminDashboardService::activity(24, 12);
        $recentActivityCount = AdminDashboardService::activityCount();
        $stockSummary = AdminDashboardService::stockSummary();
        $countInStock = (int)$stockSummary['currentQty'];
        $qtytotals = array_map(static fn($row) => ['qty_total' => $row['qty_total']], $stockSummary['history']);

        $this->setMeta('Панель управления');
        $this->set(compact(
            'countNewOrders',
            'countCategories',
            'countProducts',
            'countUsers',
            'usersonline',
            'countOnlineUsers',
            'countOneClick',
            'curr',
            'countInStock',
            'qtytotals',
            'managerSales',
            'salesMonth',
            'recentActivity',
            'recentActivityCount',
            'stockSummary'
        ));
    }
}
