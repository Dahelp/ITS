<?php
// Prepare reviewed generation outputs for the existing category image slots.
if(PHP_SAPI!=='cli') exit;
$root=dirname(__DIR__); $dir=$root.'/outputs/chain-category-images-20261030';
$plan=json_decode(file_get_contents($dir.'/prompts.json'),true,512,JSON_THROW_ON_ERROR);
$mapping=[]; $files=[];
foreach($plan['assets'] as $asset) {
    $original=$dir.'/'.$asset['key'].'.png';
    if(!is_file($original)) throw new RuntimeException('Missing original');
    $image=imagecreatefrompng($original); $w=imagesx($image); $h=imagesy($image);
    foreach(['baseimg'=>[600,90],'mini'=>[240,86]] as $folder=>[$edge,$quality]) {
        $canvas=imagecreatetruecolor($edge,$edge); imagefill($canvas,0,0,imagecolorallocate($canvas,255,255,255));
        $scale=min($edge/$w,$edge/$h); $tw=(int)round($w*$scale); $th=(int)round($h*$scale);
        imagecopyresampled($canvas,$image,(int)(($edge-$tw)/2),(int)(($edge-$th)/2),0,0,$tw,$th,$w,$h);
        $file='public/images/category/'.$folder.'/'.$asset['filename'];
        if(!is_dir(dirname($root.'/'.$file))) mkdir(dirname($root.'/'.$file),0775,true);
        if(!imagewebp($canvas,$root.'/'.$file,$quality)) throw new RuntimeException('WebP conversion failed');
        imagedestroy($canvas); $files[]=$file;
    }
    imagedestroy($image);
    $mapping[]=['alias'=>$asset['alias'],'filename'=>$asset['filename'],'original_sha256'=>hash_file('sha256',$original)];
}
file_put_contents($root.'/app/data/chain-category-images.json',json_encode(['generated_on'=>'2026-10-30','method'=>'built-in image_gen','categories'=>$mapping],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
file_put_contents($dir.'/asset-files.txt',implode("\n",$files)."\n");
echo json_encode(['categories'=>count($mapping),'web_files'=>count($files)],JSON_PRETTY_PRINT).PHP_EOL;
