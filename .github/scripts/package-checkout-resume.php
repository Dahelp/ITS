<?php
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__, 2);
$target = '/tmp/checkout-resume-package';
if (file_exists($target)) throw new RuntimeException('Package already exists');
mkdir($target, 0700, true);
$manifest = json_decode(file_get_contents($root.'/.github/checkout-resume-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($manifest as $path => $hashes) {
    if (!preg_match('~^(app|public)/[A-Za-z0-9_./-]+$~D', $path) || str_contains($path, '..')) throw new RuntimeException('Invalid path');
    if (hash_file('sha256', $root.'/'.$path) !== $hashes['after']) throw new RuntimeException('Source mismatch');
    $dest = $target.'/files/'.$path;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0700, true);
    copy($root.'/'.$path, $dest);
}
copy($root.'/.github/checkout-resume-manifest.json', $target.'/manifest.json');
copy(__DIR__.'/deploy-lead-forms.php', $target.'/install.php');
copy(__DIR__.'/smoke-checkout-resume.php', $target.'/smoke.php');
