<?php
if (PHP_SAPI !== 'cli') exit(1);
$checks = [];
function favoritesHttp($path, $post = null, $expectedStatus = 200) {
    global $checks;
    $ch = curl_init('https://its-center.ru' . $path . (str_contains($path, '?') ? '&' : '?') . 'favorites_check=' . time());
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => ['Cache-Control: no-cache']]);
    if ($post !== null) curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $status !== $expectedStatus || preg_match('~Fatal error:|Uncaught (?:Error|Exception)|Parse error:|Warning:~', $body)) throw new RuntimeException('HTTP check failed: ' . $path . ' status=' . $status);
    $checks[] = ['path' => $path, 'status' => $status];
    return $body;
}
$home = favoritesHttp('/');
if (!preg_match('~id="wishlist-([0-9]+)"~', $home, $match)) throw new RuntimeException('Guest product hearts absent');
$id = (int)$match[1];
$page = favoritesHttp('/favorites/');
foreach (['id="favorites-page"', 'id="favorites-save"', 'data-favorites-user="0"', '/js/guest-favorites.js', 'id="wishlist_kol"'] as $marker) if (!str_contains($page, $marker)) throw new RuntimeException('Missing favorites marker');
$items = json_decode(favoritesHttp('/favorites/items', ['ids' => [$id]]), true, 512, JSON_THROW_ON_ERROR);
if (empty($items['ok']) || !in_array($id, $items['ids'], true) || !str_contains($items['html'], 'id="wishlist-' . $id . '"')) throw new RuntimeException('Guest items failed');
$empty = json_decode(favoritesHttp('/favorites/items', ['ids' => []]), true, 512, JSON_THROW_ON_ERROR);
if (empty($empty['ok']) || $empty['ids'] !== [] || $empty['html'] !== '') throw new RuntimeException('Empty guest list failed');
$merge = json_decode(favoritesHttp('/favorites/merge', ['ids' => [$id]], 401), true, 512, JSON_THROW_ON_ERROR);
if ($merge['ok'] !== false) throw new RuntimeException('Unauthenticated merge accepted');
favoritesHttp('/js/guest-favorites.js');
favoritesHttp('/js/main.js');
favoritesHttp('/css/style.css');
file_put_contents(__DIR__ . '/smoke.json', json_encode(['checks' => $checks, 'product_id' => $id, 'account_writes' => 0, 'orders_created' => 0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Favorites: guest page, hearts, items, empty list, auth and assets passed\n";
