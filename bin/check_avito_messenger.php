<?php
// Read-only live check. Never send messages or mark conversations as read.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/init.php';
\ishop\App::$app = \ishop\Registry::instance();
$params = require CONF.'/params.php';
\ishop\App::$app->setProperty('avito', (array)($params['avito'] ?? []));
$stage = 'authentication';
try {
    $client = new \app\services\AvitoApiClient();
    $client->getAccessToken();
    echo "Authentication: OK\n";
    $stage = 'account';
    $account = $client->api('GET', '/core/v1/accounts/self');
    if (empty($account['id'])) { throw new RuntimeException('Account ID missing'); }
    echo "Account: OK\n";
    $stage = 'messenger';
    $result = (new \app\services\AvitoMessenger($client))->diagnose();
    echo 'Messenger: OK; sample chats: '.$result['sample_chat_count'].PHP_EOL;
    echo 'History: '.($result['history'] ? 'OK' : 'not checked (no chats)').PHP_EOL;
    echo "No messages sent; no read receipts sent.\n";
} catch (\Throwable $e) {
    $detail = 'Connection or configuration error';
    if (preg_match('/HTTP (\d{3})/', $e->getMessage(), $match)) { $detail = 'HTTP '.$match[1]; }
    elseif (strpos($e->getMessage(), 'curl error') !== false) { $detail = 'Network/TLS connection error'; }
    fwrite(STDERR, $stage.': FAILED ('.$detail.")\n");
    exit(1);
}
