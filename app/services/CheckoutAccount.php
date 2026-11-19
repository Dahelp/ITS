<?php
namespace app\services;

/** First access is granted only through the mailbox, never by checkout session. */
final class CheckoutAccount
{
    public static function baseUrl(): string
    {
        // Email links must not depend on the request's Host header.
        $url = rtrim((string)(getenv('CHECKOUT_ACCOUNT_BASE_URL') ?: 'https://its-center.ru'), '/');
        if (!preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?(?:/[a-z0-9/_-]*)?$~iD', $url)) throw new \RuntimeException('Invalid account base URL');
        return $url;
    }

    public static function ensureSchema(): void
    {
        \R::exec('CREATE TABLE IF NOT EXISTS checkout_account_access (user_id INT NOT NULL PRIMARY KEY, token_hash CHAR(64) NULL, expires INT NOT NULL DEFAULT 0, sent_at INT NOT NULL DEFAULT 0, pending TINYINT NOT NULL DEFAULT 1, UNIQUE KEY token_hash (token_hash)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        \R::exec('CREATE TABLE IF NOT EXISTS checkout_order_contact (order_id INT NOT NULL PRIMARY KEY, email VARCHAR(150) NOT NULL, name VARCHAR(255) NOT NULL, telefon VARCHAR(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        \R::exec('CREATE TABLE IF NOT EXISTS checkout_order_company (order_id INT NOT NULL PRIMARY KEY, fields_json MEDIUMTEXT NOT NULL, contact_json TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public static function issue(int $userId): ?string
    {
        self::ensureSchema();
        $token = bin2hex(random_bytes(32));
        // Atomic cooldown; concurrent resends cannot invalidate a just-sent link.
        $changed = \R::exec('UPDATE checkout_account_access SET token_hash=?, expires=?, sent_at=? WHERE user_id=? AND pending=1 AND sent_at<?',
            [hash('sha256', $token), time()+3600, time(), $userId, time()-300]);
        return $changed ? self::baseUrl() . '/user/setup-password?key=' . $token : null;
    }

    public static function setPassword(string $token, string $password): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
        self::ensureSchema();
        \R::begin();
        try {
            $row = \R::getRow('SELECT * FROM checkout_account_access WHERE token_hash=? AND pending=1 AND expires>? FOR UPDATE', [hash('sha256', $token), time()]);
            if (!$row) { \R::rollback(); return false; }
            \R::exec('UPDATE user SET password=? WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
            \R::exec('UPDATE checkout_account_access SET pending=0,token_hash=NULL,expires=0 WHERE user_id=?', [$row['user_id']]);
            \R::exec('DELETE FROM recover WHERE email=(SELECT email FROM user WHERE id=?)', [$row['user_id']]);
            \R::commit();
            return true;
        } catch (\Throwable $e) { \R::rollback(); throw $e; }
    }

    public static function mode($requested): string
    {
        if ($requested===null || $requested==='') return 'guest';
        if (!is_string($requested) || !in_array($requested,['guest','register'],true)) throw new \DomainException('Выберите способ оформления заказа.');
        return $requested;
    }

    public static function fields(array $data): array
    {
        $result=[];
        foreach (['checkout_mode','dostavka_id','transport_id','branch_id','city_name','address','groups','nds','dogovor','name','telefon','email','form_callback','note','comp_name','kpp','url_address','comp_short_name','inn','ogrn','dir_name','bank','bik','raschet','korschet','purchase_purpose','payment_method'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) $result[$key]=mb_substr($data[$key],0,$key==='note'?5000:2000);
        }
        return $result;
    }
}
