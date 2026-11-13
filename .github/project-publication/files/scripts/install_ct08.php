<?php
// Run from private release directory via SSH, never via HTTP.
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';$package=__DIR__;
$manifest=json_decode(file_get_contents($package.'/ct08-manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest as$path=>$hash){
 if(!preg_match('~^(app|public)/[a-zA-Z0-9_./-]+$~D',$path)||str_contains($path,'..'))throw new RuntimeException('Invalid path');
 if(!is_file($package.'/files/'.$path)||hash_file('sha256',$package.'/files/'.$path)!==$hash['after'])throw new RuntimeException('Package mismatch '.$path);
 if($hash['before']===null?file_exists($root.'/'.$path):(!is_file($root.'/'.$path)||hash_file('sha256',$root.'/'.$path)!==$hash['before']))throw new RuntimeException('Production drift '.$path);
 exec('/usr/local/bin/php8.2 -l '.escapeshellarg($package.'/files/'.$path).' 2>&1',$lint,$code);
 if(str_ends_with($path,'.php')&&$code!==0)throw new RuntimeException('Lint failed '.$path);
}
if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');
foreach($manifest as$path=>$hash){
 if($hash['before']!==null){$backup=$package.'/backup/'.$path;if(!is_dir(dirname($backup)))mkdir(dirname($backup),0700,true);if(!copy($root.'/'.$path,$backup))throw new RuntimeException('Backup failure');}
}
$written=[];
try{
 foreach($manifest as$path=>$hash){
  $dest=$root.'/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0755,true);
  if(!copy($package.'/files/'.$path,$dest.'.ct08-new')||!rename($dest.'.ct08-new',$dest))throw new RuntimeException('Write failure '.$path);
  $written[]=$path;
 }
 foreach($manifest as$path=>$hash)if(hash_file('sha256',$root.'/'.$path)!==$hash['after'])throw new RuntimeException('Verify failure');
 file_put_contents($package.'/receipt.json',json_encode(['applied_at'=>date(DATE_ATOM),'files'=>$manifest],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 echo "CT08 published; file backup and SHA256 verified; no database writes\n";
}catch(Throwable$e){foreach(array_reverse($written)as$path){if($manifest[$path]['before']===null)unlink($root.'/'.$path);else copy($package.'/backup/'.$path,$root.'/'.$path);}throw$e;}
