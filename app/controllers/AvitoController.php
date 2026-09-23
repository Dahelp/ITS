<?php

namespace app\controllers;

use app\models\admin\Avito;

class AvitoController extends AppController
{
    public function imageAction()
    {
        $this->layout = false;
        $this->view = false;

        $url = isset($_GET['u']) ? urldecode((string)$_GET['u']) : '';
        if ($url === '' || !preg_match('~^https?://([^/]+\.)?avito\.ru/~i', $url)) {
            http_response_code(400);
            exit;
        }

        $url = preg_replace('~^http://~i', 'https://', $url);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Referer: https://www.avito.ru/',
            ],
        ]);
        $data = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode !== 200 || !$data || stripos($contentType, 'image/') === false) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $contentType);
        header('Cache-Control: public, max-age=86400');
        echo $data;
        exit;
    }
    public function refreshTovarsAction()
    {
        $this->layout = false;
        $this->view = false;

        $xml = Avito::buildFeedXml();

        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $xml;
        exit;
    }
}

