<?php
require dirname(__DIR__).'/vendor/autoload.php';
$root=dirname(__DIR__);$package=$root.'/outputs/chain-filter-repair';
$plan=json_decode(file_get_contents($package.'/patches.json'),true,512,JSON_THROW_ON_ERROR);
$fixture=$root.'/tmp/chain-filter-baseline-'.bin2hex(random_bytes(4));
mkdir($fixture.'/config',0775,true);file_put_contents($fixture.'/config/init.php','<?php');
$paths=array_unique(array_column($plan,'path'));
foreach($paths as $path) {
    $text=str_replace("\r\n","\n",file_get_contents($root.'/'.$path));
    foreach(array_reverse($plan) as $p) {
        if($p['path']!==$path)continue;
        if($p['kind']==='replace')$text=str_replace($p['replace'],$p['find'],$text);
        elseif($p['kind']==='method')$text=str_replace($p['replace'],"    public function editFilter(\$id, \$data){\n        foreach(\$data['attrs'] as \$s){}\n    }\n\n",$text);
        else {
            $text=preg_replace('~<\?php \$chainSizeMultiple = .*?\?>\s*~','',$text);
            $opening='<select class="form-control" name="attrs[<?=$group_id ?>]<?= $chainSizeMultiple ? \'[]\' : \'\' ?>"<?= $chainSizeMultiple ? \' multiple size="6"\' : \'\' ?>>';
            $text=str_replace($opening,'<select class="form-control" name="attrs[<?=$group_id ?>]">',$text);
            $text=preg_replace('~<\?php if \(\$chainSizeMultiple\): \?><small.*?</small><\?php endif; \?>~s','',$text);
        }
    }
    if(!is_dir(dirname($fixture.'/'.$path)))mkdir(dirname($fixture.'/'.$path),0775,true);
    $text=preg_replace('/<\?php/', '<?php /* unrelated baseline sentinel */ ', $text, 1);
    file_put_contents($fixture.'/'.$path,$text);
}
$command=escapeshellarg(PHP_BINARY).' '.escapeshellarg($package.'/install.php').' '.escapeshellarg($fixture);
exec($command.' 2>&1',$output,$code);
if($code!==0)throw new RuntimeException(implode("\n",$output));
$report=json_decode(implode("\n",$output),true,512,JSON_THROW_ON_ERROR);
if($report['mode']!=='preview'||count($report['files'])!==7)throw new RuntimeException('Unexpected package scope');
foreach($paths as $path) {
    $lint=file_get_contents($package.'/lint-'.sha1($path).'.php');
    if(strpos($lint,'unrelated baseline sentinel')===false)throw new RuntimeException('Unrelated live changes were lost');
    if(strpos(file_get_contents($fixture.'/'.$path),'ProductFilterSelection')!==false)throw new RuntimeException('Preview modified baseline');
}
foreach(['edit','copy'] as $form) {
    $lint=file_get_contents($package.'/lint-'.sha1('app/views/itscenter/admin/Product/'.$form.'.php').'.php');
    if(substr_count($lint,'multiple size="6"')!==1)throw new RuntimeException('Multi-size control missing or duplicated');
}
// Simulate a data-repair failure after it declares similarly named variables.
// The installer must keep its own backup intact and restore every existing file.
$failurePackage=$fixture.'/failure-package';mkdir($failurePackage.'/payload/app/services',0775,true);mkdir($failurePackage.'/payload/scripts',0775,true);
copy($package.'/install.php',$failurePackage.'/install.php');copy($package.'/patches.json',$failurePackage.'/patches.json');
copy($package.'/payload/app/services/ProductFilterSelection.php',$failurePackage.'/payload/app/services/ProductFilterSelection.php');
file_put_contents($failurePackage.'/payload/scripts/repair_chain_filter_sizes.php','<?php $before = []; $changes = []; throw new RuntimeException("simulated data failure");');
$beforeHashes=[];foreach($paths as $path)$beforeHashes[$path]=hash_file('sha256',$fixture.'/'.$path);
foreach(['app/services','scripts'] as $dir)if(!is_dir($fixture.'/'.$dir))mkdir($fixture.'/'.$dir,0775,true);
$output=[];exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($failurePackage.'/install.php').' '.escapeshellarg($fixture).' --apply 2>&1',$output,$code);
if($code===0||strpos(implode("\n",$output),'Installation rolled back: RuntimeException: simulated data failure')===false)throw new RuntimeException('Failure not reported');
foreach($beforeHashes as $path=>$hash)if(!is_file($fixture.'/'.$path)||hash_file('sha256',$fixture.'/'.$path)!==$hash)throw new RuntimeException('Rollback lost baseline: '.$path);
foreach(['app/services/ProductFilterSelection.php','scripts/repair_chain_filter_sizes.php'] as $path)if(is_file($fixture.'/'.$path))throw new RuntimeException('Rollback left a new file');
echo "Chain filter package: seven scoped files, lint, preview, unrelated changes and rollback after data failure OK\n";
