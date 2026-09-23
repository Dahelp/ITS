<?php

namespace app\services;

use ishop\App;

class AvitoApiClient
{
    private string $tokenUrl = 'https://api.avito.ru/token';
    private string $apiBaseUrl = 'https://api.avito.ru';
    private string $cacheFile;

    public function __construct(?string $cacheFile = null)
    {
        $this->cacheFile = $cacheFile ?: ROOT . '/tmp/cache/avito_token.json';
    }

    public function getAccessToken(bool $forceRefresh = false): string
    {
        if (!$forceRefresh) {
            $cached = $this->readCachedToken();
            if ($cached !== '') {
                return $cached;
            }
        }

        $cfg = $this->config();
        $clientId = trim((string)($cfg['client_id'] ?? ''));
        $clientSecret = trim((string)($cfg['client_secret'] ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('Avito API client_id/client_secret не настроены.');
        }

        $response = $this->request('POST', $this->tokenUrl, [
            'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            'body' => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ], '', '&'),
            'auth' => false,
        ]);

        if (empty($response['access_token'])) {
            throw new \RuntimeException('Avito API не вернул access_token.');
        }

        $expiresIn = (int)($response['expires_in'] ?? 86400);
        $payload = [
            'access_token' => (string)$response['access_token'],
            'expires_at' => time() + max(60, $expiresIn - 300),
        ];
        $this->writeCachedToken($payload);

        return $payload['access_token'];
    }

    public function api(string $method, string $path, array $options = []): array
    {
        $url = rtrim($this->apiBaseUrl, '/') . '/' . ltrim($path, '/');
        $options['auth'] = true;

        try {
            return $this->request($method, $url, $options);
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), 'HTTP 401') === false) {
                throw $e;
            }
            $this->getAccessToken(true);
            return $this->request($method, $url, $options);
        }
    }

    private function request(string $method, string $url, array $options = []): array
    {
        $headers = (array)($options['headers'] ?? []);
        if (!empty($options['auth'])) {
            $headers[] = 'Authorization: Bearer ' . $this->getAccessToken();
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if (array_key_exists('body', $options)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$options['body']);
        }

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('Avito API curl error: ' . $error);
        }

        $decoded = json_decode((string)$body, true);
        if ($code < 200 || $code >= 300) {
            $message = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : (string)$body;
            throw new \RuntimeException('Avito API HTTP ' . $code . ': ' . $message);
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function readCachedToken(): string
    {
        if (!is_file($this->cacheFile)) {
            return '';
        }
        $data = json_decode((string)file_get_contents($this->cacheFile), true);
        if (!is_array($data) || empty($data['access_token']) || (int)($data['expires_at'] ?? 0) <= time()) {
            return '';
        }
        return (string)$data['access_token'];
    }

    private function writeCachedToken(array $payload): void
    {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($this->cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function config(): array
    {
        return (array)(App::$app->getProperty('avito') ?? []);
    }
}
