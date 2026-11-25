<?php
namespace app\services;
final class TrafficSource
{
    public const SEARCH='search', DIRECT='yandex_direct', DIRECT_VISIT='direct_visit';
    private const SESSION_KEY='traffic_source';
    public const COOKIE_NAME='its_traffic_source';
    private static ?array $captured = null;
    public static function capture(array $query,string $referer=''): string
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_array($stored) && self::validCode($stored['code'] ?? null)) return $stored['code'];
        self::$captured = self::fromCookie() ?? [
            'code'=>self::classify($query,$referer),
            'landing_url'=>mb_substr((string)($_SERVER['REQUEST_URI']??''),0,1000),
            'referer'=>mb_substr($referer,0,500),
            'captured_at'=>date('Y-m-d H:i:s'),
        ];
        self::$captured['label'] = self::label(self::$captured['code']);
        if (session_status() === PHP_SESSION_ACTIVE) $_SESSION[self::SESSION_KEY] = self::$captured;
        return self::$captured['code'];
    }
    public static function code(): string
    {
        $code=$_SESSION[self::SESSION_KEY]['code']??self::$captured['code']??(self::fromCookie()['code']??self::DIRECT_VISIT);
        return self::validCode($code)?$code:self::DIRECT_VISIT;
    }
    private static function validCode($code): bool
    {
        return is_string($code) && in_array($code,[self::SEARCH,self::DIRECT,self::DIRECT_VISIT],true);
    }
    private static function fromCookie(): ?array
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($raw) || strlen($raw) > 8000) return null;
        $data = json_decode($raw, true);
        // Analytics metadata only; never an authentication/authorization input.
        if (!is_array($data) || !self::validCode($data['code'] ?? null)) return null;
        $result = ['code'=>$data['code']];
        foreach (['landing_url'=>1000,'referer'=>500,'captured_at'=>40] as $key=>$limit) {
            $result[$key] = isset($data[$key]) && is_string($data[$key]) ? mb_substr($data[$key],0,$limit) : '';
        }
        return $result;
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
