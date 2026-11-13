<?php
namespace app\services;

/** Yandex Direct: industrial tyres with free stock and a valid product photo. */
class SpecialTyreFeedService
{
    /** API sync stores free rest in quantity, including stock adjustment. Reserve is informational. */
    public static function stock(): string
    {
        return 'GREATEST(0,COALESCE(product.quantity,0)+COALESCE((SELECT SUM(m.quantity) FROM modification m WHERE m.product_id=product.id),0))';
    }
    public function __construct(private string $publicRoot, private string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function generate(): array
    {
        $db=\R::getPDO();$ownsSnapshot=!$db->inTransaction();
        if($ownsSnapshot){$db->exec('SET TRANSACTION READ ONLY');$db->beginTransaction();}
        try {
        $categories = \R::getAll("SELECT id, name FROM category WHERE parent_id = 1 AND hide = 'show' ORDER BY id");
        $stock = self::stock();
        $price = CatalogSelection::price();
        $offers = \R::getAll("SELECT product.*, brand.name AS vendor, ($stock) AS feed_stock, ($price) AS feed_price
            FROM product JOIN category ON category.id = product.category_id
            JOIN brand ON brand.id = product.brand_id
            WHERE category.parent_id = 1 AND category.hide = 'show'
                AND product.hide = 'show' AND product.stock_status_id = 1
                AND TRIM(product.sku) <> '' AND TRIM(product.name) <> '' AND TRIM(product.alias) <> ''
                AND ($stock) > 0 AND ($price) > 0 ORDER BY product.id");
        $params = \R::getAll("SELECT pa.product_id, pa.attribute_id, pa.attribute_group_id, a.attribute_name, a.url_params, pa.attribute_text
            FROM product_attribute pa JOIN attribute a ON a.id = pa.attribute_id
            JOIN product p ON p.id = pa.product_id JOIN category c ON c.id = p.category_id
            WHERE c.parent_id = 1 AND c.hide = 'show' AND p.hide = 'show'
            ORDER BY pa.product_id, a.attribute_name");
        $templates=[];foreach(\R::getAll("SELECT id,category_id,name,title,description,content FROM plagins_inseo WHERE tip='product' AND hide='show' ORDER BY id")as$template)$templates[(int)$template['category_id']][]=$template;
        $seoAttributes=[];foreach($params as$param)if(trim((string)($param['url_params']??''))!=='')$seoAttributes[(int)$param['product_id']][$param['url_params']]=$param['attribute_text'];
        foreach($offers as&$offer){
            $offer['name']=CatalogNaming::apply((int)$offer['id'],'feed',(string)$offer['name']);
            $seo=CatalogSeoAudit::assess($offer,$templates[(int)$offer['category_id']]??[],$seoAttributes[(int)$offer['id']]??[]);
            $content=$seo['fields']['content'];
            $offer['feed_description']=$seo['ambiguous']&&$content['source']==='inseo'?'':$content['value'];
        }unset($offer);
        $result=$this->render($categories, $offers, $params, CatalogPublishedAttributes::load($db));
        if($ownsSnapshot)$db->commit();return$result;
        } catch(\Throwable $e){if($ownsSnapshot&&$db->inTransaction())$db->rollBack();throw$e;}
    }

    public function render(array $categories, array $offers, array $params = [], array $profiles = []): array
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $add = static function (\DOMNode $parent, string $name, $value = null) use ($doc): \DOMElement {
            $node = $doc->createElement($name);
            if ($value !== null) {
                $clean = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string)$value);
                $node->appendChild($doc->createTextNode($clean ?? ''));
            }
            $parent->appendChild($node);
            return $node;
        };
        $root = $add($doc, 'yml_catalog');
        $root->setAttribute('date', date('Y-m-d H:i'));
        $shop = $add($root, 'shop');
        $add($shop, 'name', 'ИТС-Центр');
        $add($shop, 'company', 'ООО ИТС-Центр');
        $add($shop, 'url', $this->baseUrl);
        $currency = $add($add($shop, 'currencies'), 'currency');
        $currency->setAttribute('id', 'RUR');
        $currency->setAttribute('rate', '1');
        $categoryList = $add($shop, 'categories');
        $parent = $add($categoryList, 'category', 'Шины для спецтехники');
        $parent->setAttribute('id', '1');
        $categoryIds = [];
        foreach ($categories as $category) {
            $id = (int)$category['id'];
            if ($id <= 1 || in_array($id, [2, 36], true)) continue;
            $node = $add($categoryList, 'category', $category['name']);
            $node->setAttribute('id', (string)$id);
            $node->setAttribute('parentId', '1');
            $categoryIds[$id] = true;
        }
        $byProduct = [];
        foreach ($params as $param) $byProduct[(int)$param['product_id']][] = $param;
        $offerList = $add($shop, 'offers');
        $count = $skipped = 0;
        $seen = [];
        foreach ($offers as $offer) {
            $projection = CatalogPublishedAttributes::project($byProduct[(int)$offer['id']] ?? [], $profiles[(int)$offer['id']] ?? null);
            foreach ($projection['meta'] as $field=>$value) $offer[$field]=$value;
            $sku = trim((string)$offer['sku']);
            $price = round((float)$offer['feed_price'], 2);
            $picture = $this->picture($offer);
            if ($this->exclusionReasons($offer,$categoryIds,$picture)) {
                $skipped++;
                continue;
            }
            if (isset($seen[$sku])) throw new \RuntimeException('Duplicate feed SKU: ' . $sku);
            $seen[$sku] = true;
            $node = $add($offerList, 'offer');
            $node->setAttribute('id', $sku);
            $node->setAttribute('available', 'true');
            $add($node, 'url', $this->baseUrl . '/product/' . str_replace(' ', '%20', $offer['alias']));
            $add($node, 'price', number_format($price, 2, '.', ''));
            $old = $price < (float)$offer['price'] ? (float)$offer['price'] : (float)($offer['price_rrs'] ?? 0);
            if ($old > $price) $add($node, 'oldprice', number_format($old, 2, '.', ''));
            $add($node, 'currencyId', 'RUR');
            $add($node, 'categoryId', (int)$offer['category_id']);
            $add($node, 'picture', $picture);
            $add($node, 'name', $offer['name']);
            $description=array_key_exists('feed_description',$offer)?(string)$offer['feed_description']:self::description((string)($offer['content']??''));
            if($description!==''&&mb_strlen($description)<=3000)$add($node,'description',$description);
            foreach (['model', 'vendor'] as $field) {
                if (trim((string)($offer[$field] ?? '')) !== '') $add($node, $field, $offer[$field]);
            }
            $attributes = [];
            foreach ($byProduct[(int)$offer['id']] ?? [] as $param) {
                if (trim((string)$param['attribute_name']) === '' || trim((string)$param['attribute_text']) === '') continue;
                $attributes[trim((string)$param['attribute_name'])] = trim((string)$param['attribute_text']);
            }
            $attributes = CatalogPublishedAttributes::feed($attributes, $projection);
            foreach ($attributes as $name => $value) {
                $p = $add($node, 'param', $value);
                $p->setAttribute('name', $name);
            }
            $count++;
        }
        return ['xml' => $doc->saveXML(), 'offers' => $count, 'skipped' => $skipped];
    }

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
        if ($picture===null) $reasons[]='Нет подходящего фото: JPEG/PNG/WebP, от 450×450, до 10 МБ';
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
    /** Export only existing product text; do not invent or truncate descriptions. */
    public static function description(string $html): string
    {
        $html=preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is','',$html)??'';
        $html=preg_replace('~<(?:br\b[^>]*|/p|/div|/li|/h[1-6])>~i',' ',$html)??'';
        return trim(preg_replace('/[\s\x{00a0}]+/u',' ',html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8'))??'');
    }
    private function picture(array $offer): ?string
    {
        foreach (['unload' => 'unload_img', 'baseimg' => 'img'] as $directory => $field) {
            $name = trim((string)($offer[$field] ?? ''));
            if ($name === '' || $name !== basename($name) || str_contains($name, '\\')) continue;
            $folder = realpath($this->publicRoot . '/images/product/' . $directory);
            $path = $folder ? realpath($folder . '/' . $name) : false;
            if (!$path || dirname($path) !== $folder || !is_file($path) || !is_readable($path)) continue;
            $size = @getimagesize($path);
            if (!$size || $size[0] < 450 || $size[1] < 450 || filesize($path) > 10 * 1024 * 1024
                || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) continue;
            return $this->baseUrl . '/images/product/' . $directory . '/' . rawurlencode($name);
        }
        return null;
    }

    public static function writeAtomically(string $path, string $xml): void
    {
        $doc = new \DOMDocument();
        if (!$doc->loadXML($xml, LIBXML_NONET)) throw new \RuntimeException('Invalid feed XML');
        $temporary = tempnam(dirname($path), '.special-tyre-feed-');
        if ($temporary === false) throw new \RuntimeException('Cannot create temporary feed');
        try {
            if (file_put_contents($temporary, $xml, LOCK_EX) !== strlen($xml) || !chmod($temporary, 0644)
                || !rename($temporary, $path)) throw new \RuntimeException('Cannot publish feed');
        } finally {
            if (is_file($temporary)) unlink($temporary);
        }
    }
}
