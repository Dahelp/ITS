<?php
$root=dirname(__DIR__);$bundle=json_decode(file_get_contents($root.'/outputs/quality-live-baseline.json'),true,512,JSON_THROW_ON_ERROR);$stage=$root.'/outputs/quality-workflow-release';$manifest=[];
foreach(['app/services/CatalogQuality.php','app/views/itscenter/admin/CatalogProperties/quality.php']as$path){$source=base64_decode($bundle[$path]['content']);if(hash('sha256',$source)!==$bundle[$path]['sha256']||hash_file('sha256',$root.'/'.$path)!==$bundle[$path]['sha256'])throw new RuntimeException('Local/live drift '.$path);$s=$source;
if(str_contains($path,'services/')){
$s=str_replace('?array $saved=null): array','?array $saved=null,?string $publicRoot=null): array',$s);
$s=str_replace('$seo=[];','$seo=[];$seoFields=[];',$s);
$s=str_replace("if(trim((string)(\$product[\$field]??''))==='')\$seo[]=\$label.': поле пустое';","if(trim((string)(\$product[\$field]??''))===''){\$seo[]=\$label.': поле пустое';\$seoFields[]=\$field;}",$s);
$s=str_replace("if(\$type!=='service'&&(int)(\$product['brand_id']??0)===0)\$seo[]='Бренд: не выбран';",<<<'CODE'
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
CODE,$s);
$s=str_replace("'seo_gaps'=>\$seo,","'seo_gaps'=>\$seo,'seo_fields'=>array_values(array_unique(\$seoFields)),'alias'=>(string)(\$product['alias']??''),",$s);
$s=str_replace('fromSnapshot(array $snapshot,array $saved=[]): array','fromSnapshot(array $snapshot,array $saved=[],?string $publicRoot=null): array',$s);
$s=str_replace('self::assess($p,$legacy,$saved[$id]??null)','self::assess($p,$legacy,$saved[$id]??null,$publicRoot)',$s);
$s=str_replace("'seo_gaps'=>0,'statuses'=>[]","'seo_gaps'=>0,'seo_fields'=>[],'statuses'=>[]",$s);
$s=str_replace("\$out[\$r['source']]++;","\$out[\$r['source']]++;foreach(\$r['seo_fields']??[] as \$field)\$out['seo_fields'][\$field]=(\$out['seo_fields'][\$field]??0)+1;",$s);
$s=str_replace("if((\$criteria['seo']??'')==='missing'&&!\$r['seo_gaps'])return false;","if((\$criteria['seo']??'')==='missing'&&!\$r['seo_gaps'])return false;\n            if((\$criteria['seo']??'')!==''&&\$criteria['seo']!=='missing'&&!in_array(\$criteria['seo'],\$r['seo_fields']??[],true))return false;",$s);
$s=str_replace('quantity,hide,seo_h1,title,description,img FROM product','quantity,hide,seo_h1,title,description,img,unload_img,content,alias FROM product',$s);
$s=str_replace('self::fromSnapshot($snapshot,$saved);','self::fromSnapshot($snapshot,$saved,defined(\'WWW\')?WWW:null);',$s);
}else{
$s=str_replace("['missing'=>'Есть пустые поля']","['missing'=>'Любое замечание','seo_h1'=>'Пустой исходный H1','title'=>'Пустой исходный Title','description'=>'Пустой meta description','content'=>'Нет описания товара','img'=>'Не задано главное фото','photo_file'=>'Проблема с файлом фото','brand_id'=>'Не выбран бренд']",$s);
$s=str_replace('Файлы и качество фото здесь не проверяются.','Проверяются наличие и читаемость указанных файлов главного фото и фото выгрузки. Качество изображения, полнота описания и итоговые метатеги InSEO требуют отдельной оценки.',$s);
$s=str_replace('Есть пустые поля','Есть замечания',$s);
$s=str_replace('Старая карточка</a>','Редактировать карточку</a><?php if(!empty($r[\'alias\'])):?><br><a href="<?=PATH?>/product/<?=$escape(rawurlencode($r[\'alias\']))?>" target="_blank" rel="noopener">Открыть на сайте ↗</a><?php endif ?>',$s);
$needle='<p>Найдено: <strong>';
$summary=<<<'HTML'
<div class="card"><div class="card-body"><strong>Замечания по всему каталогу: <?=$report['summary']['seo_gaps']?> товаров</strong><div class="mt-2"><?php foreach(['seo_h1'=>'H1','title'=>'Title','description'=>'Meta description','content'=>'Описание','img'=>'Не задано фото','photo_file'=>'Файл фото','brand_id'=>'Бренд'] as $field=>$label):$count=$report['summary']['seo_fields'][$field]??0;?><a class="btn btn-sm btn-outline-secondary mr-2 mb-2" href="<?=ADMIN?>/catalog-properties/quality?<?=$escape(http_build_query(array_merge($criteria,['seo'=>$field,'page'=>1])))?>"><?=$escape($label)?>: <?=$count?></a><?php endforeach ?></div><small>Количество товаров с каждым замечанием; один товар может входить в несколько групп. Отбор сохраняет выбранные условия.</small></div></div>
HTML;
$s=str_replace($needle,$summary."\n".$needle,$s);
}
if($s===$source)throw new RuntimeException('No changes');$dest=$stage.'/files/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$s);file_put_contents($root.'/'.$path,$s);$manifest[$path]=['before'=>hash('sha256',$source),'after'=>hash('sha256',$s)];}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo "Prepared 2 files\n";
