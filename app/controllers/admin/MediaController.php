<?php
namespace app\controllers\admin;

use ishop\App;               // ВАЖНО: правильный App
use app\helpers\Upload;      // наш хелпер сохранения файлов
use app\helpers\UploadPresets;

class MediaController extends AppController
{    
    /** Унифицированный JSON-ответ (если используешь в других местах) */
    private function json(array $data, int $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        return;
    }

    /** Карта путей для секций */
    private function paths(string $section) : array
    {
        $w = rtrim(WWW, '/');
        switch ($section) {
            case 'product':
                return [
                    'baseimg' => "$w/images/product/baseimg/",
                    'mini'    => "$w/images/product/mini/",
                    'gallery' => "$w/images/product/gallery/",
                    'unload'  => "$w/images/product/unload/",
                ];
            case 'complete':
                return [
                    'baseimg' => "$w/images/complete/baseimg/",
                    'mini'    => "$w/images/complete/mini/",
                    'gallery' => "$w/images/complete/gallery/",
                ];
            case 'review':
                return [
                    'gallery' => "$w/images/review/gallery/",
                    'mini'    => "$w/images/review/mini/",
                ];
            case 'contents':
                return [
                    'baseimg' => "$w/images/contents/baseimg/",
                    'mini'    => "$w/images/contents/mini/",
                ];
            case 'technics':
                return [
                    'baseimg' => "$w/images/technics/baseimg/",
                    'mini'    => "$w/images/technics/mini/",
                ];
            case 'category':
                return [
                    'baseimg' => "$w/images/category/baseimg/",
                ];
            case 'brand':
                return [
                    'baseimg' => "$w/images/brand/baseimg/",
                ];
            case 'technics_type':
                return [
                    'baseimg' => "$w/images/technics_type/baseimg/",
                ];
            case 'technics_manufacturer':
                return [
                    'baseimg' => "$w/images/technics_manufacturer/baseimg/",
                ];
            default:
                return [
                    'baseimg' => "$w/images/{$section}/baseimg/",
                ];
        }
    }

    /** Строим URL превью. */
    private function previewUrl(string $section, string $mode, string $fname): string
    {
        $p = $this->paths($section);

        if ($section === 'product') {
            if ($mode === 'multi') {
                return $p['url']['gallery'] . $fname;
            } elseif ($mode === 'unload') {
                return $p['url']['unload'] . $fname;
            } else {
                return $p['url']['baseimg'] . $fname;
            }
        } elseif ($section === 'complete') {
            if ($mode === 'multi') {
                return $p['url']['gallery'] . $fname;
            } else {
                return $p['url']['baseimg'] . $fname;
            }
        } elseif ($section === 'review') {
            return $p['url']['mini'] . $fname;
        } elseif ($section === 'contents') {
            return $p['url']['baseimg'] . $fname;
        } elseif ($section === 'technics') {
            return $p['url']['baseimg'] . $fname;
        } elseif (in_array($section, ['category','brand','technics_type','technics_manufacturer'], true)) {
            return $p['url']['baseimg'] . $fname;
        } else {
            $base = isset($p['url']['baseimg']) ? $p['url']['baseimg'] : '/images/'.$section.'/baseimg/';
            return $base . $fname;
        }
    }


    /** Безопасное удаление файла */
    private function rm(string $path) : void
    {
        if ($path && is_file($path)) { @unlink($path); }
    }

    public function uploadAction()
{
    // только JSON, без шаблонов
    $this->layout = false;
    if (property_exists($this, 'view')) { $this->view = false; }

    try {
        if (empty($_GET['upload'])) {
            throw new \RuntimeException('Bad request');
        }

        $section = (string)($_POST['section'] ?? $_GET['section'] ?? 'product');
        $mode    = (string)($_POST['mode']    ?? $_GET['mode']    ?? 'single');

        if (empty($_FILES['file'])) {
            throw new \RuntimeException('No file');
        }

        $format  = (string)(\ishop\App::$app->getProperty('img_target_format') ?: 'webp');

        // ВАЖНО: используем $result везде ниже
        $result = \app\helpers\Upload::handle($section, $mode, $_FILES['file'], $format);

        // НИКАКОЙ записи в БД здесь! Только в сессию (как раньше)
        if (!isset($_SESSION['upload'])) $_SESSION['upload'] = [];
        $_SESSION['upload'][$section][$mode] = $_SESSION['upload'][$section][$mode] ?? [];

        switch ($mode) {
            case 'single':
                $_SESSION['single'] = $result['file'];
                $_SESSION['upload'][$section][$mode] = $result['file'];
                break;
            case 'multi':
                $_SESSION['multi'][] = $result['file'];
                $_SESSION['upload'][$section][$mode][] = $result['file'];
                break;
            case 'unload':
                $_SESSION['unload'] = $result['file'];
                $_SESSION['upload'][$section][$mode] = $result['file'];
                break;
        }

        // Чистый JSON
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'         => true,
            'file'       => $result['file'],
            'previewUrl' => $result['previewUrl'] ?? null,
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (\Throwable $e) {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

    public function deleteAction()
{
    // Только админ
    if (empty($_SESSION['user']['id']) || ($_SESSION['user']['groups'] ?? '') !== '1') {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }

    // Гарантированно выключаем рендеринг и объявляем тип ответа
    $this->layout = false;
    // в этом фреймворке свойство обычно есть — просто гаси:
    $this->view   = false;
    header('Content-Type: application/json; charset=UTF-8');

    $section = (string)($_POST['section'] ?? $_POST['razdel'] ?? 'product');
    $id      = (int)($_POST['id'] ?? $_POST['entity_id'] ?? 0);
    $src     = trim((string)($_POST['src'] ?? $_POST['filename'] ?? ''));
    $modeIn  = (string)($_POST['mode'] ?? '');

    if ($src === '') {
        echo json_encode(['ok' => false, 'error' => 'no src']);
        exit;
    }
    $src = basename($src);

    $p    = $this->paths($section);
    $mode = $this->normalizeMode($modeIn);
    if ($mode === '') {
        if ($section === 'product') {
            if (!empty($p['gallery']) && is_file($p['gallery'].$src)) $mode = 'gallery';
            elseif (!empty($p['unload']) && is_file($p['unload'].$src)) $mode = 'unload';
            else $mode = 'base';
        } elseif ($section === 'complete') {
            $mode = (!empty($p['gallery']) && is_file($p['gallery'].$src)) ? 'gallery' : 'base';
        } elseif ($section === 'review') {
            $mode = 'gallery';
        } else {
            $mode = 'base';
        }
    }

    try {
        switch ($section) {
            case 'product':
                if ($mode === 'base') {
                    if ($id > 0) {
                        \R::exec('UPDATE product SET img=NULL WHERE id=? AND img=?', [$id, $src]);
                    }
                    if (!empty($p['baseimg'])) $this->rm($p['baseimg'].$src);
                    if (!empty($p['mini']))    $this->rm($p['mini'].$src);
                } elseif ($mode === 'gallery') {
                    if ($id > 0) {
                        \R::exec('DELETE FROM gallery WHERE product_id=? AND img=?', [$id, $src]);
                    }
                    if (!empty($p['gallery'])) $this->rm($p['gallery'].$src);
                } elseif ($mode === 'unload') {
                    if ($id > 0) {
                        \R::exec('UPDATE product SET unload_img=NULL WHERE id=? AND unload_img=?', [$id, $src]);
                    }
                    if (!empty($p['unload'])) $this->rm($p['unload'].$src);
                }
                break;

            case 'complete':
                if ($mode === 'base') {
                    if ($id > 0) {
                        \R::exec('UPDATE plagins_complete SET img=NULL WHERE id=? AND img=?', [$id, $src]);
                    }
                    if (!empty($p['baseimg'])) $this->rm($p['baseimg'].$src);
                    if (!empty($p['mini']))    $this->rm($p['mini'].$src);
                } elseif ($mode === 'gallery') {
                    if ($id > 0) {
                        \R::exec('DELETE FROM plagins_complete_gallery WHERE complete_id=? AND img=?', [$id, $src]);
                    }
                    if (!empty($p['gallery'])) $this->rm($p['gallery'].$src);
                }
                break;

            case 'review':
                if ($id > 0) {
                    \R::exec('DELETE FROM review_gallery WHERE review_id=? AND img=?', [$id, $src]);
                }
                if (!empty($p['mini']))    $this->rm($p['mini'].$src);
                if (!empty($p['gallery'])) $this->rm($p['gallery'].$src);
                break;

            case 'contents':
            case 'technics':
            case 'category':
            case 'brand':
            case 'technics_type':
            case 'technics_manufacturer':
                if (!empty($p['mini']))    $this->rm($p['mini'].$src);
                if (!empty($p['baseimg'])) $this->rm($p['baseimg'].$src);
                break;

            default:
                if (!empty($p['baseimg'])) $this->rm($p['baseimg'].$src);
        }

        echo json_encode(['ok' => true, 'result' => 1]);
        exit;

    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
    // --- помощники ----------------------------------------------------------

    /** Преобразуем старые значения в новые: single->base, multi->gallery */
    private function normalizeMode(string $modeIn) : string
    {
        $m = strtolower(trim($modeIn));
        if ($m === '' )      return '';
        if ($m === 'base' )  return 'base';
        if ($m === 'gallery')return 'gallery';
        if ($m === 'unload') return 'unload';
        if ($m === 'single') return 'base';
        if ($m === 'multi')  return 'gallery';
        return 'base';
    }

    /** Находим оригинал (расширение может отличаться) */
    private static function findOriginal(string $derived, string $origDirRel): string
    {
        $stem = pathinfo($derived, PATHINFO_FILENAME);
        $dir  = rtrim(WWW,'/').'/'.trim($origDirRel,'/');
        if (!is_dir($dir)) return '';
        foreach (['jpg','jpeg','png','gif','webp','avif'] as $e) {
            $cand = $dir.'/'.$stem.'.'.$e;
            if (is_file($cand)) return basename($cand);
        }
        return '';
    }

    public function convertAction()
    {
        if (empty($_SESSION['user']['id']) || $_SESSION['user']['groups'] !== '1') {
            $_SESSION['error'] = 'Доступ запрещён';
            redirect(ADMIN);
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $section = $_GET['section'] ?? 'product'; // или 'all'
        $id      = (int)($_GET['id'] ?? 0);
        $back    = $_GET['back'] ?? null;

        $media   = (array)App::$app->getProperty('media');
        $target  = strtolower($media['target_ext'] ?? 'webp');
        $quality = (array)($media['quality'] ?? []);

        $stats = ['ok'=>0,'skip'=>0,'err'=>0];

        try {
            if ($id > 0) {
                $this->convertOne($section, $id, $target, $quality, $stats, $media);
            } else {
                $sections = ($section==='all')
                    ? ['product','complete','review','technics','technics_type','technics_brand','category','brand','content']
                    : [$section];

                foreach ($sections as $sec) {
                    $ids = $this->idsForSection($sec);
                    foreach ($ids as $one) {
                        $this->convertOne($sec, (int)$one, $target, $quality, $stats, $media);
                    }
                }
            }

            $_SESSION['success'] = "Переконвертация: ок {$stats['ok']}, пропущено {$stats['skip']}, ошибок {$stats['err']}";
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Ошибка переконвертации: '.$e->getMessage();
        }

        // аккуратно «режем» длинные back: берём только путь (без гигантских query)
        if ($back) {
            $p = parse_url($back);
            $safeBack = ($p['path'] ?? ADMIN);
            if (!empty($p['query'])) {
                // можно оставить пару параметров, если нужно; либо совсем без query:
                $safeBack .= '?'.http_build_query([]);
            }
            redirect($safeBack);
        } else {
            // дефолт: на страницу раздела
            redirect(ADMIN.'/'.($section==='all' ? 'plagins/complete' : $section));
        }
    }

    private function idsForSection(string $section): array
    {
        switch ($section) {
            case 'product':         return \R::getCol('SELECT id FROM product');
            case 'complete':        return \R::getCol('SELECT id FROM plagins_complete');
            case 'review':          return \R::getCol('SELECT id FROM review_gallery'); // по галереям (ид может повторяться — норм, мы берём по файлам)
            case 'technics':        return \R::getCol('SELECT id FROM technics');
            case 'technics_type':   return \R::getCol('SELECT id FROM technics_type');
            case 'technics_brand':  return \R::getCol('SELECT id FROM technics_manufacturer');
            case 'category':        return \R::getCol('SELECT id FROM category');
            case 'brand':           return \R::getCol('SELECT id FROM brand');
            case 'content':         return \R::getCol('SELECT id FROM content');
            default:                return [];
        }
    }

    private function convertOne(string $section, int $id, string $target, array $quality, array &$stats, array $media)
    {
        // формат-переопределения по виду (например, product/unload → jpg)
        $over = (array)($media['format_overrides'][$section] ?? []);

        try {
            switch ($section) {

                case 'product': {
                    $row = \R::findOne('product','id=?',[$id]);
                    if (!$row) { $stats['skip']++; break; }

                    // baseimg + mini
                    if (!empty($row->img)) {
                        $absB = WWW.'/images/product/baseimg/'.$row->img;
                        $absM = WWW.'/images/product/mini/'.$row->img;

                        $to = Upload::pickTargetExt($target); // глобальный формат
                        $newB = Upload::convertFile($absB, $to, ['quality'=>$quality]);
                        if ($newB) {
                            if (is_file($absM)) {
                                $newM = Upload::convertFile($absM, $to, ['quality'=>$quality]);
                            }
                            if ($newB !== $row->img) {
                                \R::exec('UPDATE product SET img=? WHERE id=?', [$newB, $id]);
                            }
                            $stats['ok']++;
                        } else { $stats['skip']++; }
                    }

                    // gallery
                    $gal = \R::findAll('gallery','product_id=?',[$id]);
                    foreach ($gal as $g) {
                        $abs = WWW.'/images/product/gallery/'.$g->img;
                        $to  = Upload::pickTargetExt($target);
                        $new = Upload::convertFile($abs, $to, ['quality'=>$quality]);
                        if ($new) { $g->img=$new; \R::store($g); $stats['ok']++; } else { $stats['skip']++; }
                    }

                    // unload → всегда JPG
                    if (!empty($row->unload_img)) {
                        $absU = WWW.'/images/product/unload/'.$row->unload_img;
                        $newU = Upload::convertFile($absU, 'jpg', ['quality'=>$quality]);
                        if ($newU && $newU !== $row->unload_img) {
                            \R::exec('UPDATE product SET unload_img=? WHERE id=?', [$newU, $id]);
                        }
                    }

                    break;
                }

                case 'complete': {
                    $row = \R::findOne('plagins_complete','id=?',[$id]);
                    if (!$row) { $stats['skip']++; break; }

                    // baseimg + mini
                    if (!empty($row->img)) {
                        $absB = WWW.'/images/complete/baseimg/'.$row->img;
                        $absM = WWW.'/images/complete/mini/'.$row->img;
                        $to   = Upload::pickTargetExt($target);
                        $newB = Upload::convertFile($absB, $to, ['quality'=>$quality]);
                        if ($newB) {
                            if (is_file($absM)) Upload::convertFile($absM, $to, ['quality'=>$quality]);
                            if ($newB !== $row->img) \R::exec('UPDATE plagins_complete SET img=? WHERE id=?', [$newB, $id]);
                            $stats['ok']++;
                        } else { $stats['skip']++; }
                    }

                    // gallery (если будете заполнять таблицу)
                    $gal = \R::findAll('plagins_complete_gallery','complete_id=?',[$id]);
                    foreach ($gal as $g) {
                        $abs = WWW.'/images/complete/gallery/'.$g->img;
                        $to  = Upload::pickTargetExt($target);
                        $new = Upload::convertFile($abs, $to, ['quality'=>$quality]);
                        if ($new) { $g->img=$new; \R::store($g); $stats['ok']++; } else { $stats['skip']++; }
                    }
                    break;
                }

                case 'review': {
                    // у отзывов мы конвертим сами картинки галереи + мини
                    $g = \R::findOne('review_gallery','id=?',[$id]);
                    if (!$g) { $stats['skip']++; break; }
                    $to = Upload::pickTargetExt($target);
                    $absG = WWW.'/images/review/gallery/'.$g->img;
                    $absM = WWW.'/images/review/mini/'.$g->img;
                    $newG = Upload::convertFile($absG, $to, ['quality'=>$quality]);
                    if ($newG) {
                        if (is_file($absM)) Upload::convertFile($absM, $to, ['quality'=>$quality]);
                        if ($newG !== $g->img) { $g->img=$newG; \R::store($g); }
                        $stats['ok']++;
                    } else { $stats['skip']++; }
                    break;
                }

                case 'technics': {
                    $row = \R::findOne('technics','id=?',[$id]);
                    if (!$row) { $stats['skip']++; break; }
                    if (!empty($row->img)) {
                        $absB = WWW.'/images/technics/baseimg/'.$row->img;
                        $absM = WWW.'/images/technics/mini/'.$row->img;
                        $to   = Upload::pickTargetExt($target);
                        $newB = Upload::convertFile($absB, $to, ['quality'=>$quality]);
                        if ($newB) {
                            if (is_file($absM)) Upload::convertFile($absM, $to, ['quality'=>$quality]);
                            if ($newB !== $row->img) \R::exec('UPDATE technics SET img=? WHERE id=?', [$newB, $id]);
                            $stats['ok']++;
                        } else { $stats['skip']++; }
                    }
                    break;
                }

                case 'technics_type': {
                    $row = \R::findOne('technics_type','id=?',[$id]);
                    if (!$row) { $stats['skip']++; break; }
                    if (!empty($row->img)) {
                        $abs = WWW.'/images/technics_type/baseimg/'.$row->img;
                        $to  = Upload::pickTargetExt($target);
                        $new = Upload::convertFile($abs, $to, ['quality'=>$quality]);
                        if ($new && $new !== $row->img) \R::exec('UPDATE technics_type SET img=? WHERE id=?',[$new,$id]);
                        $stats['ok']++;
                    } else { $stats['skip']++; }
                    break;
                }

                case 'technics_brand': {
                    $row = \R::findOne('technics_manufacturer','id=?',[$id]);
                    if (!$row) { $stats['skip']++; break; }
                    if (!empty($row->img)) {
                        $abs = WWW.'/images/technics_manufacturer/baseimg/'.$row->img;
                        $to  = Upload::pickTargetExt($target);
                        $new = Upload::convertFile($abs, $to, ['quality'=>$quality]);
                        if ($new && $new !== $row->img) \R::exec('UPDATE technics_manufacturer SET img=? WHERE id=?',[$new,$id]);
                        $stats['ok']++;
                    } else { $stats['skip']++; }
                    break;
                }

                case 'category': {
                    $row = \R::findOne('category','id=?',[$id]);
                    if (!$row) { $stats['skip']++; break; }
                    if (!empty($row->img)) {
                        $abs = WWW.'/images/category/baseimg/'.$row->img;
                        $to  = Upload::pickTargetExt($target);
                        $new = Upload::convertFile($abs, $to, ['quality'=>$quality]);
                        if ($new && $new !== $row->img) \R::exec('UPDATE category SET img=? WHERE id=?',[$new,$id]);
                        $stats['ok']++;
                    } else { $stats['skip']++; }
                    break;
                }

                case 'brand': {
                    $row = \R::findOne('brand','id=?',[$id]);
                    if (!$row) { $stats['skip']++; break; }
                    if (!empty($row->img)) {
                        $abs = WWW.'/images/brand/baseimg/'.$row->img;
                        $to  = Upload::pickTargetExt($target);
                        $new = Upload::convertFile($abs, $to, ['quality'=>$quality]);
                        if ($new && $new !== $row->img) \R::exec('UPDATE brand SET img=? WHERE id=?',[$new,$id]);
                        $stats['ok']++;
                    } else { $stats['skip']++; }
                    break;
                }

                case 'content': {
                    $row = \R::findOne('content','id=?',[$id]);
                    if (!$row) { $stats['skip']++; break; }
                    if (!empty($row->img)) {
                        $absB = WWW.'/images/contents/baseimg/'.$row->img;
                        $absM = WWW.'/images/contents/mini/'.$row->img;
                        $to   = Upload::pickTargetExt($target);
                        $newB = Upload::convertFile($absB, $to, ['quality'=>$quality]);
                        if ($newB) {
                            if (is_file($absM)) Upload::convertFile($absM, $to, ['quality'=>$quality]);
                            if ($newB !== $row->img) \R::exec('UPDATE content SET img=? WHERE id=?',[$newB,$id]);
                            $stats['ok']++;
                        } else { $stats['skip']++; }
                    }
                    break;
                }
            }
        } catch (\Throwable $e) {
            $stats['err']++;
        }
    }
}
