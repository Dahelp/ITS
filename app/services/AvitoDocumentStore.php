<?php
namespace app\services;

/** Temporary invoice copies. Only token hashes are used as storage names. */
class AvitoDocumentStore
{
    public const LIFETIME = 7 * 86400;
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?: ROOT.'/storage/avito-documents';
    }

    public static function maxUploadBytes(): int
    {
        return min(10 * 1048576, AvitoMessenger::maxUploadBytes());
    }

    public static function validate(string $path, string $name): array
    {
        if (!is_file($path) || !is_readable($path) || filesize($path) <= 0 || filesize($path) > self::maxUploadBytes()) {
            throw new \InvalidArgumentException('Документ пуст или превышает допустимый размер.');
        }
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1f\x7f]/u', '_', $name);
        if ($name === null) { throw new \InvalidArgumentException('Некорректное имя документа.'); }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'docx'], true)) {
            throw new \InvalidArgumentException('Выберите документ PDF или DOCX.');
        }
        if ($extension === 'pdf') {
            if ((new \finfo(FILEINFO_MIME_TYPE))->file($path) !== 'application/pdf' || file_get_contents($path, false, null, 0, 5) !== '%PDF-') {
                throw new \InvalidArgumentException('Содержимое файла не соответствует формату PDF.');
            }
            $mime = 'application/pdf';
        } else {
            if (!class_exists(\ZipArchive::class)) {
                throw new \InvalidArgumentException('DOCX временно недоступен на сервере. Сохраните документ в PDF.');
            }
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) { throw new \InvalidArgumentException('Не удалось прочитать DOCX.'); }
            try {
                if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('word/document.xml') === false || $zip->numFiles > 5000) {
                    throw new \InvalidArgumentException('Файл не является документом Word DOCX.');
                }
                $total = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->statIndex($i);
                    $total += (int)$entry['size'];
                    if ($total > 100 * 1048576 || stripos($entry['name'], 'vbaProject') !== false) {
                        throw new \InvalidArgumentException('DOCX содержит макросы или слишком большой объём данных.');
                    }
                }
            } finally { $zip->close(); }
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }
        $stem = mb_substr(pathinfo($name, PATHINFO_FILENAME), 0, 140, 'UTF-8');
        return ['name'=>($stem ?: 'invoice').'.'.$extension, 'mime'=>$mime, 'size'=>(int)filesize($path)];
    }

    private function key(string $token): string
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) { throw new \RuntimeException('Документ не найден.', 404); }
        return hash('sha256', $token);
    }

    public static function url(string $token): string
    {
        // Canonical host, independent of the request's Host header.
        return 'https://its-center.ru/avito-document/download?token='.rawurlencode($token);
    }

    public function store(string $path, string $name, string $chat, int $actor, ?int $now = null): array
    {
        if ($chat === '' || strlen($chat) > 256 || preg_match('/[\x00-\x20\x7f]/', $chat) || $actor <= 0) {
            throw new \InvalidArgumentException('Выберите диалог для отправки документа.');
        }
        $meta = self::validate($path, $name);
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Document storage unavailable');
        }
        if (file_put_contents($this->directory.'/.htaccess', "Require all denied\n", LOCK_EX) === false) {
            throw new \RuntimeException('Document storage protection unavailable');
        }
        $this->cleanup($now);
        $token = bin2hex(random_bytes(32)); $key = $this->key($token); $now = $now ?? time();
        $meta += ['chat'=>$chat, 'created_by'=>$actor, 'created_at'=>$now, 'expires_at'=>$now+self::LIFETIME, 'revoked'=>false];
        if (!copy($path, $this->directory.'/'.$key.'.bin')) { throw new \RuntimeException('Document storage write failed'); }
        @chmod($this->directory.'/'.$key.'.bin', 0600);
        try { $this->write($key, $meta); }
        catch (\Throwable $e) { unlink($this->directory.'/'.$key.'.bin'); throw $e; }
        return ['token'=>$token, 'url'=>self::url($token), 'name'=>$meta['name'], 'expires_at'=>$meta['expires_at']];
    }

    private function write(string $key, array $meta): void
    {
        $temporary = tempnam($this->directory, '.meta-');
        if (!$temporary) throw new \RuntimeException('Document metadata write failed');
        try {
            if (file_put_contents($temporary, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX) === false ||
                !rename($temporary, $this->directory.'/'.$key.'.json')) {
                throw new \RuntimeException('Document metadata write failed');
            }
            @chmod($this->directory.'/'.$key.'.json', 0600);
        } finally { if (is_file($temporary)) unlink($temporary); }
    }

    public function get(string $token, ?int $now = null): array
    {
        $key = $this->key($token);
        $file = $this->directory.'/'.$key.'.json';
        $meta = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
        if (!is_array($meta) || !is_file($this->directory.'/'.$key.'.bin')) { throw new \RuntimeException('Документ не найден.', 404); }
        if (!empty($meta['revoked']) || (int)$meta['expires_at'] <= ($now ?? time())) {
            throw new \RuntimeException('Срок действия ссылки истёк или доступ отозван. Попросите продавца прислать новую ссылку.', 410);
        }
        $meta['path'] = $this->directory.'/'.$key.'.bin';
        return $meta;
    }

    public function forChat(string $token, string $chat): array
    {
        $meta = $this->get($token);
        if ($meta['chat'] !== $chat) { throw new \InvalidArgumentException('Документ подготовлен для другого диалога.'); }
        return $meta;
    }

    public function revoke(string $token, string $chat): void
    {
        $meta = $this->forChat($token, $chat); unset($meta['path']); $meta['revoked'] = true;
        $this->write($this->key($token), $meta);
    }

    public function cleanup(?int $now = null): void
    {
        $now = $now ?? time();
        foreach (glob($this->directory.'/*.json') ?: [] as $path) {
            $key = basename($path, '.json');
            if (!preg_match('/\A[a-f0-9]{64}\z/', $key)) continue;
            $meta = json_decode((string)file_get_contents($path), true);
            if (is_array($meta) && isset($meta['expires_at']) && (int)$meta['expires_at'] <= $now) {
                $blob = $this->directory.'/'.$key.'.bin';
                if (is_file($blob)) unlink($blob);
                unlink($path);
            }
        }
    }
}
