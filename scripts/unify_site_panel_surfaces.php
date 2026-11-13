<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/public/css/style.css';$css=file_get_contents($file);
$marker='/* Shared panel surface: matches the product detail card. */';
if(str_contains($css,$marker))throw new RuntimeException('Already applied');
$rules=<<<'CSS'

/* Shared panel surface: matches the product detail card. */
:root {
  --site-panel-border: #e8edf3;
  --site-panel-shadow: 0 10px 30px rgba(15, 23, 42, .06);
  --home-section-shadow: var(--site-panel-shadow);
}
.site-header--modern {
  --hdr-shadow: var(--site-panel-shadow);
  --hdr-shadow-scrolled: var(--site-panel-shadow);
}
.site-header--modern .header-shell,
.category-b2b__bar,
.category-b2b__panel,
.hp-catalog .hp-catalog__inner,
.catalog_text,
.faq-block,
.faq-block.catalog-faq,
.technics-props-box,
.decide.decide--pro .decide-card--info,
.decide.decide--pro .decide-right {
  border: 1px solid var(--site-panel-border);
  box-shadow: var(--site-panel-shadow);
}
.uslugi-inner > .container::before,
.text-inner > .container::before {
  border: 1px solid var(--site-panel-border);
  box-shadow: var(--site-panel-shadow);
}
CSS;
$dir=$root.'/storage/site-panel-surfaces/'.date('Ymd-His');
if(!mkdir($dir,0775,true)||!copy($file,$dir.'/style.css'))throw new RuntimeException('Backup failed');
file_put_contents($file,$css."\n".$rules."\n");echo $dir.PHP_EOL;
