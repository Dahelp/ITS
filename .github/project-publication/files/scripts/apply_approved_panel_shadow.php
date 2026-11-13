<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/public/css/style.css';$css=file_get_contents($file);
$marker='/* Approved block shadow: exact .desc-prod-inner.bg-light.shadow reference. */';
if(str_contains($css,$marker))throw new RuntimeException('Already applied');
$rules=<<<'CSS'

/* Approved block shadow: exact .desc-prod-inner.bg-light.shadow reference. */
:root {
  --site-block-shadow: 0 0.1rem 2rem rgba(46, 46, 46, 0.1);
  --home-section-shadow: var(--site-block-shadow);
}
.site-header--modern {
  --hdr-shadow: var(--site-block-shadow);
  --hdr-shadow-scrolled: var(--site-block-shadow);
}
/* Structural surfaces only. Keep existing borders, radii, padding and grids. */
.site-header--modern .header-shell,
.site-header--modern.is-scrolled .header-shell,
.category-b2b__bar,
.category-b2b__panel,
.hp-catalog .hp-catalog__inner,
.hp-catalog .hp-tile,
.hp-catalog .hp-tile:hover,
.hp-catalog .hp-accessory,
.advantages-inner .pch_td_2,
.advantages-inner .pch_td_2:hover,
.decide.decide--pro .decide-card--info,
.decide.decide--pro .decide-right,
.decide.decide--pro .decide-adv-card,
.technics-card,
.technics-card:hover,
.technics-props-box,
.category-inner .category,
.category-inner .category:hover,
.catalog_text,
.faq-block,
.faq-block.catalog-faq,
.related-sizes,
.desc-prod-inner,
.product-card,
.product-card:hover,
.product-one .card:hover,
.hit-inner .product-card,
.hit-inner .product-card:hover,
.cont_ht,
.cont_ht:hover,
.complete-premium__card,
.complete-summary,
.complete-items-premium,
.comparison-toolbar,
.comparison-card,
.review-card,
.review-box,
.aiz-user-sidenav-wrap,
.wbg,
.panel,
.card,
.shadow {
  box-shadow: var(--site-block-shadow) !important;
}
/* These sections draw their surface on a pseudo-element. */
.uslugi-inner > .container::before,
.text-inner > .container::before,
.advantages-inner > .container::before {
  box-shadow: var(--site-block-shadow) !important;
}
CSS;
$dir=$root.'/storage/approved-panel-shadow/'.date('Ymd-His');
if(!mkdir($dir,0775,true)||!copy($file,$dir.'/style.css'))throw new RuntimeException('Backup failed');
file_put_contents($file,$css."\n".$rules."\n");echo $dir.PHP_EOL;
