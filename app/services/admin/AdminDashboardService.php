<?php

namespace app\services\admin;

class AdminDashboardService
{
    public static function monthBounds(?string $month = null): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string)$month) ? $month : date('Y-m');
        $start = $month . '-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($start));

        return [$month, $start, $end];
    }

    public static function onlineAdmins(int $limit = 8): array
    {
        $threshold = time() - 900;

        return \R::getAll(
            "SELECT u.id, u.name, u.email, MAX(CAST(uo.unix AS UNSIGNED)) AS last_seen
             FROM user_online uo
             JOIN user u ON u.id = CAST(uo.user_id AS UNSIGNED)
             WHERE u.role != 'user' AND CAST(uo.unix AS UNSIGNED) >= ?
             GROUP BY u.id
             ORDER BY last_seen DESC
             LIMIT {$limit}",
            [$threshold]
        );
    }

    public static function onlineAdminsCount(): int
    {
        $threshold = time() - 900;

        return (int)\R::getCell(
            "SELECT COUNT(DISTINCT u.id)
             FROM user_online uo
             JOIN user u ON u.id = CAST(uo.user_id AS UNSIGNED)
             WHERE u.role != 'user' AND CAST(uo.unix AS UNSIGNED) >= ?",
            [$threshold]
        );
    }

    public static function managerSales(?string $month = null): array
    {
        [, $start, $end] = self::monthBounds($month);

        return \R::getAll(
            "SELECT
                u.id AS manager_id,
                u.name AS manager_name,
                COUNT(DISTINCT o.id) AS orders_count,
                COALESCE(ROUND(SUM(op.price * op.qty), 2), 0) AS sales_sum
             FROM `order` o
             JOIN user u ON u.id = o.admin_id
             JOIN order_product op ON op.order_id = o.id
             WHERE o.admin_id > 0
               AND o.status IN (4,5,6)
               AND o.date BETWEEN ? AND ?
             GROUP BY u.id
             ORDER BY sales_sum DESC, orders_count DESC, manager_name ASC",
            [$start, $end]
        );
    }

    public static function activity(int $hours = 24, int $limit = 20): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        $rows = \R::getAll(
            "SELECT
                h.*,
                gh.name_gh,
                ah.name_ah,
                ah.controller,
                ah.status,
                u.name AS actor_name
             FROM admin_last_history h
             LEFT JOIN admin_group_history gh ON gh.id_gh = h.gh_id
             LEFT JOIN admin_action_history ah ON ah.id_ah = h.ah_id
             LEFT JOIN user u ON u.id = h.customer_id
             WHERE h.date_modified >= ?
             ORDER BY h.date_modified DESC
             LIMIT {$limit}",
            [$since]
        );

        return self::withActivityUrls($rows);
    }

    public static function activityCount(int $hours = 24): int
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        return (int)\R::getCell('SELECT COUNT(*) FROM admin_last_history WHERE date_modified >= ?', [$since]);
    }

    public static function activityRows(?string $dateFrom = null, ?string $dateTo = null, int $limit = 500): array
    {
        $dateFrom = $dateFrom ?: date('Y-m-d', strtotime('-7 days'));
        $dateTo = $dateTo ?: date('Y-m-d');

        $rows = \R::getAll(
            "SELECT
                h.*,
                gh.name_gh,
                ah.name_ah,
                ah.controller,
                ah.status,
                u.name AS actor_name
             FROM admin_last_history h
             LEFT JOIN admin_group_history gh ON gh.id_gh = h.gh_id
             LEFT JOIN admin_action_history ah ON ah.id_ah = h.ah_id
             LEFT JOIN user u ON u.id = h.customer_id
             WHERE h.date_modified BETWEEN ? AND ?
             ORDER BY h.date_modified DESC
             LIMIT {$limit}",
            [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']
        );

        return self::withActivityUrls($rows);
    }

    public static function activityUrl(array $row): string
    {
        $admin = '/admin';
        $table = (string)($row['name_tbl'] ?? '');
        $id = (int)($row['id_tbl'] ?? 0);
        $controller = trim((string)($row['controller'] ?? ''));

        if ($controller !== '') {
            $url = $admin . '/' . ltrim($controller, '/');
            if ($id > 0 && in_array($controller, ['order/view', 'product/edit', 'company/edit', 'user/edit', 'review/edit'], true)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . 'id=' . $id;
            }
            return $url;
        }

        return match ($table) {
            'order' => $id > 0 ? $admin . '/order/view?id=' . $id : $admin . '/order',
            'product' => $id > 0 ? $admin . '/product/edit?id=' . $id : $admin . '/product',
            'company' => $id > 0 ? $admin . '/company/edit?id=' . $id : $admin . '/company',
            'user' => $id > 0 ? $admin . '/user/edit?id=' . $id : $admin . '/user',
            'callback' => $admin . '/callback',
            'mail_oneclick' => $admin . '/oneclick',
            'mail_availability' => $admin . '/availability',
            'mail_request' => $admin . '/request',
            'cron' => $admin . '/cron',
            'review' => $id > 0 ? $admin . '/review/edit?id=' . $id : $admin . '/review',
            'mails_imap' => $admin . '/mailbox',
            'attribute_group' => $id > 0 ? $admin . '/filtrs/group-edit?id=' . $id : $admin . '/filtrs/attribute-group',
            'attribute_comparison' => $id > 0 ? $admin . '/attribute/edit?id=' . $id : $admin . '/attribute',
            'contents' => $id > 0 ? $admin . '/contents/page-edit?id=' . $id : $admin . '/contents/pages',
            'plagins_promocode' => $id > 0 ? $admin . '/plagins/promocode-edit?id=' . $id : $admin . '/plagins/promocode',
            'technics' => $id > 0 ? $admin . '/plagins/technics-edit?id=' . $id : $admin . '/plagins/technics',
            'technics_type' => $id > 0 ? $admin . '/plagins/technics-edit-type?id=' . $id : $admin . '/plagins/technics-type',
            'technics_manufacturer' => $id > 0 ? $admin . '/plagins/technics-edit-manufacturer?id=' . $id : $admin . '/plagins/technics-manufacturer',
            default => $admin . '/activity',
        };
    }

    private static function withActivityUrls(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['activity_url'] = self::activityUrl($row);
        }
        unset($row);

        return $rows;
    }

    public static function stockSummary(bool $includeStockDetails = false): array
    {
        $hasInStock = self::tableExists('in_stock');
        $hasStockIndexes = $hasInStock && self::hasIndex('in_stock', 'idx_stock_product_branch_id');
        $inStockRows = $hasInStock ? (int)\R::getCell('SELECT COUNT(*) FROM in_stock') : 0;
        $productQty = (int)\R::getCell("SELECT COALESCE(SUM(quantity), 0) FROM product WHERE hide = 'show'");
        $source = 'product';
        $currentQty = $productQty;
        $productsCount = (int)\R::getCell("SELECT COUNT(*) FROM product WHERE hide = 'show' AND quantity > 0");
        $inStockQty = 0;
        $rawInStockQty = 0;
        $duplicateStockRows = null;
        $actualStockRows = 0;
        $stockDate = null;
        $branchesCount = $hasInStock ? (int)\R::getCell('SELECT COUNT(DISTINCT branch_id) FROM in_stock') : 0;

        if ($includeStockDetails && $hasInStock) {
            $rawInStockQty = (int)\R::getCell('SELECT COALESCE(SUM(quantity), 0) FROM in_stock');
            $stockDate = \R::getCell('SELECT MAX(date_scheduling) FROM in_stock WHERE date_scheduling IS NOT NULL');

            if ($hasStockIndexes) {
                $latestStockSql = self::latestStockSql();
                $actual = \R::getRow(
                    "SELECT COALESCE(SUM(s.quantity), 0) AS qty, COUNT(*) AS rows_count
                     FROM ({$latestStockSql}) s"
                );
                $inStockQty = (int)($actual['qty'] ?? 0);
                $actualStockRows = (int)($actual['rows_count'] ?? 0);
                $duplicateStockRows = max(0, $inStockRows - $actualStockRows);
            }
        }

        $lastDate = \R::getCell("SELECT MAX(COALESCE(NULLIF(data_edit_all, ''), NULLIF(data_edit_price, ''))) FROM product WHERE hide = 'show'");

        $history = self::tableExists('in_stock_history_total')
            ? \R::getAll(
                "SELECT date_total, qty_total
                 FROM in_stock_history_total
                 WHERE date_total IS NOT NULL
                 ORDER BY date_total DESC, id DESC
                 LIMIT 14"
            )
            : [];
        $history = array_reverse($history);

        $prevQty = count($history) > 1 ? (int)$history[count($history) - 2]['qty_total'] : 0;
        $latestHistoryQty = count($history) > 0 ? (int)$history[count($history) - 1]['qty_total'] : 0;
        $historyDelta = $latestHistoryQty > 0 ? $currentQty - $latestHistoryQty : 0;

        return compact(
            'currentQty',
            'productsCount',
            'branchesCount',
            'lastDate',
            'history',
            'prevQty',
            'source',
            'inStockRows',
            'inStockQty',
            'rawInStockQty',
            'duplicateStockRows',
            'actualStockRows',
            'stockDate',
            'hasInStock',
            'hasStockIndexes',
            'productQty',
            'latestHistoryQty',
            'historyDelta'
        );
    }

    public static function tableExists(string $table): bool
    {
        return (bool)\R::getCell('SHOW TABLES LIKE ?', [$table]);
    }

    public static function hasIndex(string $table, string $index): bool
    {
        return (bool)\R::getCell("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
    }

    public static function latestStockSql(): string
    {
        return "SELECT s.*
                FROM in_stock s
                INNER JOIN (
                    SELECT product_id, branch_id, MAX(stock_id) AS stock_id
                    FROM in_stock
                    GROUP BY product_id, branch_id
                ) latest ON latest.stock_id = s.stock_id";
    }

    public static function notificationItems(): array
    {
        $admin = '/admin';
        $items = [];

        $items[] = self::notificationItem(
            'Входящие письма',
            (int)\R::count('mails_imap', "is_seen != '1'"),
            $admin . '/mailbox?seen=0',
            \R::getCell("SELECT MAX(date_dispatch) FROM mails_imap WHERE is_seen != '1'"),
            'fas fa-envelope'
        );

        $items[] = self::notificationItem(
            'Новые заказы',
            (int)\R::count('order', "status = '1'"),
            $admin . '/order?status=1',
            \R::getCell("SELECT MAX(date) FROM `order` WHERE status = '1'"),
            'fas fa-shopping-cart'
        );

        $items[] = self::notificationItem(
            'Заказы в 1 клик',
            (int)\R::count('mail_oneclick', "hide = '0'"),
            $admin . '/oneclick?filter=new',
            \R::getCell("SELECT MAX(data_create) FROM mail_oneclick WHERE hide = '0'"),
            'fas fa-mouse-pointer'
        );

        $items[] = self::notificationItem(
            'Обратные звонки',
            (int)\R::count('callback', "hide = 'show' AND (topic IS NULL OR topic NOT LIKE '%каталог%') AND status = '0'"),
            $admin . '/callback?filter=new&type=callback',
            \R::getCell("SELECT MAX(date_create) FROM callback WHERE hide = 'show' AND (topic IS NULL OR topic NOT LIKE '%каталог%') AND status = '0'"),
            'fas fa-phone'
        );

        $items[] = self::notificationItem(
            'Запросы каталога',
            (int)\R::count('callback', "hide = 'show' AND topic LIKE '%каталог%' AND status = '0'"),
            $admin . '/callback?filter=new&type=catalog',
            \R::getCell("SELECT MAX(date_create) FROM callback WHERE hide = 'show' AND topic LIKE '%каталог%' AND status = '0'"),
            'fas fa-file-download'
        );

        $items[] = self::notificationItem(
            'Заявки о поступлении',
            (int)\R::count('mail_availability', "status_nalichiya = '0'"),
            $admin . '/availability?filter=new',
            \R::getCell("SELECT MAX(data_create) FROM mail_availability WHERE status_nalichiya = '0'"),
            'fas fa-boxes'
        );

        $items[] = self::notificationItem(
            'Заявки о товаре',
            (int)\R::count('mail_request', "hide = '0'"),
            $admin . '/request?filter=new',
            \R::getCell("SELECT MAX(data_create) FROM mail_request WHERE hide = '0'"),
            'fas fa-clipboard-list'
        );

        return array_values(array_filter($items, static fn($item) => $item['count'] > 0));
    }

    public static function notificationsCount(): int
    {
        return array_sum(array_column(self::notificationItems(), 'count'));
    }

    private static function notificationItem(string $title, int $count, string $url, ?string $date, string $icon): array
    {
        return compact('title', 'count', 'url', 'date', 'icon');
    }
}
