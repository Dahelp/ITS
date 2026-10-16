<?php
namespace app\services;

/** Server-side gateway for the single connected Avito account. */
class AvitoMessenger
{
    private AvitoApiClient $client;
    private ?string $accountId = null;

    public function __construct(?AvitoApiClient $client = null)
    {
        $this->client = $client ?: new AvitoApiClient();
    }

    public static function ensureSchema(): void
    {
        \R::exec('CREATE TABLE IF NOT EXISTS avito_chat_manager (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            enabled_by INT UNSIGNED NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public static function allowed(int $userId): bool
    {
        return (bool)\R::getCell("SELECT u.id FROM user u INNER JOIN avito_chat_manager m ON m.user_id = u.id WHERE u.id = ? AND u.role = 'admin'", [$userId]);
    }

    public static function saveManagers(array $ids, int $actor): void
    {
        $ids = array_unique(array_filter(array_map('intval', $ids), static function ($id) { return $id > 0; }));
        \R::begin();
        try {
            \R::exec('DELETE FROM avito_chat_manager');
            foreach ($ids as $id) {
                \R::exec("INSERT INTO avito_chat_manager (user_id, enabled_by) SELECT id, ? FROM user WHERE id = ? AND role = 'admin'", [$actor, $id]);
            }
            \R::commit();
        } catch (\Throwable $e) {
            \R::rollback();
            throw $e;
        }
    }

    private function base(int $version): string
    {
        if ($this->accountId === null) {
            $account = $this->client->api('GET', '/core/v1/accounts/self');
            $id = (string)($account['id'] ?? '');
            if (!ctype_digit($id) || $id === '0') {
                throw new \RuntimeException('Не удалось определить аккаунт Авито.');
            }
            $this->accountId = $id;
        }
        return '/messenger/v'.$version.'/accounts/'.$this->accountId;
    }

    private function chat(string $id): string
    {
        if ($id === '' || strlen($id) > 256 || preg_match('/[\x00-\x20\x7f]/', $id)) {
            throw new \InvalidArgumentException('Некорректный идентификатор чата.');
        }
        return rawurlencode($id);
    }

    public function chats(int $offset = 0, bool $unread = false): array
    {
        return $this->client->api('GET', $this->base(2).'/chats?'.http_build_query([
            'limit' => 50, 'offset' => max(0, $offset), 'unread_only' => $unread ? 'true' : 'false',
        ]));
    }

    public function diagnose(): array
    {
        $response = $this->chats();
        $chats = $response['chats'] ?? null;
        if (!is_array($chats)) {
            throw new \RuntimeException('Unexpected Messenger response');
        }
        $result = ['account' => true, 'chats' => true, 'sample_chat_count' => count($chats), 'history' => null];
        if (!empty($chats[0]['id'])) {
            $this->messages((string)$chats[0]['id']);
            $result['history'] = true;
        }
        return $result;
    }

    public function messages(string $chat, int $offset = 0): array
    {
        $chat = $this->chat($chat);
        return $this->client->api('GET', $this->base(3).'/chats/'.$chat.'/messages/?'.http_build_query([
            'limit' => 100, 'offset' => max(0, $offset),
        ]));
    }

    public function send(string $chat, string $text): array
    {
        $chat = $this->chat($chat);
        $text = trim($text);
        if ($text === '' || mb_strlen($text, 'UTF-8') > 1000) {
            throw new \InvalidArgumentException('Введите сообщение длиной до 1000 символов.');
        }
        return $this->client->api('POST', $this->base(1).'/chats/'.$chat.'/messages', [
            'json' => ['message' => ['text' => $text], 'type' => 'text'],
        ]);
    }

    public function read(string $chat): array
    {
        $chat = $this->chat($chat);
        return $this->client->api('POST', $this->base(1).'/chats/'.$chat.'/read');
    }
}
