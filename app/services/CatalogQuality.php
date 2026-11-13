<?php
namespace app\services;

/** Read-only quality queue. Legacy suggestions are never labelled as saved values. */
final class CatalogQuality
{
    public static function assess(array $product,array $legacy,?array $saved=null,?string $publicRoot=null): array
    {
        $data=$saved??$legacy['proposed'];$type=$data['type']??'unclassified';$issues=[];$required=0;$filled=0;
        $add=static function(string $kind,string $field,string $message)use(&$issues):void{$issues[]=['kind'=>$kind,'field'=>$field,'message'=>$message];};
        if($type==='unclassified')$add('classification','type','Определить тип изделия: правила обязательности пока не определены');
        try{CatalogProperties::normalize($data);}catch(\InvalidArgumentException $e){$add('invalid','profile',$e->getMessage());}
        foreach(CatalogProperties::definitions() as $code=>$def){
            if(in_array($def['kind'],['external','ratings','size_relations'],true))continue;
            $row=$data['values'][$code]??[];$state=$row['state']??'unknown';$verification=$row['verification']??'candidate';
            if(CatalogProperties::applicability($code,$type)==='required'){
                $required++;if($state==='known')$filled++;else $add('required',$code,$def['label'].': заполнить обязательное свойство');
            }
            if($verification==='conflict')$add('conflict',$code,$def['label'].': противоречие, нужна сверка источников');
            elseif($state==='known'&&$verification!=='confirmed')$add('confirmation',$code,$def['label'].': проверить источник');
        }
        foreach(['sizes'=>'Связь размера','ratings'=>'Режим нагрузки'] as $group=>$label)foreach($data[$group]??[] as $i=>$row){
            if(($row['verification']??'candidate')==='conflict')$add('conflict',$group.'.'.$i,$label.': противоречие');
            elseif(($row['verification']??'candidate')!=='confirmed')$add('confirmation',$group.'.'.$i,$label.': подтвердить применимость и условия');
        }
        if($saved===null){
            foreach($legacy['warnings']??[] as $w)$add('legacy_review','legacy',$w);
            foreach($legacy['unmapped']??[] as $row)$add('unmapped',$row['code'],'Разобрать старое значение: '.$row['raw'].' — '.$row['reason']);
        }
        $seo=[];$seoFields=[];
        foreach(['seo_h1'=>'H1','title'=>'Title','description'=>'Meta description','img'=>'Главное фото'] as $field=>$label)
            if(trim((string)($product[$field]??''))===''){$seo[]=$label.': поле пустое';$seoFields[]=$field;}
        if($type!=='service'&&(int)($product['brand_id']??0)===0){$seo[]='Бренд: не выбран';$seoFields[]='brand_id';}
        if(array_key_exists('content',$product)&&trim(html_entity_decode(strip_tags((string)$product['content']),ENT_QUOTES|ENT_HTML5,'UTF-8'))===''){$seo[]='Описание товара: отсутствует текст';$seoFields[]='content';}
        if($publicRoot!==null){
            foreach(['img'=>'baseimg','unload_img'=>'unload'] as $field=>$folder){
                $name=trim((string)($product[$field]??''));if($name==='')continue;
                $base=realpath($publicRoot.'/images/product/'.$folder);
                $file=$base&&$name===basename($name)&&!str_contains($name,'\\')?realpath($base.'/'.$name):false;
                if(!$file||dirname($file)!==$base||!is_file($file)||!is_readable($file)||!@getimagesize($file)){
                    $seo[]=($field==='img'?'Главное фото':'Фото для выгрузки').': файл отсутствует или не читается как изображение';$seoFields[]='photo_file';
                }
            }
        }
        $counts=array_count_values(array_column($issues,'kind'));
        $status=isset($counts['invalid'])||isset($counts['conflict'])?'conflict':(isset($counts['classification'])?'classification':(isset($counts['required'])?'missing':($issues?'review':'clear')));
        $priority=match($status){'conflict'=>500,'classification'=>400,'missing'=>300,'review'=>200,default=>0};
        if((float)($product['quantity']??0)>0)$priority+=50;if(($product['hide']??'')==='show')$priority+=10;
        return ['id'=>(int)$product['id'],'sku'=>(string)($product['sku']??''),'name'=>(string)$product['name'],'category_id'=>(int)$product['category_id'],'quantity'=>(float)($product['quantity']??0),'visible'=>($product['hide']??'')==='show','type'=>$type,'source'=>$saved===null?'legacy_proposal':'saved','version'=>$saved['version']??0,'status'=>$status,'priority'=>$priority,'required_total'=>$required,'required_filled'=>$filled,'issues'=>$issues,'seo_gaps'=>$seo,'seo_fields'=>array_values(array_unique($seoFields)),'alias'=>(string)($product['alias']??''),'legacy_warnings'=>$legacy['warnings']??[]];
    }

    public static function fromSnapshot(array $snapshot,array $saved=[],?string $publicRoot=null): array
    {
        $attrs=[];foreach($snapshot['product_attribute'] as $r)$attrs[(int)$r['product_id']][]=$r;
        $filterValues=array_column($snapshot['attribute_value'],null,'id');$filters=[];
        foreach($snapshot['attribute_product'] as $r)if(isset($filterValues[$r['attr_id']]))$filters[(int)$r['product_id']][]=$filterValues[$r['attr_id']];
        $items=[];$dictionary=[];$templates=[];$seoAttrs=[];
        foreach($snapshot['seo_templates']??[] as $t)$templates[(int)$t['category_id']][]=$t;
        foreach($snapshot['seo_attributes']??[] as $a)if(!isset($seoAttrs[(int)$a['product_id']][$a['url_params']]))$seoAttrs[(int)$a['product_id']][$a['url_params']]=$a['attribute_text'];
        foreach($snapshot['products'] as $p){
            $id=(int)$p['id'];$legacy=CatalogLegacyPreview::forProduct($p,$attrs[$id]??[],$filters[$id]??[]);
            $row=self::assess($p,$legacy,$saved[$id]??null,$publicRoot);
            if(array_key_exists('seo_templates',$snapshot)){
                $naming=null;if(!empty($snapshot['naming']['rules'])||!empty($snapshot['naming']['overrides']))$naming=['state'=>$snapshot['naming'],'context'=>CatalogNaming::context($p,$saved[$id]??[],(string)($p['naming_brand']??''))];
                $row['effective_seo']=CatalogSeoAudit::assess($p,$templates[(int)$p['category_id']]??[],$seoAttrs[$id]??[],$naming);
                foreach($row['effective_seo']['issues'] as $issue)$row['seo_fields'][]=$issue;
            }
            $items[]=$row;
            $seen=[];foreach($legacy['unmapped'] as $r){$key=$r['code']."\0".$r['raw'];if(isset($seen[$key]))continue;$seen[$key]=true;if(!isset($dictionary[$key]))$dictionary[$key]=['code'=>$r['code'],'raw'=>$r['raw'],'reason'=>$r['reason'],'products'=>0,'examples'=>[]];$dictionary[$key]['products']++;if(count($dictionary[$key]['examples'])<5)$dictionary[$key]['examples'][]=['id'=>$id,'sku'=>$p['sku']];}
        }
        $items=CatalogSeoAudit::duplicates($items);
        usort($items,static fn($a,$b)=>($b['priority']<=>$a['priority'])?:($a['id']<=>$b['id']));
        $dictionary=array_values($dictionary);usort($dictionary,static fn($a,$b)=>($b['products']<=>$a['products'])?:strcmp($a['code'].$a['raw'],$b['code'].$b['raw']));
        return ['mode'=>'read_only','generated_at'=>date(DATE_ATOM),'source_generated_at'=>$snapshot['generated_at']??null,'items'=>$items,'dictionary_review'=>$dictionary,'summary'=>self::summary($items)];
    }

    public static function summary(array $items): array
    {
        $out=['total'=>count($items),'saved'=>0,'legacy_proposal'=>0,'with_stock'=>0,'seo_gaps'=>0,'seo_fields'=>[],'effective_seo_gaps'=>0,'statuses'=>[]];
        foreach($items as $r){$out[$r['source']]++;if(!empty($r['effective_seo']['issues']))$out['effective_seo_gaps']++;foreach($r['seo_fields']??[] as $field)$out['seo_fields'][$field]=($out['seo_fields'][$field]??0)+1;if($r['quantity']>0)$out['with_stock']++;if($r['seo_gaps'])$out['seo_gaps']++;$out['statuses'][$r['status']]=($out['statuses'][$r['status']]??0)+1;}
        return $out;
    }

    public static function filter(array $items,array $criteria): array
    {
        return array_values(array_filter($items,static function($r)use($criteria){
            $q=trim((string)($criteria['q']??''));if($q!==''&&mb_stripos($r['sku'].' '.$r['name'],$q)===false)return false;
            if(($criteria['type']??'')!==''&&$r['type']!==$criteria['type'])return false;
            if(($criteria['status']??'')!==''&&$r['status']!==$criteria['status'])return false;
            if(($criteria['source']??'')!==''&&$r['source']!==$criteria['source'])return false;
            if(($criteria['category']??'')!==''&&$r['category_id']!==(int)$criteria['category'])return false;
            if(($criteria['stock']??'')==='positive'&&$r['quantity']<=0)return false;
            if(($criteria['stock']??'')==='zero'&&$r['quantity']>0)return false;
            if(($criteria['seo']??'')==='missing'&&!$r['seo_gaps'])return false;
            if(($criteria['seo']??'')==='effective'&&empty($r['effective_seo']['issues']))return false;
            if(($criteria['seo']??'')!==''&&$criteria['seo']!=='missing'&&$criteria['seo']!=='effective'&&!in_array($criteria['seo'],$r['seo_fields']??[],true))return false;
            return true;
        }));
    }

    public static function fromDatabase(\PDO $db): array
    {
        if($db->inTransaction())throw new \RuntimeException('Очередь запускается вне изменяющей транзакции.');
        $db->exec('SET TRANSACTION READ ONLY');$db->beginTransaction();
        try{
            $snapshot=['generated_at'=>date(DATE_ATOM),'products'=>$db->query('SELECT id,sku,name,model,category_id,brand_id,quantity,hide,seo_h1,title,description,img,unload_img,content,alias,(SELECT name FROM brand WHERE brand.id=product.brand_id) AS naming_brand FROM product ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC)];
            foreach(['product_attribute','attribute_value','attribute_product'] as $t)$snapshot[$t]=$db->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);
            $snapshot['seo_templates']=$db->query("SELECT id,category_id,name,title,description,content FROM plagins_inseo WHERE tip='product' AND hide='show' ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC);
            $snapshot['seo_attributes']=$db->query('SELECT pa.product_id,a.url_params,pa.attribute_text FROM product_attribute pa JOIN attribute a ON a.id=pa.attribute_id ORDER BY pa.id')->fetchAll(\PDO::FETCH_ASSOC);
            $snapshot['naming']=(new CatalogNaming($db))->snapshot();
            $snapshot['categories']=$db->query('SELECT id,name FROM category ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
            $saved=[];$service=new CatalogProperties($db);
            if($service->installed())$saved=$service->readAllProfiles();
            $db->rollBack();
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        $result=self::fromSnapshot($snapshot,$saved,defined('WWW')?WWW:null);$result['categories']=$snapshot['categories'];return $result;
    }
}
