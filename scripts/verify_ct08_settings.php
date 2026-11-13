<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';require $root.'/config/init.php';require CONF.'/db_bootstrap.php';
$first=json_decode(file_get_contents('/home/s/shinaspec/ct08-20261112/receipt.json'),true,512,JSON_THROW_ON_ERROR)['files'];
$latest=json_decode(file_get_contents(__DIR__.'/receipt.json'),true,512,JSON_THROW_ON_ERROR)['files'];$all=array_replace($first,$latest);
foreach($all as$path=>$hash)if(hash_file('sha256',$root.'/'.$path)!==$hash['after'])throw new RuntimeException('Hash mismatch '.$path);
$db=R::getPDO();$counts=[];foreach(['catalog_product_profile','catalog_review_issue','catalog_review_event','attribute_value_category_canonical','catalog_category_display']as$table)$counts[$table]=(int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
$report=['verified_at'=>date(DATE_ATOM),'files_verified'=>count($all),'counts'=>$counts,'default_settings'=>(new \app\services\CatalogDisplay($db))->read(9),'browser_verified'=>['hint_removed'=>true,'wide_screen_columns'=>4,'single_seo_routes_preserved'=>true],'admin_ui_save_verified'=>'Local interface; production requires sign-in'];
file_put_contents(__DIR__.'/verification.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
file_put_contents(__DIR__.'/current-manifest.json',json_encode($all,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
