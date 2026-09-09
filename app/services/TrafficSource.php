<?php
namespace app\services;
final class TrafficSource
{
    public const SEARCH='search', DIRECT='yandex_direct', DIRECT_VISIT='direct_visit';
    private const SESSION_KEY='traffic_source';
    public static function capture(array $query,string $referer=''): string
    {
        if(session_status()!==PHP_SESSION_ACTIVE) session_start();
        if(!empty($_SESSION[self::SESSION_KEY]['code'])) return (string)$_SESSION[self::SESSION_KEY]['code'];
        $code=self::classify($query,$referer);
        $_SESSION[self::SESSION_KEY]=['code'=>$code,'label'=>self::label($code),'landing_url'=>mb_substr((string)($_SERVER['REQUEST_URI']??''),0,2000),'referer'=>mb_substr($referer,0,1000),'captured_at'=>date('Y-m-d H:i:s')];
        return $code;
    }
    public static function code(): string
    {
        $code=$_SESSION[self::SESSION_KEY]['code']??self::DIRECT_VISIT;
        return in_array($code,[self::SEARCH,self::DIRECT,self::DIRECT_VISIT],true)?$code:self::DIRECT_VISIT;
    }
    public static function label(?string $code=null): string
    {
        return [self::SEARCH=>'Поиск',self::DIRECT=>'Директ',self::DIRECT_VISIT=>'Прямой'][($code??self::code())]??'Прямой';
    }
    public static function emailHtml(): string { return '<p><b>Источник обращения:</b> '.self::label().'</p>'; }
    public static function classify(array $query,string $referer=''): string
    {
        if(trim((string)($query['yclid']??''))!=='') return self::DIRECT;
        $source=mb_strtolower(trim((string)($query['utm_source']??'')));
        $medium=mb_strtolower(trim((string)($query['utm_medium']??'')));
        $tag=mb_strtolower(trim((string)($query['utm_referer']??'')));
        if(in_array($source,['yandex_direct','yadirect','direct','geoadv_yabs'],true)||($source==='yandex'&&in_array($medium,['cpc','ppc','paid','paid_search','context','display','cpm'],true))||$tag==='geoadv_yabs') return self::DIRECT;
        if($medium==='organic') return self::SEARCH;
        $host=mb_strtolower((string)parse_url($referer,PHP_URL_HOST));
        foreach(['yandex.','google.','bing.com','mail.ru','rambler.ru'] as $searchHost) if(strpos($host,$searchHost)!==false) return self::SEARCH;
        return self::DIRECT_VISIT;
    }
}
