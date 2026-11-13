<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';$package='/home/s/shinaspec/ct08-auto-20261112';
require $root.'/config/init.php';require CONF.'/db_bootstrap.php';
$manifest=file_get_contents($package.'/current-manifest.json');$all=json_decode($manifest,true,512,JSON_THROW_ON_ERROR);
foreach($all as$path=>$hash)if(hash_file('sha256',$root.'/'.$path)!==$hash['after'])throw new RuntimeException('Hash mismatch '.$path);
$counts=[];foreach(['catalog_product_profile','catalog_review_issue','catalog_review_event','attribute_value_category_canonical','catalog_category_display']as$table)$counts[$table]=(int)R::getCell('SELECT COUNT(*) FROM '.$table);
$report=['verified_at'=>date(DATE_ATOM),'files_verified'=>count($all),'counts'=>$counts,'database_writes'=>false,'initial_batch'=>12,'page_sizes'=>[24,48,96]];
file_put_contents($package.'/verification.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode(['manifest'=>$manifest,'receipt'=>file_get_contents($package.'/receipt.json'),'batch_receipt'=>file_get_contents($package.'/first-batch-receipt.json'),'verification'=>json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),'card'=>file_get_contents($root.'/app/widgets/product/product_tpl.php')],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
