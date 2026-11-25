<?php
namespace app\services;

/** Shared validation for short lead forms. No mail is sent until a lead is saved. */
final class LeadForm
{
    /** Rendering a public page must not create a session or a visitor token. */
    public static function tokenField(string $scope): string
    {
        if (!in_array($scope, ['callback', 'oneclick'], true)) throw new \InvalidArgumentException('Invalid lead scope');
        $active = session_status() === PHP_SESSION_ACTIVE;
        $token = $active ? self::token($scope) : '';
        $html = '<input type="hidden" name="lead_token" data-lead-scope="'.$scope.'" value="'.$token.'">';
        if (!$active) {
            $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
            // A local path only; avoid network-path links and control characters.
            $path = '/' . ltrim(str_replace(["\r", "\n", "\\"], ['', '', '/'], $path), '/');
            // Apache's front-controller rewrite adds a routing key to $_GET.
            // Rebuild only the query that the visitor actually requested.
            parse_str((string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_QUERY) ?? ''), $query);
            $query['lead_form'] = '1';
            $url = htmlspecialchars($path . '?' . http_build_query($query), ENT_QUOTES, 'UTF-8');
            $html .= '<noscript><p>Перед отправкой <a rel="nofollow" href="'.$url.'">подготовьте форму без JavaScript</a>.</p></noscript>';
        }
        return $html;
    }

    public static function token(string $scope): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !session_start()) throw new \RuntimeException('Cannot start lead session');
        return $_SESSION['lead_tokens'][$scope] ??= bin2hex(random_bytes(32));
    }

    public static function phone($value): string
    {
        if (!is_string($value) || !preg_match('/^\+?[0-9 ()-]+$/D', $value)) {
            throw new \DomainException('Укажите полный номер мобильного телефона РФ: +7 9ХХ ХХХ-ХХ-ХХ.');
        }
        $digits = preg_replace('/\D+/', '', $value);
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

    /** Serialize the duplicate check and save across independent sessions. */
    public static function withPhoneLock(string $phone, callable $save)
    {
        $key = 'lead:' . substr(hash('sha256', $phone), 0, 56);
        if ((int)\R::getCell('SELECT GET_LOCK(?, 5)', [$key]) !== 1) {
            throw new \DomainException('Заявка обрабатывается. Подождите и попробуйте снова.');
        }
        try {
            self::rejectRecent('callback', 'phone', $phone);
            self::rejectRecent('mail_oneclick', 'tell_click', $phone);
            return $save();
        } finally {
            \R::getCell('SELECT RELEASE_LOCK(?)', [$key]);
        }
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
