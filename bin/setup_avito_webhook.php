<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/init.php';
\ishop\App::$app = \ishop\Registry::instance();
$params = require CONF.'/params.php';
\ishop\App::$app->setProperty('avito', (array)($params['avito'] ?? []));
try {
    $hook = new \app\services\AvitoWebhook();
    $result = $hook->activate(new \app\services\AvitoApiClient());
    $hook->probe();
    echo 'Avito webhook: '.($result['new_subscription'] ? 'registered' : 'already registered').PHP_EOL;
    echo "Avito webhook public endpoint: OK (2s timeout)\n";
} catch (\Throwable $e) {
    // Upstream exceptions can contain the private callback URL. Never print them.
    fwrite(STDERR, "Avito webhook setup failed. Check credentials, subscription and server storage.\n"); exit(1);
}
