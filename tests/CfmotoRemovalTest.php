<?php

$root = dirname(__DIR__);
$failures = [];

$index = (string)file_get_contents($root . '/public/index.php');
$sitemap = (string)file_get_contents($root . '/app/views/itscenter/Cron/sitemap.php');

$redirects = [
    '/technics/kvadrocikl/sf-moto',
    '/technics/kvadrocikl-moto-cf-c-forse-400l-eps-x4-eps',
    '/technics/kvadrocikl-moto-cf-500-x5-basic',
    '/technics/kvadrocikl-moto-cf-500-x5-ho-eps',
];

foreach ($redirects as $redirect) {
    if (strpos($index, "'{$redirect}' => '/category/atv'") === false) {
        $failures[] = 'Missing 301 redirect: ' . $redirect;
    }
}

if (strpos($sitemap, "WHERE hide = 'show' AND alias <> ''") === false) {
    $failures[] = 'Sitemap must include only visible technics pages';
}

if ($failures) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "OK: trademark-removal redirects and sitemap guard are configured\n";
