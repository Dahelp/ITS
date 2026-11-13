<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$file=$root.'/public/css/style.css';$css=file_get_contents($file);
$marker='/* Historical product card surface, verified in the 2026-10-28 stylesheet. */';
if(str_contains($css,$marker))throw new RuntimeException('Already applied');
$rules=<<<'CSS'

/* Historical product card surface, verified in the 2026-10-28 stylesheet. */
:root {
  --surface-card-shadow: 0 0 13px 0 rgba(82, 63, 105, 0.05);
  --home-section-shadow: var(--surface-card-shadow);
}
.site-header--modern {
  --hdr-shadow: var(--surface-card-shadow);
  --hdr-shadow-scrolled: var(--surface-card-shadow);
}
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
.decide.decide--pro .decide-trust-item,
.decide.decide--pro .decide-right-form,
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
.shadow-sm,
.shadow-lg,
.shadow,
.site-footer.site-footer--deluxe {
  -webkit-box-shadow: var(--surface-card-shadow) !important;
  box-shadow: var(--surface-card-shadow) !important;
}
.uslugi-inner > .container::before,
.text-inner > .container::before,
.advantages-inner > .container::before {
  box-shadow: var(--surface-card-shadow) !important;
}
CSS;
$dir=$root.'/storage/legacy-card-surface/'.date('Ymd-His');
if(!mkdir($dir,0775,true)||!copy($file,$dir.'/style.css'))throw new RuntimeException('Backup failed');
file_put_contents($file,$css."\n".$rules."\n");echo $dir.PHP_EOL;
