<?php

namespace app\controllers;

class CookieController extends AppController {

    public function logAction() {
        $data = json_decode(file_get_contents('php://input'), true);
        $cookie_id = $data['cookie_session_id'] ?? '';
        $ua = $data['user_agent'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if ($cookie_id) {
            $log = date('Y-m-d H:i:s') . " | Cookie согласие | IP: $ip | UA: $ua | ID: $cookie_id\n";
            file_put_contents(ROOT . '/storage/logs/cookie_log.txt', $log, FILE_APPEND);
        }

        echo json_encode(['status' => 'ok']); exit;
    }
}
