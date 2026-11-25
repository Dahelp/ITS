<?php
namespace app\services;

/** Same-origin, non-cacheable token issuance, deliberately independent of DB. */
final class LeadTokenEndpoint
{
    public static function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            self::respond(405, ['ok' => false]);
            return;
        }
        // The custom header requires CORS preflight for foreign origins. No CORS
        // grant is issued here; additionally reject explicit cross-site requests.
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $expected = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
            || ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site'
            || ($origin !== '' && strcasecmp($origin, $expected) !== 0)) {
            self::respond(403, ['ok' => false]);
            return;
        }
        $scope = $_POST['scope'] ?? '';
        if (!is_string($scope) || !in_array($scope, ['callback', 'oneclick'], true)) {
            self::respond(400, ['ok' => false]);
            return;
        }
        $token = LeadForm::token($scope);
        TrafficSource::capture([], (string)($_SERVER['HTTP_REFERER'] ?? ''));
        session_write_close();
        self::respond(200, ['ok' => true, 'token' => $token]);
    }

    private static function respond(int $status, array $data): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
