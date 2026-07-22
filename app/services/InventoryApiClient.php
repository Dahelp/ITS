<?php

namespace app\services;

final class InventoryApiClient
{
    private $config;
    private $cacheDir;
    private $logFile;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?: require ROOT . '/config/api.php';
        $this->cacheDir = ROOT . '/storage/cache/inventory_api';
        $this->logFile = ROOT . '/storage/logs/inventory_api.jsonl';
        if (!is_dir($this->cacheDir)) @mkdir($this->cacheDir, 0750, true);
        if (!is_dir(dirname($this->logFile))) @mkdir(dirname($this->logFile), 0750, true);
    }

    public static function normalizeArticle(string $article): string
    {
        $article = trim($article);
        if ($article === '') return '';
        $value = ltrim($article, '0');
        return $value === '' ? '0' : $value;
    }

    public function fetch(string $article, bool $allowStale = true): array
    {
        $article = self::normalizeArticle($article);
        if ($article === '') return ['ok' => false, 'source' => 'invalid', 'error' => 'empty article'];
        $cacheFile = $this->cacheDir . '/' . hash('sha256', $article) . '.json';
        $cache = $this->readCache($cacheFile);
        $ttl = max(5, (int)(getenv('INVENTORY_API_CACHE_TTL') ?: 60));
        $staleTtl = max($ttl, (int)(getenv('INVENTORY_API_STALE_TTL') ?: 86400));
        if ($cache && time() - $cache['saved_at'] <= $ttl) {
            return ['ok' => true, 'source' => 'cache', 'data' => $cache['data']];
        }

        $host = trim((string)($this->config['host'] ?? ''));
        $auth = $this->config['auth'] ?? [];
        if (!preg_match('~^https?://~i', $host) || count($auth) < 2) {
            return $this->fallback($cache, $staleTtl, $allowStale, 'API configuration is incomplete');
        }
        $url = $host . (strpos($host, '?') === false ? '?' : '&') . 'code=' . rawurlencode($article);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => (string)$auth[0] . ':' . (string)$auth[1],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_CONNECTTIMEOUT => max(1, (int)(getenv('INVENTORY_API_CONNECT_TIMEOUT') ?: 3)),
            CURLOPT_TIMEOUT => max(2, (int)(getenv('INVENTORY_API_TIMEOUT') ?: 8)),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = is_string($body) ? json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $body), true) : null;
        $row = is_array($decoded) && isset($decoded[0]) && is_array($decoded[0]) ? $decoded[0] : null;
        if ($status !== 200 || !$row || !array_key_exists('rest', $row)) {
            $message = $error ?: 'HTTP ' . $status . ' or invalid payload';
            $this->log('error', $article, ['http_status' => $status, 'error' => $message]);
            return $this->fallback($cache, $staleTtl, $allowStale, $message);
        }
        $data = $this->normalizeData($row);
        $tmp = $cacheFile . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, json_encode(['saved_at' => time(), 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        @rename($tmp, $cacheFile);
        return ['ok' => true, 'source' => 'api', 'data' => $data];
    }

    private function normalizeData(array $row): array
    {
        $row['rest'] = max(0, (int)($row['rest'] ?? 0));
        $row['reserve'] = max(0, (int)($row['reserve'] ?? 0));
        foreach (['price_rozn', 'price_spec', 'price_opt'] as $field) $row[$field] = max(0, (float)($row[$field] ?? 0));
        return $row;
    }

    private function fallback(?array $cache, int $staleTtl, bool $allowStale, string $error): array
    {
        if ($allowStale && $cache && time() - $cache['saved_at'] <= $staleTtl) return ['ok' => true, 'source' => 'stale_cache', 'data' => $cache['data'], 'warning' => $error];
        return ['ok' => false, 'source' => 'database', 'error' => $error];
    }

    private function readCache(string $file): ?array
    {
        if (!is_file($file)) return null;
        $value = json_decode((string)@file_get_contents($file), true);
        return is_array($value) && isset($value['saved_at'], $value['data']) && is_array($value['data']) ? $value : null;
    }

    public function log(string $event, string $article, array $context = []): void
    {
        @file_put_contents($this->logFile, json_encode(['time' => date('c'), 'event' => $event, 'article' => $article] + $context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
