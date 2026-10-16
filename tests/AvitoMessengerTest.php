<?php
require dirname(__DIR__).'/vendor/autoload.php';

use app\services\AvitoApiClient;
use app\services\AvitoMessenger;

class MessengerFakeApi extends AvitoApiClient
{
    public array $calls = [];
    public function __construct() {}
    public function api(string $method, string $path, array $options = []): array
    {
        $this->calls[] = [$method, $path, $options];
        return $path === '/core/v1/accounts/self' ? ['id' => 12345] : ['messages' => []];
    }
}
function messengerCheck(bool $value, string $message): void
{
    if (!$value) { throw new RuntimeException($message); }
}
$api = new MessengerFakeApi();
$chat = new AvitoMessenger($api);
$chat->chats(-10, true);
messengerCheck($api->calls[1][1] === '/messenger/v2/accounts/12345/chats?limit=50&offset=0&unread_only=true', 'Account-scoped chats and unread filter');
$chat->messages('u2i-example', 100);
messengerCheck($api->calls[2][1] === '/messenger/v3/accounts/12345/chats/u2i-example/messages/?limit=100&offset=100', 'Message history pagination');
$chat->send('u2i-example', ' Привет ');
messengerCheck($api->calls[3] === ['POST', '/messenger/v1/accounts/12345/chats/u2i-example/messages', ['json' => ['message' => ['text' => 'Привет'], 'type' => 'text']]], 'Text payload');
$chat->read('u2i-example');
messengerCheck($api->calls[4][0] === 'POST' && substr($api->calls[4][1], -5) === '/read', 'Explicit read receipt');
messengerCheck(count(array_filter($api->calls, static function ($call) { return $call[1] === '/core/v1/accounts/self'; })) === 1, 'Resolve account once per gateway');
$count = count($api->calls);
foreach (['', '  ', str_repeat('я', 1001)] as $invalid) {
    try { $chat->send('example', $invalid); throw new RuntimeException('Invalid text accepted'); }
    catch (InvalidArgumentException $expected) {}
}
foreach (['', "chat\n", str_repeat('a', 257)] as $invalid) {
    try { $chat->messages($invalid); throw new RuntimeException('Invalid chat accepted'); }
    catch (InvalidArgumentException $expected) {}
}
messengerCheck(count($api->calls) === $count, 'Invalid input sends no upstream requests');
$chat->messages('../other?x=1');
messengerCheck(strpos(end($api->calls)[1], '/chats/..%2Fother%3Fx%3D1/messages/') !== false, 'Chat IDs cannot change API path');
echo "AvitoMessenger: OK\n";
