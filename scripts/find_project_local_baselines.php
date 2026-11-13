<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$out=$root.'/outputs/project-publication';
$audit=json_decode(file_get_contents($out.'/server-audit-normalized/server-audit.json'),true);
$wanted=[];foreach($audit['files'] as $row)if($row['state']==='different')$wanted[$row['normalized_sha256']??$row['sha256']][]=$row['path'];
$chain=json_decode(file_get_contents($root.'/outputs/chain-filter-repair/actions-reports/preview.json'),true);
foreach($chain['baseline_sha256'] as $path=>$hash)if($hash)$wanted[$hash][]='chain-before/'.$path;
$found=[];
$consider=static function(string $text,string $source)use(&$found,$wanted,$out){
    $hash=hash('sha256',str_replace("\r\n","\n",$text));$raw=hash('sha256',$text);$targets=array_unique(array_merge($wanted[$hash]??[],$wanted[$raw]??[]));
    foreach($targets as $path){if(isset($found[$path]))continue;$dest=$out.'/live-baselines/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$text);$found[$path]=['source'=>$source,'normalized_sha256'=>$hash];}
};
$walkJson=static function($data,string $source,int $depth=0)use(&$walkJson,$consider){
    if(!is_array($data)||$depth>8)return;
    if(isset($data['content'])&&is_string($data['content'])){$decoded=base64_decode($data['content'],true);if($decoded!==false)$consider($decoded,$source);}
    foreach($data as $key=>$value)if(is_array($value))$walkJson($value,$source.'#'.$key,$depth+1);
};
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/outputs',FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){if(!$file->isFile()||$file->getSize()>15000000)continue;$path=str_replace('\\','/',$file->getPathname());if(strpos($path,$out.'/')===0)continue;$ext=strtolower(pathinfo($path,PATHINFO_EXTENSION));if(!in_array($ext,['php','js','css','json'],true)&&basename($path)!=='.htaccess')continue;$text=file_get_contents($path);$consider($text,$path);if($ext==='json'){$data=json_decode(ltrim($text,"\xef\xbb\xbf"),true);if(is_array($data))$walkJson($data,$path);}}
$missing=[];foreach($audit['files'] as $row)if($row['state']==='different'&&!isset($found[$row['path']]))$missing[]=$row['path'];
file_put_contents($out.'/local-baseline-map.json',json_encode(['found'=>$found,'missing'=>$missing],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode(['found'=>count($found),'missing'=>$missing],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
