<?php
$root=dirname(__DIR__);$stage=$root.'/outputs/yandex-release';
$baselinePath=$root.'/outputs/yandex-live-baseline.json';
$raw=is_file($baselinePath)?trim(file_get_contents($baselinePath),"\xEF\xBB\xBF \n\r\t"):'';
$baseline=$raw!==''?json_decode($raw,true,512,JSON_THROW_ON_ERROR):[];
foreach(['app/controllers/admin/PlaginsController.php','app/views/itscenter/admin/Plagins/yandex-tovars.php','app/services/SpecialTyreFeedService.php'] as $path){
 $dest=$stage.'/files/'.$path;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);
 $source=isset($baseline[$path])?base64_decode($baseline[$path]['content']):file_get_contents($root.'/'.$path);
 if(str_ends_with($path,'PlaginsController.php')){
  $start=strpos($source,'public function yandexTovarsAction()');$end=strpos($source,'/*YANDEX PRODUCTS*/',$start);
  if($start===false||$end===false)throw new RuntimeException('Controller marker missing');
  $source=substr($source,0,$start).<<<'CODE'
public function yandexTovarsAction(){
        $feeds=[]; $preview=null; $error=null;
        try {
            $feeds=\app\services\YandexProductsCabinet::snapshot(WWW);
            if (($_GET['preview']??'')==='1') {
                $preview=(new \app\services\SpecialTyreFeedService(WWW,'https://its-center.ru'))->diagnose();
            }
        } catch (\Throwable $e) {
            error_log('Yandex cabinet local diagnostics failed: '.get_class($e));
            $error='Локальная диагностика временно недоступна. Данные товаров и опубликованный XML не изменены.';
        }
        $this->setMeta('Яндекс Товары — состояние выгрузок');
        $this->set(compact('feeds','preview','error'));
    }

CODE
  .substr($source,$end);
 }
 if(str_ends_with($path,'SpecialTyreFeedService.php')){
  $start=strpos($source,'            if (!isset($categoryIds');$end=strpos($source,'            if (isset($seen[$sku]))',$start);
  if($start===false||$end===false)throw new RuntimeException('Feed marker missing');
  $source=substr($source,0,$start)."            if (\$this->exclusionReasons(\$offer,\$categoryIds,\$picture)) {\n                \$skipped++;\n                continue;\n            }\n".substr($source,$end);
  $pos=strpos($source,'    private function picture(');
  $methods=<<<'CODE'
    public function exclusionReasons(array $offer,array $categoryIds,?string $picture): array
    {
        $reasons=[];
        if (!isset($categoryIds[(int)$offer['category_id']])) $reasons[]='Категория не входит в этот фид';
        if (isset($offer['hide']) && $offer['hide']!=='show') $reasons[]='Товар скрыт';
        if (isset($offer['stock_status_id']) && (int)$offer['stock_status_id']!==1) $reasons[]='Статус наличия не разрешает выгрузку';
        if (array_key_exists('brand_exists',$offer) && !$offer['brand_exists']) $reasons[]='Не найден производитель';
        if ((float)$offer['feed_stock']<=0) $reasons[]='Нет свободного остатка';
        $price=round((float)$offer['feed_price'],2);
        if (!is_finite($price)||$price<=0) $reasons[]='Нет положительной цены';
        foreach(['sku'=>'артикул','name'=>'название','alias'=>'адрес страницы'] as $key=>$label) if(trim((string)($offer[$key]??''))==='') $reasons[]='Не заполнено: '.$label;
        if ($picture===null) $reasons[]='Нет подходящего фото: JPEG/PNG/GIF/WebP, от 450×450, до 10 МБ';
        return $reasons;
    }

    /** No file writes. Uses the same renderer and photo checks as publication. */
    public function diagnose(): array
    {
        $categories=\R::getAll("SELECT id,name FROM category WHERE parent_id=1 AND hide='show' ORDER BY id");
        $ids=[];foreach($categories as$c)if((int)$c['id']>1&&!in_array((int)$c['id'],[2,36],true))$ids[(int)$c['id']]=true;
        $stock=self::stock();$price=CatalogSelection::price();
        $rows=\R::getAll("SELECT product.*,brand.name AS vendor,brand.id AS brand_exists,($stock) AS feed_stock,($price) AS feed_price FROM product LEFT JOIN category ON category.id=product.category_id LEFT JOIN brand ON brand.id=product.brand_id WHERE category.parent_id=1 ORDER BY product.id");
        $blocked=null;
        try { $published=$this->generate(); }
        catch (\RuntimeException $e) {
            if (!str_starts_with($e->getMessage(),'Duplicate feed SKU: ')) throw $e;
            $blocked='Выгрузка заблокирована: повторяющийся артикул '.substr($e->getMessage(),20);
            $published=['xml'=>'<yml_catalog><shop><offers/></shop></yml_catalog>','offers'=>0];
        }
        $xml=new \DOMDocument();$xml->loadXML($published['xml'],LIBXML_NONET);
        $nodes=[];foreach($xml->getElementsByTagName('offer') as$node)$nodes[$node->getAttribute('id')]=$node;
        $result=[];$counts=[];
        foreach($rows as$row){
            $picture=$this->picture($row);$reasons=$this->exclusionReasons($row,$ids,$picture);
            $row['name']=CatalogNaming::apply((int)$row['id'],'feed',(string)$row['name']);
            if (!$reasons && $blocked!==null) $reasons[]=$blocked;
            $node=$nodes[trim((string)$row['sku'])]??null;
            foreach($reasons as$reason)$counts[$reason]=($counts[$reason]??0)+1;
            $result[]=['id'=>(int)$row['id'],'sku'=>$row['sku'],'name'=>$row['name'],'included'=>!$reasons&&$node!==null,'reasons'=>$reasons,'stock'=>$row['feed_stock'],'price'=>$row['feed_price'],'picture'=>$picture,'xml'=>$node?$xml->saveXML($node):null];
        }
        arsort($counts);
        return ['rows'=>$result,'counts'=>$counts,'offers'=>$published['offers'],'checked_at'=>date('Y-m-d H:i:s')];
    }

CODE;
  $source=substr($source,0,$pos).$methods.substr($source,$pos);
 }
 if(str_ends_with($path,'yandex-tovars.php'))$source=file_get_contents($root.'/app/views/itscenter/admin/Plagins/yandex-tovars.php');
 file_put_contents($dest,$source);
}
$path='app/services/YandexProductsCabinet.php';@mkdir(dirname($stage.'/files/'.$path),0775,true);copy($root.'/'.$path,$stage.'/files/'.$path);
if(!$baseline){echo "Prepared local draft; live baseline not yet available\n";exit;}
$manifest=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage.'/files',FilesystemIterator::SKIP_DOTS))as$f){$p=str_replace('\\','/',substr($f->getPathname(),strlen($stage.'/files/')));$manifest[$p]=['before'=>$baseline[$p]['sha256']??null,'after'=>hash_file('sha256',$f->getPathname())];}
file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
$installer=str_replace("'/ct08-manifest.json'","'/manifest.json'",file_get_contents(__DIR__.'/install_ct08.php'));file_put_contents($stage.'/install.php',$installer);
copy(__DIR__.'/yandex_cabinet_release_check.php',$stage.'/check.php');
$files=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS))as$f){$p=str_replace('\\','/',substr($f->getPathname(),strlen($stage)+1));$files[$p]=base64_encode(file_get_contents($f->getPathname()));}
$runner=<<<'CODE'
<?php
if(PHP_SAPI!=='cli')exit(1);$package='/home/s/shinaspec/catalog-yandex-20261113';if(file_exists($package.'/receipt.json'))throw new RuntimeException('Already applied');if(!is_dir($package))mkdir($package,0700,true);foreach(json_decode(base64_decode('__FILES__'),true)as$p=>$data){if(str_contains($p,'..'))throw new RuntimeException('Path');$dest=$package.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0700,true);file_put_contents($dest,base64_decode($data));}
passthru('/usr/local/bin/php8.2 '.escapeshellarg($package.'/check.php').' before',$code);if($code)throw new RuntimeException('Preflight failed');require$package.'/install.php';
passthru('/usr/local/bin/php8.2 '.escapeshellarg($package.'/check.php').' after',$code);if($code)throw new RuntimeException('Verification failed; inspect private backup before further changes');
CODE;
file_put_contents($root.'/outputs/yandex-runner.php',str_replace('__FILES__',base64_encode(json_encode($files)),$runner));echo "Packaged Yandex cabinet\n";
