<?php
$cronId=(int)($_GET['id']??0);
if ($cronId<=0) throw new \RuntimeException('Cron ID required');
$result=\app\services\DirectGallerySelection::generate();
foreach ($result as $key=>$feed) {
    \app\services\SpecialTyreFeedService::writeAtomically(WWW.'/cron/direct-715980319-'.$key.'.xml',$feed['xml']);
}
$updated=date('Y-m-d H:i:s');
\R::exec('UPDATE cron SET date_update=? WHERE id=?',[$updated,$cronId]);
\app\services\admin\AdminActivityLogger::cron($cronId,PHP_SAPI!=='cli',$_SESSION['user']['id']??null,$updated);
if (PHP_SAPI==='cli') echo 'DONE direct-gallery-715980319 offers='.$result['all']['offers'].PHP_EOL;
else { $_SESSION['success']='Фиды товарной галереи обновлены'; redirect(PATH.'/admin/cron'); }
