<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/s/shinaspec/its-center.ru/public_html';$package='/home/s/shinaspec/ct08-auto-20261112';
$manifest=json_decode(file_get_contents($package.'/current-manifest.json'),true,512,JSON_THROW_ON_ERROR);
$changes=[
 'app/controllers/CategoryController.php'=>['array_slice(array_values($products), 10)'=>'array_slice(array_values($products), 12)'],
 'app/views/itscenter/Category/view.php'=>['array_slice(array_values($products), 0, 10)'=>'array_slice(array_values($products), 0, 12)','count($products) > 10'=>'count($products) > 12'],
];$new=[];
foreach($changes as$path=>$replacements){
 if(hash_file('sha256',$root.'/'.$path)!==$manifest[$path]['after'])throw new RuntimeException('Production drift '.$path);
 $text=file_get_contents($root.'/'.$path);
 foreach($replacements as$old=>$replacement){if(substr_count($text,$old)!==1)throw new RuntimeException('Batch anchor');$text=str_replace($old,$replacement,$text);}
 $new[$path]=$text;
}
$backup=$package.'/first-batch-backup';if(!is_dir($backup))mkdir($backup,0700);
foreach($new as$path=>$text){if(!copy($root.'/'.$path,$backup.'/'.basename($path)))throw new RuntimeException('Backup failed');file_put_contents($package.'/batch-'.basename($path),$text);exec('/usr/local/bin/php8.2 -l '.escapeshellarg($package.'/batch-'.basename($path)).' 2>&1',$lint,$status);if($status!==0)throw new RuntimeException('Lint failed');}
$written=[];try{foreach($new as$path=>$text){file_put_contents($root.'/'.$path.'.ct08-new',$text);if(!rename($root.'/'.$path.'.ct08-new',$root.'/'.$path))throw new RuntimeException('Write failed');$written[]=$path;$manifest[$path]['after']=hash('sha256',$text);}file_put_contents($package.'/current-manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));file_put_contents($package.'/first-batch-receipt.json',json_encode(['at'=>date(DATE_ATOM),'batch'=>12,'paths'=>array_keys($new)],JSON_PRETTY_PRINT));echo "Initial and lazy product batches aligned at12; both files verified\n";}catch(Throwable$e){foreach($written as$path)copy($backup.'/'.basename($path),$root.'/'.$path);throw$e;}
