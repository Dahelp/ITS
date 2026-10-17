<?php
namespace app\controllers\admin;

use app\services\AvitoMessenger;
use app\services\AvitoDocumentStore;

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
        \app\services\AvitoChatWorkspace::ensureSchema();
        $allowed = AvitoMessenger::allowed((int)$_SESSION['user']['id']);
        $csrf = $this->csrf();
        $maxImageBytes = AvitoMessenger::maxUploadBytes();
        $maxDocumentBytes = AvitoDocumentStore::maxUploadBytes();
        $this->setMeta('Чат Авито');
        $this->set(compact('allowed', 'csrf', 'maxImageBytes', 'maxDocumentBytes'));
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
            $operation = (string)($_GET['operation'] ?? '');
            if ($operation !== 'events') AvitoMessenger::ensureSchema();
            if (!AvitoMessenger::allowed((int)$_SESSION['user']['id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ к чату отключён в настройках.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if (!in_array($operation, ['workspace','assign','note','template-save','template-delete','events', 'upload-document', 'send-document', 'revoke-document', 'voice', 'send-image', 'unread', 'check', 'chats', 'messages', 'send', 'read'], true)) {
                throw new \InvalidArgumentException('Неизвестная операция.');
            }
            if (in_array($operation, ['assign','note','template-save','template-delete','upload-document', 'send-document', 'revoke-document', 'send', 'send-image', 'read'], true)) { $this->checkPost(); }
            elseif (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
                throw new \InvalidArgumentException('Требуется GET-запрос.');
            }
            $result = (new \app\services\AvitoChatOperations())->execute($operation, $_GET, $_POST, $_FILES, (int)$_SESSION['user']['id']);
            echo json_encode(['data' => $result], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            // Do not return upstream bodies, credentials or customer data to the browser.
            http_response_code(502);
            $message = 'Авито недоступен. Проверьте подключение API и повторите запрос.';
            if (($operation ?? '') === 'upload-document') {
                $message = 'Не удалось сохранить документ. Повторите загрузку или обратитесь к администратору.';
            }
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
