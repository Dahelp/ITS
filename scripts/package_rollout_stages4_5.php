<?php
$r=dirname(__DIR__);$files=[];foreach(['stage4'=>'size-search-runner.php','stage5'=>'naming-runner.php']as$key=>$file)$files[$key]=base64_encode(file_get_contents($r.'/outputs/'.$file));$code=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);$base='/home/s/shinaspec/catalog-rollout-20261113';if(!is_dir($base))mkdir($base,0700,true);$files=json_decode(base64_decode('__FILES__'),true,512,JSON_THROW_ON_ERROR);
foreach(['stage4'=>'/home/s/shinaspec/catalog-size-search-20261112','stage5'=>'/home/s/shinaspec/catalog-naming-20261113']as$key=>$release){
 if(file_exists($release.'/receipt.json')){$receipt=json_decode(file_get_contents($release.'/receipt.json'),true,512,JSON_THROW_ON_ERROR);foreach($receipt['files']as$p=>$hash)if(hash_file('sha256','/home/s/shinaspec/its-center.ru/public_html/'.$p)!==$hash['after'])throw new RuntimeException('Previously published release drift '.$p);echo $key." already published and verified\n";continue;}
 $path=$base.'/'.$key.'.php';file_put_contents($path,base64_decode($files[$key],true));passthru('/usr/local/bin/php8.2 -d display_errors=1 '.escapeshellarg($path),$exit);if($exit!==0)exit($exit);
}
echo "Stages 4 and 5 published\n";
CODE;
file_put_contents($r.'/outputs/rollout-stages4-5-runner.php',str_replace('__FILES__',base64_encode(json_encode($files)),$code));echo "Sequential rollout prepared\n";
