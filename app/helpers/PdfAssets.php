<?php
namespace app\helpers;

class PdfAssets
{
    /** Верхнее лого (перекод в RGB-JPEG, лечит CMYK и «кривые» PNG) */
    public static function logoTop(int $maxW = 1000, int $quality = 85): string {
        // читаем СНАЧАЛА по абсолютному URL
        $bytes = self::fetchBytes('https://its-center.ru/images/Logo_round.jpg');
        if ($bytes === false) {
            $bytes = self::fetchBytes(self::absPath('/images/Logo_round.jpg'));
        }
        return self::normalizeJpegDataUri($bytes, $maxW, $quality);
    }

    public static function logoBottom(int $maxW = 800): string {
        $bytes = self::fetchBytes('https://its-center.ru/images/logo-2.png');
        if ($bytes === false) {
            $bytes = self::fetchBytes(self::absPath('/images/logo-2.png'));
        }
        return self::normalizePngDataUri($bytes, $maxW);
    }

    public static function productImage($product, int $maxW = 900, int $quality = 82): string {
        // как было — не трогаем (тут у тебя всё ок)
        $img = '';
        if (!empty($product->unload_img ?? '')) {
            $img = self::imgToDataUriForPdfSmart('https://its-center.ru/images/product/unload/'.$product->unload_img, $maxW, $quality);
            if ($img === '') $img = self::imgToDataUriForPdfSmart(self::absPath('/images/product/unload/'.$product->unload_img), $maxW, $quality);
        }
        if ($img === '' && !empty($product->img ?? '')) {
            $img = self::imgToDataUriForPdfSmart('https://its-center.ru/images/product/baseimg/'.$product->img, $maxW, $quality);
            if ($img === '') $img = self::imgToDataUriForPdfSmart(self::absPath('/images/product/baseimg/'.$product->img), $maxW, $quality);
        }
        return $img;
    }

    /** Комплект сразу: ['logo','logos','product'] */
    public static function imagesFor($product): array {
        return [
            'logo'    => self::logoTop(),
            'logos'   => self::logoBottom(),
            'product' => self::productImage($product),
        ];
    }

    /** ====== Внутренние хелперы ====== */

    // Лого: нормализация через GD → RGB-JPEG
    private static function dataUriNormalizeForPdf(string $relPath, int $maxW, int $quality): string {
        $abs   = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/').'/'.ltrim($relPath, '/');
        $bytes = null;
        if (is_file($abs) && is_readable($abs)) {
            $bytes = @file_get_contents($abs);
        }
        if ($bytes === false || $bytes === null) {
            // ЖЁСТКИЙ паблик fallback: используем основной домен
            $publicUrl = 'https://its-center.ru/'.ltrim($relPath,'/');
            $bytes = @file_get_contents($publicUrl);
            if ($bytes === false) return '';
        }
        if ($bytes === false) return '';

        if (!function_exists('imagecreatefromstring')) {
            if (strncmp($bytes, "\xFF\xD8\xFF", 3) === 0) return 'data:image/jpeg;base64,'.base64_encode($bytes);
            if (strncmp($bytes, "\x89PNG\x0D\x0A\x1A\x0A", 8) === 0) return 'data:image/png;base64,'.base64_encode($bytes);
            return '';
        }

        $src = @imagecreatefromstring($bytes);
        if (!$src) {
            if (strncmp($bytes, "\xFF\xD8\xFF", 3) === 0) return 'data:image/jpeg;base64,'.base64_encode($bytes);
            if (strncmp($bytes, "\x89PNG\x0D\x0A\x1A\x0A", 8) === 0) return 'data:image/png;base64,'.base64_encode($bytes);
            return '';
        }

        $w = imagesx($src); $h = imagesy($src);
        $dst = $src;
        if ($w > $maxW) {
            $nw = $maxW; $nh = (int)round($h * ($maxW / $w));
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, true); imagesavealpha($dst, false);
            imagecopyresampled($dst, $src, 0,0,0,0, $nw,$nh, $w,$h);
            imagedestroy($src);
        }

        ob_start(); imagejpeg($dst, null, $quality); $out = ob_get_clean(); imagedestroy($dst);
        return $out ? 'data:image/jpeg;base64,'.base64_encode($out) : '';
    }

    // Футер-лого: PNG с прозрачностью
    private static function dataUriPngAlpha(string $relPath, int $maxW): string {
        $abs   = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/').'/'.ltrim($relPath, '/');
        $bytes = null;
        if (is_file($abs) && is_readable($abs)) {
            $bytes = @file_get_contents($abs);
        }
        if ($bytes === false || $bytes === null) {
            $publicUrl = 'https://its-center.ru/'.ltrim($relPath,'/');
            $bytes = @file_get_contents($publicUrl);
            if ($bytes === false) return '';
        }
        if ($bytes === false) return '';

        if (!function_exists('imagecreatefromstring')) {
            if (strncmp($bytes, "\x89PNG\x0D\x0A\x1A\x0A", 8) === 0) return 'data:image/png;base64,'.base64_encode($bytes);
            if (strncmp($bytes, "\xFF\xD8\xFF", 3) === 0) return 'data:image/jpeg;base64,'.base64_encode($bytes);
            return '';
        }

        $src = @imagecreatefromstring($bytes);
        if (!$src) return '';

        $w = imagesx($src); $h = imagesy($src);
        $nw = $w; $nh = $h;
        if ($w > $maxW) { $nw = $maxW; $nh = (int)round($h * ($maxW/$w)); }

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        imagecopyresampled($dst, $src, 0,0,0,0, $nw,$nh, $w,$h);
        imagedestroy($src);

        ob_start(); imagepng($dst); $out = ob_get_clean(); imagedestroy($dst);
        return $out ? 'data:image/png;base64,'.base64_encode($out) : '';
    }

    // ===== Вспомогательные (как у тебя, только добавлены маленькие обёртки) =====
    private static function absPath(string $rel): string {
        return rtrim($_SERVER['DOCUMENT_ROOT'] ?? '','/').'/'.ltrim($rel,'/');
    }
    private static function fetchBytes(string $pathOrUrl) {
        return @file_get_contents($pathOrUrl);
    }

    private static function normalizeJpegDataUri($bytes, int $maxW, int $quality): string {
        if ($bytes === false) return '';
        if (!function_exists('imagecreatefromstring')) return 'data:image/jpeg;base64,'.base64_encode($bytes);
        $src = @imagecreatefromstring($bytes); if (!$src) return 'data:image/jpeg;base64,'.base64_encode($bytes);
        $w=imagesx($src); $h=imagesy($src);
        $dst=$src;
        if ($w>$maxW){ $nw=$maxW; $nh=(int)round($h*($maxW/$w)); $dst=imagecreatetruecolor($nw,$nh); imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h); imagedestroy($src); }
        ob_start(); imagejpeg($dst,null,$quality); $out=ob_get_clean(); imagedestroy($dst);
        return $out ? 'data:image/jpeg;base64,'.base64_encode($out) : '';
    }
    private static function normalizePngDataUri($bytes, int $maxW): string {
        if ($bytes === false) return '';
        if (!function_exists('imagecreatefromstring')) return 'data:image/png;base64,'.base64_encode($bytes);
        $src=@imagecreatefromstring($bytes); if(!$src) return 'data:image/png;base64,'.base64_encode($bytes);
        $w=imagesx($src); $h=imagesy($src); $nw=$w; $nh=$h;
        if ($w>$maxW){ $nw=$maxW; $nh=(int)round($h*($maxW/$w)); }
        $dst=imagecreatetruecolor($nw,$nh);
        imagealphablending($dst,false); imagesavealpha($dst,true);
        $transparent=imagecolorallocatealpha($dst,0,0,0,127); imagefilledrectangle($dst,0,0,$nw,$nh,$transparent);
        imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h); imagedestroy($src);
        ob_start(); imagepng($dst); $out=ob_get_clean(); imagedestroy($dst);
        return $out ? 'data:image/png;base64,'.base64_encode($out) : '';
    }

    private static function imgToDataUriForPdfSmart(string $relOrAbs, int $maxW, int $quality): string {
        $isUrl = preg_match('#^https?://#i',$relOrAbs);
        $bytes = self::fetchBytes($relOrAbs);
        if ($bytes === false && !$isUrl) $bytes = self::fetchBytes(self::absPath($relOrAbs));
        if ($bytes === false) return '';
        if (!function_exists('imagecreatefromstring')) {
            // отдаём как есть
            if (strncmp($bytes,"\xFF\xD8\xFF",3)===0) return 'data:image/jpeg;base64,'.base64_encode($bytes);
            if (strncmp($bytes,"\x89PNG\x0D\x0A\x1A\x0A",8)===0) return 'data:image/png;base64,'.base64_encode($bytes);
            return '';
        }
        $src=@imagecreatefromstring($bytes); if(!$src) return '';
        $w=imagesx($src); $h=imagesy($src);
        $dst=$src;
        if ($w>$maxW){ $nw=$maxW; $nh=(int)round($h*($maxW/$w)); $dst=imagecreatetruecolor($nw,$nh); imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h); imagedestroy($src); }
        ob_start(); imagejpeg($dst,null,$quality); $jpeg=ob_get_clean(); imagedestroy($dst);
        return $jpeg ? 'data:image/jpeg;base64,'.base64_encode($jpeg) : '';
    }
}
