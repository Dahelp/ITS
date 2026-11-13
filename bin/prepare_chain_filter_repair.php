<?php
if (PHP_SAPI !== 'cli') exit(1);
$root=dirname(__DIR__); $out=$root.'/outputs/chain-filter-repair';
if(!is_dir($out.'/payload'))mkdir($out.'/payload',0775,true);
function segment($text,$start,$end) {
    $a=strpos($text,$start); $b=$a===false?false:strpos($text,$end,$a);
    if($a===false||$b===false)throw new RuntimeException('Patch segment missing');
    return substr($text,$a,$b-$a);
}
$product=str_replace("\r\n","\n",file_get_contents($root.'/app/models/admin/Product.php'));
$controller=str_replace("\r\n","\n",file_get_contents($root.'/app/controllers/CategoryController.php'));
$adminController=str_replace("\r\n","\n",file_get_contents($root.'/app/controllers/admin/ProductController.php'));
$tail=segment($controller,'        // A moved assortment need not leave products','    protected function getNamedFilterIdsFromQuery(): array');
$plan=[
 ['path'=>'app/models/admin/Product.php','kind'=>'replace','find'=>'$flag = traverseArray($value);','replace'=>'$flag = $this->traverseArray($value);'],
 ['path'=>'app/models/admin/Product.php','kind'=>'method','start'=>'    public function editFilter(','end'=>'    public function getImg()',
  'guard'=>"foreach(\$data['attrs'] as \$s)",'replace'=>segment($product,'    public function editFilter(','    public function getImg()')],
 ['path'=>'app/controllers/CategoryController.php','kind'=>'replace',
  'find'=>'$this->findApplicableFilterValue((string)$filterAlias, (string)$ids);',
  'replace'=>'$this->findApplicableFilterValue((string)$filterAlias, (string)$ids, (int)$category->id);'],
 ['path'=>'app/controllers/CategoryController.php','kind'=>'replace',
  'find'=>'protected function findApplicableFilterValue(string $filterAlias, string $categoryIds)',
  'replace'=>'protected function findApplicableFilterValue(string $filterAlias, string $categoryIds, int $sourceCategoryId = 0)'],
 ['path'=>'app/controllers/CategoryController.php','kind'=>'replace',
  'find'=>"        return null;\n    }\n\n    protected function getNamedFilterIdsFromQuery(): array",
  'replace'=>$tail.'    protected function getNamedFilterIdsFromQuery(): array'],
];
$newAjaxLine=segment($adminController, '            $chainSizeMultiple = (int)$group_id === 2 && in_array(', "\n\t\t\t\t<option value=''>");
$plan[]=['path'=>'app/controllers/admin/ProductController.php','kind'=>'replace',
    'find'=>"\t\t    echo \"<select class='form-control' name='attrs[\".\$group_id.\"]'>            ", 'replace'=>$newAjaxLine];
$plan[]=['path'=>'app/controllers/admin/ProductController.php','kind'=>'replace',
    'find'=>'echo "<option value=\'".$attr_id."\' ".$selected.">".$value."</option>";',
    'replace'=>'echo "<option value=\'".$attr_id."\'>".$value."</option>";'];
foreach(['edit','copy'] as $form) $plan[]=['path'=>'app/views/itscenter/admin/Product/'.$form.'.php','kind'=>'multi'];
file_put_contents($out.'/patches.json',json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
foreach(['app/services/ProductFilterSelection.php','scripts/repair_chain_filter_sizes.php'] as $path) {
    $target=$out.'/payload/'.$path; if(!is_dir(dirname($target)))mkdir(dirname($target),0775,true); copy($root.'/'.$path,$target);
}
copy($root.'/scripts/install_chain_filter_repair.php',$out.'/install.php');
echo $out.PHP_EOL;
