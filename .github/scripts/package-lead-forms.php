<?php
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__, 2);
$target = getenv('LEAD_PACKAGE_DIR') ?: '/tmp/lead-forms-package';
if (file_exists($target)) throw new RuntimeException('Package directory already exists');
mkdir($target, 0700, true);
$manifest = json_decode(file_get_contents($root.'/.github/lead-forms-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($manifest as $path => $hashes) {
    if (!preg_match('~^(app|public)/[A-Za-z0-9_./-]+$~D', $path) || str_contains($path, '..')) throw new RuntimeException('Invalid release path');
    if (hash_file('sha256', $root.'/'.$path) !== $hashes['after']) throw new RuntimeException('Source hash mismatch: '.$path);
    $dest = $target.'/files/'.$path;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0700, true);
    if (!copy($root.'/'.$path, $dest)) throw new RuntimeException('Package copy failed');
}
copy($root.'/.github/lead-forms-manifest.json', $target.'/manifest.json');
copy(__DIR__.'/deploy-lead-forms.php', $target.'/install.php');
copy(__DIR__.'/smoke-lead-forms.php', $target.'/smoke.php');
echo 'Lead release package: '.count($manifest)." files\n";
