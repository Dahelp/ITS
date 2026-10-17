<?php
declare(strict_types=1);

namespace app\services;

/** Read a diagnostic log tail without loading the complete file into memory. */
final class BoundedLogTail
{
    public static function read(string $path, int $lines = 80, int $maxBytes = 65536): array
    {
        if ($lines < 1 || $maxBytes < 1) throw new \InvalidArgumentException('Positive tail limits required');
        $result = ['lines'=>[], 'bytes_read'=>0, 'truncated'=>false];
        $handle = @fopen($path, 'rb');
        if (!$handle) return $result;
        try {
            $stat = fstat($handle);
            $size = (int)($stat['size'] ?? 0);
            if ($size === 0) return $result;
            // Include one preceding byte to recognize whether the first line is partial.
            $offset = max(0, $size - $maxBytes - 1);
            if (fseek($handle, $offset, SEEK_SET) !== 0) return $result;
            $data = stream_get_contents($handle, $maxBytes + 1);
            if ($data === false || $data === '') return $result;
            $result['bytes_read'] = strlen($data);
            if ($size > $maxBytes) {
                $preceding = $data[0]; $data = substr($data, 1); $result['truncated'] = true;
                if ($preceding !== "\n") {
                    $newline = strpos($data, "\n");
                    // A single huge line is still useful, but only its bounded suffix.
                    $data = $newline === false ? '[...truncated...] '.$data : substr($data, $newline+1);
                }
            }
            $parts = explode("\n", $data);
            if (end($parts) === '') array_pop($parts);
            $result['lines'] = array_map(static function ($line) { return rtrim($line, "\r"); }, array_slice($parts, -$lines));
            return $result;
        } finally { fclose($handle); }
    }
}
