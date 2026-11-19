<?php
namespace app\controllers {
    class AppController {}
}
namespace {
    require dirname(__DIR__) . '/app/controllers/FavoritesController.php';
    class R {
        public static $saved = [10];
        public static function getCell($sql, $args) { return in_array($args[0], [10, 20], true) ? $args[0] : null; }
        public static function exec($sql, $args) {
            if ($args[1] !== 7 || $args[3] !== 7 || $args[0] !== $args[2]) throw new \Exception('Wrong account binding');
            if (!in_array($args[0], self::$saved, true)) self::$saved[] = $args[0];
        }
        public static function count($table, $where, $args) {
            if ($args !== [7]) throw new \Exception('Wrong count account');
            return count(self::$saved);
        }
    }
    $controller = new \app\controllers\FavoritesController();
    if (!empty($argv[1])) {
        session_save_path(sys_get_temp_dir());
        session_start();
        $_SESSION['favorites_csrf'] = 'test-token';
        $_SESSION['user']['id'] = $argv[1] === 'guest' ? 0 : 7;
        $_SERVER['REQUEST_METHOD'] = $argv[1] === 'get' ? 'GET' : 'POST';
        $_POST = ['csrf' => $argv[1] === 'csrf' ? 'wrong' : 'test-token', 'ids' => [10, 20, 20, 99]];
        register_shutdown_function(function () { session_destroy(); echo "\n" . http_response_code(); });
        $controller->mergeAction();
        exit;
    }
    $method = new \ReflectionMethod($controller, 'ids');
    $method->setAccessible(true);
    $_POST['ids'] = [1, '2', 2, 0, -3];
    if ($method->invoke($controller) !== [1, 2]) throw new \Exception('ID validation failed');
    $_POST['ids'] = range(1, 600);
    if (count($method->invoke($controller)) !== 500) throw new \Exception('ID limit failed');
    $_POST['ids'] = 'not-an-array';
    if ($method->invoke($controller) !== []) throw new \Exception('Invalid list accepted');
    foreach (['guest' => [false, '401'], 'csrf' => [false, '403'], 'get' => [false, '403'], 'merge' => [true, '']] as $mode => $expected) {
        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($mode));
        $parts = explode("\n", trim($output));
        $response = json_decode($parts[0], true);
        if (($response['ok'] ?? null) !== $expected[0]) throw new \Exception('Unexpected result: ' . $mode . ' ' . $output);
        if ($expected[1] && end($parts) !== $expected[1]) throw new \Exception('Unexpected status: ' . $mode);
        if ($mode === 'merge' && $response['count'] !== 2) throw new \Exception('Merge must preserve existing, skip missing, deduplicate');
    }
    echo "Favorites controller: ID validation, limits, auth, CSRF, POST and additive account merge passed\n";
}
