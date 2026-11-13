<?php
$root=dirname(__DIR__);$bundle=json_decode(file_get_contents($root.'/outputs/naming-live-baseline.json'),true,512,JSON_THROW_ON_ERROR);$stage=$root.'/outputs/naming-release/files';
$qualityView='app/views/itscenter/admin/CatalogProperties/quality.php';$v=file_get_contents($root.'/'.$qualityView);$qproof=json_decode(file_get_contents($root.'/outputs/quality-text-publication.json'),true);$hash=$qproof['receipt']['files'][$qualityView]['after'];if(hash('sha256',$v)!==$hash)throw new RuntimeException('Quality view local/live drift');$bundle[$qualityView]=['sha256'=>$hash,'content'=>base64_encode($v)];file_put_contents($root.'/outputs/naming-live-baseline.json',json_encode($bundle));
$patch=static function($p,$s){$replace=static function($needle,$replacement)use(&$s,$p){if(substr_count($s,$needle)!==1)throw new RuntimeException('Patch target mismatch '.$p.' '.substr($needle,0,50));$s=str_replace($needle,$replacement,$s);};
if(str_ends_with($p,'/ProductController.php')){
 $replace('        $description = $product->description ?: \\ishop\\App::seoreplace($inseo->description ?? \'\', $product->id);',"        \$title=\\app\\services\\CatalogNaming::apply((int)\$product->id,'title',(string)\$title);\n        \$description = \$product->description ?: \\ishop\\App::seoreplace(\$inseo->description ?? '', \$product->id);");
}elseif($p==='app/views/itscenter/Product/view.php'){
 $replace("echo h(\\ishop\\App::seoreplace(\$seoH1, \$product->id));","echo h(\\app\\services\\CatalogNaming::apply((int)\$product->id,'h1',(string)\\ishop\\App::seoreplace(\$seoH1,\$product->id)));");
 $replace("echo h(\\ishop\\App::seoreplace(\$inseo->name, \$product->id));","echo h(\\app\\services\\CatalogNaming::apply((int)\$product->id,'h1',(string)\\ishop\\App::seoreplace(\$inseo->name,\$product->id)));");
 $replace('echo h($product->name);',"echo h(\\app\\services\\CatalogNaming::apply((int)\$product->id,'h1',(string)\$product->name));");
}elseif(str_ends_with($p,'/product_tpl.php')){
 $replace('// CURRENCY',<<<'CODE'
+// Naming is batched when the normal widget context is available.
+\app\services\CatalogNaming::prime(array_map(static fn($id)=>['id'=>(int)$id],array_keys($context['seoNameByProductId']??[$pid=>''])));
+$seoName=\app\services\CatalogNaming::apply($pid,'catalog',(string)$seoName);
+
+// CURRENCY
CODE);
 $s=preg_replace('/^\+/m','',$s);
}elseif(str_ends_with($p,'/SpecialTyreFeedService.php')){
 $replace('        return $this->render($categories, $offers, $params, CatalogPublishedAttributes::load(\\R::getPDO()));',"        foreach(\$offers as&\$offer)\$offer['name']=CatalogNaming::apply((int)\$offer['id'],'feed',(string)\$offer['name']);unset(\$offer);\n        return \$this->render(\$categories, \$offers, \$params, CatalogPublishedAttributes::load(\\R::getPDO()));");
}elseif(str_ends_with($p,'/CatalogSeoAudit.php')){
 $replace('array $attributes): array','array $attributes,?array $naming=null): array');
 $needle="        return ['fields'=>\$fields";$pos=strpos($s,$needle);if($pos===false)throw new RuntimeException('Audit return missing');$code=<<<'CODE'
        if($naming!==null){
            foreach(['h1','title']as$field){$r=CatalogNaming::resolve($field,$fields[$field]['value'],$naming['context'],$naming['state']);$fields[$field]['value']=$r['value'];$fields[$field]['naming_source']=$r['source'];$fields[$field]['length']=mb_strlen($r['value']);$fields[$field]['empty']=trim($r['value'])==='';}
            $issues=count($templates)>1?['seo_template_conflict']:[];
            foreach($fields as$field=>$r){if($r['empty'])$issues[]='effective_'.$field;if(in_array($field,['h1','title','description'],true)&&preg_match('/\{[\p{L}\p{N}_-]+\}/u',$r['value']))$issues[]='seo_placeholder';if(preg_match('/^(?:test|тест|lorem ipsum|без названия|new product)[.! ]*$/iu',$r['value']))$issues[]='seo_stub';}
        }
CODE;
 $s=substr($s,0,$pos).$code."\n".substr($s,$pos);
}elseif(str_ends_with($p,'/CatalogQuality.php')){
 $replace("\$row['effective_seo']=CatalogSeoAudit::assess(\$p,\$templates[(int)\$p['category_id']]??[],\$seoAttrs[\$id]??[]);",<<<'CODE'
$naming=null;if(!empty($snapshot['naming']['rules'])||!empty($snapshot['naming']['overrides']))$naming=['state'=>$snapshot['naming'],'context'=>CatalogNaming::context($p,$saved[$id]??[],(string)($p['naming_brand']??''))];
                $row['effective_seo']=CatalogSeoAudit::assess($p,$templates[(int)$p['category_id']]??[],$seoAttrs[$id]??[],$naming);
CODE);
 $replace('content,alias FROM product ORDER BY id','content,alias,(SELECT name FROM brand WHERE brand.id=product.brand_id) AS naming_brand FROM product ORDER BY id');
 $replace("            \$snapshot['categories']=\$db->query", "            \$snapshot['naming']=(new CatalogNaming(\$db))->snapshot();\n            \$snapshot['categories']=\$db->query");
}elseif($p==='app/views/itscenter/admin/CatalogProperties/index.php'){
 $replace('<section class="content">','<section class="content"><p><a class="btn btn-outline-primary" href="<?=ADMIN?>/catalog-names">Названия каталога, H1, Title и YML</a></p>');
}elseif($p==='app/views/itscenter/admin/CatalogProperties/quality.php'){
 $replace("\$escape(['product'=>'Карточка','inseo'=>'InSEO','name'=>'Название товара'][\$value['source']])","\$escape(\$value['naming_source']??(['product'=>'Карточка','inseo'=>'InSEO','name'=>'Название товара'][\$value['source']]))");
}
return$s;};
foreach($bundle as$p=>$r){$source=base64_decode($r['content']);if(hash('sha256',$source)!==$r['sha256'])throw new RuntimeException('Baseline mismatch');$dest=$stage.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$patch($p,$source));$local=file_get_contents($root.'/'.$p);file_put_contents($root.'/'.$p,$patch($p,$local));}echo 'Naming channels connected in '.count($bundle)." existing files\n";
