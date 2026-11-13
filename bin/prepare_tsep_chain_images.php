<?php
// Resize reviewed generated originals without cropping or changing their content.
if(PHP_SAPI!=='cli') exit;
$root=dirname(__DIR__);
$dir=$root.'/outputs/tsep-chain-images-20261030';
$plan=json_decode(file_get_contents($dir.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
$map=['generated_on'=>'2026-10-30','method'=>'built-in image_gen','assets'=>[],'products'=>[]];
$files=[]; $totalBytes=0;
foreach($plan['assets'] as &$asset) {
    $source=$dir.'/'.$asset['key'].'.png';
    if(!is_file($source)) throw new RuntimeException('Missing generated original: '.$asset['key']);
    $image=imagecreatefrompng($source);
    if(!$image) throw new RuntimeException('Invalid image');
    $w=imagesx($image); $h=imagesy($image);
    foreach(['original'=>[max($w,$h),94],'baseimg'=>[1000,90],'mini'=>[320,86]] as $folder=>[$max,$quality]) {
        $scale=min(1,$max/max($w,$h)); $nw=(int)round($w*$scale); $nh=(int)round($h*$scale);
        $canvas=imagecreatetruecolor($nw,$nh);
        imagefill($canvas,0,0,imagecolorallocate($canvas,255,255,255));
        imagecopyresampled($canvas,$image,0,0,0,0,$nw,$nh,$w,$h);
        $relative='public/images/product/'.$folder.'/'.$asset['filename'];
        if(!is_dir(dirname($root.'/'.$relative))) mkdir(dirname($root.'/'.$relative),0775,true);
        if(!imagewebp($canvas,$root.'/'.$relative,$quality)) throw new RuntimeException('Image conversion failed');
        imagedestroy($canvas); $files[]=$relative; $totalBytes+=filesize($root.'/'.$relative);
    }
    imagedestroy($image);
    $asset['status']='generated_and_visually_reviewed';
    $asset['original']=$asset['key'].'.png';
    $map['assets'][]=['key'=>$asset['key'],'group'=>$asset['group'],'series'=>$asset['series'],'caliber'=>$asset['caliber'],'filename'=>$asset['filename'],'product_count'=>$asset['product_count'],'original_sha256'=>hash_file('sha256',$source)];
    foreach($asset['products'] as $p) {
        if(isset($map['products'][$p['article']])) throw new RuntimeException('Duplicate product');
        $map['products'][$p['article']]=['filename'=>$asset['filename'],'series'=>$asset['series'],'group'=>$asset['group'],'alias'=>$p['alias']];
    }
}
unset($asset);
if(count($map['assets'])!==9 || count($map['products'])!==55) throw new RuntimeException('Incomplete mapping');
file_put_contents($root.'/app/data/tsep-chain-images.json',json_encode($map,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
file_put_contents($dir.'/manifest.json',json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
file_put_contents($dir.'/asset-files.txt',implode("\n",$files)."\n");
echo json_encode(['reviewed_images'=>9,'mapped_products'=>55,'web_files'=>count($files),'total_web_bytes'=>$totalBytes],JSON_PRETTY_PRINT).PHP_EOL;
