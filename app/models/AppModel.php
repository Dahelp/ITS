<?php

namespace app\models;

use ishop\base\Model;

class AppModel extends Model{

    public static function createAlias($table, $field, $str, $id = null, $allowDot = false, $allowSlash = false)
    {
        $str = self::str2url($str, $allowDot, $allowSlash);

        $res = \R::findOne($table, "$field = ?", [$str]);

        if ($res && $res->id != $id) {
            $counter = 2;
            $newStr = $str;
            do {
                $newStr = "{$str}-{$counter}";
                $res = \R::findOne($table, "$field = ?", [$newStr]);
                $counter++;
            } while ($res && $res->id != $id);

            return $newStr;
        }

        return $str;
    }

    public static function str2url($str, $allowDot = false, $allowSlash = false)
    {
        // переводим в транслит
        $str = self::rus2translit($str);
        // в нижний регистр
        $str = strtolower($str);

        // Разрешённые символы:
        // - всегда: a-z 0-9 _ -
        // - опционально: .
        // - опционально: /
        $allowed = '-a-z0-9_';

        if ($allowDot) {
            $allowed .= '\.';
        }

        if ($allowSlash) {
            $allowed .= '\/';
        }

        $pattern = '~[^' . $allowed . ']+~u';
        $str = preg_replace($pattern, '-', $str);

        // чистим повторяющиеся дефисы
        $str = preg_replace('~-{2,}~', '-', $str);

        // чистим повторяющиеся слеши
        if ($allowSlash) {
            $str = preg_replace('~/+~', '/', $str);
        }

        // удаляем мусор по краям
        $str = trim($str, "-/");

        return $str;
    }

    public static function rus2translit($string) {

        $converter = array(

            'а' => 'a',   'б' => 'b',   'в' => 'v',

            'г' => 'g',   'д' => 'd',   'е' => 'e',

            'ё' => 'e',   'ж' => 'zh',  'з' => 'z',

            'и' => 'i',   'й' => 'y',   'к' => 'k',

            'л' => 'l',   'м' => 'm',   'н' => 'n',

            'о' => 'o',   'п' => 'p',   'р' => 'r',

            'с' => 's',   'т' => 't',   'у' => 'u',

            'ф' => 'f',   'х' => 'h',   'ц' => 'c',

            'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',

            'ь' => '',  'ы' => 'y',   'ъ' => '',

            'э' => 'e',   'ю' => 'yu',  'я' => 'ya',



            'А' => 'A',   'Б' => 'B',   'В' => 'V',

            'Г' => 'G',   'Д' => 'D',   'Е' => 'E',

            'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',

            'И' => 'I',   'Й' => 'Y',   'К' => 'K',

            'Л' => 'L',   'М' => 'M',   'Н' => 'N',

            'О' => 'O',   'П' => 'P',   'Р' => 'R',

            'С' => 'S',   'Т' => 'T',   'У' => 'U',

            'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',

            'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',

            'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',

            'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
        );

        return strtr($string, $converter);

    }
	
	/**
     * Универсальный ресайз с сохранением пропорций и поддержкой прозрачности.
     *
     * @param string $src     Путь к исходному файлу
     * @param string $dest    Путь сохранения результата
     * @param int    $wmax    Максимальная ширина рамки
     * @param int    $hmax    Максимальная высота рамки
     * @param string $outExt  Формат результата: webp|jpeg|jpg|png|gif
     * @param array  $opts    Опции качества: ['jpeg_quality'=>85,'webp_quality'=>85,'png_compress'=>6,'no_upscale'=>true]
     * @return bool
     */
    public static function resize(string $src, string $dest, int $wmax, int $hmax, string $outExt = 'webp', array $opts = []): bool
    {
        if (!is_file($src)) return false;

        $outExt = strtolower($outExt);
        if ($outExt === 'jpg') $outExt = 'jpeg';

        // Качества и флаги
        $jpegQ = isset($opts['jpeg_quality']) ? (int)$opts['jpeg_quality'] : 85;
        $webpQ = isset($opts['webp_quality']) ? (int)$opts['webp_quality'] : 85;
        $pngC  = isset($opts['png_compress']) ? (int)$opts['png_compress'] : 6; // 0..9
        $noUps = array_key_exists('no_upscale', $opts) ? (bool)$opts['no_upscale'] : true;

        // Читаем исходник и определяем его тип
        $info = @getimagesize($src);
        if (!$info) return false;

        $wOrig = (int)$info[0];
        $hOrig = (int)$info[1];
        $type  = (int)$info[2]; // IMAGETYPE_*

        // Не апскейлим при желании
        if ($noUps) {
            $wmax = min($wmax, $wOrig);
            $hmax = min($hmax, $hOrig);
        }

        if ($wOrig <= 0 || $hOrig <= 0 || $wmax <= 0 || $hmax <= 0) return false;

        // Коэффициенты вписывания
        $ratioOrig = $wOrig / $hOrig;
        $ratioBox  = $wmax / $hmax;

        if ($ratioBox > $ratioOrig) {
            // рамка "шире" — ограничиваем высотой
            $wNew = (int)round($hmax * $ratioOrig);
            $hNew = $hmax;
        } else {
            // рамка "уже" — ограничиваем шириной
            $wNew = $wmax;
            $hNew = (int)round($wmax / $ratioOrig);
        }

        // Загружаем исходник
        switch ($type) {
            case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($src); break;
            case IMAGETYPE_PNG:  $im = @imagecreatefrompng($src);  break;
            case IMAGETYPE_GIF:  $im = @imagecreatefromgif($src);  break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $im = @imagecreatefromwebp($src);
                    break;
                }
                // fallback — попробуем как jpeg
                $im = @imagecreatefromjpeg($src);
                break;
            default:
                // неизвестный формат — пробуем jpeg
                $im = @imagecreatefromjpeg($src);
        }
        if (!$im) return false;

        // Целевой холст
        $new = imagecreatetruecolor($wNew, $hNew);

        // Прозрачность
        $preserveAlpha = in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF], true) || $outExt === 'png' || $outExt === 'gif' || $outExt === 'webp';
        if ($preserveAlpha) {
            // для GIF: сделаем прозрачный фон
            imagealphablending($new, false);
            imagesavealpha($new, true);
            $transparent = imagecolorallocatealpha($new, 0, 0, 0, 127);
            imagefill($new, 0, 0, $transparent);
        }

        // Сам ресайз
        if (function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        imagecopyresampled($new, $im, 0, 0, 0, 0, $wNew, $hNew, $wOrig, $hOrig);

        // Сохранение в нужный формат
        $ok = false;
        switch ($outExt) {
            case 'webp':
                if (function_exists('imagewebp')) {
                    // качество 0..100
                    $ok = @imagewebp($new, $dest, $webpQ);
                }
                break;
            case 'jpeg':
                // качество 0..100
                $ok = @imagejpeg($new, $dest, $jpegQ);
                break;
            case 'png':
                // уровень сжатия 0..9 (0 — без сжатия, 9 — макс.)
                $ok = @imagepng($new, $dest, max(0, min(9, $pngC)));
                break;
            case 'gif':
                // у GIF нет понятия "качества" в GD
                $ok = @imagegif($new, $dest);
                break;
            default:
                // по умолчанию — jpeg
                $ok = @imagejpeg($new, $dest, $jpegQ);
        }

        imagedestroy($new);
        imagedestroy($im);

        return (bool)$ok;
    }

}