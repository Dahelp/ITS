<?php
// Reuse approved P350 artwork at exactly the same sizes; no image regeneration.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/config/init.php';
require CONF.'/db_bootstrap.php';
$specs = [
    ['article'=>'12487','source_article'=>'11419','file'=>'ekka-p350-27x9-14-4pr-12487.webp'],
    ['article'=>'12488','source_article'=>'11421','file'=>'ekka-p350-27x11-14-4pr-12488.webp'],
];
$changes = [];
foreach ($specs as $spec) {
    $target = R::getRow('SELECT id,name,article,alias,img FROM product WHERE article = ?', [$spec['article']]);
    $source = R::getRow('SELECT id,name,article,img FROM product WHERE article = ?', [$spec['source_article']]);
    if (!$target || !$source || stripos($source['name'],'P350') === false || stripos($target['name'],'P350') === false) {
        throw new RuntimeException('Не найдены ожидаемые карточки P350');
    }
    foreach (['baseimg','mini'] as $dir) {
        $from=WWW.'/images/product/'.$dir.'/'.basename($source['img']);
        $to=WWW.'/images/product/'.$dir.'/'.$spec['file'];
        if (!is_file($from)) { throw new RuntimeException('Нет исходного фото: '.$from); }
        if (is_file($to) && hash_file('sha256',$from) !== hash_file('sha256',$to)) {
            throw new RuntimeException('Файл назначения уже существует и отличается: '.$to);
        }
        if (!is_file($to) && !copy($from,$to)) { throw new RuntimeException('Не удалось скопировать фото'); }
        if (hash_file('sha256',$from) !== hash_file('sha256',$to)) { throw new RuntimeException('Контрольная сумма фото не совпадает'); }
    }
    $changes[]=['target'=>$target,'source'=>$source,'new_img'=>$spec['file']];
}
$backup=ROOT.'/storage/p350-27-4pr-photos-before-20261016.json';
if (!is_file($backup) && file_put_contents($backup,json_encode($changes,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) === false) {
    throw new RuntimeException('Не удалось сохранить резервную копию');
}
R::begin();
try {
    foreach ($changes as $change) {
        R::exec('UPDATE product SET img = ? WHERE id = ? AND article = ?', [$change['new_img'],$change['target']['id'],$change['target']['article']]);
    }
    R::commit();
} catch (Throwable $e) { R::rollback(); throw $e; }
foreach ($changes as $change) {
    echo json_encode(['id'=>$change['target']['id'],'article'=>$change['target']['article'],
        'url'=>'https://its-center.ru/product/'.$change['target']['alias'], 'img'=>$change['new_img'],
        'source_article'=>$change['source']['article'],'unchanged_pixels'=>true],JSON_UNESCAPED_UNICODE).PHP_EOL;
}
