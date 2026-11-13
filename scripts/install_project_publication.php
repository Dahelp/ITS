<?php
/** CLI-only bounded file release. Backups stay private on the hosting account. */
if(PHP_SAPI!=='cli')exit(1);
$root=getenv('PROJECT_RELEASE_TEST_ROOT')?:'/home/s/shinaspec/its-center.ru/public_html';$package=__DIR__;
if(!is_dir($root)||file_exists($package.'/receipt.json'))throw new RuntimeException('Invalid root or release already applied');
$manifest=json_decode(file_get_contents($package.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);$locks=[];$installed=[];$before=[];
function releaseRun($file,$arg=''){passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($file).' '.escapeshellarg($arg),$code);if($code)throw new RuntimeException('Release check failed: '.basename($file));}
try {
 if(!getenv('PROJECT_RELEASE_TEST_ROOT'))foreach(['/its_cron_task_36.lock','/its_inventory_api_36.lock','/its_cron_task_32.lock']as$name){$h=fopen(sys_get_temp_dir().$name,'c');$deadline=time()+180;while(!flock($h,LOCK_EX|LOCK_NB)){if(time()>$deadline)throw new RuntimeException('Inventory/feed task is still running');usleep(500000);} $locks[]=$h;}
 foreach($manifest as$p=>$v){
  if(!preg_match('~^(app|public|bin|scripts|config|vendor/ishop/core)/[A-Za-z0-9_./-]+$~D',$p)||str_contains($p,'..')||str_contains($p,'\\')||preg_match('~^(?:config/(?:api|config_db|config_db_tamozh|params)\.php|public/(?:uploads|storage|xml|xls|tmp|hot)/)~',$p))throw new RuntimeException('Protected path');
  $file=$root.'/'.$p;if(is_link($file))throw new RuntimeException('Symlink target');$hash=is_file($file)?hash_file('sha256',$file):null;
  if($hash!==$v['before'])throw new RuntimeException('Production drift: '.$p);
  if($v['after']!==null){$src=$package.'/files/'.$p;if(!is_file($src)||hash_file('sha256',$src)!==$v['after'])throw new RuntimeException('Package mismatch: '.$p);if(str_ends_with($p,'.php')){exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($src).' 2>&1',$lint,$code);if($code)throw new RuntimeException('PHP lint: '.$p);}}
  $before[$p]=['sha256'=>$hash,'mode'=>is_file($file)?fileperms($file)&0777:0644];
 }
 if(is_file($package.'/stage9/check.php'))releaseRun($package.'/stage9/check.php','before');
 foreach($manifest as$p=>$v){if($v['before']===null)continue;$backup=$package.'/backup/'.$p;if(!is_dir(dirname($backup)))mkdir(dirname($backup),0700,true);if(!copy($root.'/'.$p,$backup)||hash_file('sha256',$backup)!==$v['before'])throw new RuntimeException('Backup failed');}
 // Static assets and services are ready before controllers/templates change.
 $paths=array_keys($manifest);usort($paths,static function($a,$b){$rank=static fn($p)=>str_starts_with($p,'public/')?0:(str_starts_with($p,'app/services/')?1:2);return$rank($a)<=>$rank($b);});
 foreach($paths as$p){$v=$manifest[$p];if($v['after']===null)continue;$dest=$root.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0755,true);$tmp=$dest.'.project-release-'.getmypid();if(!copy($package.'/files/'.$p,$tmp))throw new RuntimeException('Copy failed');chmod($tmp,$before[$p]['mode']);if(!rename($tmp,$dest))throw new RuntimeException('Atomic install failed');$installed[]=$p;}
 foreach($manifest as$p=>$v)if($v['after']===null&&is_file($root.'/'.$p)){if(!unlink($root.'/'.$p))throw new RuntimeException('Remove obsolete asset failed');$installed[]=$p;}
 foreach($manifest as$p=>$v){$hash=is_file($root.'/'.$p)?hash_file('sha256',$root.'/'.$p):null;if($hash!==$v['after'])throw new RuntimeException('Installed hash mismatch');}
 $receipt=['published_at'=>date(DATE_ATOM),'files'=>count($manifest),'writes'=>count(array_filter($manifest,fn($v)=>$v['after']!==null)),'deletes'=>count(array_filter($manifest,fn($v)=>$v['after']===null)),'private_backup'=>$package.'/backup','schedule_changed'=>false];
 if(is_file($package.'/stage9/check.php')){file_put_contents($package.'/stage9/receipt.json',json_encode($receipt));releaseRun($package.'/stage9/check.php','after');}
 if(is_file($package.'/smoke.php'))releaseRun($package.'/smoke.php');
 file_put_contents($package.'/receipt.json',json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($receipt,JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch(Throwable $e){
 foreach(array_reverse($installed)as$p){$dest=$root.'/'.$p;if($before[$p]['sha256']===null){if(is_file($dest))unlink($dest);}else{$tmp=$dest.'.restore-'.getmypid();copy($package.'/backup/'.$p,$tmp);chmod($tmp,$before[$p]['mode']);rename($tmp,$dest);}}
 file_put_contents($package.'/failure.json',json_encode(['error'=>$e->getMessage(),'rolled_back'=>count($installed),'at'=>date(DATE_ATOM)],JSON_PRETTY_PRINT));fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);
} finally{foreach($locks as$h){flock($h,LOCK_UN);fclose($h);}}
