<?php
namespace app\services;

/** Read-only local diagnostics. This does not assert acceptance by Yandex. */
final class YandexProductsCabinet
{
    public static function fileState(string $directory, string $name): array
    {
        $out=['filename'=>$name,'status'=>'Файл отсутствует','offers'=>null,'date'=>null,'bytes'=>0];
        if (!preg_match('/^[a-zA-Z0-9_-]+\.xml$/D',$name)) { $out['status']='Недопустимое имя файла'; return $out; }
        $root=realpath($directory); $path=$root ? realpath($root.'/'.$name) : false;
        if (!$path || dirname($path)!==$root || !is_file($path) || !is_readable($path)) return $out;
        $out['date']=date('Y-m-d H:i:s',filemtime($path)); $out['bytes']=filesize($path);
        $out['age_hours']=(int)floor(max(0,time()-filemtime($path))/3600);
        if ($out['bytes']>50*1024*1024) { $out['status']='Файл больше лимита диагностики 50 МБ'; return $out; }
        $old=libxml_use_internal_errors(true);
        try {
            $doc=new \DOMDocument();
            $xml=file_get_contents($path);
            if (stripos($xml,'<!DOCTYPE')!==false || !$doc->loadXML($xml,LIBXML_NONET) || $doc->documentElement->tagName!=='yml_catalog') {
                $out['status']='Ошибка структуры XML'; return $out;
            }
            $out['offers']=(new \DOMXPath($doc))->query('/yml_catalog/shop/offers/offer')->length;
            $out['status']='XML читается';
            $out['checks']=CatalogFeedAudit::inspect($doc,dirname($directory));
            $out['generated']=$doc->documentElement->getAttribute('date');
            return $out;
        } finally { libxml_clear_errors(); libxml_use_internal_errors($old); }
    }

    public static function snapshot(string $publicRoot): array
    {
        $feeds=[];
        foreach (\R::getAll("SELECT id,name,url_params,url_download,date_update FROM cron ORDER BY id") as $task) {
            $name=(string)$task['url_download'];
            if (!str_contains(strtolower((string)$task['url_params']),'yml')) continue;
            if (!preg_match('/\.xml$/i',$name)) continue;
            $feeds[]=array_merge($task,self::fileState($publicRoot.'/cron',$name));
        }
        return $feeds;
    }
}
