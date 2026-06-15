<?php 

namespace app\helpers;

use ishop\App;

class Upload
{
    /**
     * Главная точка входа: сохраняет изображение в нужные папки с нужными размерами/форматом.
     *
     * @param string $section  Раздел: product|complete|review|contents|technics|category|brand|technics_type|technics_manufacturer|...
     * @param string $mode     Режим: single|multi|unload
     * @param array  $file     $_FILES['file']
     * @param string $format   Желаемый формат ('webp'|'avif'|'jpg'|'png'); если пусто — возьмём из params (img_target_format)
     * @param array  $opts     Доп. опции (необязательно)
     *
     * @return array ['ok'=>true, 'file'=>'xxx.ext', 'previewUrl'=>'/images/.../xxx.ext']
     * @throws \RuntimeException
     */
    public static function handle(
        string $section,
        string $mode,
        array  $file,
        string $format = 'webp',
        array  $opts   = []
    ): array
    {

        $section = trim(strtolower($section));
            $mode    = trim(strtolower($mode));
            if (!in_array($mode, ['single','multi','unload'], true)) {
                $mode = 'single';
        }

        self::assertOkFile($file);

        $DEF = [
            'img_w'        => (int)self::cfg('img_width', 600),
            'img_h'        => (int)self::cfg('img_height', 600),
            'mini_w'       => (int)self::cfg('mini_img_width', 250),
            'mini_h'       => (int)self::cfg('mini_img_height', 250),
            'gal_w'        => (int)self::cfg('gallery_width', 1000),
            'gal_h'        => (int)self::cfg('gallery_height', 750),
            'content_w'    => (int)self::cfg('img_width_content', 1400),
            'content_h'    => (int)self::cfg('img_height_content', 460),
            'brand_w'      => (int)self::cfg('img_width_brand', 250),
            'brand_h'      => (int)self::cfg('img_height_brand', 250),
            // NEW
            'filtrs_w'     => (int)self::cfg('img_width_filtrs', 600),
            'filtrs_h'     => (int)self::cfg('img_height_filtrs', 450),
        ];

        // Режимы ресайза: fit (вписать, возможны поля) или cover (заполнить, с обрезкой по центру)
        $resizeModeDefault = strtolower((string) self::cfg('img_resize_mode_default', 'fit'));
        $resizeModeKey     = 'img_resize_mode_' . strtolower($section);
        $resizeModeSection = strtolower((string) self::cfg($resizeModeKey, $resizeModeDefault));

        $resizeMode = in_array($resizeModeSection, ['fit','cover'], true) ? $resizeModeSection : 'fit';

        // принудительно кропить «под размер»
        if ($section === 'contents' || $section === 'filtrs') {
            $resizeMode = 'cover';
        }

        // Качества/сжатия
        $quality = [
            'avif' => (int) self::cfg('img_avif_quality', 50),
            'webp' => (int) self::cfg('img_webp_quality', 85),
            'jpeg' => (int) self::cfg('img_jpeg_quality', 85),
            'png'  => (int) self::cfg('img_png_compress', 6),
        ];
        $noUpscale = (bool) self::cfg('img_no_upscale', true);


        // Целевые форматы
        $targetFmtArg = trim(strtolower($format ?: ''));
        $targetFmtCfg = trim(strtolower((string) self::cfg('img_target_format', 'webp')));
        $targetFmt    = $targetFmtArg ?: ($targetFmtCfg ?: 'webp');
        $unloadFmt    = trim(strtolower((string) self::cfg('img_unload_format', 'jpg'))); // для product:unload

        // --- Вычисляем размеры для конкретной секции/режима
        $w = $h = $wmini = $hmini = 0;
        switch ($section) {
            case 'product':
                if ($mode === 'single') {
                    $w = $DEF['img_w'];    $h = $DEF['img_h'];
                    $wmini = $DEF['mini_w']; $hmini = $DEF['mini_h'];
                } elseif ($mode === 'multi') {
                    $w = $DEF['gal_w'];    $h = $DEF['gal_h'];
                } elseif ($mode === 'unload') {
                    $w = $DEF['img_w'];    $h = $DEF['img_h'];
                }
                break;

            case 'complete':
                if ($mode === 'single') {
                    $w = $DEF['img_w'];    $h = $DEF['img_h'];
                    $wmini = $DEF['mini_w']; $hmini = $DEF['mini_h'];
                } elseif ($mode === 'multi') {
                    $w = $DEF['gal_w'];    $h = $DEF['gal_h'];
                }
                break;

            case 'review':
                $w = $DEF['gal_w'];      $h = $DEF['gal_h'];
                $wmini = $DEF['mini_w']; $hmini = $DEF['mini_h'];
                break;

            case 'contents':
                $w = $DEF['content_w'];  $h = $DEF['content_h'];
                $wmini = $DEF['mini_w']; $hmini = $DEF['mini_h'];
                break;

            case 'technics':
                $w = $DEF['img_w'];      $h = $DEF['img_h'];
                $wmini = $DEF['mini_w']; $hmini = $DEF['mini_h'];
                break;

            case 'category':
                $w = $DEF['img_w'];      $h = $DEF['img_h'];
                break;

            case 'brand':
                $w = $DEF['brand_w'];    $h = $DEF['brand_h'];
                break;

            case 'technics_type':
            case 'technics_manufacturer':
                $w = $DEF['img_w'];      $h = $DEF['img_h'];
                break;
            case 'filtrs':
                $w = $DEF['filtrs_w'];  $h = $DEF['filtrs_h'];
                break;

            default:
                $w = $DEF['img_w'];      $h = $DEF['img_h'];
        }

        // --- Папки секции
        $p = self::paths($section);

        // --- Готовим tmp
        self::ensureDir($p['tmp'] ?? (WWW . '/tmp/'));
        $tmpExt  = self::extFromUpload($file);
        $tmpName = self::rnd() . '.' . $tmpExt;
        $tmpPath = ($p['tmp'] ?? (WWW . '/tmp/')) . $tmpName;

        if (!@move_uploaded_file($file['tmp_name'], $tmpPath)) {
            throw new \RuntimeException('move_uploaded_file failed');
        }

        // --- Загружаем исходное изображение (для GD-ветки)
        $srcIm = self::loadImage($tmpPath);
        if (!$srcIm) {
            @unlink($tmpPath);
            throw new \RuntimeException('unsupported image');
        }

        // --- Формат на этот режим
        $fmt = ($section === 'product' && $mode === 'unload') ? ($unloadFmt ?: 'jpg') : $targetFmt;
        $fmt = self::normalizeExt($fmt);

        $previewUrl = '';
        // мы будем генерить базовый путь БЕЗ расширения, а функция сохранения вернёт итоговый путь и расширение
        $save = function(string $dir, int $W, int $H, ?string $baseName = null)
            use ($tmpPath, $srcIm, $fmt, $quality, $noUpscale, $resizeMode) {
            self::ensureDir($dir);
            $base = rtrim($dir, '/').'/'.($baseName ?: self::rnd());
            list($dst, $ext, $ok) = self::resizeAndSaveSmart($tmpPath, $srcIm, $base, $W, $H, $fmt, $quality, $noUpscale, $resizeMode);
            if (!$ok) throw new \RuntimeException('save failed');
            return basename($dst);
        };


        // --- Сохраняем по секциям/режимам
        switch ($section) {

            case 'product':
                if ($mode === 'single') {
                    $commonBase = self::rnd();
                    $fname = $save($p['baseimg'], $w, $h, $commonBase);
                    if ($wmini && $hmini) {
                        $save($p['mini'], $wmini, $hmini, $commonBase);
                    }
                    $previewUrl = '/images/product/baseimg/'.$fname;

                } elseif ($mode === 'multi') {
                    $fname = $save($p['gallery'], $w, $h);
                    $previewUrl = '/images/product/gallery/'.$fname;

                } elseif ($mode === 'unload') {
                    $fname = $save($p['unload'], $w, $h);
                    $previewUrl = '/images/product/unload/'.$fname;

                } else {
                    throw new \InvalidArgumentException('bad mode for product');
                }
                break;

            case 'complete':
                if ($mode === 'single') {
                    $commonBase = self::rnd();
                    $fname = $save($p['baseimg'], $w, $h, $commonBase);
                    if ($wmini && $hmini) {
                        $save($p['mini'], $wmini, $hmini, $commonBase);
                    }
                    $previewUrl = '/images/complete/baseimg/'.$fname;

                } elseif ($mode === 'multi') {
                    $fname = $save($p['gallery'], $w, $h);
                    $previewUrl = '/images/complete/gallery/'.$fname;

                } else {
                    throw new \InvalidArgumentException('bad mode for complete');
                }
                break;

            case 'review':
                $commonBase = self::rnd();
                $fname = $save($p['gallery'], $w, $h, $commonBase);
                if ($wmini && $hmini) {
                    $save($p['mini'], $wmini, $hmini, $commonBase);
                }
                $previewUrl = '/images/review/gallery/'.$fname;
                break;

            case 'contents':
                $commonBase = self::rnd();
                $fname = $save($p['baseimg'], $w, $h, $commonBase);
                if ($wmini && $hmini) {
                    $save($p['mini'], $wmini, $hmini, $commonBase);
                }
                $previewUrl = '/images/contents/baseimg/'.$fname;
                break;

            case 'technics':
                $commonBase = self::rnd();
                $fname = $save($p['baseimg'], $w, $h, $commonBase);
                if ($wmini && $hmini) {
                    $save($p['mini'], $wmini, $hmini, $commonBase);
                }
                $previewUrl = '/images/technics/baseimg/'.$fname;
                break;

            case 'category':
                $fname = $save($p['baseimg'], $w, $h);
                $previewUrl = '/images/category/baseimg/'.$fname;
                break;

            case 'brand':
                $fname = $save($p['baseimg'], $w, $h);
                $previewUrl = '/images/brand/baseimg/'.$fname;
                break;

            case 'technics_type':
                $fname = $save($p['baseimg'], $w, $h);
                $previewUrl = '/images/technics_type/baseimg/'.$fname;
                break;

            case 'technics_manufacturer':
                $fname = $save($p['baseimg'], $w, $h);
                $previewUrl = '/images/technics_manufacturer/baseimg/'.$fname;
                break;

            default:
                $dir = $p['baseimg'] ?? WWW."/images/{$section}/baseimg/";
                $fname = $save($dir, $w, $h);
                $previewUrl = "/images/{$section}/baseimg/".$fname;
        }

        imagedestroy($srcIm);
        @unlink($tmpPath);

        return [
            'ok'         => true,
            'file'       => $fname,
            'previewUrl' => $previewUrl,
            'usedFormat' => pathinfo($fname, PATHINFO_EXTENSION), // avif|webp|jpg|png
        ];
    }

    /* =======================
       ====== ХЕЛПЕРЫ ========
       ======================= */

    // Папки по секциям (подставь свои при необходимости)
    public static function paths(string $section): array
    {
        $W = WWW; // корень /public_html
        switch ($section) {
            case 'product':
                return [
                    'tmp'     => $W.'/images/product/tmp/',
                    'baseimg' => $W.'/images/product/baseimg/',
                    'mini'    => $W.'/images/product/mini/',
                    'gallery' => $W.'/images/product/gallery/',
                    'unload'  => $W.'/images/product/unload/',
                ];
            case 'complete':
                return [
                    'tmp'     => $W.'/images/complete/tmp/',
                    'baseimg' => $W.'/images/complete/baseimg/',
                    'mini'    => $W.'/images/complete/mini/',
                    'gallery' => $W.'/images/complete/gallery/',
                ];
            case 'review':
                return [
                    'tmp'     => $W.'/images/review/tmp/',
                    'mini'    => $W.'/images/review/mini/',
                    'gallery' => $W.'/images/review/gallery/',
                ];
            case 'contents':
                return [
                    'tmp'     => $W.'/images/contents/tmp/',
                    'baseimg' => $W.'/images/contents/baseimg/',
                    'mini'    => $W.'/images/contents/mini/',
                ];
            case 'technics':
                return [
                    'tmp'     => $W.'/images/technics/tmp/',
                    'baseimg' => $W.'/images/technics/baseimg/',
                    'mini'    => $W.'/images/technics/mini/',
                ];
            case 'category':
                return [
                    'tmp'     => $W.'/images/category/tmp/',
                    'baseimg' => $W.'/images/category/baseimg/',
                ];
            case 'brand':
                return [
                    'tmp'     => $W.'/images/brand/tmp/',
                    'baseimg' => $W.'/images/brand/baseimg/',
                ];
            case 'technics_type':
                return [
                    'tmp'     => $W.'/images/technics_type/tmp/',
                    'baseimg' => $W.'/images/technics_type/baseimg/',
                ];
            case 'technics_manufacturer':
                return [
                    'tmp'     => $W.'/images/technics_manufacturer/tmp/',
                    'baseimg' => $W.'/images/technics_manufacturer/baseimg/',
                ];
            case 'filtrs':
                return [
                    'tmp'     => $W.'/images/filtrs/tmp/',
                    'baseimg' => $W.'/images/filtrs/baseimg/',
                ];
            default:
                return [
                    'tmp'     => $W."/images/{$section}/tmp/",
                    'baseimg' => $W."/images/{$section}/baseimg/",
                ];
        }
    }

    /* ---------- AVIF через Imagick + GD fallback ---------- */

    private static function gdTmpPath($im): string {
        $tmp = sys_get_temp_dir() . '/__gd_tmp_' . bin2hex(random_bytes(4)) . '.png';
        imagepng($im, $tmp, 6);
        return $tmp;
    }

    private static function hasImagickAvif(): bool {
        if (!extension_loaded('imagick')) return false;
        try {
            $fmts = \Imagick::queryFormats();
            if (!is_array($fmts) || !in_array('AVIF', $fmts)) return false;

            $im = new \Imagick();
            $im->newImage(1, 1, new \ImagickPixel('white'), 'png');
            $im->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            $im->thumbnailImage(1, 1, true, true);
            $im->setImageFormat('AVIF');
            $im->setImageCompressionQuality((int) self::cfg('img_avif_quality', 50));
            $im->setImageDepth(8);
            if (method_exists($im, 'setOption')) {
                @$im->setOption('heic:speed', '6');
                @$im->setOption('heic:quality', (string) self::cfg('img_avif_quality', 50));
            }
            $tmp = sys_get_temp_dir() . '/__avif_test_' . bin2hex(random_bytes(4)) . '.avif';
            $ok = $im->writeImage($tmp);
            $im->clear(); $im->destroy();
            if ($ok && is_file($tmp)) { @unlink($tmp); return true; }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function resizeAndSaveImagick(
        string $srcPath,
        string $dstNoExt,
        int    $w,
        int    $h,
        string $fmt,        // 'avif'
        array  $q,          // ['avif'=>качество]
        bool   $noUpscale = true,
        string $mode = 'fit' // <— ДОБАВИЛИ
    ): array {
        $im = new \Imagick();
        $im->readImage($srcPath);

        if (method_exists($im, 'setImageColorspace')) {
            @$im->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        }
        @$im->setBackgroundColor(new \ImagickPixel('transparent'));
        if (method_exists($im, 'setImageAlphaChannel')) {
            @$im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
        }

        $iw = $im->getImageWidth();
        $ih = $im->getImageHeight();
        if ($noUpscale && ($iw < $w || $ih < $h)) {
            $w = min($w, $iw);
            $h = min($h, $ih);
        }

        if ($mode === 'cover' && method_exists($im, 'cropThumbnailImage')) {
            // Заполнение с обрезкой по центру — без полей
            $im->cropThumbnailImage($w, $h);
        } else {
            // Вписать (fit) — возможны поля при выводе, но не увеличиваем
            $im->thumbnailImage($w, $h, true, true);
        }

        $avifQ = (int)($q['avif'] ?? 50);
        $im->setImageFormat('AVIF');
        $im->setImageCompressionQuality($avifQ);
        $im->setImageDepth(8);
        if (method_exists($im, 'setOption')) {
            @ $im->setOption('heic:speed', '6');
            @ $im->setOption('heic:quality', (string)$avifQ);
            @ $im->setOption('heic:chroma-subsampling', '4:2:0');
        }

        $dst = $dstNoExt . '.avif';
        $ok  = $im->writeImage($dst);
        $im->clear(); $im->destroy();

        return [$dst, 'avif', (bool)$ok];
    }

    private static function resizeAndSaveSmart(
        string $srcPath,    // tmpPath
        $gdIm,              // ресурс GD
        string $dstNoExt,   // без расширения
        int    $w,
        int    $h,
        string $fmt,        // нужен формат предпочтения
        array  $q,
        bool   $noUpscale,
        string $mode = 'fit'   // <— ДОБАВИЛИ
    ): array {
        $want = strtolower($fmt);

        // AVIF -> Imagick (если доступен)
        if ($want === 'avif' && self::hasImagickAvif()) {
            return self::resizeAndSaveImagick($srcPath, $dstNoExt, $w, $h, $want, $q, $noUpscale, $mode);
        }

        // GD (webp/jpg/png)
        $dstIm = ($mode === 'cover')
            ? self::resizeToCover($gdIm, $w, $h, $noUpscale)
            : self::resizeToBox($gdIm,   $w, $h, $noUpscale);

        return self::saveWithFormatGD($dstIm, $dstNoExt, $want, $q);    
    }

    private static function resizeToCover($srcIm, int $w, int $h, bool $noUpscale = true) {
        $iw = imagesx($srcIm);
        $ih = imagesy($srcIm);

        if ($noUpscale && ($iw < $w || $ih < $h)) {
            // даже для cover не увеличиваем меньше исходника
            $w = min($w, $iw);
            $h = min($h, $ih);
        }

        // Масштабирование с заполнением, затем центрированная обрезка
        $scale = max($w / $iw, $h / $ih);
        $tw = max(1, (int)round($iw * $scale));
        $th = max(1, (int)round($ih * $scale));

        $tmp = imagecreatetruecolor($tw, $th);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        imagecopyresampled($tmp, $srcIm, 0, 0, 0, 0, $tw, $th, $iw, $ih);

        // Кроп по центру до точного W×H
        $x = max(0, (int)floor(($tw - $w) / 2));
        $y = max(0, (int)floor(($th - $h) / 2));

        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopy($dst, $tmp, 0, 0, $x, $y, $w, $h);
        imagedestroy($tmp);

        return $dst;
    }



    private static function resizeToBox($srcIm, int $w, int $h, bool $noUpscale = true) {
        $iw = imagesx($srcIm);
        $ih = imagesy($srcIm);

        // было: if ($noUpscale && ($iw < $w && $ih < $h)) {
        if ($noUpscale && ($iw < $w || $ih < $h)) {
            $w = min($w, $iw);
            $h = min($h, $ih);
        }

        $ratio = min($w / $iw, $h / $ih);
        $tw = max(1, (int)round($iw * $ratio));
        $th = max(1, (int)round($ih * $ratio));

        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $srcIm, 0, 0, 0, 0, $tw, $th, $iw, $ih);
        return $dst;
    }

    private static function saveWithFormatGD($im, string $dstNoExt, string $want, array $q): array {
        // желаемый
        if ($want === 'webp' && function_exists('imagewebp')) {
            $p = $dstNoExt . '.webp';
            if (@imagewebp($im, $p, (int)($q['webp'] ?? 85))) return [$p, 'webp', true];
        }
        if ($want === 'jpg' || $want === 'jpeg') {
            $p = $dstNoExt . '.jpg';
            if (@imagejpeg($im, $p, (int)($q['jpeg'] ?? 85))) return [$p, 'jpg', true];
        }
        if ($want === 'png') {
            $p = $dstNoExt . '.png';
            $lvl = (int)($q['png'] ?? 6);
            $lvl = max(0, min(9, $lvl));
            if (@imagepng($im, $p, $lvl)) return [$p, 'png', true];
        }

        // fallback: webp → jpg → png
        if (function_exists('imagewebp')) {
            $p = $dstNoExt . '.webp';
            if (@imagewebp($im, $p, (int)($q['webp'] ?? 85))) return [$p, 'webp', true];
        }
        $p = $dstNoExt . '.jpg';
        if (@imagejpeg($im, $p, (int)($q['jpeg'] ?? 85))) return [$p, 'jpg', true];

        $p = $dstNoExt . '.png';
        if (@imagepng($im, $p, (int)($q['png'] ?? 6))) return [$p, 'png', true];

        return [$dstNoExt.'.'.$want, $want, false];
    }

    /* ---------- Утилиты ---------- */

    private static function assertOkFile(array $file): void
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('bad upload');
        }
        if (!empty($file['error'])) {
            throw new \RuntimeException('upload error '.$file['error']);
        }
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
    }

    private static function extFromUpload(array $file): string
    {
        // попытаемся понять по mime
        $mime = strtolower((string)($file['type'] ?? ''));
        if (strpos($mime, 'webp') !== false) return 'webp';
        if (strpos($mime, 'avif') !== false) return 'avif';
        if (strpos($mime, 'jpeg') !== false || strpos($mime, 'jpg') !== false) return 'jpg';
        if (strpos($mime, 'png')  !== false) return 'png';
        if (strpos($mime, 'gif')  !== false) return 'gif';

        // иначе — по имени
        $ext = strtolower(pathinfo((string)($file['name'] ?? 'file'), PATHINFO_EXTENSION));
        return $ext ?: 'jpg';
    }

    private static function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) return null;
        switch ($info[2]) {
            case IMAGETYPE_WEBP: return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
            case IMAGETYPE_JPEG: return @imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:  return @imagecreatefrompng($path);
            case IMAGETYPE_GIF:  return @imagecreatefromgif($path);
            default: return null;
        }
    }

    private static function normalizeExt(string $fmt): string
    {
        $fmt = strtolower($fmt);
        if ($fmt === 'jpeg') return 'jpg';
        return in_array($fmt, ['avif','webp','jpg','png']) ? $fmt : 'webp';
    }

    private static function rnd(): string
    {
        return bin2hex(random_bytes(8)) . dechex((int)(microtime(true) * 1000000) % 0xFFFFFF);
    }

    /** Нормализуем целевой формат */
    public static function pickTargetExt(string $ext) : string
    {
        $e = strtolower(trim($ext));
        if ($e === 'jpeg') $e = 'jpg';
        return in_array($e, ['avif','webp','jpg','png'], true) ? $e : 'webp';
    }

    /**
     * Переконвертация одного файла «как есть» (без изменения размеров).
     * @param string $absPath Абсолютный путь к исходнику
     * @param string $toExt   'avif'|'webp'|'jpg'|'png'
     * @param array  $opts    ['quality'=>['avif'=>50,'webp'=>85,'jpeg'=>85,'png'=>6]]
     * @return string|null    Новое имя файла (basename) или null, если не удалось
     */
    public static function convertFile(string $absPath, string $toExt, array $opts = []) : ?string
	{
		if (!is_file($absPath)) return null;

		$dir    = dirname($absPath);
		$base   = pathinfo($absPath, PATHINFO_FILENAME);
		$srcExt = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
		$toExt  = self::pickTargetExt($toExt);

		if ($srcExt === $toExt) {
			return basename($absPath);
		}

		$quality = (array)($opts['quality'] ?? []);
		$q = [
			'avif' => (int)($quality['avif'] ?? self::cfg('img_avif_quality', 50)),
			'webp' => (int)($quality['webp'] ?? self::cfg('img_webp_quality', 85)),
			'jpeg' => (int)($quality['jpeg'] ?? self::cfg('img_jpeg_quality', 85)),
			'png'  => (int)($quality['png']  ?? self::cfg('img_png_compress', 6)),
		];

		$dst = rtrim($dir, '/') . '/' . $base . '.' . $toExt;

		// AVIF/WebP/любые сложные форматы сначала пробуем через Imagick
		if (class_exists(\Imagick::class)) {
			try {
				$img = new \Imagick($absPath);
				$img->setImageOrientation(\Imagick::ORIENTATION_UNDEFINED);

				switch ($toExt) {
					case 'avif':
						$img->setImageFormat('avif');
						$img->setImageCompressionQuality($q['avif']);
						break;

					case 'webp':
						$img->setImageFormat('webp');
						$img->setImageCompressionQuality($q['webp']);
						break;

					case 'jpg':
						$img->setImageFormat('jpeg');
						$img->setImageCompressionQuality($q['jpeg']);
						$img->setImageBackgroundColor(new \ImagickPixel('white'));
						$img = $img->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
						break;

					case 'png':
						$img->setImageFormat('png');
						break;
				}

				$img->writeImage($dst);
				$img->clear();
				$img->destroy();

				if (is_file($dst)) {
					if (realpath($absPath) !== realpath($dst)) {
						@unlink($absPath);
					}
					return basename($dst);
				}
			} catch (\Throwable $e) {
				// fallback ниже
			}
		}

		// fallback через GD только для форматов, которые GD реально читает
		$gdIm = self::loadImage($absPath);
		if (!$gdIm) return null;

		$iw = imagesx($gdIm);
		$ih = imagesy($gdIm);
		$dstNoExt = rtrim($dir,'/').'/'.$base;

		[$savedDst, $ext, $ok] = self::resizeAndSaveSmart($absPath, $gdIm, $dstNoExt, $iw, $ih, $toExt, $q, (bool) self::cfg('img_no_upscale', true));
		imagedestroy($gdIm);

		if (!$ok || !is_file($savedDst)) {
			return null;
		}

		if (realpath($absPath) !== realpath($savedDst)) {
			@unlink($absPath);
		}

		return basename($savedDst);
	}

    private static function cfg(string $key, $default = null) {
        return \ishop\App::$app->getProperty($key) ?? $default;
    }


}
