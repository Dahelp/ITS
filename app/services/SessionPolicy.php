<?php
namespace app\services;

/** Request/session policy, loaded before the application and before output. */
final class SessionPolicy
{
    public static function isPrivatePath(string $path): bool
    {
        return (bool)preg_match('~^/(?:user|cart|order|checkout|comparison|wishlist|favorites|admin|session)(?:/|$)~i', $path);
    }

    public static function needsSession(string $path, string $method, array $cookies, array $query): bool
    {
        // /session endpoints validate the request before opening a session.
        return self::isPrivatePath($path)
            || !in_array($method, ['GET', 'HEAD'], true)
            || (isset($cookies[session_name()]) && is_string($cookies[session_name()]) && $cookies[session_name()] !== '')
            || (($query['lead_form'] ?? null) === '1');
    }

    public static function register(string $path, string $method): void
    {
        if (($_GET['lead_form'] ?? null) === '1') {
            header('X-Robots-Tag: noindex, follow');
        }
        // PHP's default nocache limiter otherwise emits no-store on session_start.
        // The final callback also covers controllers that close their session early.
        if (session_status() === PHP_SESSION_NONE) { session_cache_limiter(''); }
        header_register_callback(static function () use ($path, $method): void {
            $headers = headers_list();
            $hasCacheControl = false;
            $setsCookie = false;
            foreach ($headers as $header) {
                $hasCacheControl = $hasCacheControl || stripos($header, 'Cache-Control:') === 0;
                $setsCookie = $setsCookie || stripos($header, 'Set-Cookie:') === 0;
            }
            if (self::isPrivatePath($path) || !in_array($method, ['GET', 'HEAD'], true)
                || session_id() !== '' || !empty($_COOKIE[session_name()]) || $setsCookie
                || http_response_code() >= 400) {
                header('Cache-Control: private, no-store, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
            } elseif (!$hasCacheControl) {
                // Storage/revalidation is allowed. Shared HTML caching is stage 3.
                header('Cache-Control: no-cache');
            }
        });
    }
}
