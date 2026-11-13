<?php
$r=dirname(__DIR__);$stage=$r.'/outputs/stage9-release';$baseline=json_decode(trim(file_get_contents($r.'/outputs/stage9-live-baseline.json'),"\xEF\xBB\xBF \r\n\t"),true,512,JSON_THROW_ON_ERROR);
$baseline=array_merge($baseline,json_decode(trim(file_get_contents($r.'/outputs/stage9-extra-baseline.json'),"\xEF\xBB\xBF \r\n\t"),true,512,JSON_THROW_ON_ERROR));
$localAdapters=in_array('--local-adapters',$argv??[],true);if($localAdapters){$stage=$r.'/outputs/stage9-local-adapters';foreach(['app/services/InventorySyncService.php','public/cron/run_task_cli.php']as$p)$baseline[$p]=['sha256'=>hash_file('sha256',$r.'/'.$p),'content'=>base64_encode(file_get_contents($r.'/'.$p))];}
$existing=['app/services/InventorySyncService.php','public/cron/run_task_cli.php','bin/sync_inventory_api.php','app/services/YandexProductsCabinet.php','app/views/itscenter/admin/Plagins/yandex-tovars.php','app/services/SpecialTyreFeedService.php','app/controllers/admin/CatalogWorkflowController.php','app/views/itscenter/admin/partials/catalog-workflow-nav.php','app/views/itscenter/admin/CatalogWorkflow/index.php'];
if($localAdapters)$existing=['app/services/InventorySyncService.php','public/cron/run_task_cli.php'];
foreach($existing as$p){$src=base64_decode($baseline[$p]['content']);
 if(str_ends_with($p,'InventorySyncService.php')){
  $start=strpos($src,'            $needsUpdate = $apiQty !== $dbQty');$end=strpos($src,'            if ($needsUpdate)', $start);if($start===false||$end===false)throw new RuntimeException('Product comparison marker');
  $src=substr($src,0,$start)."            \$needsUpdate = self::productNeedsUpdate(\$product, \$data);\n".substr($src,$end);
  $src=str_replace("\$stats['modifications_updated'] = 0;","\$stats['modifications_updated'] = 0;\n        \$stats['modifications_api'] = 0;\n        \$stats['modifications_cache'] = 0;",$src);
  $marker="            \$data = \$result['data'];\n            \$apiQty = (int)\$data['rest'];";if(substr_count($src,$marker)!==1)throw new RuntimeException('Modification source marker');$src=str_replace($marker,"            \$stats[\$result['source'] === 'api' ? 'modifications_api' : 'modifications_cache']++;\n".$marker,$src);
  $marker="if (\$mode !== 'shadow' && \$stats['updated'] > 0) Cron::finalizeCronUpdate";if(substr_count($src,$marker)!==1)throw new RuntimeException('Completion marker');$src=str_replace($marker,"if (\$mode !== 'shadow' && (\$stats['api'] + \$stats['cache'] + \$stats['modifications_api'] + \$stats['modifications_cache']) > 0) Cron::finalizeCronUpdate",$src);
  $marker='    /** @return array<int, int> category ID => fixed quantity */';
  $method=<<<'CODE'
    /** Compare source inventory fields, before permanent storefront adjustments. */
    public static function productNeedsUpdate(array $product,array $data): bool
    {
        foreach(['rest'=>'inventory_quantity','reserve'=>'reserve'] as$key=>$field)if((int)($data[$key]??0)!==(int)($product[$field]??($field==='inventory_quantity'?($product['quantity']??0):0)))return true;
        if((int)($data['rest']??0)!==(int)($product['inventory_rest']??$product['rest']??0))return true;
        foreach(['price_rozn'=>'price','price_opt'=>'opt_price','price_spec'=>'spec_price']as$key=>$field)if(abs((float)($data[$key]??0)-(float)($product[$field]??0))>0.009)return true;
        if((int)($data['wait']??0)!==(int)($product['wait']??0))return true;
        $date=static function($value):string {$value=trim((string)$value);if(preg_match('/^\d{4}-\d{2}-\d{2}/D',$value))$value=substr($value,0,10);return$value==='0000-00-00'?'':$value;};
        if($date($data['wait_date']??null)!==$date($product['wait_date']??null))return true;
        return false;
    }

CODE;
  if(substr_count($src,$marker)!==1)throw new RuntimeException('Method marker');$src=str_replace($marker,$method.$marker,$src);
 }elseif(str_ends_with($p,'run_task_cli.php')){
  $marker="\$lockPath = sys_get_temp_dir() . '/its_cron_task_' . \$id . '.lock';";if(substr_count($src,$marker)!==1)throw new RuntimeException('CLI lock marker');$src=str_replace($marker,"\$lockPath = \\app\\services\\CatalogTaskStatus::lockPath(\$id, \$task);",$src);
  $marker='$startedAt = time();';$start=<<<'CODE'
$statusStore = null; $statusRun = null;
if (in_array($task, ['refresh-tovars-server','ymlfid-specshiny'], true)) {
    $statusStore = new \app\services\CatalogTaskStatus(ROOT . '/storage/catalog-task-status');
    $statusRun = $statusStore->start($id,$task,$task==='ymlfid-specshiny'?'feed':'live');
    register_shutdown_function(static function() use ($statusStore,$statusRun,$id): void {
        try { if (($statusStore->read($id)['state']??'')==='running') $statusStore->finish($id,$statusRun,'interrupted'); } catch (\Throwable $ignored) {}
    });
}
$startedAt = time();
CODE;
  if(substr_count($src,$marker)!==1)throw new RuntimeException('CLI receipt marker');$src=str_replace($marker,$start,$src);
  $marker='        cron_cli_log("DONE_API id={$id} stats={$json}");';if(substr_count($src,$marker)!==1)throw new RuntimeException('API done marker');$src=str_replace($marker,$marker."\n        \$noFresh=(\$stats['api']+\$stats['cache']+\$stats['modifications_api']+\$stats['modifications_cache'])===0 && (\$stats['seen']+\$stats['modifications_seen'])>0;\n        if (\$statusRun) \$statusStore->finish(\$id,\$statusRun,\$noFresh?'error':((\$stats['db_fallback']??0)>0?'partial':'success'),\$stats);\n        if(\$noFresh)exit(5);",$src);
  $marker='    cron_cli_log("DONE id={$id} task={$task}");';if(substr_count($src,$marker)!==1)throw new RuntimeException('Feed done marker');$src=str_replace($marker,$marker."\n    if (\$statusRun) \$statusStore->finish(\$id,\$statusRun,'success',is_array(\$result??null)?\$result:[]);",$src);
  $marker='} catch (Throwable $e) {';if(substr_count($src,$marker)!==1)throw new RuntimeException('CLI catch marker');$src=str_replace($marker,$marker."\n    if (\$statusRun) { try { \$statusStore->finish(\$id,\$statusRun,'error',is_array(\$stats??null)?\$stats:[]); } catch (\\Throwable \$ignored) {} }",$src);
 }elseif(str_ends_with($p,'sync_inventory_api.php')){
  $marker="\$lock = fopen(sys_get_temp_dir() . '/its_inventory_api_' . \$id . '.lock', 'c');";if(substr_count($src,$marker)!==1)throw new RuntimeException('Bin lock marker');$src=str_replace($marker,"\$lock = fopen(\\app\\services\\CatalogTaskStatus::lockPath(\$id,'refresh-tovars-server'), 'c');",$src);
  $marker='try { $stats =';if(substr_count($src,$marker)!==1)throw new RuntimeException('Bin start marker');$src=str_replace($marker,"\$statusStore=new \\app\\services\\CatalogTaskStatus(ROOT.'/storage/catalog-task-status');\$statusRun=\$statusStore->start(\$id,'refresh-tovars-server',\$mode);\nregister_shutdown_function(static function()use(\$statusStore,\$statusRun,\$id):void {try{if((\$statusStore->read(\$id)['state']??'')==='running')\$statusStore->finish(\$id,\$statusRun,'interrupted');}catch(\\Throwable \$ignored){}});\n".$marker,$src);
  $marker='echo json_encode($stats,';if(substr_count($src,$marker)!==1)throw new RuntimeException('Bin done marker');$src=str_replace($marker,"\$noFresh=(\$stats['api']+\$stats['cache']+\$stats['modifications_api']+\$stats['modifications_cache'])===0&&(\$stats['seen']+\$stats['modifications_seen'])>0; \$requireMissing=(\$args['require-api']??'0')==='1'&&(int)(\$stats['api']??0)===0; \$statusStore->finish(\$id,\$statusRun,\$noFresh?'error':(((\$stats['db_fallback']??0)>0||\$requireMissing)?'partial':'success'),\$stats); if(\$noFresh)exit(5); ".$marker,$src);
  $marker='catch (Throwable $e) {';$src=str_replace($marker,$marker." try{\$statusStore->finish(\$id,\$statusRun,'error',is_array(\$stats??null)?\$stats:[]);}catch(\\Throwable \$ignored){}",$src);
 }elseif(str_ends_with($p,'YandexProductsCabinet.php')){
  $marker="\$out['status']='XML читается';";if(substr_count($src,$marker)!==1)throw new RuntimeException('Feed checks marker');$src=str_replace($marker,$marker."\n            \$out['checks']=CatalogFeedAudit::inspect(\$doc,dirname(\$directory));",$src);
 }elseif(str_ends_with($p,'yandex-tovars.php')){
  $marker='<th>Размер</th>';$src=str_replace($marker,$marker.'<th>Диагностика товарного YML</th>',$src);
  $marker=' КБ</td></tr>';
  $addition=<<<'HTML'
 КБ</td><td><?php $checks=$f['checks']??['errors'=>[],'warnings'=>[],'examples'=>[]]; ?><?php if(!$checks['errors']&&!$checks['warnings']):?>Замечаний по выполненным проверкам нет<?php else:?><?php foreach(['errors'=>'Ошибки','warnings'=>'Предупреждения']as$kind=>$caption):if(!$checks[$kind])continue;?><details><summary><?= $e($caption)?>: <?=array_sum($checks[$kind])?></summary><ul><?php foreach($checks[$kind]as$key=>$count):?><li><?=$e(\app\services\CatalogFeedAudit::LABELS[$key]??$key)?>: <?=(int)$count?><?php if(!empty($checks['examples'][$key])):?><br><small>Примеры ID: <?=$e(implode(', ',$checks['examples'][$key]))?></small><?php endif ?></li><?php endforeach ?></ul></details><?php endforeach ?><?php endif ?></td></tr>
HTML;
  $addition=str_replace("if(!\$checks['errors']&&!\$checks['warnings']):", "if(!isset(\$f['checks'])):?>Проверка полей не выполнена<?php elseif(!\$checks['errors']&&!\$checks['warnings']):",$addition);
  if(substr_count($src,$marker)!==1)throw new RuntimeException('Feed diagnostics column marker');$src=str_replace($marker,$addition,$src);$src=str_replace('colspan="6"','colspan="7"',$src);
  $marker='<div class="card"><div class="card-header"><h2 class="card-title">Опубликованные выгрузки';
  $src=str_replace($marker,'<p><a href="<?=ADMIN?>/catalog-workflow/updates">Обновление каталога и контроль запусков</a>. Проверка фото относится к требованиям Яндекс Товаров; порог 450×450 — внутреннее правило спецшин. Суммы замечаний могут пересекаться по товарам. Проверка не подтверждает доступность фото внешнему роботу и приём фида площадкой.</p>'.$marker,$src);
 }elseif(str_ends_with($p,'SpecialTyreFeedService.php')){
  $src=str_replace('IMAGETYPE_GIF, ','',$src);$src=str_replace('JPEG/PNG/GIF/WebP','JPEG/PNG/WebP',$src);
  $src=str_replace('a.attribute_name, pa.attribute_text','a.attribute_name, a.url_params, pa.attribute_text',$src);
  $marker="        foreach(\$offers as&\$offer)\$offer['name']=CatalogNaming::apply((int)\$offer['id'],'feed',(string)\$offer['name']);unset(\$offer);";
  $descriptionLoop=<<<'CODE'
        $templates=[];foreach(\R::getAll("SELECT id,category_id,name,title,description,content FROM plagins_inseo WHERE tip='product' AND hide='show' ORDER BY id")as$template)$templates[(int)$template['category_id']][]=$template;
        $seoAttributes=[];foreach($params as$param)if(trim((string)($param['url_params']??''))!=='')$seoAttributes[(int)$param['product_id']][$param['url_params']]=$param['attribute_text'];
        foreach($offers as&$offer){
            $offer['name']=CatalogNaming::apply((int)$offer['id'],'feed',(string)$offer['name']);
            $seo=CatalogSeoAudit::assess($offer,$templates[(int)$offer['category_id']]??[],$seoAttributes[(int)$offer['id']]??[]);
            $content=$seo['fields']['content'];
            $offer['feed_description']=$seo['ambiguous']&&$content['source']==='inseo'?'':$content['value'];
        }unset($offer);
CODE;
  if(substr_count($src,$marker)!==1)throw new RuntimeException('Description source loop marker');$src=str_replace($marker,$descriptionLoop,$src);
  $marker="    public function generate(): array\n    {";if(!str_contains($src,$marker))$marker="    public function generate(): array\r\n    {";
  if(substr_count($src,$marker)!==1)throw new RuntimeException('Feed snapshot start marker');$src=str_replace($marker,$marker."\n        \$db=\\R::getPDO();\$ownsSnapshot=!\$db->inTransaction();\n        if(\$ownsSnapshot){\$db->exec('SET TRANSACTION READ ONLY');\$db->beginTransaction();}\n        try {",$src);
  $marker='        return $this->render($categories, $offers, $params, CatalogPublishedAttributes::load(\R::getPDO()));';
  if(substr_count($src,$marker)!==1)throw new RuntimeException('Feed snapshot end marker');$src=str_replace($marker,"        \$result=\$this->render(\$categories, \$offers, \$params, CatalogPublishedAttributes::load(\$db));\n        if(\$ownsSnapshot)\$db->commit();return\$result;\n        } catch(\\Throwable \$e){if(\$ownsSnapshot&&\$db->inTransaction())\$db->rollBack();throw\$e;}",$src);
  $marker="            \$add(\$node, 'name', \$offer['name']);";if(substr_count($src,$marker)!==1)throw new RuntimeException('Feed description marker');
  $src=str_replace($marker,$marker."\n            \$description=array_key_exists('feed_description',\$offer)?(string)\$offer['feed_description']:self::description((string)(\$offer['content']??''));\n            if(\$description!==''&&mb_strlen(\$description)<=3000)\$add(\$node,'description',\$description);",$src);
  $marker='    private function picture(array $offer): ?string';
  $method=<<<'CODE'
    /** Export only existing product text; do not invent or truncate descriptions. */
    public static function description(string $html): string
    {
        $html=preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is','',$html)??'';
        $html=preg_replace('~<(?:br\b[^>]*|/p|/div|/li|/h[1-6])>~i',' ',$html)??'';
        return trim(preg_replace('/[\s\x{00a0}]+/u',' ',html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8'))??'');
    }

CODE;
  if(substr_count($src,$marker)!==1)throw new RuntimeException('Description method marker');$src=str_replace($marker,$method.$marker,$src);
 }elseif(str_ends_with($p,'CatalogWorkflowController.php')){
  $marker='    public function landingsAction(): void';if(substr_count($src,$marker)!==1)throw new RuntimeException('Health route marker');
  $new=<<<'CODE'
    public function updatesAction(): void
    {
        $this->guard();$health=\app\services\CatalogUpdateHealth::snapshot(\R::getPDO(),ROOT);$this->setMeta('Обновление каталога и YML');$this->set(compact('health'));
    }

CODE;
  $src=str_replace($marker,$new.$marker,$src);
 }elseif(str_ends_with($p,'catalog-workflow-nav.php')){
  $marker="'/catalog-workflow/history'=>'История'";if(substr_count($src,$marker)!==1)throw new RuntimeException('Health navigation marker');$src=str_replace($marker,"'/catalog-workflow/updates'=>'Обновления и YML',".$marker,$src);
 }elseif(str_ends_with($p,'CatalogWorkflow/index.php')){
  $marker='<a href="<?=ADMIN?>/cron">Задания выгрузки</a>';
  if(substr_count($src,$marker)!==1)throw new RuntimeException('Health overview marker');$src=str_replace($marker,'<a href="<?=ADMIN?>/catalog-workflow/updates">Контроль обновлений и запусков</a> · '.$marker,$src);
 }
 $dest=$stage.'/files/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$src);
}
$new=['app/services/CatalogTaskStatus.php','app/services/CatalogFeedAudit.php','app/services/CatalogUpdateHealth.php','app/views/itscenter/admin/CatalogWorkflow/updates.php'];foreach($new as$p){$dest=$stage.'/files/'.$p;@mkdir(dirname($dest),0775,true);copy($r.'/'.$p,$dest);}
if($localAdapters){echo "Local inventory/CLI adapters prepared; production package unchanged\n";exit;}
$manifest=[];foreach(array_merge($new,$existing)as$p)$manifest[$p]=['before'=>$baseline[$p]['sha256']??null,'after'=>hash_file('sha256',$stage.'/files/'.$p)];file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$installer=str_replace(["'/ct08-manifest.json'",'(app|public)'],["'/manifest.json'",'(app|public|bin)'],file_get_contents(__DIR__.'/install_ct08.php'));file_put_contents($stage.'/install.php',$installer);copy(__DIR__.'/check_catalog_stage9.php',$stage.'/check.php');copy(__DIR__.'/check_catalog_stage9_manual.php',$stage.'/manual.php');
$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS))as$f){$p=str_replace('\\','/',substr($f->getPathname(),strlen($stage)+1));$files[$p]=base64_encode(file_get_contents($f->getPathname()));}
$runner=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);$package='/home/s/shinaspec/catalog-stage9-20261113';if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');$locks=[];
foreach(['/its_cron_task_36.lock','/its_inventory_api_36.lock','/its_cron_task_32.lock']as$name){$lock=fopen(sys_get_temp_dir().$name,'c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Inventory/feed task is running; retry after it completes');$locks[]=$lock;}
try{if(!is_dir($package))mkdir($package,0700,true);foreach(json_decode(base64_decode('__FILES__'),true)as$p=>$data){if(str_contains($p,'..'))throw new RuntimeException('Path');$dest=$package.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);file_put_contents($dest,base64_decode($data));}passthru('/usr/local/bin/php8.2 '.escapeshellarg($package.'/check.php').' before',$code);if($code)throw new RuntimeException('Preflight failed');require$package.'/install.php';passthru('/usr/local/bin/php8.2 '.escapeshellarg($package.'/check.php').' after',$code);if($code)throw new RuntimeException('Verification failed');}finally{foreach($locks as$lock){flock($lock,LOCK_UN);fclose($lock);}}
passthru('/usr/local/bin/php8.2 '.escapeshellarg($package.'/manual.php'),$code);if($code)throw new RuntimeException('Published, but manual verification needs follow-up');
CODE;
file_put_contents($r.'/outputs/stage9-runner.php',str_replace('__FILES__',base64_encode(json_encode($files)),$runner));echo 'Stage9 packaged: '.count($manifest)." files; no schedule changes\n";
