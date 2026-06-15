<?php
namespace app\helpers;

use ishop\App;

class Img
{
    /** Экранируем атрибуты */
    private static function esc($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }

    /** Абсолютный URL (если прилетел относительный) */
    private static function absUrl(string $src): string {
        if (preg_match('~^https?://~i', $src)) return $src;
        $base = App::$app->getProperty('base_url') ?: (defined('PATH') ? PATH : '');
        $base = rtrim((string)$base, '/');
        if ($src && $src[0] !== '/') $src = '/'.$src;
        return $base . $src;
    }

    /** Сборка строки атрибутов */
    private static function buildAttrs(array $attrs): string {
        $parts = [];
        foreach ($attrs as $k => $v) {
            if ($v === null || $v === '') continue;
            $parts[] = $k.'="'.self::esc($v).'"';
        }
        return implode(' ', $parts);
    }

    /**
     * Базовый рендер <img>
     * $opts:
     *  - eager (bool)        : LCP-изображение (ставит loading=eager + fetchpriority=high)
     *  - lazy (bool)         : по умолчанию true, если eager=false
     *  - class (string)
     *  - decoding (string)   : 'async' (по умолчанию)
     *  - srcset (string|array)
     *  - sizes  (string)
     *  - data   (array)      : ['key' => 'val'] -> data-key="val"
     *  - attrs  (array)      : любые доп. атрибуты
     */
    public static function img(string $src, string $alt = '', ?int $width = null, ?int $height = null, array $opts = []): string
    {
        $attrs = [
            'src'      => self::absUrl($src),
            'alt'      => $alt,
            'width'    => $width,
            'height'   => $height,
            'decoding' => $opts['decoding'] ?? 'async',
        ];

        $eager = !empty($opts['eager']);
        $lazy  = array_key_exists('lazy', $opts) ? (bool)$opts['lazy'] : !$eager;

        $attrs['loading'] = $eager ? 'eager' : ($lazy ? 'lazy' : null);
        if ($eager) $attrs['fetchpriority'] = 'high';

        if (!empty($opts['class'])) $attrs['class'] = $opts['class'];

        // srcset/sizes
        if (!empty($opts['srcset'])) {
            if (is_array($opts['srcset'])) {
                // ожидаем массив вида ['url 1x', 'url2 2x'] или ['url 800w', ...]
                $attrs['srcset'] = implode(', ', array_map('strval', $opts['srcset']));
            } else {
                $attrs['srcset'] = (string)$opts['srcset'];
            }
        }
        if (!empty($opts['sizes'])) {
            $attrs['sizes'] = (string)$opts['sizes'];
        }

        // data-*
        if (!empty($opts['data']) && is_array($opts['data'])) {
            foreach ($opts['data'] as $k => $v) {
                $attrs['data-'.$k] = $v;
            }
        }

        // произвольные атрибуты
        if (!empty($opts['attrs']) && is_array($opts['attrs'])) {
            $attrs = array_merge($attrs, $opts['attrs']);
        }

        // фильтруем null/пустые
        $attrs = array_filter($attrs, fn($v) => $v !== null && $v !== '');

        return '<img '.self::buildAttrs($attrs).'>';
    }

    /**
     * Удобный шорткат для твоих путей/размеров
     * $type: base | gallery | mini | gallery_mini
     * $opts — те же, что у img()
     */
    public static function product(string $type, ?string $file, string $alt = '', array $opts = []): string
{
    $map = [
        'base'         => ['/images/product/baseimg/',    'img_width',          'img_height'],
        'gallery'      => ['/images/product/gallery/',    'gallery_width',      'gallery_height'],
        'mini'         => ['/images/product/mini/',       'mini_img_width',     'mini_img_height'],
        'gallery_mini' => ['/images/product/gallery/mini/','mini_gallery_width','mini_gallery_height'],
    ];
    if (!isset($map[$type])) $type = 'base';
    [$dir, $kw, $kh] = $map[$type];

    $width  = $opts['width']  ?? App::$app->getProperty($kw) ?? null;
    $height = $opts['height'] ?? App::$app->getProperty($kh) ?? null;

    // fallback, если файла нет
    if (empty($file)) {
        $file = App::$app->getProperty('product_no_image')
             ?? ('/images/' . (App::$app->getProperty('og_logo') ?? 'Logo_round_1200.jpg'));
    }

    // если передан абсолютный путь — не добавляем $dir
    $srcIsAbsolute = (bool)preg_match('~^(?:/|https?://)~i', $file);
    $src = $srcIsAbsolute ? $file : $dir . ltrim($file, '/');

    if ($alt === '') $alt = 'Фото товара';

    return self::img($src, $alt, $width ? (int)$width : null, $height ? (int)$height : null, $opts);
}

    /** Проверка существования файла по веб-пути */
    private static function fileExistsWeb(string $path): bool {
        if (preg_match('~^https?://~i', $path)) return true; // для внешних ссылок считаем ок
        $doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        if (!$doc) return false;
        $rel = $path[0] === '/' ? $path : '/'.$path;
        return is_file($doc . $rel);
    }

    /**
     * Универсальный <picture>
     * $cfg:
     *   - fallback : string  // путь к базовому (обычно .jpg/.png). Если нет — можно указать webp
     *   - webp     : string|null  // если не задано, попробуем подставить fallback с заменой расширения на .webp
     *   - avif     : string|null  // если не задано, попробуем fallback с .avif
     *   - srcset        : string[]|string|null  // для fallback <img>
     *   - webp_srcset   : string[]|string|null
     *   - avif_srcset   : string[]|string|null
     *   - sizes         : string|null
     * $opts: как у img() + 'classPicture' (класс на <picture>)
     */
    public static function picture(array $cfg, string $alt = '', ?int $width = null, ?int $height = null, array $opts = []): string
    {
        $fallback = $cfg['fallback'] ?? '';
        if (!$fallback) return ''; // без <img> смысла нет

        // Автопостроение соседних форматов по имени fallback
        $pathNoExt = preg_replace('~\.(jpe?g|png|webp|avif)$~i', '', $fallback);
        $webp = $cfg['webp'] ?? ($pathNoExt ? $pathNoExt . '.webp' : null);
        $avif = $cfg['avif'] ?? ($pathNoExt ? $pathNoExt . '.avif' : null);

        // Проверим существование, чтобы не отдавать битые source
        $hasWebp = $webp && self::fileExistsWeb($webp);
        $hasAvif = $avif && self::fileExistsWeb($avif);
        $hasFallback = self::fileExistsWeb($fallback);

       // если fallback уже webp — не дублируем его отдельным <source>
        if ($hasWebp && $webp === $fallback) {
            $hasWebp = false;
        }

        // если фолбэка нет, но есть webp/avif — используем доступный как <img>
        if (!$hasFallback) {
            if ($hasAvif) {
                $fallback = $avif; $hasFallback = true; $hasAvif = false;
            } elseif ($hasWebp) {
                $fallback = $webp; $hasFallback = true; $hasWebp = false;
            }
        }

        // Сборка <source> по приоритету: AVIF → WebP
        $sources = [];

        $sizes = $cfg['sizes'] ?? null;

        $srcsetStr = function($v): ?string {
            if (!$v) return null;
            if (is_array($v)) return implode(', ', array_map('strval', $v));
            return (string)$v;
        };

        if ($hasAvif) {
            $sources[] = '<source type="image/avif" '
                . 'srcset="'.self::esc(self::absUrl($avif)).($cfg['avif_srcset'] ? ', '.$srcsetStr($cfg['avif_srcset']) : '').'"'
                . ($sizes ? ' sizes="'.self::esc($sizes).'"' : '')
                . '>';
        }
        if ($hasWebp) {
            $sources[] = '<source type="image/webp" '
                . 'srcset="'.self::esc(self::absUrl($webp)).($cfg['webp_srcset'] ? ', '.$srcsetStr($cfg['webp_srcset']) : '').'"'
                . ($sizes ? ' sizes="'.self::esc($sizes).'"' : '')
                . '>';
        }

        // Сборка <img> (fallback)
        $imgOpts = $opts;
        if (!empty($cfg['srcset']))  $imgOpts['srcset'] = $cfg['srcset'];
        if (!empty($cfg['sizes']))   $imgOpts['sizes']  = $cfg['sizes'];

        $img = self::img($fallback, $alt, $width, $height, $imgOpts);

        $classPicture = !empty($opts['classPicture']) ? ' class="'.self::esc($opts['classPicture']).'"' : '';
        return '<picture'.$classPicture.'>' . implode('', $sources) . $img . '</picture>';
    }

    /**
     * Шорткат под твои каталоги товара.
     * $type: base | gallery | mini | gallery_mini
     * $file: имя файла (с расширением .webp/.jpg/.png)
     * Если передал .webp — попробуем собрать .avif и .jpg фолбэк по тому же имени (если есть).
     */
    public static function productPicture(string $type, ?string $file, string $alt = '', array $opts = []): string
{
    $map = [
        'base'         => ['/images/product/baseimg/',     'img_width',           'img_height'],
        'gallery'      => ['/images/product/gallery/',     'gallery_width',       'gallery_height'],
        'mini'         => ['/images/product/mini/',        'mini_img_width',      'mini_img_height'],
        //'gallery_mini' => ['/images/product/gallery/mini/','mini_gallery_width',  'mini_gallery_height'],
        'complete'      => ['/images/complete/baseimg/', 'img_width',      'img_height'],
        'complete_mini' => ['/images/complete/mini/',    'mini_img_width', 'mini_img_height'],
    ];
    if (!isset($map[$type])) $type = 'base';
    [$dir, $kw, $kh] = $map[$type];

    $width  = $opts['width']  ?? \ishop\App::$app->getProperty($kw) ?? null;
    $height = $opts['height'] ?? \ishop\App::$app->getProperty($kh) ?? null;

    // fallback, если файла нет
    if (empty($file)) {
        $file = \ishop\App::$app->getProperty('product_no_image')
             ?? ('/images/' . (\ishop\App::$app->getProperty('og_logo') ?? 'Logo_round_1200.jpg'));
    }

    // абсолютный путь? тогда не добавляем $dir
    $srcIsAbsolute = (bool)preg_match('~^(?:/|https?://)~i', $file);
    $path = $srcIsAbsolute ? $file : $dir . ltrim($file, '/');

    // ↑↑ ВАЖНО: больше НЕ меняем расширение на .jpg — используем как есть!
    // Если рядом лежит avif, picture() сам добавит <source type="image/avif">

    $cfg = [
        'fallback' => $path, // как есть (webp останется webp)
        // webp/avif автоматически достроятся внутри picture() по тому же имени,
        // но если fallback уже webp, webp-<source> будет пропущен как дубликат.
    ];

    if ($alt === '') $alt = 'Фото товара';

    return self::picture($cfg, $alt, $width ? (int)$width : null, $height ? (int)$height : null, $opts);
}


}
