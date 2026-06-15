<?php

namespace app\models\admin;

use app\models\AppModel;
use Guzzlehttp\Guzzle;

class PlaginsIndexnow extends AppModel{
	
	public $attributes = [
		'search_engine' => '',
		'url' => '',
        'verification' => '',
        'hide' => '',
	
    ];

    public $rules = [
        'required' => [
            ['search_engine'],
            ['url'],
            ['verification'],
        ],
    ];

	public function indexNowEngine(string $url, string $controller, ?string $alias, string $verification): string
    {
        // Приводим alias к строке и убираем пробелы
        $alias = trim((string)$alias);

        // Если alias пустой — не дергаем IndexNow, но ничего не ломаем
        if ($alias === '') {
            return '<br>' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . ' IndexNow: SKIP (пустой alias товара)';
        }

        // Соберём endpoint корректно (с протоколом и /indexnow в конце)
        $endpoint = $url;
        if (!preg_match('~^https?://~i', $endpoint)) {
            $endpoint = 'https://' . ltrim($endpoint, '/');
        }
        // Если в конце уже есть /indexnow, не дублируем
        $endpoint = rtrim($endpoint, '/');
        if (!preg_match('~/indexnow$~i', $endpoint)) {
            $endpoint .= '/indexnow';
        }

        // Целевой URL страницы, которую пингуем
        // PATH обычно вроде "https://its-center.ru"
        $base = defined('PATH') ? rtrim(PATH, '/') : '';
        $targetUrl = $base . '/' . trim($controller, '/') . '/' . ltrim($alias, '/');

        try {
            $client = new \GuzzleHttp\Client([
                'timeout'         => 2.5,
                'connect_timeout' => 1.5,
            ]);

            $resp = $client->get($endpoint, [
                'query' => [
                    'url' => $targetUrl,
                    'key' => $verification,
                ],
                'headers' => [
                    'User-Agent' => 'ITS-Center IndexNow Client',
                    'Accept'     => 'application/json,text/plain,*/*',
                ],
                'http_errors' => false,
            ]);

            $code   = $resp->getStatusCode();
            $status = ($code >= 200 && $code < 300) ? 'OK' : ('HTTP ' . $code);

        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (mb_strlen($msg) > 120) {
                $msg = mb_substr($msg, 0, 117) . '...';
            }
            $status = 'SKIP: ' . $msg;
        }

        return '<br>' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . ' IndexNow: ' . $status;
    }
	
	public function checkUnique(){
        $indexnow = \R::findOne('plagins_indexnow', 'search_engine = ?', [$this->attributes['search_engine']]);
        if($indexnow){
            if($indexnow->search_engine == $this->attributes['search_engine']){
                $this->errors['unique'][] = 'Поисковая система уже существует';
            }
            return false;
        }
        return true;
    }
	
}