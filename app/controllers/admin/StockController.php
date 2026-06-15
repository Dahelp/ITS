<?php

namespace app\controllers\admin;

use app\services\admin\AdminDashboardService;
use app\services\admin\AdminActivityLogger;

class StockController extends AppController
{
    public function indexAction()
    {
        $summary = AdminDashboardService::stockSummary(true);
        $rows = [];
        $branches = [];

        if (!empty($summary['hasStockIndexes'])) {
            $latestStockSql = AdminDashboardService::latestStockSql();
            $branches = \R::getAll(
                "SELECT
                    s.branch_id,
                    bo.branch_name,
                    bo.tbl,
                    bo.hide,
                    COUNT(DISTINCT s.product_id) AS products_count,
                    COALESCE(SUM(s.quantity), 0) AS quantity,
                    MAX(s.date_scheduling) AS date_scheduling
                 FROM ({$latestStockSql}) s
                 LEFT JOIN branch_office bo ON bo.branch_id = s.branch_id
                 GROUP BY s.branch_id, bo.branch_name, bo.tbl, bo.hide
                 ORDER BY quantity DESC"
            );
        }

        $historyRows = AdminDashboardService::tableExists('in_stock_history')
            ? \R::getAll(
                "SELECT
                    h.date_ish,
                    h.product_id,
                    p.article,
                    p.name,
                    CAST(h.qty AS SIGNED) AS qty,
                    p.quantity AS current_quantity,
                    h.price
                 FROM in_stock_history h
                 LEFT JOIN product p ON p.id = h.product_id
                 WHERE h.date_ish = (SELECT MAX(date_ish) FROM in_stock_history WHERE date_ish IS NOT NULL)
                 ORDER BY h.id DESC
                 LIMIT 500"
            )
            : [];

        $statusRows = \R::getAll(
            "SELECT stock_status_id, COUNT(*) AS products_count, COALESCE(SUM(quantity), 0) AS quantity
             FROM product
             WHERE hide = 'show'
             GROUP BY stock_status_id
             ORDER BY stock_status_id ASC"
        );

        $this->setMeta('Наличие товаров');
        $this->set(compact('summary', 'rows', 'branches', 'historyRows', 'statusRows'));
    }

    public function productsAction()
    {
        $draw = max(0, (int)($_GET['draw'] ?? 0));
        $start = max(0, (int)($_GET['start'] ?? 0));
        $length = (int)($_GET['length'] ?? 50);
        if ($length < 1 || $length > 500) {
            $length = 50;
        }

        $columns = [
            0 => 'p.id',
            1 => 'p.article',
            2 => 'p.name',
            3 => 'p.stock_status_id',
            4 => 'p.quantity',
            5 => 'p.reserve',
            6 => 'p.wait',
            7 => 'date_scheduling',
        ];
        $orderColumnIndex = (int)($_GET['order'][0]['column'] ?? 4);
        $orderColumn = $columns[$orderColumnIndex] ?? 'p.quantity';
        $orderDir = strtolower((string)($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $where = ["p.hide = 'show'", 'p.quantity > 0'];
        $params = [];
        $search = trim((string)($_GET['search']['value'] ?? ''));
        if ($search !== '') {
            $where[] = '(p.id = ? OR p.article LIKE ? OR p.name LIKE ?)';
            $params[] = ctype_digit($search) ? (int)$search : 0;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $whereSql = implode(' AND ', $where);

        $total = (int)\R::getCell("SELECT COUNT(*) FROM product p WHERE p.hide = 'show' AND p.quantity > 0");
        $filtered = (int)\R::getCell("SELECT COUNT(*) FROM product p WHERE {$whereSql}", $params);
        $rows = \R::getAll(
            "SELECT
                p.id AS product_id,
                p.article,
                p.name,
                p.quantity,
                p.reserve,
                p.wait,
                p.stock_status_id,
                COALESCE(NULLIF(p.data_edit_all, ''), NULLIF(p.data_edit_price, '')) AS date_scheduling
             FROM product p
             WHERE {$whereSql}
             ORDER BY {$orderColumn} {$orderDir}, p.name ASC
             LIMIT {$start}, {$length}",
            $params
        );

        $statusNames = [
            0 => 'Нет в наличии',
            1 => 'В наличии',
            2 => 'Под заказ',
            3 => 'Ожидается поступление',
        ];
        $num = static fn($v) => number_format((float)$v, 0, '.', ' ');
        $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $data = [];
        foreach ($rows as $row) {
            $id = (int)$row['product_id'];
            $sid = (int)($row['stock_status_id'] ?? 0);
            $data[] = [
                $id,
                $e($row['article']),
                '<a href="' . ADMIN . '/product/edit?id=' . $id . '">' . $e($row['name']) . '</a>',
                $e($statusNames[$sid] ?? ('Статус #' . $sid)),
                $num($row['quantity']),
                $num($row['reserve'] ?? 0),
                $num($row['wait'] ?? 0),
                $e($row['date_scheduling']),
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        die;
    }

    public function clearHistoryAction()
    {
        $deletedHistory = 0;
        $deletedTotal = 0;

        if (AdminDashboardService::tableExists('in_stock_history')) {
            $deletedHistory = \R::exec(
                'DELETE FROM in_stock_history WHERE date_ish IS NOT NULL AND date_ish < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)'
            );
        }

        if (AdminDashboardService::tableExists('in_stock_history_total')) {
            $deletedTotal = \R::exec(
                'DELETE FROM in_stock_history_total WHERE date_total IS NOT NULL AND date_total < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)'
            );
        }

        $_SESSION['success'] = 'История остатков старше 1 года очищена. Удалено записей: '
            . ((int)$deletedHistory + (int)$deletedTotal) . '.';
        AdminActivityLogger::admin(AdminActivityLogger::ACTION_CRON_MANUAL, 'stock_history', 1);

        redirect(ADMIN . '/stock');
    }
}
