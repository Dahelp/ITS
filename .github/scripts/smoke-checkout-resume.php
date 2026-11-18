<?php
if (PHP_SAPI !== 'cli') exit(1);
$checks = [];
foreach (['/', '/cart/view', '/js/checkout-progress.js'] as $path) {
    $ch = curl_init('https://its-center.ru'.$path.'?cr01='.time());
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30, CURLOPT_FOLLOWLOCATION=>true]);
    $body = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($body === false || $status !== 200 || preg_match('~Fatal error:|Uncaught (?:Error|Exception)|Parse error:~', $body)) throw new RuntimeException('HTTP failure: '.$path);
    if ($path === '/js/checkout-progress.js') {
        if (!str_contains($body, 'function resumeText(')) throw new RuntimeException('Outdated script');
    } elseif (!str_contains($body, 'id="checkout-site-resume"') || substr_count($body, 'src="/js/checkout-progress.js?') !== 1) {
        throw new RuntimeException('Missing reminder or duplicate script: '.$path);
    }
    $checks[] = ['path'=>$path, 'status'=>$status];
}
file_put_contents(__DIR__.'/smoke.json', json_encode($checks, JSON_PRETTY_PRINT));
echo "CR-01: home, cart and script HTTP checks OK; no orders created\n";
