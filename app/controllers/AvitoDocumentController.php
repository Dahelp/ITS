<?php
namespace app\controllers;

use app\services\AvitoDocumentStore;

class AvitoDocumentController extends \ishop\base\Controller
{
    public function downloadAction()
    {
        $this->layout = false; $this->view = false;
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Referrer-Policy: no-referrer');
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)) {
            http_response_code(405); header('Allow: GET, HEAD'); exit;
        }
        try {
            $meta = (new AvitoDocumentStore())->get((string)($_GET['token'] ?? ''));
            $stream = @fopen($meta['path'], 'rb');
            if (!$stream) throw new \RuntimeException('Документ не найден.', 404);
            header('Content-Type: '.$meta['mime']);
            header('Content-Length: '.fstat($stream)['size']);
            $fallback = $meta['mime'] === 'application/pdf' ? 'invoice.pdf' : 'invoice.docx';
            header("Content-Disposition: attachment; filename=\"".$fallback."\"; filename*=UTF-8''".rawurlencode($meta['name']));
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') fpassthru($stream);
            fclose($stream);
        } catch (\RuntimeException $e) {
            $status = in_array($e->getCode(), [404, 410], true) ? $e->getCode() : 404;
            http_response_code($status); header('Content-Type: text/plain; charset=utf-8');
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
                echo $status === 410 ? 'Ссылка больше не действует. Попросите продавца прислать новую.' : 'Документ не найден.';
            }
        }
        exit;
    }
}
