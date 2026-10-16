<?php
namespace app\controllers\admin;

use app\services\AvitoMessenger;

class AvitoChatController extends AppController
{
    private function csrf(): string
    {
        if (empty($_SESSION['avito_chat_csrf'])) {
            $_SESSION['avito_chat_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['avito_chat_csrf'];
    }

    private function checkPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            throw new \InvalidArgumentException('Требуется POST-запрос.');
        }
        if (!hash_equals($this->csrf(), (string)($_POST['csrf'] ?? ''))) {
            throw new \InvalidArgumentException('Обновите страницу и повторите действие.');
        }
    }

    public function indexAction()
    {
        if (($_SESSION['user']['role'] ?? '') === 'manager') { $this->layout = 'avito-chat'; }
        AvitoMessenger::ensureSchema();
        $allowed = AvitoMessenger::allowed((int)$_SESSION['user']['id']);
        $csrf = $this->csrf();
        $maxImageBytes = AvitoMessenger::maxUploadBytes();
        $this->setMeta('Чат Авито');
        $this->set(compact('allowed', 'csrf', 'maxImageBytes'));
    }

    public function settingsAction()
    {
        redirect(ADMIN.'/user/customers');
    }

    public function apiAction()
    {
        $this->layout = false;
        $this->view = false;
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        try {
            AvitoMessenger::ensureSchema();
            if (!AvitoMessenger::allowed((int)$_SESSION['user']['id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ к чату отключён в настройках.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $operation = (string)($_GET['operation'] ?? '');
            if (!in_array($operation, ['voice', 'send-image', 'unread', 'check', 'chats', 'messages', 'send', 'read'], true)) {
                throw new \InvalidArgumentException('Неизвестная операция.');
            }
            if (in_array($operation, ['send', 'send-image', 'read'], true)) { $this->checkPost(); }
            elseif (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
                throw new \InvalidArgumentException('Требуется GET-запрос.');
            }
            $client = new AvitoMessenger();
            $offset = max(0, min(100000, (int)($_GET['offset'] ?? 0)));
            switch ($operation) {
                case 'voice': $result = $client->voiceUrl((string)($_GET['voice_id'] ?? '')); break;
                case 'send-image':
                    $file = $_FILES['image'] ?? [];
                    if (!is_array($file) || !isset($file['error']) || !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                        throw new \InvalidArgumentException('Не удалось загрузить фото. Проверьте размер файла и выберите его заново.');
                    }
                    if (!is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
                        throw new \InvalidArgumentException('Выберите фотографию для отправки.');
                    }
                    $result = $client->sendPhoto((string)($_POST['chat'] ?? ''), $file['tmp_name']);
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
                case 'chats': $result = $client->chatsWithStatus($offset, !empty($_GET['unread'])); break;
                case 'messages': $result = $client->messages((string)($_GET['chat'] ?? ''), $offset); break;
                case 'send': $result = $client->send((string)($_POST['chat'] ?? ''), (string)($_POST['text'] ?? '')); break;
                case 'read': $result = $client->read((string)($_POST['chat'] ?? '')); break;
            }
            echo json_encode(['data' => $result], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            // Do not return upstream bodies, credentials or customer data to the browser.
            http_response_code(502);
            $message = 'Авито недоступен. Проверьте подключение API и повторите запрос.';
            if (strpos($e->getMessage(), 'HTTP 402') !== false || strpos($e->getMessage(), 'HTTP 403') !== false) {
                $message = 'Авито не разрешил доступ. Проверьте права и подписку Messenger API.';
            } elseif (strpos($e->getMessage(), 'HTTP 429') !== false) {
                $message = 'Достигнут лимит запросов Авито. Повторите позже.';
            }
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
