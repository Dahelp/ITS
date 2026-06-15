<?php
namespace app\helpers;

/**
 * Небольшой помощник для конвертации JPG/PNG -> WebP.
 * - не трёт оригиналы (JPG/PNG остаются)
 * - пропускает, если WebP уже существует и новее по времени
 * - умеет GD и Imagick (подхватит то, что доступно)
 * - возвращает статистику
 */
class ImageConverter
{
    public static function convertDir(string $webPath, array $ext = ['jpg','jpeg','png'], int $quality = 82): array
    {
        $doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $base = $doc . (str_starts_with($webPath, '/') ? '' : '/') . $webPath;

        if (!is_dir($base)) {
            return ['converted'=>0, 'skipped'=>0, 'errors'=>1, 'log'=>["Dir not found: $base"]];
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        $converted = $skipped = $errors = 0;
        $log = [];

        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) continue;

            $src = $file->getPathname();
            $extCur = strtolower($file->getExtension());

            if (!in_array($extCur, $ext, true)) {
                continue; // уже webp/avif или что-то другое
            }

            // целевой путь .webp рядом
            $dst = preg_replace('~\.(jpe?g|png)$~i', '.webp', $src);
            if (!$dst) { $skipped++; continue; }

            // пропускаем no-image, если у тебя так называется
            if (preg_match('~no-?image~i', $src)) { $skipped++; continue; }

            // если webp существует и не старше исходника — пропускаем
            if (is_file($dst) && filemtime($dst) >= filemtime($src)) {
                $skipped++;
                continue;
            }

            try {
                self::convertToWebp($src, $dst, $quality);
                // переносим время файла, чтобы кэш корректно работал
                @touch($dst, filemtime($src));
                $converted++;
            } catch (\Throwable $e) {
                $errors++;
                $log[] = sprintf('Error: %s -> %s (%s)', $src, $dst, $e->getMessage());
            }
        }

        return compact('converted', 'skipped', 'errors', 'log');
    }

    /** Конвертировать один файл (Imagick -> GD fallback) */
    public static function convertToWebp(string $src, string $dst, int $quality = 82): void
    {
        // 1) Imagick, если есть
        if (class_exists(\Imagick::class)) {
            $img = new \Imagick($src);
            $img->setImageFormat('webp');
            // Для PNG/прозрачности: математика с альфой
            if ($img->getImageAlphaChannel()) {
                $img->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                $img->setBackgroundColor(new \ImagickPixel('transparent'));
            }
            // качество (0..100)
            $img->setImageCompressionQuality($quality);
            // lossless для PNG по желанию (компромисс размера/качества)
            if (preg_match('~\.png$~i', $src)) {
                $img->setOption('webp:lossless', 'true');
            }
            $img->writeImage($dst);
            $img->clear();
            $img->destroy();
            return;
        }

        // 2) GD fallback
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            $im = imagecreatefrompng($src);
            if (!$im) throw new \RuntimeException('GD cannot open PNG');
            // сохраняем прозрачность
            imagepalettetotruecolor($im);
            imagealphablending($im, true);
            imagesavealpha($im, true);
        } else {
            $im = imagecreatefromjpeg($src);
            if (!$im) throw new \RuntimeException('GD cannot open JPEG');
        }

        // Попробуем включить лучший алгоритм (если у PHP есть флаг)
        if (defined('IMG_WEBP_LOSSLESS') && $ext === 'png') {
            imagewebp($im, $dst, IMG_WEBP_LOSSLESS);
        } else {
            imagewebp($im, $dst, $quality);
        }
        imagedestroy($im);

        if (!is_file($dst)) {
            throw new \RuntimeException('Failed writing WebP');
        }
    }
}
