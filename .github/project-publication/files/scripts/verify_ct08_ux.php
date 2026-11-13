<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';require $root.'/config/init.php';require CONF.'/db_bootstrap.php';
$prior=json_decode(file_get_contents('/home/s/shinaspec/ct08-settings-20261112/current-manifest.json'),true,512,JSON_THROW_ON_ERROR);
$latest=json_decode(file_get_contents(__DIR__.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);$all=array_replace($prior,$latest);
foreach($all as$path=>$hash)if(hash_file('sha256',$root.'/'.$path)!==$hash['after'])throw new RuntimeException('Hash mismatch '.$path);
$db=R::getPDO();$counts=[];foreach(['catalog_product_profile','catalog_review_issue','catalog_review_event','attribute_value_category_canonical','catalog_category_display']as$table)$counts[$table]=(int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
$result=['verified_at'=>date(DATE_ATOM),'files_verified'=>count($all),'counts'=>$counts,'database_writes'=>false];
file_put_contents(__DIR__.'/current-manifest.json',json_encode($all,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
file_put_contents(__DIR__.'/verification.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode($result,JSON_UNESCAPED_SLASHES)."\n";
