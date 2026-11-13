<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';$package=__DIR__;
$original=json_decode(file_get_contents($package.'/receipt.json'),true,512,JSON_THROW_ON_ERROR)['files'];
$final=json_decode(file_get_contents($package.'/ct08-current-manifest.json'),true,512,JSON_THROW_ON_ERROR);
$css='public/css/catalog-facets.css';$view='app/views/itscenter/Category/view.php';
foreach($original as$path=>$hash){
 $actual=hash_file('sha256',$root.'/'.$path);
 $allowed=[$hash['after'],$final[$path]['after']];
 if($path===$css){$firstFix=str_replace('.catalog-facet-layout{display:grid;','.catalog-facet-layout{clear:both;width:100%;display:grid;',file_get_contents($package.'/files/'.$css));$allowed[]=hash('sha256',$firstFix);}
 if(!in_array($actual,$allowed,true))throw new RuntimeException('Production drift '.$path);
}
$backup=$package.'/layout-backup';if(!is_dir($backup))mkdir($backup,0700);
foreach([$css,$view]as$path){
 if(hash_file('sha256',$package.'/layout-files/'.$path)!==$final[$path]['after'])throw new RuntimeException('Package mismatch');
 if(!copy($root.'/'.$path,$backup.'/'.basename($path)))throw new RuntimeException('Backup failed');
}
$written=[];
try{
 foreach([$css,$view]as$path){copy($package.'/layout-files/'.$path,$root.'/'.$path.'.ct08-new');if(!rename($root.'/'.$path.'.ct08-new',$root.'/'.$path))throw new RuntimeException('Write failed');$written[]=$path;}
 foreach($final as$path=>$hash)if(hash_file('sha256',$root.'/'.$path)!==$hash['after'])throw new RuntimeException('Final hash mismatch '.$path);
 file_put_contents($package.'/final-receipt.json',json_encode(['at'=>date(DATE_ATOM),'files'=>$final,'database_writes'=>false],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 echo "CT08 layout correction delivered; all 9 files verified\n";
}catch(Throwable$e){foreach($written as$path)copy($backup.'/'.basename($path),$root.'/'.$path);throw$e;}
