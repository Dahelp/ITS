<?php
namespace app\services;
/** Canonical source used by both snapshot planning and locked live rechecks. */
final class CatalogMigrationSource
{
    public static function canonical(array $p,array $attributes,array $filters): array
    {
        $product=['id'=>(int)$p['id'],'sku'=>(string)($p['sku']??''),'name'=>(string)$p['name'],'model'=>(string)($p['model']??''),'category_id'=>(int)$p['category_id'],'brand_id'=>(int)($p['brand_id']??0),'brand_name'=>(string)($p['brand_name']??'')];
        $attrs=[];foreach($attributes as $r)$attrs[]=['id'=>(int)$r['id'],'attribute_id'=>(int)$r['attribute_id'],'attribute_text'=>(string)$r['attribute_text']];
        $links=[];foreach($filters as $r)$links[]=['link_id'=>(int)$r['link_id'],'attr_id'=>(int)$r['attr_id'],'attr_group_id'=>(int)$r['attr_group_id'],'value'=>(string)$r['value']];
        usort($attrs,static fn($a,$b)=>$a['id']<=>$b['id']);usort($links,static fn($a,$b)=>$a['link_id']<=>$b['link_id']);
        return ['product'=>$product,'attributes'=>$attrs,'filters'=>$links];
    }
    public static function fingerprint(array $source): string
    {return hash('sha256',json_encode(self::canonical($source['product'],$source['attributes'],$source['filters']),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));}
    public static function rulesFingerprint(): string
    {
        $files=[__FILE__,__DIR__.'/CatalogProperties.php',__DIR__.'/CatalogSize.php',__DIR__.'/CatalogLegacyPreview.php',__DIR__.'/CatalogMigrationPlan.php',dirname(__DIR__).'/data/catalog-properties-v1.json'];
        return hash('sha256',implode('',array_map(static fn($f)=>hash_file('sha256',$f),$files)).json_encode(CatalogProperties::definitions(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    }
    public static function preview(array $source): array
    {
        $legacy=CatalogLegacyPreview::forProduct($source['product'],$source['attributes'],$source['filters']);
        $handled=[4,5,18,67,70,71];
        foreach(CatalogProperties::definitions() as $code=>$def){
            if(in_array($def['kind'],['external','ratings','size_relations'],true)||in_array($code,['package','inflation_pressure_kpa','star_rating'],true))continue;
            $handled=array_merge($handled,$def['legacy_attribute_ids']??[]);
        }
        foreach($source['attributes'] as $r){
            if(trim($r['attribute_text'])==='')continue;$id=(int)$r['attribute_id'];
            if($id===8){
                if(mb_strtolower(trim($r['attribute_text']),'UTF-8')!==mb_strtolower(trim($source['product']['brand_name']),'UTF-8'))$legacy['unmapped'][]=['code'=>'brand','raw'=>$r['attribute_text'],'reason'=>'Старый производитель не совпадает с выбранным брендом; не подменять автоматически'];
            }elseif(!in_array($id,$handled,true))$legacy['unmapped'][]=['code'=>'legacy_attribute_'.$id,'raw'=>$r['attribute_text'],'reason'=>'Старое поле ещё не охвачено обработчиком переноса'];
        }
        $legacy['source_fingerprint']=self::fingerprint($source);return $legacy;
    }
    public static function capture(\PDO $db,int $id): array
    {
        if(!$db->inTransaction())throw new \RuntimeException('Source capture requires transaction.');
        $q=$db->prepare('SELECT id,sku,name,model,category_id,brand_id FROM product WHERE id=? FOR UPDATE');$q->execute([$id]);$p=$q->fetch(\PDO::FETCH_ASSOC);if(!$p)throw new \RuntimeException('Исходный товар отсутствует.');
        $brand=$db->prepare('SELECT name FROM brand WHERE id=? LOCK IN SHARE MODE');$brand->execute([$p['brand_id']]);$p['brand_name']=(string)($brand->fetchColumn()?:'');
        $q=$db->prepare('SELECT id,attribute_id,attribute_text FROM product_attribute WHERE product_id=? ORDER BY id FOR UPDATE');$q->execute([$id]);$a=$q->fetchAll(\PDO::FETCH_ASSOC);
        $q=$db->prepare('SELECT id,attr_id FROM attribute_product WHERE product_id=? ORDER BY id FOR UPDATE');$q->execute([$id]);$links=$q->fetchAll(\PDO::FETCH_ASSOC);$filters=[];
        if($links){$ids=array_values(array_unique(array_column($links,'attr_id')));sort($ids,SORT_NUMERIC);$q=$db->prepare('SELECT id,attr_group_id,value FROM attribute_value WHERE id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY id LOCK IN SHARE MODE');$q->execute($ids);$values=array_column($q->fetchAll(\PDO::FETCH_ASSOC),null,'id');foreach($links as $link){if(!isset($values[$link['attr_id']]))throw new \RuntimeException('Потеряна связь фильтра.');$v=$values[$link['attr_id']];$filters[]=['link_id'=>$link['id'],'attr_id'=>$v['id'],'attr_group_id'=>$v['attr_group_id'],'value'=>$v['value']];}}
        return self::canonical($p,$a,$filters);
    }
}
