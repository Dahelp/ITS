<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);require $root.'/config/init.php';require LIBS.'/functions.php';require CONF.'/db_bootstrap.php';
$task=R::findOne('cron','url_params=?',['ymlfid-gallery']);
if(!$task){$task=R::dispense('cron');$task->name='Директ 715980319 — 30 выбранных товаров';$task->alias='direct-gallery-715980319';$task->url_params='ymlfid-gallery';$task->url_download='direct-715980319-all';$task->hide='show';$task->categories='';R::store($task);}
echo 'CRON_ID='.$task->id.PHP_EOL;
$controller=APP.'/controllers/CronController.php';
$code=file_get_contents($controller);
if (!str_contains($code,'function ymlfidGalleryAction(')) {
    $anchor='class CronController extends AppController {';
    if (!str_contains($code,$anchor)) throw new RuntimeException('Controller anchor missing');
    $backup=$controller.'.before-gallery-715980319';
    if (!is_file($backup) && !copy($controller,$backup)) throw new RuntimeException('Backup failed');
    $code=str_replace($anchor,$anchor."\n    public function ymlfidGalleryAction() { return 'Задание выполнено!'; }",$code);
    if(file_put_contents($controller,$code,LOCK_EX)===false)throw new RuntimeException('Controller update failed');
}
