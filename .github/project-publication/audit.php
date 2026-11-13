<?php
/** Private read-only checksum audit; source contents and configuration are never exported. */
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';
$inventory=json_decode(file_get_contents(__DIR__.'/inventory.json'),true,512,JSON_THROW_ON_ERROR);
$report=['checked_at'=>date(DATE_ATOM),'files'=>[]];
foreach($inventory as $item) {
    $path=$item['path'];
    if(!preg_match('~^(?:app/|public/|bin/|scripts/|vendor/ishop/core/|config/|composer\.(?:json|lock)$|router\.php$|\.htaccess$)~',$path)||strpos($path,'..')!==false||strpos($path,'\\')!==false)throw new RuntimeException('Unexpected path');
    if(in_array($path,['config/api.php','config/config_db.php','config/config_db_tamozh.php','config/params.php'],true))throw new RuntimeException('Protected configuration');
    $file=$root.'/'.$path;$exists=is_file($file);
    $row=['path'=>$path,'exists'=>$exists,'sha256'=>$exists?hash_file('sha256',$file):null,'bytes'=>$exists?filesize($file):0];
    $row['state']=$item['action']==='delete'?($exists?'delete':'already_absent'):(!$exists?'new':($row['sha256']===$item['sha256']?'same':'different'));
    $report['files'][]=$row;
}
file_put_contents(__DIR__.'/server-audit.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode(['files'=>count($report['files']),'states'=>array_count_values(array_column($report['files'],'state'))],JSON_PRETTY_PRINT).PHP_EOL;
