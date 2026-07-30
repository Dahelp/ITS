<?php

declare(strict_types=1);

if (!hash_equals('c81f7a6d930e42b5', (string)($_GET['token'] ?? ''))) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');

echo function_exists('opcache_reset') && opcache_reset()
    ? 'OPCACHE_RESET_OK'
    : 'OPCACHE_RESET_UNAVAILABLE';
