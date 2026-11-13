<?php
// Prepare prompts and the exact product mapping. Does not generate or publish images.
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\TsepChainCatalog;
$root=dirname(__DIR__);
$catalog=json_decode(file_get_contents($root.'/app/data/tsep-chains.json'),true,512,JSON_THROW_ON_ERROR);
$products=TsepChainCatalog::products($catalog);
$assets=[];
foreach($catalog['series'] as $series) {
    $special=$series['group']==='special';
    $pattern=strpos($series['series'],'Лесенка')===0 ? 'ladder' : (strpos($series['series'],'Зигзаг')!==false ? 'zigzag' : 'honeycomb');
    [$working,$side]=explode('/',$series['options']['Толщина']);
    $key=$series['group'].'-'.$pattern.'-'.$working.'-'.$side;
    $wheel=$special
        ? 'One upright large heavy-duty construction/tractor wheel with black deep chevron industrial R4 tread and a plain neutral light-gray steel rim; tall heavy-equipment tire proportions, not a car tire.'
        : 'One upright squat black forklift wheel with chunky industrial block tread and a plain neutral light-gray steel rim; proportions like a 27x10-12 forklift wheel, not a car tire.';
    $geometry=[
        'ladder'=>'The chain pattern is LADDER (Russian Лесенка): two circumferential side chains lie on the two sidewalls and are joined by evenly spaced straight parallel transverse cross chains covering the complete tread width, perpendicular to the rolling direction. Separate straight rungs, no diagonal tread chains, no diamonds, no hexagons.',
        'honeycomb'=>'The chain pattern is HONEYCOMB (Russian Сота): continuous connected long hexagonal cells tile the tread; oblique chain segments join in a regular honeycomb network with distinct open cells and chains connected over both shoulders to circumferential side chains. Do not substitute straight ladder rungs or a single zigzag strand. Show recognizable broad honeycomb geometry on the visible tread.',
        'zigzag'=>'The chain pattern is ZIGZAG (Russian Сота / Зигзаг): a continuous repeating serpentine diagonal chain path crosses alternately between the two tread shoulders, making clearly visible offset V-shaped chevrons along the rolling circumference, fastened to two side chains on the sidewalls. It must be visibly different from both straight ladder rungs and a dense full hexagonal honeycomb mesh.',
    ][$pattern];
    $fastener=$pattern==='ladder' ? 'a side tensioning hook and an inner hook' : ($special ? 'an outer side tensioning hook and an inner connecting hook' : 'a lateral tensioner and outer/inner hooks');
    $prompt="Use case: product-mockup. Asset type: square ecommerce catalog photograph of a wheel wearing traction chains.\nScene/backdrop: pure white seamless studio background with a restrained soft ground shadow.\nSubject: ".$wheel."\nChain execution: ".$geometry." Nominal round-steel rod diameters: working tread chain ".$working." mm, side chain ".$side." mm. Show visibly ".($working==='10'?'heavy':($working==='8'?'medium-heavy':'medium'))." welded oval steel links with alternating link planes, naturally satin silver, snugly following the rubber surface. Realistic hardware, ".$fastener.". No spikes or studs.\nStyle: photorealistic professional industrial product photography, sharply defined rubber and metal, softbox lighting, neutral exposure, no dramatic effects.\nComposition: one whole complete wheel standing upright, three-quarter front view exposing both tread pattern and part of sidewall/rim; all edges visible, consistent camera height, centered subject filling approximately 82 percent of square canvas, clean margins.\nConstraints: physically plausible chain connections and tension; no floating links, broken rings, loose dangling metal, extra wheels, vehicle body, people, props, snow or mud. No lettering, size markings, logos, brands, captions, watermarks, frames, panels, diagrams or collages. A representative execution image shared across tire sizes in the same series; do not imply an exact labeled tire size.";
    $matches=array_values(array_filter($products,static function($p) use($series) { return $p['source']===$series['source']; }));
    $assets[]=['key'=>$key,'series'=>$series['series'],'group'=>$series['group'],'caliber'=>$working.'/'.$side,
        'filename'=>'snow-chain-'.$key.'-v1.webp','source_url'=>$series['source'],'product_count'=>count($matches),
        'products'=>array_map(static function($p) { return ['article'=>$p['article'],'alias'=>$p['alias']]; },$matches),
        'prompt'=>$prompt,'status'=>'pending_generation'];
}
$articles=[]; foreach($assets as $asset) foreach($asset['products'] as $p) $articles[]=$p['article'];
if(count($assets)!==9 || count($articles)!==55 || count(array_unique($articles))!==55) throw new RuntimeException('Incomplete or duplicate mapping');
$dir=$root.'/outputs/tsep-chain-images-20261030';
if(!is_dir($dir)) mkdir($dir,0775,true);
$manifest=['image_count'=>9,'product_count'=>55,'generation_mode'=>'built-in image_gen','background'=>'white',
    'publication_rule'=>'Only publish generated images after visual inspection of the chain pattern. Do not change price, status, stock, product facts or unrelated product images.',
    'assets'=>$assets];
file_put_contents($dir.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
foreach($assets as $asset) file_put_contents($dir.'/'.$asset['key'].'.prompt.txt',$asset['prompt'].PHP_EOL);
echo json_encode(['assets'=>count($assets),'products'=>count($articles),'series'=>array_map(static function($a) {return [$a['key'],$a['product_count']];},$assets)],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
