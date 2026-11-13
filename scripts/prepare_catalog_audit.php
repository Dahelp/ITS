<?php
$root=dirname(__DIR__);$expected=[];$sources=['outputs/catalog-workflow-published-manifest-20261112.json','outputs/catalog-publication-current-manifest-20261112.json','outputs/ct08-auto-publication/manifest.json','outputs/ct08-polish-release/manifest.json','outputs/quality-text-release/manifest.json'];foreach($sources as$source){$m=json_decode(file_get_contents($root.'/'.$source),true,512,JSON_THROW_ON_ERROR);if(isset($m['files']))foreach($m['files']as$f)$expected[$f['path']]=$f['sha256'];else foreach($m as$p=>$v)$expected[$p]=$v['after'];}file_put_contents($root.'/outputs/catalog-audit-expected.json',json_encode($expected,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$code=<<<'CODE'
<?php
$root='/home/s/shinaspec/its-center.ru/public_html';require$root.'/config/init.php';require CONF.'/db_bootstrap.php';$db=\R::getPDO();$db->exec('SET TRANSACTION READ ONLY');$db->beginTransaction();
try{
$out=['checked_at'=>date(DATE_ATOM),'mode'=>'read_only','files'=>[],'tables'=>[]];$expected=json_decode(base64_decode('__EXPECTED__'),true);
foreach($expected as$p=>$hash){$actual=is_file($root.'/'.$p)?hash_file('sha256',$root.'/'.$p):null;$out['files'][$p]=['expected'=>$hash,'actual'=>$actual,'match'=>$actual===$hash];}
$tables=['product','product_attribute','attribute_product','attribute_group','attribute_value','attribute_category','plagins_inseo','catalog_product_profile','catalog_product_property','catalog_product_size','catalog_tire_service_rating','catalog_property','catalog_property_option','catalog_property_revision','catalog_review_issue','catalog_review_event','catalog_category_display'];
foreach($tables as$t){$q=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$q->execute([$t]);$out['tables'][$t]=$q->fetchColumn()?(int)$db->query('SELECT COUNT(*) FROM '.$t)->fetchColumn():null;}
$out['profiles']=$db->query('SELECT t.code,COUNT(*) AS total FROM catalog_product_profile p JOIN catalog_type t ON t.id=p.type_id GROUP BY t.code')->fetchAll(PDO::FETCH_ASSOC);
$out['property_verification']=$db->query('SELECT verification,COUNT(*) AS total FROM catalog_product_property GROUP BY verification')->fetchAll(PDO::FETCH_ASSOC);
$out['size_verification']=$db->query('SELECT verification,role,COUNT(*) AS total FROM catalog_product_size GROUP BY verification,role')->fetchAll(PDO::FETCH_ASSOC);
$out['display_settings']=$db->query('SELECT category_id,columns_count,groups_json,version FROM catalog_category_display ORDER BY category_id')->fetchAll(PDO::FETCH_ASSOC);
$out['cron']=$db->query('SELECT id,url_params,url_download,date_update FROM cron WHERE id=32')->fetch(PDO::FETCH_ASSOC);
$defs=\app\services\CatalogProperties::definitions();$kinds=[];foreach($defs as$d)$kinds[$d['kind']]=($kinds[$d['kind']]??0)+1;$out['dictionary']=['definitions'=>count($defs),'kinds'=>$kinds,'definition_source'=>'app/data/catalog-properties-v1.json','editable_controller_actions'=>['index','edit','quality','reviews','dictionaries','migrations']];
$model=file_get_contents($root.'/app/models/admin/PlaginsYandexTovars.php');$out['yandex_api']=['hardcoded_oauth'=>(bool)preg_match('/OAuth\s+[a-zA-Z0-9_-]{12,}/',$model),'has_explicit_timeout'=>str_contains($model,'timeout'),'has_exception_handling'=>str_contains($model,'catch'),'uses_environment'=>str_contains($model,'getenv(')];
$view=file_get_contents($root.'/app/views/itscenter/admin/Plagins/yandex-tovars.php');$out['yandex_ui']=['empty_status_column'=>str_contains($view,'<th>Статус</th>'),'has_offer_preview'=>str_contains($view,'preview'),'has_catalog_link'=>str_contains($view,'catalog-properties')];
foreach(['app/services/CatalogFacets.php','app/controllers/SearchController.php']as$p){$s=file_get_contents($root.'/'.$p);$out['legacy_dependencies'][$p]=['normalized_properties'=>str_contains($s,'catalog_product_property'),'normalized_sizes'=>str_contains($s,'catalog_product_size'),'published_projection'=>str_contains($s,'CatalogPublishedAttributes'),'legacy_filter_tables'=>str_contains($s,'attribute_product')];}
$db->rollBack();$out['transaction_closed']=!$db->inTransaction();$feed=$root.'/public/cron/ymlfid_specshiny.xml';$doc=new DOMDocument();$out['feed']=['valid'=>$doc->load($feed,LIBXML_NONET),'date'=>null,'offers'=>0];if($out['feed']['valid']){$out['feed']['date']=$doc->documentElement->getAttribute('date');$out['feed']['offers']=(new DOMXPath($doc))->query('/yml_catalog/shop/offers/offer')->length;}
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
}catch(Throwable$e){if($db->inTransaction())$db->rollBack();throw$e;}
CODE;
file_put_contents($root.'/outputs/run-catalog-audit.php',str_replace('__EXPECTED__',base64_encode(json_encode($expected)),$code));echo 'Expected paths: '.count($expected).PHP_EOL;
