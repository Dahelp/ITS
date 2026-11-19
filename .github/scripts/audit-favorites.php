<?php
if (PHP_SAPI !== 'cli') exit(1);
$root = realpath($argv[1] ?? '');
if (!$root) throw new RuntimeException('Missing site root');
$paths = ["app/controllers/FavoritesController.php","app/views/itscenter/Favorites/index.php","public/js/guest-favorites.js","config/routes.php","app/views/itscenter/partials/header.php","app/widgets/product/card.php","app/views/itscenter/Product/view.php","app/views/itscenter/Cross/view.php","app/views/itscenter/layouts/watches.php","public/js/main.js","public/css/style.css"];
$manifest = [];
foreach ($paths as $p) {
    $file = $root . '/' . $p;
    if (is_link($file)) throw new RuntimeException('Symlink target');
    $manifest[$p] = is_file($file) ? hash_file('sha256', $file) : null;
    if (!is_file($file)) continue;
    $dest = __DIR__ . '/before/' . $p;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0700, true);
    if (!copy($file, $dest)) throw new RuntimeException('Copy failed');
}
file_put_contents(__DIR__ . '/baseline.json', json_encode($manifest, JSON_PRETTY_PRINT));
exec('tar -C ' . escapeshellarg(__DIR__) . ' -czf ' . escapeshellarg(__DIR__ . '/audit.tar.gz') . ' before baseline.json', $output, $code);
if ($code) throw new RuntimeException('Archive failed');
echo json_encode($manifest, JSON_UNESCAPED_SLASHES) . PHP_EOL;
