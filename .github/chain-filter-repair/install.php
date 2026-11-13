<?php
/** Run from the private release directory: php install.php /absolute/site/root [--apply] */
if(PHP_SAPI!=='cli')exit(1);
$package=__DIR__; $siteRoot=realpath($argv[1]??'');
if(!$siteRoot||!is_file($siteRoot.'/config/init.php')||!is_file($package.'/patches.json'))throw new RuntimeException('Invalid site root or package');
if(is_file($package.'/receipt.json'))throw new RuntimeException('Release already applied');
$lock=fopen($package.'/install.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Installation running');
$changes=[];
foreach(json_decode(file_get_contents($package.'/patches.json'),true,512,JSON_THROW_ON_ERROR) as $patch) {
    $path=$patch['path'];
    $text=$changes[$path]??str_replace("\r\n","\n",file_get_contents($siteRoot.'/'.$path));
    if($patch['kind']==='replace') {
        if(substr_count($text,$patch['find'])!==1)throw new RuntimeException('Baseline mismatch: '.$path);
        $text=str_replace($patch['find'],$patch['replace'],$text);
    } elseif($patch['kind']==='method') {
        $a=strpos($text,$patch['start']);$b=$a===false?false:strpos($text,$patch['end'],$a);
        if($a===false||$b===false||strpos(substr($text,$a,$b-$a),$patch['guard'])===false)throw new RuntimeException('Method baseline mismatch');
        $text=substr($text,0,$a).$patch['replace'].substr($text,$b);
    } else {
        $opening='<select class="form-control" name="attrs[<?=$group_id ?>]">';
        if(substr_count($text,$opening)!==1)throw new RuntimeException('Form baseline mismatch');
        $a=strpos($text,$opening);$b=strpos($text,'</select>',$a)+strlen('</select>');
        $block=substr($text,$a,$b-$a);
        $newOpening='<?php $chainSizeMultiple = (int)$group_id === 2 && \app\services\TsepChainCatalog::isChain($product); ?>'."\n".
            '<select class="form-control" name="attrs[<?=$group_id ?>]<?= $chainSizeMultiple ? \'[]\' : \'\' ?>"<?= $chainSizeMultiple ? \' multiple size="6"\' : \'\' ?>>';
        $block=str_replace($opening,$newOpening,$block)."\n".
            '<?php if ($chainSizeMultiple): ?><small class="form-text text-muted">Можно выбрать несколько размеров шин. Для 8.15-15 / 28x9-15 отметьте оба обозначения (Ctrl + щелчок).</small><?php endif; ?>';
        $text=substr($text,0,$a).$block.substr($text,$b);
    }
    $changes[$path]=$text;
}
foreach(['app/services/ProductFilterSelection.php','scripts/repair_chain_filter_sizes.php'] as $path) {
    if(is_file($siteRoot.'/'.$path))throw new RuntimeException('New file already exists: '.$path);
    $changes[$path]=file_get_contents($package.'/payload/'.$path);
}
$report=['files'=>array_keys($changes),'baseline_sha256'=>[],'mode'=>in_array('--apply',$argv,true)?'apply':'preview'];
foreach($changes as $path=>$text) {
    $test=$package.'/lint-'.sha1($path).'.php';file_put_contents($test,$text);
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($test).' 2>&1',$output,$code);
    if($code!==0)throw new RuntimeException('Lint failed: '.$path.' '.implode("\n",$output));
    $report['baseline_sha256'][$path]=is_file($siteRoot.'/'.$path)?hash_file('sha256',$siteRoot.'/'.$path):null;
}
if(!in_array('--apply',$argv,true)){echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;exit;}
$before=[];
foreach($changes as $path=>$text){$before[$path]=is_file($siteRoot.'/'.$path)?file_get_contents($siteRoot.'/'.$path):null;}
if(file_put_contents($package.'/before.json',json_encode($before,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false)throw new RuntimeException('Backup failed');
$written=[];
try {
    foreach($changes as $path=>$text){$written[]=$path;if(file_put_contents($siteRoot.'/'.$path,$text)===false)throw new RuntimeException('Write failed');}
    // The repair script backs up links and compensates its own insertions on failure.
    $argv=[$siteRoot.'/scripts/repair_chain_filter_sizes.php','--apply'];
    require $siteRoot.'/scripts/repair_chain_filter_sizes.php';
    foreach($changes as $path=>$text)if(hash_file('sha256',$siteRoot.'/'.$path)!==hash('sha256',$text))throw new RuntimeException('Installed hash mismatch');
} catch(Throwable $e) {
    foreach(array_reverse($written) as $path){if($before[$path]===null)unlink($siteRoot.'/'.$path);else file_put_contents($siteRoot.'/'.$path,$before[$path]);}
    throw $e;
}
file_put_contents($package.'/receipt.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
