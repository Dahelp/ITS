<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$out=$root.'/outputs/project-publication';if(!is_dir($out))mkdir($out,0775,true);
$git='git -c safe.directory=C:/xampp82/htdocs/its-center -c core.autocrlf=false -c core.safecrlf=false ';
$changed=explode("\0",(string)shell_exec($git.'diff --name-only -z origin/master --'));
$new=explode("\0",(string)shell_exec($git.'ls-files --others --exclude-standard -z'));
$paths=array_values(array_unique(array_filter(array_merge($changed,$new))));sort($paths);
$exclude=['config/api.php','config/config_db.php','config/config_db_tamozh.php','config/params.php'];
$inventory=[];$repoOnly=[];
foreach($paths as $path) {
    if(in_array($path,$exclude,true))continue;
    if(!preg_match('~^(?:app/|public/|bin/|scripts/|vendor/ishop/core/|config/|composer\.(?:json|lock)$|router\.php$|\.htaccess$)~',$path)){$repoOnly[]=$path;continue;}
    if(preg_match('~^(?:public/(?:uploads|storage|xml|xls|tmp|hot)/)~',$path))continue;
    $file=$root.'/'.$path;$exists=is_file($file);
    $text=$exists&&(in_array(strtolower(pathinfo($path,PATHINFO_EXTENSION)),['php','css','js','json','mjs'],true)||basename($path)==='.htaccess');
    $inventory[]=['path'=>$path,'action'=>$exists?'write':'delete','sha256'=>$exists?hash_file('sha256',$file):null,'normalized_sha256'=>$text?hash('sha256',str_replace("\r\n","\n",file_get_contents($file))):null,'bytes'=>$exists?filesize($file):0];
}
file_put_contents($out.'/inventory.json',json_encode($inventory,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
file_put_contents($out.'/repo-only.json',json_encode($repoOnly,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
$summary=['runtime_files'=>count($inventory),'write'=>count(array_filter($inventory,static function($r){return $r['action']==='write';})),'delete'=>count(array_filter($inventory,static function($r){return $r['action']==='delete';})),'total_bytes'=>array_sum(array_column($inventory,'bytes')),'repo_only'=>count($repoOnly)];
echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
