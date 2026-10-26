<?php
namespace app\services;

/** Shared validation for short lead forms. No mail is sent until a lead is saved. */
final class LeadForm
{
    public static function token(string $scope): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return $_SESSION['lead_tokens'][$scope] ??= bin2hex(random_bytes(32));
    }

    public static function phone($value): string
    {
        $digits = is_string($value) ? preg_replace('/\D+/', '', $value) : '';
        if (strlen($digits) === 11 && $digits[0] === '8') $digits[0] = '7';
        if (!preg_match('/^79\d{9}$/D', $digits) || preg_match('/^(\d)\1{8}$/D', substr($digits, 2))) {
            throw new \DomainException('Укажите полный номер мобильного телефона РФ: +7 9ХХ ХХХ-ХХ-ХХ.');
        }
        return '+' . $digits;
    }

    public static function text($value, int $max): string
    {
        if (!is_string($value) || mb_strlen($value) > $max) throw new \DomainException('Проверьте заполненные поля: текст слишком длинный.');
        return trim($value);
    }

    public static function guard(string $scope, array $post): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $token = $post['lead_token'] ?? '';
        $expected = $_SESSION['lead_tokens'][$scope] ?? '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token) || !$expected || !hash_equals($expected, $token)) {
            throw new \DomainException('Форма уже отправлена или устарела. Обновите страницу.');
        }
        if (!empty($post['hp_field'])) throw new \DomainException('Не удалось отправить форму. Обновите страницу.');
        if (time() - (int)($_SESSION['lead_last_sent'] ?? 0) < 60) throw new \DomainException('Заявка уже принята. Повторить отправку можно через минуту.');
    }

    public static function consumed(string $scope): void
    {
        unset($_SESSION['lead_tokens'][$scope]);
        $_SESSION['lead_last_sent'] = time();
    }

    public static function rejectRecent(string $table, string $phoneField, string $phone): void
    {
        if (!in_array($table, ['callback','mail_oneclick'], true) || !in_array($phoneField,['phone','tell_click'],true)) throw new \LogicException('Invalid lead table');
        $digits = substr($phone, -10);
        $expr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`$phoneField`,' ',''),'+',''),'-',''),'(',''),')','')";
        $date = $table === 'callback' ? 'date_create' : 'data_create';
        if (\R::getCell("SELECT id FROM `$table` WHERE RIGHT($expr,10)=? AND `$date` >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 1",[$digits])) {
            throw new \DomainException('Заявка с этим телефоном уже принята. Дождитесь звонка менеджера.');
        }
    }

    public static function quantity($value): int
    {
        if (!is_scalar($value) || !preg_match('/^[1-9]\d{0,3}$/D', (string)$value)) throw new \DomainException('Укажите количество от 1 до 9999.');
        return (int)$value;
    }

    public static function source(): string
    {
        $uri = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $host = parse_url($uri, PHP_URL_HOST);
        $siteHost = parse_url(PATH, PHP_URL_HOST);
        if ($host && strcasecmp($host, (string)$siteHost) !== 0) return '';
        return mb_substr((string)(parse_url($uri, PHP_URL_PATH) ?? ''), 0, 500);
    }
}
