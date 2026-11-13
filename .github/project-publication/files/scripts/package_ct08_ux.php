<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/ct08-ux-release';
$manifest=json_decode(file_get_contents($stage.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest as$path=>&$hash)$hash['after']=hash_file('sha256',$stage.'/files/'.$path);unset($hash);
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$install=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents(__DIR__.'/install_ct08.php'));
$install=str_replace('CT08 published;','CT08 UX published;',$install);file_put_contents($stage.'/install.php',$install);
copy(__DIR__.'/verify_ct08_ux.php',$stage.'/verify.php');
echo "CT08 UX package refreshed without changing production baselines\n";
