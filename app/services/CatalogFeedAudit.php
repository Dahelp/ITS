<?php
namespace app\services;

/** Structural checks and local photo checks; not remote moderation or network crawling. */
final class CatalogFeedAudit
{
    public const LABELS=[
        'duplicate_id'=>'Повторяющийся или пустой ID предложения',
        'category_reference'=>'Предложение ссылается на отсутствующую категорию',
        'category_parent'=>'У категории отсутствует родитель в XML',
        'price'=>'Нет положительной числовой цены',
        'name'=>'Не заполнено название',
        'name_length'=>'Название длиннее 150 символов',
        'description'=>'Не передано описание товара',
        'description_length'=>'Описание длиннее 3000 символов',
        'url'=>'Не заполнена корректная HTTP(S)-ссылка товара',
        'picture'=>'Не задана корректная HTTP(S)-ссылка на фото',
        'picture_file'=>'Локальный файл фото отсутствует или не читается',
        'picture_format'=>'Фото не JPEG/PNG/WebP',
        'picture_minimum'=>'Фото меньше 300×400 пикселей',
        'picture_quality'=>'Фото меньше внутреннего порога спецшин 450×450',
        'picture_size'=>'Фото больше 10 МБ',
        'http'=>'Ссылка товара использует HTTP',
    ];
    public static function inspect(\DOMDocument $doc,string $publicRoot): array
    {
        $xp=new \DOMXPath($doc);$errors=[];$warnings=[];$examples=[];$ids=[];$categories=[];$photos=[];
        $add=static function(string $kind,string $key,string $id)use(&$errors,&$warnings,&$examples):void {if($kind==='error')$errors[$key]=($errors[$key]??0)+1;else$warnings[$key]=($warnings[$key]??0)+1;if(count($examples[$key]??[])<3)$examples[$key][]=$id;};
        foreach($xp->query('/yml_catalog/shop/categories/category')as$c)$categories[$c->getAttribute('id')]=true;
        foreach($xp->query('/yml_catalog/shop/categories/category[@parentId]')as$c)if(!isset($categories[$c->getAttribute('parentId')]))$add('error','category_parent',$c->getAttribute('id'));
        $folder=realpath($publicRoot.'/images/product');
        foreach($xp->query('/yml_catalog/shop/offers/offer')as$o){
            $id=$o->getAttribute('id');if($id===''||isset($ids[$id]))$add('error','duplicate_id',$id);$ids[$id]=true;
            $value=static fn($tag)=>trim((string)$xp->evaluate('string('.$tag.')',$o));
            if(!isset($categories[$value('categoryId')]))$add('error','category_reference',$id);
            $price=$value('price');if(!is_numeric($price)||!is_finite((float)$price)||(float)$price<=0)$add('error','price',$id);
            if($value('name')===''&&!($o->getAttribute('type')==='vendor.model'&&$value('vendor')!==''&&$value('model')!==''))$add('error','name',$id);
            if(mb_strlen($value('name'))>150)$add('error','name_length',$id);
            if($value('description')==='')$add('error','description',$id);elseif(mb_strlen($value('description'))>3000)$add('error','description_length',$id);
            $url=$value('url');if(!filter_var($url,FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true))$add('error','url',$id);elseif(str_starts_with(strtolower($url),'http://'))$add('warning','http',$id);
            $picture=$value('picture');if(!filter_var($picture,FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($picture,PHP_URL_SCHEME)),['http','https'],true)){$add('error','picture',$id);continue;}
            if(!in_array(strtolower((string)parse_url($picture,PHP_URL_HOST)),['its-center.ru','www.its-center.ru'],true))continue;
            $path=rawurldecode((string)parse_url($picture,PHP_URL_PATH));if(!str_starts_with($path,'/images/product/'))continue;
            if(!isset($photos[$path])){
                $issues=[];$file=$folder?realpath($publicRoot.$path):false;
                if(!$file||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$folder).'/')||!is_file($file)||!is_readable($file)||!($size=@getimagesize($file)))$issues[]='picture_file';
                else{
                    if(!in_array($size[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP],true))$issues[]='picture_format';
                    if($size[0]<300||$size[1]<400)$issues[]='picture_minimum';
                    elseif($size[0]<450||$size[1]<450)$issues[]='picture_quality';
                    if(filesize($file)>10*1024*1024)$issues[]='picture_size';
                }$photos[$path]=$issues;
            }
            foreach($photos[$path]as$key)$add($key==='picture_quality'?'warning':'error',$key,$id);
        }
        return ['errors'=>$errors,'warnings'=>$warnings,'examples'=>$examples];
    }
}
