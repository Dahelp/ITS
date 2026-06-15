<?php

namespace app\services\admin;

class AdminActivityLogger
{
    public const GROUP_SYSTEM = 1;
    public const GROUP_ADMIN = 2;
    public const GROUP_MANAGER = 3;
    public const GROUP_GUEST = 4;
    public const GROUP_CLIENT = 5;

    public const ACTION_ONECLICK = 1;
    public const ACTION_CALLBACK = 2;
    public const ACTION_AVAILABILITY = 3;
    public const ACTION_ORDER_SITE = 32;
    public const ACTION_CLIENT_SIGNUP = 42;
    public const ACTION_CRON_MANUAL = 49;
    public const ACTION_CRON_AUTO = 51;
    public const ACTION_PASSWORD_RECOVER = 53;
    public const ACTION_PRODUCT_REQUEST = 62;
    public const ACTION_CATALOG_REQUEST = 66;
    public const ACTION_LEAD_PROCESS = 67;
    public const ACTION_LEAD_DONE = 68;
    public const ACTION_LEAD_CLOSE = 69;

    private const DEFAULT_ACTIONS = [
        self::ACTION_CATALOG_REQUEST => [
            'name' => 'Запрос каталога',
            'controller' => 'callback',
            'status' => 'warning',
        ],
        self::ACTION_LEAD_PROCESS => [
            'name' => 'Заявка взята в обработку',
            'controller' => 'activity',
            'status' => 'info',
        ],
        self::ACTION_LEAD_DONE => [
            'name' => 'Заявка обработана',
            'controller' => 'activity',
            'status' => 'success',
        ],
        self::ACTION_LEAD_CLOSE => [
            'name' => 'Заявка закрыта',
            'controller' => 'activity',
            'status' => 'secondary',
        ],
    ];

    public static function log(
        int $groupId,
        int $actionId,
        string $table,
        int $recordId,
        ?int $actorId = null,
        ?string $date = null
    ): void {
        if ($recordId <= 0 || $table === '') {
            return;
        }

        self::ensureAction($actionId);

        if ($actorId === null && isset($_SESSION['user']['id']) && is_numeric($_SESSION['user']['id'])) {
            $actorId = (int)$_SESSION['user']['id'];
        }

        \R::exec(
            "INSERT INTO admin_last_history
                (gh_id, ah_id, name_tbl, id_tbl, date_modified, customer_id)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$groupId, $actionId, $table, $recordId, $date ?: date('Y-m-d H:i:s'), $actorId]
        );
    }

    public static function incoming(int $actionId, string $table, int $recordId, ?int $userId = null): void
    {
        $groupId = $userId && $userId > 0 ? self::GROUP_CLIENT : self::GROUP_GUEST;
        self::log($groupId, $actionId, $table, $recordId, $userId ?: null);
    }

    public static function admin(int $actionId, string $table, int $recordId, ?int $adminId = null): void
    {
        self::log(self::GROUP_ADMIN, $actionId, $table, $recordId, $adminId);
    }

    public static function cron(int $cronId, bool $manual, ?int $adminId = null, ?string $date = null): void
    {
        self::log(
            $manual && $adminId ? self::GROUP_ADMIN : self::GROUP_SYSTEM,
            $manual ? self::ACTION_CRON_MANUAL : self::ACTION_CRON_AUTO,
            'cron',
            $cronId,
            $adminId,
            $date
        );
    }

    private static function ensureAction(int $actionId): void
    {
        if (!isset(self::DEFAULT_ACTIONS[$actionId])) {
            return;
        }

        $exists = \R::getCell('SELECT id_ah FROM admin_action_history WHERE id_ah = ?', [$actionId]);
        if ($exists) {
            return;
        }

        $action = self::DEFAULT_ACTIONS[$actionId];
        \R::exec(
            "INSERT INTO admin_action_history (id_ah, name_ah, controller, status)
             VALUES (?, ?, ?, ?)",
            [$actionId, $action['name'], $action['controller'], $action['status']]
        );
    }
}
