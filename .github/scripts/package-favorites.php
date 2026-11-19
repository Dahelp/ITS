<?php
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__, 2);
$target = getenv('FAVORITES_PACKAGE_DIR') ?: '/tmp/favorites-package';
if (file_exists($target)) throw new RuntimeException('Package already exists');
mkdir($target, 0700, true);
$manifest = json_decode(file_get_contents($root . '/.github/favorites-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
if (count($manifest) !== 11) throw new RuntimeException('Unexpected file count');
foreach ($manifest as $path => $hashes) {
    if (!preg_match('~^(app|public|config)/[A-Za-z0-9_./-]+$~D', $path) || str_contains($path, '..') || (str_starts_with($path, 'config/') && $path !== 'config/routes.php')) throw new RuntimeException('Invalid path');
    if (hash_file('sha256', $root . '/' . $path) !== $hashes['after']) throw new RuntimeException('Source mismatch: ' . $path);
    $dest = $target . '/files/' . $path;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0700, true);
    if (!copy($root . '/' . $path, $dest)) throw new RuntimeException('Copy failed');
}
foreach (['.github/favorites-manifest.json' => 'manifest.json', '.github/scripts/deploy-lead-forms.php' => 'install.php', '.github/scripts/smoke-favorites.php' => 'smoke.php'] as $source => $dest) {
    if (!copy($root . '/' . $source, $target . '/' . $dest)) throw new RuntimeException('Support copy failed');
}
echo 'Favorites bounded package: ' . count($manifest) . " files\n";
