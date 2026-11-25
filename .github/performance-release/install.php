<?php
if(PHP_SAPI!=='cli')exit(1);
$target=$argv[1]??'';$package=__DIR__;
if($target!==''&&!str_starts_with($target,'/')&&!preg_match('~^[A-Za-z]:[\\\\/]~',$target))$target=dirname($package).'/'.$target;
$root=realpath($target);$mode=$argv[2]??'';
if(!$root||!in_array($mode,['audit','publish'],true))throw new RuntimeException('Invalid release parameters');
$manifest=json_decode(file_get_contents($package.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);$installed=[];$before=[];$audit=[];@mkdir($package.'/reports',0700,true);
foreach($manifest as$p=>$v){
 if(!preg_match('~^(app|public|bin|vendor/ishop/core)/[A-Za-z0-9_./-]+$~D',$p)||str_contains($p,'..')||str_contains($p,'\\')||str_starts_with($p,'public/tmp/'))throw new RuntimeException('Invalid release path');
 $file=$root.'/'.$p;if(is_link($file))throw new RuntimeException('Symlink target: '.$p);
 $hash=is_file($file)?hash_file('sha256',$file):null;$lf=is_file($file)?hash('sha256',str_replace("\r\n","\n",file_get_contents($file))):null;
 $compatible=$hash===$v['before']||($hash!==null&&$lf===($v['before_lf']??null));$audit[$p]=['actual'=>$hash,'expected'=>$v['before'],'compatible'=>$compatible];$before[$p]=['hash'=>$hash,'mode'=>is_file($file)?fileperms($file)&0777:0644];
 $src=$package.'/files/'.$p;if(!is_file($src)||hash_file('sha256',$src)!==$v['after'])throw new RuntimeException('Package hash: '.$p);
 if(str_ends_with($p,'.php')){exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($src).' 2>&1',$lint,$code);if($code)throw new RuntimeException('Lint: '.$p);}
}
file_put_contents($package.'/reports/audit.json',json_encode(['at'=>gmdate('c'),'mode'=>$mode,'files'=>$audit],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
if($mode==='audit'){echo 'Read-only audit: '.count($audit).' files, drift='.count(array_filter($audit,fn($v)=>!$v['compatible'])).PHP_EOL;exit;}
if(is_file($package.'/receipt.json'))throw new RuntimeException('Already applied');foreach($audit as$p=>$v)if(!$v['compatible'])throw new RuntimeException('Production drift: '.$p);
try{
 foreach($before as$p=>$v){if($v['hash']===null)continue;$backup=$package.'/backup/'.$p;if(!is_dir(dirname($backup)))mkdir(dirname($backup),0700,true);if(!copy($root.'/'.$p,$backup)||hash_file('sha256',$backup)!==$v['hash'])throw new RuntimeException('Backup: '.$p);}
 $paths=array_keys($manifest);usort($paths,static function($a,$b){$rank=static fn($p)=>$p==='public/index.php'?100:(str_starts_with($p,'public/js/')?0:(str_starts_with($p,'app/services/')?10:30));return $rank($a)<=>$rank($b);});
 foreach($paths as$p){$dest=$root.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0755,true);$tmp=$dest.'.performance-'.getmypid();if(!copy($package.'/files/'.$p,$tmp))throw new RuntimeException('Copy: '.$p);chmod($tmp,$before[$p]['mode']);if(!rename($tmp,$dest))throw new RuntimeException('Atomic install: '.$p);$installed[]=$p;}
 foreach($manifest as$p=>$v)if(hash_file('sha256',$root.'/'.$p)!==$v['after'])throw new RuntimeException('Installed hash: '.$p);
 putenv('PERFORMANCE_BASE_URL=https://its-center.ru');putenv('PERFORMANCE_REPORT_PATH='.$package.'/reports/smoke.json');passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($package.'/smoke.php'),$code);if($code)throw new RuntimeException('Production HTTP smoke failed');
 $receipt=['at'=>gmdate('c'),'files'=>count($manifest),'backup'=>$package.'/backup','database_changed'=>false,'smoke'=>'passed'];file_put_contents($package.'/receipt.json',json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));copy($package.'/receipt.json',$package.'/reports/receipt.json');echo 'Stages 1-2 published; HTTP smoke passed'.PHP_EOL;
}catch(Throwable$e){
 foreach(array_reverse($installed)as$p){$dest=$root.'/'.$p;if($before[$p]['hash']===null){if(is_file($dest))unlink($dest);}else{$tmp=$dest.'.performance-restore-'.getmypid();if(!copy($package.'/backup/'.$p,$tmp))throw new RuntimeException('ROLLBACK COPY FAILED: '.$p);chmod($tmp,$before[$p]['mode']);if(!rename($tmp,$dest))throw new RuntimeException('ROLLBACK RENAME FAILED: '.$p);}}
 foreach($installed as$p){$actual=is_file($root.'/'.$p)?hash_file('sha256',$root.'/'.$p):null;if($actual!==$before[$p]['hash'])throw new RuntimeException('ROLLBACK HASH FAILED: '.$p);}
 file_put_contents($package.'/reports/failure.json',json_encode(['error'=>$e->getMessage(),'rolled_back'=>count($installed),'at'=>gmdate('c')],JSON_PRETTY_PRINT));fwrite(STDERR,$e->getMessage().'; changes rolled back'.PHP_EOL);exit(1);
}
