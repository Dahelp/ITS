<?php
namespace app\controllers;

class AvitoWebhookController extends \ishop\base\Controller
{
    public function receiveAction()
    {
        $this->layout = false; $this->view = false;
        header('Cache-Control: no-store'); header('Content-Type: application/json');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
        $receiver = new \app\services\AvitoWebhook();
        if (!$receiver->authorized((string)($_GET['key'] ?? ''))) { http_response_code(403); exit; }
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 262144) { http_response_code(413); exit; }
        try {
            $body = file_get_contents('php://input', false, null, 0, 262145);
            if (strlen($body) > 262144) { http_response_code(413); exit; }
            $event = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($event)) throw new \InvalidArgumentException('Invalid JSON object');
            $receiver->accept($event);
            echo '{"ok":true}';
        } catch (\JsonException | \InvalidArgumentException $e) { http_response_code(400); }
        catch (\Throwable $e) { http_response_code(503); header('Retry-After: 1'); }
        exit;
    }
}
