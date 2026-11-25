<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__,2);$target=getenv('PERFORMANCE_PACKAGE_DIR')?:'/tmp/performance-package';
if(file_exists($target))throw new RuntimeException('Package exists');mkdir($target,0700,true);
$manifest=json_decode(file_get_contents($root.'/.github/performance-release/manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest as$p=>$v){if(!preg_match('~^(app|public|bin|vendor/ishop/core)/[A-Za-z0-9_./-]+$~D',$p)||str_contains($p,'..'))throw new RuntimeException('Invalid path');if(hash_file('sha256',$root.'/'.$p)!==$v['after'])throw new RuntimeException('Source hash: '.$p);$dest=$target.'/files/'.$p;@mkdir(dirname($dest),0700,true);if(!copy($root.'/'.$p,$dest))throw new RuntimeException('Package copy');}
copy($root.'/.github/performance-release/manifest.json',$target.'/manifest.json');copy($root.'/.github/performance-release/install.php',$target.'/install.php');copy($root.'/tests/LazySessionHttpTest.php',$target.'/smoke.php');echo 'Performance package: '.count($manifest).' files'.PHP_EOL;
