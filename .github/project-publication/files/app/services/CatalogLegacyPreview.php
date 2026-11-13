<?php
namespace app\services;

/** Read-only, loss-aware suggestions. This class never imports or confirms legacy facts. */
final class CatalogLegacyPreview
{
    public static function forProduct(array $product,array $attributes,array $filters=[]): array
    {
        $by=[];foreach($attributes as $row)$by[(int)$row['attribute_id']][]=trim((string)$row['attribute_text']);
        $ambiguous=[];foreach($by as $id=>$rawValues)if(count(array_unique(array_filter($rawValues,static fn($v)=>$v!=='')))>1)$ambiguous[$id]=true;
        $first=static function(int $id)use($by,$ambiguous):string{if(isset($ambiguous[$id]))return '';foreach($by[$id]??[] as $value)if($value!=='')return $value;return '';};
        $name=(string)$product['name'];$category=(int)$product['category_id'];
        $type='unclassified';$rawType=$first(61);
        if(preg_match('/колесо.+(?:в сборе|диском)/iu',$name))$type='wheel_assembly';
        elseif(preg_match('/цельнолит/iu',$rawType))$type='tyre_solid';
        elseif(preg_match('/бандаж/iu',$rawType))$type='tyre_bandage';
        elseif(preg_match('/пневмат/iu',$rawType))$type='tyre_pneumatic';
        elseif(in_array($category,[26,27,28,29,30],true))$type='rim';
        elseif($category===31)$type='tube';elseif($category===32)$type='flap';elseif($category===33)$type='seal';elseif($category===41)$type='rim_ring';
        elseif(in_array($category,[10,11,12,13,14,15,16,17],true))$type='filter';
        elseif(in_array($category,[44,45],true))$type='chain';elseif(in_array($category,[38,39,40,42],true))$type='service';
        $nameType=null;$nameConflict=false;$attributeTypeConflict=false;$typeMarkers=[];
        if(in_array($category,[2,9,18,19,20,21,22,23,24,35,36],true))foreach([16,63] as $aid){
            $raw=$first($aid);
            if(preg_match('/^(?:шина\s+)?(пневматическ(?:ая|ие)|цельнолит(?:ая|ые)|бандажн(?:ая|ые))(?:\s+шина)?$/iu',$raw,$m)){
                $word=mb_strtolower($m[1],'UTF-8');$marker=str_starts_with($word,'пневмат')?'tyre_pneumatic':(str_starts_with($word,'цельнолит')?'tyre_solid':'tyre_bandage');$typeMarkers[$aid]=$marker;
                $declared=preg_match('/цельнолит/iu',$rawType)?'tyre_solid':(preg_match('/бандаж/iu',$rawType)?'tyre_bandage':(preg_match('/пневмат/iu',$rawType)?'tyre_pneumatic':null));
                if($declared!==null&&$declared!==$marker)$attributeTypeConflict=true;
                if($type==='unclassified')$type=$marker;elseif(in_array($type,['tyre_pneumatic','tyre_solid','tyre_bandage'],true)&&$type!==$marker)$attributeTypeConflict=true;
            }
        }
        if(in_array($category,[2,9,18,19,20,21,22,23,24,35,36],true)&&preg_match('/(?:шина|шины)\s+(пневматическ\p{L}*|цельнолит\p{L}*|бандаж\p{L}*)/iu',$name,$match)){
            $word=mb_strtolower($match[1],'UTF-8');$nameType=str_starts_with($word,'пневмат')?'tyre_pneumatic':(str_starts_with($word,'цельнолит')?'tyre_solid':'tyre_bandage');
            if($type==='unclassified')$type=$nameType;elseif(in_array($type,['tyre_pneumatic','tyre_solid','tyre_bandage'],true)&&$type!==$nameType)$nameConflict=true;
        }
        $values=[];$unmapped=[];$warnings=[];
        if($nameConflict)$warnings[]='Явный тип шины в названии противоречит характеристике: требуется сверка';
        if($attributeTypeConflict)$warnings[]='Явные обозначения типа шины в характеристиках противоречат друг другу: требуется сверка';
        $rimRaw=in_array(mb_strtolower($first(16),'UTF-8'),['диск сборный','диск сварной','сборный','сварной'],true);
        foreach($ambiguous as $id=>$_)$warnings[]='У атрибута '.$id.' несколько разных значений: '.implode(' / ',array_unique($by[$id])).'. Автоматический выбор отключён.';
        foreach(CatalogProperties::definitions()as$code=>$def){
            if(in_array($def['kind'],['external','ratings','size_relations'],true))continue;
            $ids=$def['legacy_attribute_ids']??[];
            if(($code==='overall_diameter_mm'&&$type==='filter')||(in_array($code,['filter_outer_diameter_mm','filter_legacy_diameter_38_mm'],true)&&in_array($type,['tyre_pneumatic','tyre_solid','tyre_bandage','wheel_assembly'],true)))$ids=[];
            if(($code==='rim_width_in'&&in_array($type,['tyre_pneumatic','tyre_solid','tyre_bandage'],true))||($code==='recommended_rim_width_in'&&!in_array($type,['tyre_pneumatic','tyre_solid','tyre_bandage'],true)))$ids=[];
            if(($code==='sealing'&&isset($typeMarkers[16]))||($code==='construction'&&isset($typeMarkers[63])))$ids=[];
            if($code==='sealing'&&($type==='rim'||($type==='wheel_assembly'&&$rimRaw)))$ids=[];
            if($code==='rim_construction'&&(!in_array($type,['rim','wheel_assembly'],true)||($type==='wheel_assembly'&&!$rimRaw)))$ids=[];
            // Different roles must not be flattened by sharing a legacy source ID.
            if(in_array($code,['package','inflation_pressure_kpa','star_rating'],true))$ids=[];
            $raw='';$aid=0;foreach($ids as$id)if($first($id)!==''){$raw=$first($id);$aid=$id;break;}
            if($raw!==''&&count($ids)>1){$seen=[];foreach($ids as$id)if($first($id)!==''){$v=$first($id);if($def['kind']==='decimal')$v=str_replace(',','.',self::sameUnitNumber($v,$def['unit']));$seen[]=$v;}if(count(array_unique($seen))>1){$warnings[]='Несколько исходных полей свойства '.$code.' содержат разные значения: требуется сверка';continue;}}
            if($code==='tire_type'&&$raw===''&&$typeMarkers){$aid=(int)array_key_first($typeMarkers);$raw=['tyre_pneumatic'=>'pneumatic','tyre_solid'=>'solid','tyre_bandage'=>'bandage'][$typeMarkers[$aid]];}
            if($code==='model'&&$raw==='')$raw=trim((string)($product['model']??''));
            if($code==='inflation_pressure_kpa'&&$first(18)!==''){$raw=$first(18);$aid=18;}
            if($code==='star_rating'&&preg_match('/^\*{1,3}$/D',$first(5)))$raw=['*'=>'one','**'=>'two','***'=>'three'][$first(5)];
            if($raw==='')continue;
            if($code==='ply_rating'&&preg_match('/^\*+$/D',$raw))continue;
            if($code==='tire_size'&&in_array($type,['tube','flap','chain','rim','rim_ring','seal'],true))continue;
            if(CatalogProperties::applicability($code,$type)==='not_applicable'){$unmapped[]=['code'=>$code,'raw'=>$raw,'reason'=>'Не применяется к предполагаемому профилю'];continue;}
            $candidate=$raw;
            if($def['kind']==='decimal'&&in_array($def['unit'],['in','mm'],true))$candidate=self::sameUnitNumber($raw,$def['unit']);
            if(in_array($def['kind'],['option','multi_option'],true)){
                $candidate=[];foreach($def['options']as$key=>$label)if(mb_strtolower($raw,'UTF-8')===mb_strtolower($label,'UTF-8')||$raw===$key)$candidate[]=$key;
                if(!$candidate){foreach($def['legacy_aliases']??[] as $old=>$target)if(mb_strtolower($raw,'UTF-8')===mb_strtolower($old,'UTF-8'))$candidate=[$target];}
                if(!$candidate&&$def['kind']==='multi_option'){
                    $parts=preg_split('/[,;]+/u',$raw);$multi=[];
                    foreach($parts as $part){$part=mb_strtolower(trim($part),'UTF-8');$found=null;
                        foreach($def['options'] as $key=>$label)if($part===mb_strtolower($label,'UTF-8')||$part===mb_strtolower($key,'UTF-8'))$found=$key;
                        foreach($def['legacy_aliases']??[] as $old=>$target)if($part===mb_strtolower($old,'UTF-8'))$found=$target;
                        if($found===null){$multi=[];break;}$multi[]=$found;
                    }
                    if($multi)$candidate=array_values(array_unique($multi));
                }
                if(!$candidate){$unmapped[]=['code'=>$code,'raw'=>$raw,'reason'=>'Нет точного соответствия справочнику; требуется разбор'];continue;}
            }
            try{
                $row=['state'=>'known','verification'=>'legacy_unverified','value'=>$candidate,'source_ref'=>'Исторические данные '.($product['sku']??$product['id']).($aid?' / attribute '.$aid:' / product.model').($candidate!==$raw?' / исходное: '.mb_substr($aid?$first($aid):$raw,0,200):'')];
                CatalogProperties::normalize(['type'=>$type,'values'=>[$code=>$row]]);
                $values[$code]=$row;
            }catch(\InvalidArgumentException $e){$unmapped[]=['code'=>$code,'raw'=>$raw,'reason'=>$e->getMessage()];}
        }
        if(!isset($values['tire_type'])&&$rawType===''&&$nameType===$type){$value=['tyre_pneumatic'=>'pneumatic','tyre_solid'=>'solid','tyre_bandage'=>'bandage'][$type];$values['tire_type']=['state'=>'known','verification'=>'legacy_unverified','value'=>[$value],'source_ref'=>'Явное обозначение в product.name: '.mb_substr($name,0,400)];}
        $sizes=[];
        $isAccessory=in_array($type,['tube','flap','chain','rim','rim_ring','seal'],true);
        $sizeInputs=$isAccessory?[4=>$first(4),67=>$first(67)]:[67=>$first(67)];
        if($type==='chain'&&$first(85)!==''&&$first(85)!=='Подбор по размеру шин заказчика')$sizeInputs[85]=$first(85);
        foreach($sizeInputs as$sizeAid=>$raw)foreach(preg_split('/[,;]+/u',$raw)as$part){if(trim($part)==='')continue;try{$size=CatalogSize::normalize($part);$primary=$values['tire_size']['value']??'';if($primary!==''&&$size===CatalogSize::normalize($primary))continue;if(!isset($sizes[$size]))$sizes[$size]=['size'=>$size,'role'=>$isAccessory?'compatible':'equivalent','verification'=>'legacy_unverified','source_ref'=>'Исторический атрибут '.$sizeAid.' товара '.($product['sku']??$product['id']),'conditions'=>''];}catch(\InvalidArgumentException$e){$unmapped[]=['code'=>'size_relation','raw'=>$part,'reason'=>$e->getMessage()];}}
        // Explicit user confirmation in the task, limited to this SKU and this pair.
        if(($product['sku']??'')==='IT000007251'&&($values['tire_size']['value']??'')==='12-16.5'&&isset($sizes['33x12-20'])){
            $sizes['33x12-20']['verification']='confirmed';
            $sizes['33x12-20']['source_ref']='Подтверждено владельцем в задаче 11.11.2026: IT000007251, 12-16.5 ↔ 33x12-20';
        }
        if($type==='chain'&&preg_match('/^(\d+)\/(\d+)$/D',$first(80),$caliber)){
            foreach([81=>1,82=>2] as $aid=>$part)if(preg_match('/^\d+$/D',$first($aid))&&(int)$first($aid)!==(int)$caliber[$part])$warnings[]='Калибр цепи в атрибутах 80 и '.$aid.' противоречит друг другу: требуется сверка';
        }
        $filterSizes=[];foreach($filters as$f)if((int)($f['attr_group_id']??0)===2)$filterSizes[]=(string)$f['value'];
        foreach($filterSizes as$raw){try{$s=CatalogSize::normalize($raw);$primary=$values['tire_size']['value']??'';if($primary!=='')$primary=CatalogSize::normalize($primary);if($s!==$primary&&!isset($sizes[$s]))$warnings[]='Размер фильтра '.$raw.' отличается от основного и не указан отдельной связью: нужна проверка.';}catch(\InvalidArgumentException$e){$warnings[]='Размер фильтра требует разбора: '.$raw;}}
        $ratings=[];
        if($first(70)!==''||$first(71)!==''){
            $load=$first(70);$speed=strtr($first(71),['А'=>'A','В'=>'B','М'=>'M']);
            $ratings[]=['raw_marking'=>trim($load.' '.$first(71)),'load_single'=>preg_match('/^\d{1,3}$/D',$load)?$load:null,'load_dual'=>null,'speed_symbol'=>preg_match('/^(?:A[1-8]|[B-Z])$/D',$speed)?$speed:null,'operating_mode'=>'manufacturer_other','pressure_kpa'=>null,'verification'=>'legacy_unverified','source_ref'=>'Исторические атрибуты 70/71: требуется проверить связанные условия'];
        }
        $input=['type'=>$type,'values'=>$values,'sizes'=>array_values($sizes),'ratings'=>$ratings];
        try{$normal=CatalogProperties::normalize($input);$issues=$normal['issues'];}
        catch(\InvalidArgumentException$e){$issues=[$e->getMessage()];}
        return ['product_id'=>(int)$product['id'],'sku'=>$product['sku']??'','name'=>$name,'proposed'=>$input,'issues'=>$issues,'warnings'=>$warnings,'unmapped'=>$unmapped,'source_fingerprint'=>hash('sha256',json_encode([$product,$attributes,$filters],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)),'mode'=>'dry_run_only'];
    }
    public static function sameUnitNumber(string $raw,string $unit): string
    {
        $value=trim($raw);
        $suffix=$unit==='in'?'(?:дюйм(?:а|ов)?|in(?:ch(?:es)?)?)':'(?:мм|mm)';
        if(preg_match('/^([0-9]+(?:[.,][0-9]+)?)\s*'.$suffix.'$/iu',$value,$m))return str_replace(',','.',$m[1]);
        if($unit==='in'&&preg_match('/^(\d{1,3})\s+(\d{1,2})\/(\d{1,2})$/D',$value,$m)){
            $den=(int)$m[3];$num=(int)$m[2];if($den>0&&$num<$den&&1000000%$den===0){$fraction=rtrim(str_pad((string)($num*intdiv(1000000,$den)),6,'0',STR_PAD_LEFT),'0');return (string)(int)$m[1].($fraction!==''?'.'.$fraction:'');}
        }
        return $raw;
    }
    public static function fromDatabase(\PDO $db,int $id): array
    {
        CatalogProperties::useDictionary($db);
        $q=$db->prepare('SELECT id,sku,name,model,category_id FROM product WHERE id=?');$q->execute([$id]);$p=$q->fetch(\PDO::FETCH_ASSOC);
        if(!$p)throw new \InvalidArgumentException('Товар не найден.');
        $q=$db->prepare('SELECT attribute_id,attribute_text FROM product_attribute WHERE product_id=? ORDER BY id');$q->execute([$id]);$attrs=$q->fetchAll(\PDO::FETCH_ASSOC);
        $q=$db->prepare('SELECT v.attr_group_id,v.value FROM attribute_product p JOIN attribute_value v ON v.id=p.attr_id WHERE p.product_id=?');$q->execute([$id]);
        return self::forProduct($p,$attrs,$q->fetchAll(\PDO::FETCH_ASSOC));
    }
}
