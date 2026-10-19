<?php
namespace app\services;

/** Shared operations for the admin and mobile clients. */
class AvitoChatOperations
{
    public const WRITES = ['assign','note','template-save','template-delete','upload-document','send-document','revoke-document','send','send-image','read'];
    public const READS = ['workspace','listing','events','voice','unread','check','chats','messages'];
    private AvitoMessenger $client;
    public function __construct(?AvitoMessenger $client = null) { $this->client = $client ?: new AvitoMessenger(); }
    public function execute(string $operation, array $query, array $input, array $files, int $actor): array
    {
        if (!in_array($operation, array_merge(self::WRITES, self::READS), true)) throw new \InvalidArgumentException('Неизвестная операция.');
            $client = $this->client;
            $offset = max(0, min(100000, (int)($query['offset'] ?? 0)));
            switch ($operation) {
                case 'workspace': $result = \app\services\AvitoChatWorkspace::workspace((string)($query['chat']??''),$actor); break;
                case 'listing': $result = AvitoListingCard::find((string)($query['item_id'] ?? '')); break;
                case 'assign': \app\services\AvitoChatWorkspace::assign((string)($input['chat']??''),(int)($input['manager']??0),$actor); $result=['ok'=>true]; break;
                case 'note': \app\services\AvitoChatWorkspace::addNote((string)($input['chat']??''),(string)($input['body']??''),$actor); $result=['ok'=>true]; break;
                case 'template-save': \app\services\AvitoChatWorkspace::saveTemplate((int)($input['id']??0),(string)($input['title']??''),(string)($input['body']??''),$actor); $result=['ok'=>true]; break;
                case 'template-delete': \app\services\AvitoChatWorkspace::deleteTemplate((int)($input['id']??0),$actor); $result=['ok'=>true]; break;
                case 'events': $result = (new \app\services\AvitoWebhook())->status(); break;
                case 'upload-document':
                    $file = $files['document'] ?? [];
                    if (!is_array($file) || ($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
                        throw new \InvalidArgumentException('Не удалось загрузить документ. Проверьте размер и выберите файл заново.');
                    }
                    $result = (new AvitoDocumentStore())->store($file['tmp_name'], (string)($file['name'] ?? ''), (string)($input['chat'] ?? ''), $actor);
                    break;
                case 'send-document':
                    $token = (string)($input['token'] ?? ''); $chat = (string)($input['chat'] ?? '');
                    $store = new AvitoDocumentStore();
                    try { $document = $store->forChat($token, $chat); }
                    catch (\RuntimeException $e) { throw new \InvalidArgumentException('Ссылка не действует. Подготовьте документ заново.'); }
                    $expires = (new \DateTimeImmutable('@'.$document['expires_at']))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('d.m.Y H:i');
                    $result = $client->send($chat, 'Документ: '.$document['name']."\n".AvitoDocumentStore::url($token)."\nСсылка действует до ".$expires.' (МСК).');
                    break;
                case 'revoke-document':
                    try { (new AvitoDocumentStore())->revoke((string)($input['token'] ?? ''), (string)($input['chat'] ?? '')); }
                    catch (\RuntimeException $e) { throw new \InvalidArgumentException('Ссылка уже не действует.'); }
                    $result = ['revoked'=>true];
                    break;
                case 'voice': $result = $client->voiceUrl((string)($query['voice_id'] ?? '')); break;
                case 'send-image':
                    $file = $files['image'] ?? [];
                    if (!is_array($file) || !isset($file['error']) || !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                        throw new \InvalidArgumentException('Не удалось загрузить фото. Проверьте размер файла и выберите его заново.');
                    }
                    if (!is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
                        throw new \InvalidArgumentException('Выберите фотографию для отправки.');
                    }
                    $result = $client->sendPhoto((string)($input['chat'] ?? ''), $file['tmp_name']);
                    break;
                case 'unread':
                    $unreadChats = $client->chats(0, true);
                    if (!isset($unreadChats['chats']) || !is_array($unreadChats['chats'])) {
                        throw new \RuntimeException('Unexpected unread response');
                    }
                    $count = count($unreadChats['chats']);
                    $result = ['count' => $count, 'has_more' => $count >= 50];
                    break;
                case 'check': $result = $client->diagnose(); break;
                case 'chats': $result = AvitoChatWorkspace::chatPage($client, $offset, !empty($query['unread']), (string)($query['assignee'] ?? 'all'), $actor); break;
                case 'messages': $result = $client->messages((string)($query['chat'] ?? ''), $offset); break;
                case 'send': $result = $client->send((string)($input['chat'] ?? ''), (string)($input['text'] ?? '')); break;
                case 'read': $result = $client->read((string)($input['chat'] ?? '')); break;
            }
            return $result;
    }
}
