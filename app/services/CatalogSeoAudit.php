<?php
namespace app\services;

/** Read-only preview using the same placeholder renderer as the public product page. */
final class CatalogSeoAudit extends \ishop\App
{
    public static function assess(array $product, array $templates, array $attributes,?array $naming=null): array
    {
        $template=$templates[0]??[];$fields=[];$issues=[];
        if(count($templates)>1)$issues[]='seo_template_conflict';
        foreach(['h1','title','description','content'] as $field){
            $key=$field==='h1'?'seo_h1':$field;$templateKey=$field==='h1'?'name':$field;
            $own=(string)($product[$key]??'');
            // H1 trims its override; metadata/content use PHP truthiness in production.
            $hasOwn=$field==='h1'?trim($own)!=='':(bool)$own;
            $source=$hasOwn?'product':'inseo';$raw=$hasOwn?($field==='h1'?trim($own):$own):(string)($template[$templateKey]??'');
            if($field==='h1'&&!$hasOwn&&empty($template['name'])){$source='name';$raw=(string)($product['name']??'');}
            $render=$field==='h1'?$source!=='name':!$hasOwn;
            $value=$render?parent::replaceSeoPlaceholders($raw,static fn($key)=>(string)($attributes[$key]??'')):$raw;
            $text=trim(preg_replace('/[\s\x{00a0}]+/u',' ',html_entity_decode(strip_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8'))??'');
            $fields[$field]=['value'=>$text,'source'=>$source,'length'=>mb_strlen($text),'empty'=>$text===''];
            if($text==='')$issues[]='effective_'.$field;
            if(in_array($field,['h1','title','description'],true)&&preg_match('/\{[\p{L}\p{N}_-]+\}/u',$text))$issues[]='seo_placeholder';
            if(preg_match('/^(?:test|тест|lorem ipsum|без названия|new product)[.! ]*$/iu',$text))$issues[]='seo_stub';
        }
        if($naming!==null){
            foreach(['h1','title']as$field){$r=CatalogNaming::resolve($field,$fields[$field]['value'],$naming['context'],$naming['state']);$fields[$field]['value']=$r['value'];$fields[$field]['naming_source']=$r['source'];$fields[$field]['length']=mb_strlen($r['value']);$fields[$field]['empty']=trim($r['value'])==='';}
            $issues=count($templates)>1?['seo_template_conflict']:[];
            foreach($fields as$field=>$r){if($r['empty'])$issues[]='effective_'.$field;if(in_array($field,['h1','title','description'],true)&&preg_match('/\{[\p{L}\p{N}_-]+\}/u',$r['value']))$issues[]='seo_placeholder';if(preg_match('/^(?:test|тест|lorem ipsum|без названия|new product)[.! ]*$/iu',$r['value']))$issues[]='seo_stub';}
        }
        return ['fields'=>$fields,'issues'=>array_values(array_unique($issues)),'template_id'=>(int)($template['id']??0),'ambiguous'=>count($templates)>1];
    }

    public static function duplicates(array $items): array
    {
        foreach(['title','description'] as $field){
            $groups=[];
            foreach($items as $i=>$row){
                if(empty($row['visible'])||empty($row['effective_seo'])||$row['effective_seo']['ambiguous'])continue;
                $text=$row['effective_seo']['fields'][$field]['value'];
                if($text!=='')$groups[mb_strtolower($text)][]=$i;
            }
            foreach($groups as $indices){
                if(count($indices)<2)continue;
                foreach($indices as $i){
                    $issue='duplicate_'.$field;
                    $items[$i]['effective_seo']['issues'][]=$issue;$items[$i]['seo_fields'][]=$issue;
                    $peers=[];foreach($indices as $j)if($i!==$j&&count($peers)<5)$peers[]=['id'=>$items[$j]['id'],'sku'=>$items[$j]['sku']];
                    $items[$i]['effective_seo']['duplicates'][$field]=['count'=>count($indices),'peers'=>$peers];
                }
            }
        }
        return $items;
    }
}
