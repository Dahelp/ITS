<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/app/views/itscenter/Product/view.php';
$text=file_get_contents($file);$text=str_replace("\r\n","\n",$text);
$old="<?php if (!empty(\$product->note) && !\\app\\services\\TsepChainCatalog::isChain(\$product)) { ?>\n            <div class=\"alert alert-success product-note\">\n                <?=\$product->note;?>\n            </div>\n          <?php } ?>";
$new=<<<'PHP'
<?php if (\app\services\TsepChainCatalog::isChain($product)): ?>
            <div class="alert alert-success product-note">Изготавливается под заказ. Стоимость и сроки изготовления и доставки уточняются у менеджера.</div>
          <?php elseif (!empty($product->note)): ?>
            <div class="alert alert-success product-note"><?=$product->note;?></div>
          <?php endif; ?>
PHP;
if(substr_count($text,$old)!==1)throw new RuntimeException('Top notice context');
$text=str_replace($old,$new,$text);
$text=preg_replace('~<div class="product-stock-box">.*?</div>\s*</div>~s',"<?php if (!\\app\\services\\TsepChainCatalog::isChain(\$product)): ?>\n$0\n<?php endif; ?>",$text,-1,$count);
if($count!==2)throw new RuntimeException('Stock boxes');
$text=preg_replace('~\s*<\?php if \(\\\\app\\\\services\\\\TsepChainCatalog::isChain\(\$product\)\): \?>\s*<div class="small text-muted mt-2 w-100">Менеджер уточнит стоимость и сроки изготовления и доставки\.</div>\s*<\?php endif; \?>~','',$text,-1,$count);
if($count!==2)throw new RuntimeException('Button notes '.$count);
$dir=$root.'/storage/chain-order-labels';if(!is_dir($dir))mkdir($dir,0775,true);
$backup=$dir.'/green-notice-'.date('Ymd-His').'.php';
if(!copy($file,$backup))throw new RuntimeException('Backup failed');
file_put_contents($file,$text);echo $backup.PHP_EOL;
