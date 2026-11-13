<?php
namespace app\services;

/** Same confirmed facts for public cards and the industrial tyre feed. No writes. */
final class CatalogPublishedAttributes
{
    public static function load(\PDO $db,?int $productId=null): array
    {
        CatalogProperties::useDictionary($db);
        $own=!$db->inTransaction();
        try{
            if($own)$db->beginTransaction();
            $sql="SELECT p.product_id,p.version,t.code FROM catalog_product_profile p JOIN catalog_type t ON t.id=p.type_id WHERE (EXISTS(SELECT 1 FROM catalog_product_property v WHERE v.product_id=p.product_id AND v.value_state='known' AND v.verification='confirmed') OR EXISTS(SELECT 1 FROM catalog_product_size s WHERE s.product_id=p.product_id AND s.verification='confirmed') OR EXISTS(SELECT 1 FROM catalog_tire_service_rating r WHERE r.product_id=p.product_id AND r.verification='confirmed'))";
            $q=$db->prepare($sql.($productId!==null?' AND p.product_id=?':''));$q->execute($productId!==null?[$productId]:[]);$out=[];foreach($q->fetchAll(\PDO::FETCH_ASSOC) as $r)$out[(int)$r['product_id']]=['type'=>$r['code'],'version'=>(int)$r['version'],'values'=>[],'sizes'=>[],'ratings'=>[]];
            if($out){$ids=array_keys($out);$marks=implode(',',array_fill(0,count($ids),'?'));
                $q=$db->prepare("SELECT v.*,d.code,d.kind FROM catalog_product_property v JOIN catalog_property d ON d.id=v.property_id WHERE v.product_id IN ($marks) AND v.value_state='known' AND (v.verification='confirmed' OR d.code IN ('tire_size','tire_type'))");$q->execute($ids);$values=$q->fetchAll(\PDO::FETCH_ASSOC);
                $q=$db->prepare("SELECT o.value_id,d.code FROM catalog_product_property_option o JOIN catalog_property_option d ON d.id=o.option_id JOIN catalog_product_property v ON v.id=o.value_id WHERE v.product_id IN ($marks) AND v.value_state='known' AND (v.verification='confirmed' OR v.property_id=(SELECT id FROM catalog_property WHERE code='tire_type')) ORDER BY d.code");$q->execute($ids);$opts=[];foreach($q->fetchAll(\PDO::FETCH_ASSOC) as $r)$opts[(int)$r['value_id']][]=$r['code'];
                foreach($values as $r){$value=in_array($r['kind'],['option','multi_option'],true)?($opts[(int)$r['id']]??[]):($r['numeric_value']??$r['bool_value']??$r['text_value']??'');if($r['kind']==='integer'&&$r['numeric_value']!==null)$value=(string)(int)$r['numeric_value'];$out[(int)$r['product_id']]['values'][$r['code']]=['state'=>'known','verification'=>$r['verification'],'value'=>$value,'source_ref'=>$r['source_ref']];}
                $q=$db->prepare("SELECT product_id,normalized_size AS size,role,verification,source_ref,conditions_text AS conditions FROM catalog_product_size WHERE product_id IN ($marks) AND verification='confirmed' ORDER BY id");$q->execute($ids);foreach($q->fetchAll(\PDO::FETCH_ASSOC) as $r){$id=(int)$r['product_id'];unset($r['product_id']);$out[$id]['sizes'][]=$r;}
                $q=$db->prepare("SELECT product_id,raw_marking,load_single,load_dual,speed_symbol,operating_mode,pressure_kpa,verification,source_ref FROM catalog_tire_service_rating WHERE product_id IN ($marks) AND verification='confirmed' ORDER BY sequence_no");$q->execute($ids);foreach($q->fetchAll(\PDO::FETCH_ASSOC) as $r){$id=(int)$r['product_id'];unset($r['product_id']);$out[$id]['ratings'][]=$r;}
            }
            $sizeIndex=new CatalogSizeSearch($db);
            if($out&&$sizeIndex->installed()){
                $eligibility=[];foreach(CatalogSizeSearch::live($db)as$id=>$entry)foreach($entry['relations']as$r)if($r['role']==='equivalent')$eligibility[$id][$r['size']]=$r['eligible'];
                foreach($out as$id=>&$profile)foreach($profile['sizes']as&$size)if($size['role']==='equivalent')$size['anchor_valid']=$eligibility[$id][$size['size']]??false;unset($size,$profile);
            }
            if($own)$db->commit();return $out;
        }catch(\PDOException $e){if($own&&$db->inTransaction())$db->rollBack();if($e->getCode()==='42S02')return [];throw $e;}
        catch(\Throwable $e){if($own&&$db->inTransaction())$db->rollBack();throw $e;}
    }
    public static function unit(string $unit): string {return ['in'=>'дюйм','mm'=>'мм','kg'=>'кг','m3'=>'м³','kPa'=>'кПа','PSI'=>'PSI','kgf/cm2'=>'кгс/см²','km/h'=>'км/ч'][$unit]??$unit;}
    private static function display(array $def,$value): string
    {
        if(is_array($value))return implode(', ',array_map(static fn($v)=>$def['options'][$v],$value));
        if($def['kind']==='boolean')return (string)$value==='1'?'Да':'Нет';
        if($def['kind']==='decimal'&&str_contains((string)$value,'.'))return rtrim(rtrim((string)$value,'0'),'.');
        return (string)$value;
    }
    public static function project(array $legacy,?array $profile): array
    {
        $result=['rows'=>$legacy,'changes'=>[],'remove_names'=>[],'meta'=>[],'blocked'=>[]];
        if(!$profile||in_array($profile['type']??'unclassified',['unclassified','service'],true))return $result;
        $defs=CatalogProperties::definitions();$known=[];foreach($profile['values']??[] as $code=>$r)if(($r['state']??'')==='known'&&($r['verification']??'')==='confirmed')$known[$code]=$r;
        // Unconfirmed tyre type is context only: never emit it, but reject incompatible pressure.
        $context=$known;if(isset($profile['values']['tire_type'])&&!isset($context['tire_type']))$context['tire_type']=$profile['values']['tire_type'];
        try{$normal=CatalogProperties::normalize(['type'=>$profile['type'],'values'=>$context]);}catch(\InvalidArgumentException $e){$result['blocked'][]=$e->getMessage();return $result;}
        $remove=[];$add=[];$aliases=['rim_diameter_in'=>['Диаметр','Посадочный диаметр (дюйм)'],'section_width_in'=>['Ширина профиля'],'construction'=>['Конструкция шины'],'sealing'=>['Способ герметизации'],'service_ratings'=>['Индекс нагрузки','Индекс скорости','Индекс категории скорости']];
        foreach($known as $code=>$r){if(!isset($defs[$code])||in_array($defs[$code]['kind'],['external','ratings','size_relations'],true))continue;$def=$defs[$code];$value=self::display($def,$normal['values'][$code]['value']);$name=['tire_size'=>'Размер шины','construction'=>'Конструкция шины','sealing'=>'Способ герметизации'][$code]??$def['label'];$unit=self::unit($def['unit']??'');if($unit!=='')$name.=' ('.$unit.')';
            // Package and sealing share legacy field 16; a package confirmation must not erase sealing.
            $ids=$code==='package'?[]:($def['legacy_attribute_ids']??[]);foreach($ids as $id)$remove[(int)$id]=true;
            foreach($aliases[$code]??[] as $label)$result['remove_names'][$label]=true;
            $add[]=['attribute_id'=>$ids[0]??(-count($add)-1),'attribute_group_id'=>1,'attribute_name'=>$name,'attribute_text'=>$value,'catalog_published'=>true,'property_code'=>$code,'unit'=>$unit];
            if($code==='model')$result['meta']['model']=$value;
        }
        // A confirmed equivalent is scoped to this product and its unchanged primary size.
        $primary='';foreach($legacy as $row)if((int)($row['attribute_id']??0)===4)$primary=(string)$row['attribute_text'];
        $storedPrimary=$profile['values']['tire_size']['value']??'';$sizes=[];
        if(($profile['values']['tire_size']['verification']??'')==='confirmed'&&trim($profile['values']['tire_size']['source_ref']??'')!=='')$primary=$storedPrimary;
        foreach($profile['sizes']??[] as $r){if(($r['verification']??'')!=='confirmed'||trim($r['source_ref']??'')===''||trim($r['conditions']??'')!==''||(isset($r['anchor_valid'])&&!$r['anchor_valid']))continue;
            try{if($r['role']==='equivalent'&&($primary===''||$storedPrimary===''||CatalogSize::normalize($primary)!==CatalogSize::normalize($storedPrimary)))continue;$sizes[$r['role']][]=CatalogSize::normalize($r['size']);}catch(\InvalidArgumentException $e){continue;}}
        foreach($sizes as $role=>$list){$list=array_values(array_unique($list));$id=$role==='equivalent'?67:0;$safe=true;foreach($legacy as $r)if($id&&(int)($r['attribute_id']??0)===$id)foreach(preg_split('/[,;]+/u',$r['attribute_text']) as $raw){try{if(trim($raw)!==''&&!in_array(CatalogSize::normalize($raw),$list,true))$safe=false;}catch(\InvalidArgumentException $e){$safe=false;}}if(!$safe)continue;
            if($id)$remove[$id]=true;$add[]=['attribute_id'=>$id?:-count($add)-1,'attribute_group_id'=>1,'attribute_name'=>$role==='equivalent'?'Взаимозаменяемый размер':'Совместимый размер шины','attribute_text'=>implode(', ',$list),'catalog_published'=>true,'property_code'=>'sizes.'.$role,'unit'=>''];}
        // Linked ratings stay one record; never flatten different speed/load modes.
        foreach($profile['ratings']??[] as $i=>$r){if(($r['verification']??'')!=='confirmed')continue;try{$n=CatalogProperties::normalize(['type'=>$profile['type'],'values'=>$context,'ratings'=>[$r]])['ratings'][0]??null;}catch(\InvalidArgumentException $e){continue;}if(!$n)continue;$value=($n['raw_marking']?:'Индекс '.($n['load_single']??'').($n['load_dual']!==null?'/'.$n['load_dual']:'' )).' · '.(['manufacturer_other'=>'Условия изготовителя','single'=>'Одинарная установка','dual'=>'Сдвоенная установка','cyclic'=>'Циклический режим','static'=>'Статический режим'][$n['operating_mode']]).($n['speed_symbol']?' · '.$n['speed_symbol']:'').($n['pressure_kpa']!==null?' · '.self::display(['kind'=>'decimal'],$n['pressure_kpa']).' кПа':'');$add[]=['attribute_id'=>-count($add)-1,'attribute_group_id'=>1,'attribute_name'=>'Режим нагрузки '.($i+1),'attribute_text'=>$value,'catalog_published'=>true,'property_code'=>'ratings.'.$i,'unit'=>''];}
        $rows=[];foreach($legacy as $r){if(isset($remove[(int)($r['attribute_id']??0)])){$result['remove_names'][(string)$r['attribute_name']]=true;continue;}$rows[]=$r;}$result['rows']=array_merge($rows,$add);$result['changes']=$add;return $result;
    }
    public static function feed(array $attributes,array $projection): array
    {
        foreach($projection['remove_names'] as $name=>$_)unset($attributes[$name]);foreach($projection['changes'] as $r)$attributes[$r['attribute_name']]=$r['attribute_text'];return $attributes;
    }
}
