<?php
namespace app\services;

class AvitoWebhook
{
    private string $directory;
    public function __construct(?string $directory = null) { $this->directory = $directory ?: ROOT.'/storage/avito-webhook'; }

    private function config(): array
    {
        $path = $this->directory.'/config.json';
        $data = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
        return is_array($data) ? $data : [];
    }

    private function writeConfig(array $data): void
    {
        $path = tempnam($this->directory, '.config-');
        if (!$path) throw new \RuntimeException('Webhook configuration unavailable');
        try {
            if (file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR), LOCK_EX) === false || !rename($path, $this->directory.'/config.json')) {
                throw new \RuntimeException('Webhook configuration write failed');
            }
            @chmod($this->directory.'/config.json', 0600);
        } finally { if (is_file($path)) unlink($path); }
    }

    public function activate(AvitoApiClient $client): array
    {
        $account = $client->api('GET', '/core/v1/accounts/self');
        $accountId = (string)($account['id'] ?? '');
        if (!ctype_digit($accountId) || $accountId === '0') throw new \RuntimeException('Webhook account missing');
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) throw new \RuntimeException('Webhook storage unavailable');
        if (file_put_contents($this->directory.'/.htaccess', "Require all denied\n", LOCK_EX) === false) throw new \RuntimeException('Webhook storage protection failed');
        $config = $this->config();
        if (!$config) {
            $config = ['secret'=>bin2hex(random_bytes(32)), 'account_id'=>$accountId, 'registered'=>false];
            $this->writeConfig($config);
        }
        if (($config['account_id'] ?? '') !== $accountId) throw new \RuntimeException('Webhook account changed; review configuration');
        $url = 'https://its-center.ru/avito-webhook/receive?key='.$config['secret'];
        $subscriptions = $client->api('POST', '/messenger/v1/subscriptions');
        if (!isset($subscriptions['subscriptions']) || !is_array($subscriptions['subscriptions'])) throw new \RuntimeException('Webhook subscription list unavailable');
        $found = false;
        foreach ($subscriptions['subscriptions'] as $item) {
            if (($item['url'] ?? '') === $url && (string)($item['version'] ?? '') === '3') $found = true;
        }
        if (!$found) {
            $result = $client->api('POST', '/messenger/v3/webhook', ['json'=>['url'=>$url]]);
            if (($result['ok'] ?? false) !== true) throw new \RuntimeException('Webhook registration not confirmed');
        }
        $config['registered'] = true; $config['registered_at'] = time();
        $this->writeConfig($config);
        return ['registered'=>true, 'new_subscription'=>!$found];
    }

    public function authorized(string $key): bool
    {
        $config = $this->config();
        return !empty($config['secret']) && hash_equals((string)$config['secret'], $key);
    }

    public function probe(): void
    {
        $config = $this->config();
        if (empty($config['secret'])) throw new \RuntimeException('Webhook not configured');
        $curl = curl_init('https://its-center.ru/avito-webhook/receive?key='.$config['secret']);
        curl_setopt_array($curl, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>'{}', CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>2, CURLOPT_TIMEOUT=>2]);
        $body = curl_exec($curl); $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        if ($code !== 200 || (json_decode((string)$body, true)['ok'] ?? false) !== true) throw new \RuntimeException('Webhook probe failed');
    }

    public function accept(array $event): string
    {
        if (!$event) return 'probe';
        if (($event['payload']['type'] ?? '') !== 'message') return 'ignored';
        $value = $event['payload']['value'] ?? [];
        $config = $this->config();
        if (empty($config['account_id']) || (string)($value['user_id'] ?? '') !== (string)$config['account_id']) return 'ignored';
        $chat = $value['chat_id'] ?? null; $message = $value['id'] ?? null;
        if (!is_string($chat) || !is_string($message) || $chat === '' || $message === '' || strlen($chat) > 256 || strlen($message) > 256) {
            throw new \InvalidArgumentException('Invalid webhook message');
        }
        $eventId = $event['id'] ?? $chat.':'.$message;
        if (!is_string($eventId) || strlen($eventId) > 512) throw new \InvalidArgumentException('Invalid webhook event');
        $key = hash('sha256', $eventId);
        $handle = fopen($this->directory.'/events.lock', 'c');
        if (!$handle) throw new \RuntimeException('Webhook event storage unavailable');
        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) throw new \RuntimeException('Webhook event storage busy');
            $statePath = $this->directory.'/events.json';
            $state = is_file($statePath) ? json_decode((string)file_get_contents($statePath), true) : null;
            $state = $state ?: ['revision'=>0,'seen'=>[],'events'=>[]];
            if (isset($state['seen'][$key])) return 'duplicate';
            $state['revision']++; $state['last_received_at'] = time(); $state['seen'][$key] = true;
            $state['seen'] = array_slice($state['seen'], -1024, null, true);
            $state['events'][] = ['revision'=>$state['revision'], 'chat_id'=>$chat, 'message_id'=>$message, 'created'=>(int)($value['created'] ?? 0)];
            $state['events'] = array_slice($state['events'], -100);
            $encoded = json_encode($state, JSON_THROW_ON_ERROR);
            $temporary = tempnam($this->directory, '.events-');
            if (!$temporary) throw new \RuntimeException('Webhook event write failed');
            try {
                if (file_put_contents($temporary, $encoded, LOCK_EX) !== strlen($encoded) || !rename($temporary, $statePath)) throw new \RuntimeException('Webhook event write failed');
            } finally { if (is_file($temporary)) unlink($temporary); }
            @chmod($this->directory.'/events.json', 0600);
            return 'accepted';
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    public function status(): array
    {
        $config = $this->config(); $state = [];
        $path = $this->directory.'/events.json';
        if (is_file($path)) $state = json_decode((string)file_get_contents($path), true) ?: [];
        return ['registered'=>!empty($config['registered']), 'revision'=>(int)($state['revision'] ?? 0), 'last_received_at'=>$state['last_received_at'] ?? null];
    }
}
