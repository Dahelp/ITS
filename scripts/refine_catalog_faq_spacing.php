<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);
$view=$root.'/app/views/itscenter/Catalog/index.php';$css=$root.'/public/css/style.css';
$html=file_get_contents($view);$style=file_get_contents($css);
$old='<section class="faq-block mb-4">';
if(substr_count($html,$old)!==1)throw new RuntimeException('FAQ context');
$rule=<<<'CSS'

/* Catalog FAQ follows the spacing and outline of the surrounding panels. */
.faq-block.catalog-faq {
  margin-top: 48px;
  padding: 28px 28px 12px;
  border: 1px solid #e3e9ef;
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 8px 26px rgba(20, 40, 70, .04);
}
@media (max-width: 767px) {
  .faq-block.catalog-faq {
    margin-top: 32px;
    padding: 20px 16px 4px;
    border-radius: 18px;
  }
}
CSS;
if(str_contains($style,'.faq-block.catalog-faq'))throw new RuntimeException('Already applied');
$dir=$root.'/storage/catalog-faq-spacing/'.date('Ymd-His');
if(!mkdir($dir,0775,true)||!copy($view,$dir.'/index.php')||!copy($css,$dir.'/style.css'))throw new RuntimeException('Backup failed');
file_put_contents($view,str_replace($old,'<section class="faq-block catalog-faq mb-4">',$html));
file_put_contents($css,$style."\n".$rule."\n");
echo $dir.PHP_EOL;
