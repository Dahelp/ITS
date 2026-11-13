<?php
// Read only supplier crawler. Restrict traversal to the two requested categories.
$root = dirname(__DIR__);
$out = $root.'/storage/tsep-chains-20261029';
@mkdir($out.'/source', 0775, true);
@mkdir($out.'/images', 0775, true);
$base = 'https://tsep-nn.ru/';
$paths = ['special'=>'catalog/cepi-protivoskolzheniya/traktory-i-spectehnika/', 'forklift'=>'catalog/cepi-protivoskolzheniya/pogruzchiki/'];
function fetchChainSource($url, $file) {
    if (is_file($file)) return file_get_contents($file);
    $c = curl_init($url);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>45,CURLOPT_PROXY=>'',CURLOPT_USERAGENT=>'ITS-Center catalog preparation']);
    $html = curl_exec($c); $status = curl_getinfo($c,CURLINFO_HTTP_CODE);
    if ($status !== 200 || !$html) throw new RuntimeException("HTTP $status: $url");
    file_put_contents($file,$html); return $html;
}
function chainDom($html) { $d = new DOMDocument(); @$d->loadHTML('<?xml encoding="utf-8" ?>'.$html); return [$d,new DOMXPath($d)]; }
function chainText($node) { return trim(preg_replace('/[\s\x{00a0}]+/u',' ',$node->textContent)); }
$queue=[]; $seen=[]; $products=[];
foreach ($paths as $group=>$path) $queue[$path]=$group;
while ($queue) {
    $path=array_key_first($queue); $group=$queue[$path]; unset($queue[$path]);
    if(isset($seen[$path])) continue; $seen[$path]=true;
    $html=fetchChainSource($base.$path,$out.'/source/'.($path===$paths[$group]?$group:basename($path)).'.html');
    [$d,$x]=chainDom($html);
    foreach($x->query('//a[@href]') as $a) {
        $p=ltrim($a->getAttribute('href'),'/');
        if(strpos($p,$paths[$group])===0 && preg_match('/\.html$/',$p) && !isset($seen[$p])) $queue[$p]=$group;
    }
    if($path===$paths[$group]) continue;
    $name=chainText($x->query('//h1')->item(0)); $options=[]; $sections=[]; $tables=[]; $images=[];
    foreach($x->query('//*[contains(@class,"options__item")]') as $opt) {
        $label=$x->query('.//dt',$opt)->item(0);
        $active=$x->query('.//a[contains(@class,"link--active")]',$opt)->item(0);
        if($label && $active) $options[chainText($label)]=chainText($active);
    }
    foreach($x->query('//section[contains(@class,"product__section")]') as $section) {
        $heading=$x->query('.//h2',$section)->item(0); if(!$heading) continue;
        $title=chainText($heading); if($title==='Варианты исполнения') continue;
        $items=[]; foreach($x->query('.//li[contains(@class,"product__list-item")]|.//figcaption|.//p',$section) as $li) { $v=chainText($li); if($v!=='') $items[]=$v; }
        if($items) $sections[$title]=$items;
        foreach($x->query('.//table',$section) as $table) {
            $rows=[];
            foreach($x->query('.//tr',$table) as $tr) { $cells=[]; foreach($x->query('./th|./td',$tr) as $td) $cells[]=chainText($td); if($cells) $rows[]=$cells; }
            $tables[]=['section'=>$title,'rows'=>$rows];
        }
    }
    foreach($x->query('//img[contains(@class,"main-slider-image")]|//a[contains(@class,"design__image-link")]|//img[contains(@class,"scheme__image")]') as $im) {
        $src=trim($im->getAttribute($im->nodeName==='a'?'href':'src'));
        if($im->nodeName==='img' && $im->hasAttribute('srcset')) { $src=preg_split('/\s+/',trim($im->getAttribute('srcset')))[0]; }
        $url=$base.ltrim($src,'/'); $ext=pathinfo(parse_url($url,PHP_URL_PATH),PATHINFO_EXTENSION);
        $file='tsep-'.substr(sha1($url),0,16).'.'.$ext;
        // Images are deliberately not downloaded: use the site's placeholder.
        $images[]=['source'=>$url,'file'=>$file,'role'=>strpos($im->getAttribute('class'),'scheme')!==false?'scheme':($im->nodeName==='a'?'fastener':'product')];
    }
    $intro=[]; foreach($x->query('//*[contains(@class,"product__description-text")]') as $p) $intro[]=chainText($p);
    $products[]=['source'=>$base.$path,'group'=>$group,'series'=>$name,'intro'=>$intro,'options'=>$options,'sections'=>$sections,'tables'=>$tables,'images'=>[]];
    echo $group.' | '.$name.' | '.count($tables).' tables'.PHP_EOL;
}
file_put_contents($out.'/catalog.json',json_encode(['collected_at'=>date(DATE_ATOM),'scope'=>$paths,'series'=>$products],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo count($products)." series saved\n";
