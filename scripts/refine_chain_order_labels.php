<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);
$dir=$root.'/storage/chain-order-labels/'.date('Ymd-His');
if(!mkdir($dir,0775,true))throw new RuntimeException('Backup failed');
$changes=[
 'app/services/TsepChainCatalog.php'=>[
  ["return 'Под заказ. Изготавливается по заказу. Цена, срок изготовления и поставки согласовываются с менеджером.';","return 'Изготовление под заказ';",1],
 ],
 'app/widgets/product/card.php'=>[
  ['href="<?= $cardUrl ?>">Оформить под заказ</a>','href="<?= $cardUrl ?>">Подробнее</a>',1],
 ],
 'app/views/itscenter/Product/view.php'=>[
  ['<div class="current_price_warning product-price-date">Цену уточняйте у менеджера</div>','<?php if (!\\app\\services\\TsepChainCatalog::isChain($product)): ?><div class="current_price_warning product-price-date">Цену уточняйте у менеджера</div><?php endif; ?>',1],
  ["                                            Оформить под заказ\n                                        </button>","                                            <?= \\app\\services\\TsepChainCatalog::isChain(\$product) ? 'Оставить заявку' : 'Оформить под заказ' ?>\n                                        </button>\n                                        <?php if (\\app\\services\\TsepChainCatalog::isChain(\$product)): ?>\n                                        <div class=\"small text-muted mt-2 w-100\">Менеджер уточнит стоимость и сроки изготовления и доставки.</div>\n                                        <?php endif; ?>",2],
  ['<div class="modal-title" id="ModalRequestLabel">Оформить товар под заказ</div>','<div class="modal-title" id="ModalRequestLabel"><?= \\app\\services\\TsepChainCatalog::isChain($product) ? \'Оставить заявку\' : \'Оформить товар под заказ\' ?></div>',1],
 ],
];
$prepared=[];
foreach($changes as $file=>$replacements){
 $original=file_get_contents($root.'/'.$file);$text=str_replace("\r\n","\n",$original);
 foreach($replacements as [$old,$new,$expected]) {
  if(substr_count($text,$old)!==$expected)throw new RuntimeException('Unexpected context '.$file);
  $text=str_replace($old,$new,$text);
 }
 $prepared[$file]=str_contains($original,"\r\n")?str_replace("\n","\r\n",$text):$text;
}
foreach($prepared as $file=>$text){
 copy($root.'/'.$file,$dir.'/'.basename($file));
 file_put_contents($root.'/'.$file,$text);
}
echo json_encode(['files'=>count($prepared),'backup'=>$dir],JSON_UNESCAPED_SLASHES).PHP_EOL;
