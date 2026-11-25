<?php
/** Read-only, sequential HTTP baseline. Never prints cookies or response bodies. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!extension_loaded('curl')) { fwrite(STDERR, "PHP cURL is required.\n"); exit(1); }
$args = $argv; array_shift($args);
$runs = 5;
$urls = [];
foreach ($args as $arg) {
    if (preg_match('/^--runs=(\d+)$/D', $arg, $m)) { $runs = max(1, min(20, (int)$m[1])); continue; }
    $parts = parse_url($arg);
    if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        fwrite(STDERR, "Use explicit HTTP(S) URLs without credentials, query strings or fragments.\n"); exit(1);
    }
    $urls[] = $arg;
}
if (!$urls) { fwrite(STDERR, "Usage: php bin/measure_ttfb.php --runs=5 https://host/category/alias https://host/product/alias\n"); exit(1); }
$report = ['measured_at_utc' => gmdate('c'), 'runs_per_mode' => $runs, 'note' => 'Sequential GETs; first request is not proof of a cold server cache. No redirects followed. Returning mode reuses only cookies issued to this probe; it is not an authenticated user.', 'pages' => []];
foreach ($urls as $url) {
    $cookieJar = tempnam(sys_get_temp_dir(), 'ttfb_');
    if ($cookieJar === false) { throw new RuntimeException('Cannot create temporary cookie jar'); }
    $page = ['url' => $url, 'modes' => []];
    try {
        foreach (['anonymous', 'returning'] as $mode) {
            $samples = [];
            for ($i = 0; $i < $runs; $i++) {
                $headers = [];
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30,
                    CURLOPT_USERAGENT => 'ITS-Performance-Baseline/1.0',
                    CURLOPT_ENCODING => '',
                    CURLOPT_WRITEFUNCTION => static function ($ch, $body) { return strlen($body); },
                    CURLOPT_HEADERFUNCTION => static function ($ch, $line) use (&$headers) {
                        $pair = explode(':', $line, 2);
                        if (count($pair) === 2) {
                            $key = strtolower(trim($pair[0]));
                            if ($key === 'set-cookie') { $headers['sets_cookie'] = true; }
                            elseif (in_array($key, ['cache-control','pragma','expires','age','x-cache','x-cache-status','server-timing','content-type','server'], true)) { $headers[$key] = trim($pair[1]); }
                        }
                        return strlen($line);
                    },
                ]);
                // First anonymous response seeds the returning visitor, without
                // sending its cookies on any subsequent anonymous request.
                if ($mode === 'returning') { curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar); }
                if ($mode === 'returning' || $i === 0) { curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar); }
                $ok = curl_exec($ch);
                $info = curl_getinfo($ch);
                $samples[] = [
                    'run' => $i + 1, 'status' => $info['http_code'],
                    'dns_s' => $info['namelookup_time'], 'connect_s' => $info['connect_time'],
                    'tls_s' => curl_getinfo($ch, CURLINFO_APPCONNECT_TIME),
                    'ttfb_s' => $info['starttransfer_time'], 'total_s' => $info['total_time'],
                    'bytes' => $info['size_download'], 'headers' => $headers,
                    'error' => $ok === false ? curl_error($ch) : null,
                ];
                curl_close($ch);
                // Bounded request rate: this is a baseline, not a load test.
                usleep(250000);
            }
            $times = array_column(array_filter($samples, static function ($s) { return $s['error'] === null && $s['status'] === 200; }), 'ttfb_s');
            sort($times);
            $count = count($times);
            $page['modes'][$mode] = ['successful_200' => $count,
                'median_ttfb_s' => $count ? ($times[(int)floor(($count - 1) / 2)] + $times[(int)ceil(($count - 1) / 2)]) / 2 : null,
                'p95_ttfb_s' => $count ? $times[(int)ceil($count * .95) - 1] : null,
                'samples' => $samples];
        }
    } finally { @unlink($cookieJar); }
    $report['pages'][] = $page;
}
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), "\n";
