<?php
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__).'/config/init.php';
require CONF.'/db_bootstrap.php';
$dir=ROOT.'/storage/chain-menu-faq/'.date('Ymd-His');
if(!mkdir($dir,0775,true)) throw new RuntimeException('Backup directory');
$menu='app/views/itscenter/partials/catalog-menu.php';
$footer='app/views/itscenter/partials/footer.php';
$html=file_get_contents(ROOT.'/'.$menu);
$marker='<a class="dropdown-item submenu" href="category/diski">';
$block=<<<'HTML'
<a class="dropdown-item submenu" href="/catalog/cepi-protivoskolzheniya">Цепи противоскольжения</a>
                <div class="dropdown-menu-byp">
                    <div class="mbl-1">
                        <div class="menu-h2">Цепи на колёса под заказ</div>
                        <a class="dropdown-item-byp" href="/category/cepi-dlya-spectehniki">Цепи для спецтехники</a>
                        <a class="dropdown-item-byp" href="/category/cepi-dlya-vilochnyh-pogruzchikov">Цепи для вилочных погрузчиков</a>
                        <a class="dropdown-item-byp" href="/catalog/cepi-protivoskolzheniya">Все цепи противоскольжения</a>
                    </div>
                    <div class="mbl-3">
                        <div class="mbl-img"><img src="/images/category/baseimg/snow-chains-category-v1.webp" width="230" height="230" alt="Цепи противоскольжения для спецтехники и погрузчиков"></div>
                    </div>
                </div>
                
HTML;
if(!str_contains($html,'href="/catalog/cepi-protivoskolzheniya"')) {
 if(substr_count($html,$marker)!==1) throw new RuntimeException('Menu context');
 copy(ROOT.'/'.$menu,$dir.'/catalog-menu.php');
 file_put_contents(ROOT.'/'.$menu,str_replace($marker,$block.$marker,$html));
}
$html=file_get_contents(ROOT.'/'.$footer);
$marker='<li><a href="category/filtry">Фильтры</a></li>';
if(!str_contains($html,'href="/catalog/cepi-protivoskolzheniya"')) {
 if(substr_count($html,$marker)!==1) throw new RuntimeException('Footer context');
 copy(ROOT.'/'.$footer,$dir.'/footer.php');
 file_put_contents(ROOT.'/'.$footer,str_replace($marker,$marker."\n\t\t\t\t\t\t<li><a href=\"/catalog/cepi-protivoskolzheniya\">Цепи противоскольжения</a></li>",$html));
}
$filesOnly=in_array('--files-only',$argv,true);
$rows=$filesOnly ? [] : R::getAll('SELECT * FROM category WHERE id IN (43,44,45)');
$faqs=$filesOnly ? [] : R::getAll('SELECT * FROM category_faq WHERE category_id IN (43,44,45)');
file_put_contents($dir.'/database.json',json_encode(['categories'=>$rows,'faq'=>$faqs],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
$controller='app/controllers/CatalogController.php';
$html=file_get_contents(ROOT.'/'.$controller);
$old="$"."this->set(compact('category', 'breadcrumbs', 'cats', 'h1'));";
$new=<<<'PHP'
$categoryFaqRows = !empty($cats->id) ? \R::getAll("SELECT question, answer FROM category_faq WHERE category_id=? AND hide='show' AND question<>'' AND answer<>'' ORDER BY sort,id LIMIT 20", [(int)$cats->id]) : [];
        $jsonLdFaq = \app\helpers\SchemaHelper::renderFaqPageJsonLd($categoryFaqRows);
        $this->set(compact('category', 'breadcrumbs', 'cats', 'h1', 'categoryFaqRows', 'jsonLdFaq'));
PHP;
if(!str_contains($html,'$categoryFaqRows =')) {
 if(substr_count($html,$old)!==1)throw new RuntimeException('Controller context');
 copy(ROOT.'/'.$controller,$dir.'/CatalogController.php');
 file_put_contents(ROOT.'/'.$controller,str_replace($old,$new,$html));
}
$view='app/views/itscenter/Catalog/index.php';
$html=file_get_contents(ROOT.'/'.$view);
$marker='<!--product-end-->';
$block=<<<'PHP'
<?php if (!empty($categoryFaqRows)): ?>
<section class="container faq-block mb-4">
  <h2 class="h4 mb-3">Вопросы и ответы</h2>
  <?php foreach ($categoryFaqRows as $faq): ?>
  <div class="faq-item mb-3"><div class="faq-q mb-1"><strong><?= h($faq['question']) ?></strong></div><div class="faq-a"><?= nl2br(h($faq['answer'])) ?></div></div>
  <?php endforeach; ?>
</section>
<?php endif; ?>
PHP;
if(!str_contains($html,'$categoryFaqRows')) {
 if(substr_count($html,$marker)!==1)throw new RuntimeException('Catalog view context');
 copy(ROOT.'/'.$view,$dir.'/catalog-index.php');
 file_put_contents(ROOT.'/'.$view,str_replace($marker,$block."\n".$marker,$html));
}
$count=0;
foreach($rows as $row) {
 $content=$row['content'];
 if(!preg_match('~<h2>(?:Вопросы о выборе цепей|Частые вопросы)</h2>((?:<h3>.*?</h3><p>.*?</p>)+)~su',$content,$section))continue;
 preg_match_all('~<h3>(.*?)</h3><p>(.*?)</p>~su',$section[1],$pairs,PREG_SET_ORDER);
 if(!$pairs)throw new RuntimeException('FAQ parsing');
 foreach($pairs as $i=>$pair) {
  $q=html_entity_decode(strip_tags($pair[1]),ENT_QUOTES,'UTF-8');
  $a=html_entity_decode(strip_tags($pair[2]),ENT_QUOTES,'UTF-8');
  if(!R::getCell('SELECT id FROM category_faq WHERE category_id=? AND question=?',[$row['id'],$q]))
   R::exec("INSERT INTO category_faq(category_id,question,answer,sort,hide) VALUES(?,?,?,?,'show')",[$row['id'],$q,$a,($i+1)*10]);
  $count++;
 }
 R::exec('UPDATE category SET content=? WHERE id=?',[str_replace($section[0],'',$content),$row['id']]);
}
echo json_encode(['backup'=>$dir,'faq_moved'=>$count],JSON_UNESCAPED_SLASHES).PHP_EOL;
