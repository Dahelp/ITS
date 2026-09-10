<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$frontendRoots = [
    $root . '/app/views/itscenter',
    $root . '/app/widgets',
];
$failures = [];

foreach ($frontendRoots as $frontendRoot) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($frontendRoot));
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());
        if (
            strpos($path, '/admin/') !== false
            || strpos($path, '/mail/') !== false
            || strpos($path, '/Cron/') !== false
            || substr($path, -18) === '/layouts/admin.php'
        ) {
            continue;
        }

        $source = (string)file_get_contents($path);
        $masked = preg_replace_callback(
            '~<\\?(?:php|=)?[\\s\\S]*?\\?>~',
            static function (array $match): string {
                return str_replace('>', ' ', $match[0]);
            },
            $source
        );

        preg_match_all('~<img\\b[^>]*>~is', (string)$masked, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$tag, $offset]) {
            $widthCount = preg_match_all('~\\bwidth\\s*=~i', $tag);
            $heightCount = preg_match_all('~\\bheight\\s*=~i', $tag);
            if ($widthCount === 1 && $heightCount === 1) {
                continue;
            }

            $relativePath = ltrim(str_replace(str_replace('\\', '/', $root), '', $path), '/');
            $line = substr_count(substr($source, 0, $offset), "\n") + 1;
            $failures[] = "{$relativePath}:{$line} (width={$widthCount}, height={$heightCount})";
        }
    }
}

if ($failures) {
    fwrite(STDERR, "FAILED: frontend images must have exactly one width and height attribute:\n");
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "Frontend image dimension tests passed\n";
