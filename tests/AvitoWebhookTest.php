<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\AvitoApiClient;
use app\services\AvitoWebhook;
class WebhookFakeApi extends AvitoApiClient
{
    public array $subscriptions = [];
    public int $registrations = 0;
    public function __construct() {}
    public function api(string $method, string $path, array $options = []): array
    {
        if ($path === '/core/v1/accounts/self') return ['id'=>123];
        if ($path === '/messenger/v1/subscriptions') return ['subscriptions'=>$this->subscriptions];
        if ($path === '/messenger/v3/webhook') {
            $this->registrations++; $this->subscriptions[]=['url'=>$options['json']['url'],'version'=>'3']; return ['ok'=>true];
        }
        throw new RuntimeException('Unexpected API call');
    }
}
function webhookCheck($ok, $label) { if (!$ok) throw new RuntimeException($label); }
$dir = sys_get_temp_dir().'/avito-hook-test-'.bin2hex(random_bytes(6));
$hook = new AvitoWebhook($dir); $api = new WebhookFakeApi();
try {
    webhookCheck(!$hook->authorized(''), 'Unconfigured receiver rejects requests');
    $hook->activate($api); $hook->activate($api);
    webhookCheck($api->registrations === 1, 'Setup preserves existing subscription and is idempotent');
    parse_str(parse_url($api->subscriptions[0]['url'], PHP_URL_QUERY), $query);
    webhookCheck($hook->authorized($query['key']) && !$hook->authorized('wrong'), 'Secret callback key required');
    webhookCheck($hook->accept([]) === 'probe', 'Empty Avito probe accepted');
    $event = ['id'=>'event-a','payload'=>['type'=>'message','value'=>['user_id'=>123,'chat_id'=>'chat-a','id'=>'message-a','created'=>12345,'content'=>['text'=>'private message']]]];
    webhookCheck($hook->accept($event) === 'accepted', 'Incoming event accepted');
    webhookCheck($hook->accept($event) === 'duplicate', 'Duplicate delivery suppressed');
    $status = $hook->status();
    webhookCheck($status['registered'] && $status['revision'] === 1, 'Only unique events change revision');
    webhookCheck(strpos(json_encode($status), $query['key']) === false, 'Status never exposes callback key');
    webhookCheck(strpos(file_get_contents($dir.'/events.json'), 'private message') === false, 'Message bodies not persisted');
    $event['id']='other'; $event['payload']['value']['user_id']=456;
    webhookCheck($hook->accept($event) === 'ignored' && $hook->status()['revision'] === 1, 'Other account ignored');
    $event['payload']['value']['user_id']=123;
    $hook->accept($event);
    webhookCheck((new AvitoWebhook($dir))->status()['revision'] === 2, 'Revision survives new process');
    echo "AvitoWebhook: OK\n";
} finally {
    if (is_dir($dir)) {
        foreach (glob($dir.'/*') ?: [] as $file) if (is_file($file)) unlink($file);
        if (is_file($dir.'/.htaccess')) unlink($dir.'/.htaccess');
        rmdir($dir);
    }
}
