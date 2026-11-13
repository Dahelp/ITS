<?php
namespace app\services;
/** Pure dry-run only. No PDO, save calls or automatic confirmation. */
final class CatalogMigrationPlan
{
    public static function decide(array $legacy,bool $hasSavedProfile): array
    {
        $decision=['product_id'=>$legacy['product_id'],'sku'=>$legacy['sku'],'source_fingerprint'=>$legacy['source_fingerprint'],'decision'=>'hold','reasons'=>[]];
        if($hasSavedProfile){$decision['decision']='skip_saved';$decision['reasons'][]='Уже есть новый профиль: не перезаписывать';return $decision;}
        if(!in_array($legacy['proposed']['type']??'unclassified',['unclassified','tyre_pneumatic','tyre_solid','tyre_bandage','wheel_assembly','rim','rim_ring','tube','flap','seal','filter','chain'],true))$decision['reasons'][]='Специальные поля этого профиля ещё не проработаны; перенос отложен';
        if(($legacy['proposed']['type']??'unclassified')==='unclassified')$decision['reasons'][]='Сначала уточнить тип изделия';
        if($legacy['warnings']){foreach(array_slice($legacy['warnings'],0,10) as $warning)$decision['reasons'][]=$warning;}
        if($legacy['unmapped']){foreach(array_slice($legacy['unmapped'],0,10) as $r)$decision['reasons'][]=$r['code'].': '.mb_substr($r['raw'],0,200).' — '.$r['reason'];if(count($legacy['unmapped'])>10)$decision['reasons'][]='Есть дополнительные неразобранные значения';}
        try{$normal=CatalogProperties::normalize($legacy['proposed']);}catch(\InvalidArgumentException $e){$decision['reasons'][]=$e->getMessage();}
        $known=array_filter($legacy['proposed']['values'],static fn($v)=>($v['state']??'')==='known');
        if(!$known&&!$legacy['proposed']['sizes']&&!$legacy['proposed']['ratings'])$decision['reasons'][]='Нет значений для переноса';
        if(!$decision['reasons']){$decision['decision']='draft_candidate';$decision['proposed']=$legacy['proposed'];$decision['issues']=$normal['issues'];}
        return $decision;
    }
    public static function build(array $snapshot,array $savedIds): array
    {
        $saved=array_fill_keys(array_map('intval',$savedIds),true);$attrs=[];$filters=[];$options=array_column($snapshot['attribute_value'],null,'id');
        foreach($snapshot['product_attribute'] as $r)$attrs[(int)$r['product_id']][]=$r;
        foreach($snapshot['attribute_product'] as $r)if(isset($options[$r['attr_id']]))$filters[(int)$r['product_id']][]=array_merge($options[$r['attr_id']],['link_id'=>$r['id'],'attr_id'=>$r['attr_id']]);
        $items=[];$counts=['draft_candidate'=>0,'hold'=>0,'skip_saved'=>0];$brands=array_column($snapshot['brand']??[],null,'id');
        foreach($snapshot['products'] as $p){$id=(int)$p['id'];$p['brand_name']=$brands[$p['brand_id']??0]['name']??'';$source=CatalogMigrationSource::canonical($p,$attrs[$id]??[],$filters[$id]??[]);$legacy=CatalogMigrationSource::preview($source);$row=self::decide($legacy,isset($saved[$id]));$items[]=$row;$counts[$row['decision']]++;}
        $canary=[];$types=[];foreach($items as $row)if($row['decision']==='draft_candidate'&&!isset($types[$row['proposed']['type']])&&count($canary)<5){$canary[]=$row['product_id'];$types[$row['proposed']['type']]=true;}
        $byId=array_column($items,null,'product_id');$ordered=[];foreach($canary as $id)$ordered[]=$byId[$id];foreach($items as $row)if(!in_array($row['product_id'],$canary,true))$ordered[]=$row;
        return ['mode'=>'dry_run_only','canary_product_ids'=>$canary,'format_version'=>2,'rules_fingerprint'=>CatalogMigrationSource::rulesFingerprint(),'source_generated_at'=>$snapshot['generated_at'],'generated_at'=>date(DATE_ATOM),'counts'=>$counts,'source_product_fields'=>array_keys($snapshot['products'][0]??[]),'rules'=>['Never overwrite an existing new profile','Only lossless proposals without ambiguity','Drafts may still lack required values; no automatic publication or confirmation','Before applying, recheck source fingerprint and profile version in a transaction'],'items'=>$ordered];
    }
}
