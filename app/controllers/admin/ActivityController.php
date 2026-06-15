<?php

namespace app\controllers\admin;

use app\services\admin\AdminDashboardService;

class ActivityController extends AppController
{
    public function indexAction()
    {
        $dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $_GET['to'] ?? date('Y-m-d');
        $activity = AdminDashboardService::activityRows($dateFrom, $dateTo);

        $this->setMeta('Журнал действий');
        $this->set(compact('activity', 'dateFrom', 'dateTo'));
    }
}
