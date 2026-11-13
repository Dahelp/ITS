<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/ct08-polish-release';
$latest=json_decode(file_get_contents($root.'/outputs/ct08-auto-publication/manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach(['app/views/itscenter/Category/view.php','public/css/catalog-facets.css','public/js/catalog-facets.js']as$path){
 $source=$root.'/outputs/ct08-polish-live/'.$path;
 if(!is_file($source))$source=$path==='app/views/itscenter/Category/view.php'?$root.'/outputs/ct08-unified-release/files/'.$path:$root.'/'.$path;
 $text=file_get_contents($source);if(hash('sha256',$text)!==$latest[$path]['after'])throw new RuntimeException('Baseline mismatch '.$path);
 if(str_ends_with($path,'view.php')){
  $change=function($s){
   $s=str_replace('$filterGroup = $filterLanding[\'group\'] ?? null;',"\$filterGroup = \$filterLanding['group'] ?? null;\n\$brandGroupTitle = mb_strtolower((string)(\$filterGroup->title ?? \$filterGroup->name ?? ''));\n\$isBrandLanding = mb_strpos(\$brandGroupTitle, 'производ') !== false || mb_strpos(\$brandGroupTitle, 'бренд') !== false;",$s);
   $anchor='<div class="catalog-top-block mb-4">';$offset=strpos($s,'if (($page ?? 1) <= 1 && $isFilterLanding');$pos=strpos($s,$anchor,$offset);if($pos===false)throw new RuntimeException('Brand hero anchor');$s=substr_replace($s,'<div class="catalog-top-block mb-4 <?= $isBrandLanding ? \'catalog-top-block--brand\' : \'\' ?>">',$pos,strlen($anchor));
   return str_replace(['class="swiper-container swiper2"','v=20261112g'],['class="swiper-container catalog-related-slider"','v=20261112h'],$s);
  };
  $new=$change($text);file_put_contents($root.'/'.$path,$change(file_get_contents($root.'/'.$path)));
 }elseif(str_ends_with($path,'.css')){
  $new=$text."\n.catalog-facets__option input:checked{background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath d='M4 10l4 4 8-8' fill='none' stroke='white' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E\");background-position:center;background-repeat:no-repeat;background-size:16px 16px}.catalog-facets__option input:checked:after{content:none}\n.catalog-top-block--brand{display:flex;align-items:center;gap:20px}.catalog-top-block--brand .catalog-top-image{flex:0 0 104px;width:104px;max-width:104px}.catalog-top-block--brand .catalog-top-image img{display:block;width:104px;height:80px;max-height:80px;object-fit:contain}.catalog-top-block--brand .catalog-top-text{flex:1;min-width:0}@media(max-width:575px){.catalog-top-block--brand{gap:14px}.catalog-top-block--brand .catalog-top-image{flex-basis:76px;width:76px;max-width:76px}.catalog-top-block--brand .catalog-top-image img{width:76px;height:64px}}@media(forced-colors:active){.catalog-facets__option input:checked{background-image:none}}\n";
  file_put_contents($root.'/'.$path,$new);
 }else{
  $function=<<<'JS'
  function initRelated() {
    if (typeof window.Swiper !== 'function') return;
    document.querySelectorAll('.catalog-related-slider').forEach(el => {
      if (el.swiper) return;
      const columns = Number.parseInt(getComputedStyle(el.closest('.catalog-facet-layout')).getPropertyValue('--catalog-columns'),10) === 3 ? 3 : 4;
      const wrapper = el.closest('.related_prod');
      new window.Swiper(el,{slidesPerView:1,slidesPerGroup:1,watchOverflow:true,spaceBetween:16,breakpoints:{576:{slidesPerView:2},992:{slidesPerView:3},1400:{slidesPerView:columns}},navigation:{nextEl:wrapper?.querySelector('.swiper-button-next-2'),prevEl:wrapper?.querySelector('.swiper-button-prev-2')}});
    });
  }

JS;
  $new=str_replace('  function init(saved) {',$function.'  function init(saved) {',$text);
  $new=str_replace('    current = state;','    current = state; initRelated();',$new);
  $start=strpos($new,"      if (typeof window.Swiper === 'function') document.querySelectorAll('.prdt .swiper2')");$end=strpos($new,"      document.dispatchEvent(new CustomEvent('catalog:updated'))",$start);if($start===false||$end===false)throw new RuntimeException('Related AJAX anchor');$new=substr_replace($new,'',$start,$end-$start);
  $new=str_replace('  init();',"  window.addEventListener('load', initRelated, {once:true});\n  init();",$new);file_put_contents($root.'/'.$path,$new);
 }
 $dest=$stage.'/files/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0777,true);file_put_contents($dest,$new);$manifest[$path]=['before'=>hash('sha256',$text),'after'=>hash('sha256',$new)];
}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo "3 presentation files prepared; live baseline hashes preserved\n";
