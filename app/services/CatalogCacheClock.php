<?php
namespace app\services;

/** Time-based changes must expire HTML even when no SQL mutation occurs. */
final class CatalogCacheClock
{
    public static function activeBanners(array $rows,int $now,callable $limit):array
    {
        $active=[];
        foreach($rows as$row){
            $start=($row['start_at']??null)===null?null:(strtotime((string)$row['start_at'])?:0);
            $end=($row['end_at']??null)===null?null:(strtotime((string)$row['end_at'])?:0);
            foreach([$start,$end===null?null:$end+1]as$boundary)if($boundary!==null&&$boundary>$now)$limit($boundary);
            if(($start===null||$start<=$now)&&($end===null||$end>=$now))$active[]=$row;
        }
        return $active;
    }
    public static function promotionBoundary():void
    {
        $cache=\ishop\Cache::instance();$next=$cache->get('catalog_next_boundary_v1');$now=time();
        if(!is_array($next)||(($next['at']??0)>0&&$next['at']<=$now)){
            $raw=\R::getCell("SELECT MIN(date_end) FROM actions WHERE hide='show' AND date_end > ?",[date('Y-m-d H:i:s',$now)]);
            $next=['at'=>$raw?(strtotime((string)$raw)?:0):0];$cache->set('catalog_next_boundary_v1',$next,60);
        }
        if(!empty($next['at']))HtmlPageCache::instance()->limitUntil((int)$next['at']);
    }
}
