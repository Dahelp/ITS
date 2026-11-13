<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/quality-seo-release';$paths=['app/services/CatalogQuality.php','app/views/itscenter/admin/CatalogProperties/quality.php','app/services/CatalogSeoAudit.php'];$manifest=[];
foreach($paths as$p){$s=file_get_contents($root.'/'.$p);$before=str_ends_with($p,'CatalogSeoAudit.php')?null:hash('sha256',$s);
if(str_ends_with($p,'CatalogQuality.php')){
$s=str_replace("$"."items=[];$"."dictionary=[];",<<<'CODE'
$items=[];$dictionary=[];$templates=[];$seoAttrs=[];
        foreach($snapshot['seo_templates']??[] as $t)$templates[(int)$t['category_id']][]=$t;
        foreach($snapshot['seo_attributes']??[] as $a)if(!isset($seoAttrs[(int)$a['product_id']][$a['url_params']]))$seoAttrs[(int)$a['product_id']][$a['url_params']]=$a['attribute_text'];
CODE,$s);
$s=str_replace('$items[]=self::assess($p,$legacy,$saved[$id]??null,$publicRoot);',<<<'CODE'
$row=self::assess($p,$legacy,$saved[$id]??null,$publicRoot);
            if(array_key_exists('seo_templates',$snapshot)){
                $row['effective_seo']=CatalogSeoAudit::assess($p,$templates[(int)$p['category_id']]??[],$seoAttrs[$id]??[]);
                foreach($row['effective_seo']['issues'] as $issue)$row['seo_fields'][]=$issue;
            }
            $items[]=$row;
CODE,$s);
$s=str_replace("'seo_fields'=>[],'statuses'=>[]","'seo_fields'=>[],'effective_seo_gaps'=>0,'statuses'=>[]",$s);
$s=str_replace("$"."out[$"."r['source']]++;","$"."out[$"."r['source']]++;if(!empty($"."r['effective_seo']['issues']))$"."out['effective_seo_gaps']++;",$s);
$s=str_replace("$"."snapshot['categories']=$"."db->query",<<<'CODE'
$snapshot['seo_templates']=$db->query("SELECT id,category_id,name,title,description,content FROM plagins_inseo WHERE tip='product' AND hide='show' ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC);
            $snapshot['seo_attributes']=$db->query('SELECT pa.product_id,a.url_params,pa.attribute_text FROM product_attribute pa JOIN attribute a ON a.id=pa.attribute_id ORDER BY pa.id')->fetchAll(\PDO::FETCH_ASSOC);
            $snapshot['categories']=$db->query
CODE,$s);
}else if(str_ends_with($p,'quality.php')){
$s=str_replace("'missing'=>'Любое замечание'","'effective_title'=>'Нет итогового Title','effective_description'=>'Нет итогового meta description','effective_h1'=>'Нет итогового H1','effective_content'=>'Нет итогового описания','seo_template_conflict'=>'Несколько активных шаблонов InSEO','missing'=>'Замечания исходных полей'",$s);
$needle='<div class="card"><div class="card-body"><strong>Замечания по всему каталогу:';
$block=<<<'HTML'
<div class="card"><div class="card-body"><strong>Проверка с учётом InSEO: <?=$report['summary']['effective_seo_gaps']??0?> товаров с замечаниями</strong><div class="mt-2"><?php foreach(['effective_h1'=>'Нет H1','effective_title'=>'Нет Title','effective_description'=>'Нет meta description','effective_content'=>'Нет описания','seo_template_conflict'=>'Несколько шаблонов'] as $field=>$label):?><a class="btn btn-sm btn-outline-danger mr-2 mb-2" href="<?=ADMIN?>/catalog-properties/quality?<?=$escape(http_build_query(array_merge($criteria,['seo'=>$field,'page'=>1])))?>"><?=$escape($label)?>: <?=$report['summary']['seo_fields'][$field]??0?></a><?php endforeach ?></div><small>Расчёт использует действующий обработчик подстановок сайта. При нескольких активных шаблонах результат неоднозначен — проверьте InSEO. Проверка не оценивает релевантность текста и индексацию.</small></div></div>
HTML;
$s=str_replace($needle,$block."\n".$needle,$s);
$s=str_replace('<th>Исходные поля карточки</th>','<th>Исходные поля карточки</th><th>Результат с учётом InSEO</th>',$s);
$needle='</td></tr><?php endforeach ?>';
$cell=<<<'HTML'
</td><td><?php if(isset($r['effective_seo'])):$seo=$r['effective_seo'];?><details><summary><?=$seo['issues']?'Есть замечания':'Поля сформированы'?></summary><?php if($seo['ambiguous']):?><p class="text-danger">Несколько активных шаблонов: показан первый по ID, результат требует сверки.</p><?php endif ?><?php foreach($seo['fields'] as $field=>$value):?><p><strong><?=$escape(['h1'=>'H1','title'=>'Title','description'=>'Meta description','content'=>'Описание'][$field])?></strong> · <?=$escape(['product'=>'Карточка','inseo'=>'InSEO','name'=>'Название товара'][$value['source']])?> · <?=$value['length']?> симв.<br><?=$escape($value['empty']?'Пусто':mb_substr($value['value'],0,350))?><?=$value['length']>350?'…':''?></p><?php endforeach ?><?php if($seo['template_id']):?><a href="<?=ADMIN?>/plagins/inseo-edit?id=<?=$seo['template_id']?>">Открыть шаблон InSEO</a><?php endif ?></details><?php else:?>Расчёт недоступен<?php endif ?></td></tr><?php endforeach ?>
HTML;
$s=str_replace($needle,$cell,$s);$s=str_replace('colspan="5"','colspan="6"',$s);
}
$dest=$stage.'/files/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$s);file_put_contents($root.'/'.$p,$s);$manifest[$p]=['before'=>$before,'after'=>hash('sha256',$s)];}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo "Prepared\n";
