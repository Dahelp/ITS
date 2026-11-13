<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$target=$root.'/.github/chain-filter-repair';
if(!is_dir($target))mkdir($target,0775,true);
$files=['install.php','patches.json','payload/app/services/ProductFilterSelection.php','payload/scripts/repair_chain_filter_sizes.php'];
foreach($files as $path){if(!is_dir(dirname($target.'/'.$path)))mkdir(dirname($target.'/'.$path),0775,true);copy($root.'/outputs/chain-filter-repair/'.$path,$target.'/'.$path);}
require $root.'/vendor/autoload.php';
$products=\app\services\TsepChainCatalog::products(json_decode(file_get_contents($root.'/app/data/tsep-chains.json'),true));
$expected=[];foreach(['8.15-15','28x9-15','10-16.5','12-16.5','4.5-12'] as $size){$expected[$size]=array_values(array_column(array_filter($products,static function($p)use($size){return in_array($size,$p['filters']['size']??[],true);}), 'alias'));sort($expected[$size]);}
file_put_contents($target.'/expected.json',json_encode($expected,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$verify=file_get_contents($root.'/bin/verify_chain_filter_repair.php');
$verify=str_replace("require dirname(__DIR__).'/vendor/autoload.php';",'',$verify);
$verify=preg_replace('~^\$products=.*?;\r?$~m',"\$expectedBySize=json_decode(file_get_contents(__DIR__.'/expected.json'),true,512,JSON_THROW_ON_ERROR);",$verify,1,$count);
if($count!==1)throw new RuntimeException('Verifier source changed');
$verify=preg_replace('~    \$expected=array_column\(array_filter\(\$products,.*?sort\(\$expected\);~', '    $expected=$expectedBySize[$size];sort($expected);', $verify,1,$count);
if($count!==1)throw new RuntimeException('Verifier product mapping changed');
file_put_contents($target.'/verify.php',$verify);
$known=file(getenv('USERPROFILE').'/.ssh/known_hosts',FILE_IGNORE_NEW_LINES);
$known=array_values(array_filter($known,static function($line){return strpos($line,'shinaspec.beget.tech ')===0;}));
if(!$known)throw new RuntimeException('No trusted server key');
file_put_contents($target.'/known_hosts',implode("\n",$known)."\n");
$files=array_merge($files,['expected.json','verify.php','known_hosts']);$sums='';
foreach($files as $path)$sums.=hash_file('sha256',$target.'/'.$path).'  '.$path."\n";
file_put_contents($target.'/SHA256SUMS',$sums);
$files[]='SHA256SUMS';
$paths=array_map(static function($p){return '.github/chain-filter-repair/'.$p;},$files);
$paths[]='.github/workflows/deploy-chain-filters.yml';
file_put_contents($root.'/outputs/chain-filter-repair/github-files.json',json_encode($paths,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo 'Scoped GitHub files: '.count($paths).PHP_EOL;
