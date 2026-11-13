<?php
namespace app\services;
final class CatalogUpdateHealth
{
    public const STATES=['unknown'=>'Контрольный запуск ещё не зафиксирован','running'=>'Выполняется','success'=>'Завершено','partial'=>'Завершено с неполными данными API','error'=>'Ошибка','interrupted'=>'Прервано до контрольной отметки'];
    public static function snapshot(\PDO $db,string $root): array
    {
        $store=new CatalogTaskStatus($root.'/storage/catalog-task-status');$tasks=[];
        foreach($db->query('SELECT id,name,url_params,date_update,url_download FROM cron ORDER BY id')as$row){if($row['url_params']!=='refresh-tovars-server'&&!str_contains($row['url_params'],'yml'))continue;$row['run']=$store->observed((int)$row['id']);$tasks[]=$row;}
        $file=$root.'/storage/catalog-task-status/probe.json';$probe=is_file($file)&&filesize($file)<65536?json_decode((string)file_get_contents($file),true):null;
        return ['checked_at'=>date('Y-m-d H:i:s'),'tasks'=>$tasks,'feeds'=>YandexProductsCabinet::snapshot($root.'/public'),'probe'=>is_array($probe)?$probe:null];
    }
}
