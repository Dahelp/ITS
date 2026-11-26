<?php
namespace app\services;

/** Preload the exact preferred source produced by the existing picture helper. */
final class ProductImagePreload
{
    public static function render(string $picture):string
    {
        $type='';$url='';
        if(preg_match('~<source\s+type="(image/(?:avif|webp))"\s+srcset="([^"]+)"~i',$picture,$match)){
            $type=$match[1];$url=preg_split('/[\s,]+/',html_entity_decode($match[2],ENT_QUOTES,'UTF-8'))[0];
        }elseif(preg_match('~<img\s+[^>]*?src="([^"]+)"~i',$picture,$match)){
            $url=html_entity_decode($match[1],ENT_QUOTES,'UTF-8');
        }
        if(!preg_match('~^(?:/(?!/)|https?://)~i',$url))return'';
        $escape=static fn($v)=>htmlspecialchars($v,ENT_QUOTES,'UTF-8');
        return '<link rel="preload" as="image" href="'.$escape($url).'"'.($type?' type="'.$escape($type).'"':'').' fetchpriority="high">';
    }
}
