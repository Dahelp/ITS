<?php
declare(strict_types=1);
/** Stage 1: SELECT-only catalogue inspection. Never boot the mutable application. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = getenv('CATALOG_AUDIT_ROOT') ?: dirname(__DIR__);
$config = require $root . '/config/config_db.php';
$pdo = new PDO($config['dsn'], $config['user'], $config['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('SET SESSION TRANSACTION READ ONLY');
$pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
$tables = ['product','brand','category','attribute','attribute_group','attribute_value','attribute_category','attribute_product','product_attribute','plagins_inseo','attribute_value_category_canonical','gallery','modification','cron'];
$result = ['generated_at'=>date(DATE_ATOM),'scope'=>getenv('CATALOG_AUDIT_ROOT') ? 'Production catalogue; read-only transaction; catalogue tables only' : 'Configured local database; read-only transaction; catalogue tables only','server_version'=>$pdo->getAttribute(PDO::ATTR_SERVER_VERSION),'schema'=>[]];
foreach ($tables as $table) {
    try {
        $result['schema'][$table] = ['columns'=>$pdo->query('SHOW FULL COLUMNS FROM `'.$table.'`')->fetchAll(), 'indexes'=>$pdo->query('SHOW INDEX FROM `'.$table.'`')->fetchAll(), 'rows'=>(int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn()];
    } catch (PDOException $e) { $result['schema'][$table] = ['unavailable'=>true]; }
}
$result['table_engines'] = $pdo->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('product','product_attribute','attribute_product','attribute','attribute_value','category')")->fetchAll();
$productFields = ['id','category_id','brand_id','article','sku','seo_h1','name','model','alias','content','short_description','date','data_edit_price','data_edit_all','price','stock_status_id','quantity','rest','reserve','stock_adjustment','hide','weight','volume','title','description','keywords','img','unload_img','size_canonical','search_type','search_subtype','unit'];
$existing = array_column($result['schema']['product']['columns'], 'Field');
$productFields = array_values(array_intersect($productFields, $existing));
$result['products'] = $pdo->query('SELECT `'.implode('`,`',$productFields).'` FROM product ORDER BY id')->fetchAll();
foreach ($result['products'] as &$product) {
    foreach (['content','short_description'] as $field) {
        $text = (string)($product[$field] ?? '');
        $product[$field.'_plain_length'] = function_exists('mb_strlen') ? mb_strlen(trim(strip_tags($text)), 'UTF-8') : strlen(trim(strip_tags($text)));
        preg_match_all('/\{([^}]+)\}/u', $text, $matches);
        $product[$field.'_placeholders'] = array_values(array_unique($matches[1]));
        unset($product[$field]);
    }
}
unset($product);
foreach (['brand','category','attribute','attribute_group','attribute_category','attribute_product','product_attribute','gallery','modification','plagins_inseo','attribute_value_category_canonical','attribute_value'] as $table) {
    if (isset($result['schema'][$table]['unavailable'])) continue;
    // Catalog text only; account, customer, payment and credentials tables are never read.
    $fields = array_column($result['schema'][$table]['columns'],'Field');
    $fields = array_values(array_diff($fields, ['content','top_content','seo_content','note']));
    $result[$table] = $pdo->query('SELECT `'.implode('`,`',$fields).'` FROM `'.$table.'`')->fetchAll();
}
$result['inseo_content_presence'] = $pdo->query("SELECT id, CHAR_LENGTH(TRIM(COALESCE(content,''))) AS content_length FROM plagins_inseo")->fetchAll();
$result['cron'] = $pdo->query("SELECT id,name,url_params,url_download,date_update,hide FROM cron WHERE url_params LIKE '%yml%' ORDER BY id")->fetchAll();
$pdo->rollBack();
$result['image_files'] = [];
foreach (array_merge(array_map(static fn($p)=>['folder'=>'baseimg','name'=>$p['img']],$result['products']), array_map(static fn($p)=>['folder'=>'unload','name'=>$p['unload_img']],$result['products']),array_map(static fn($p)=>['folder'=>'gallery','name'=>$p['img']],$result['gallery'])) as $image) {
    $name = trim((string)$image['name']);
    $key = $image['folder'].'/'.$name;
    if ($name === '' || isset($result['image_files'][$key])) continue;
    if (basename($name) !== $name || strpos($name,'\\') !== false) { $result['image_files'][$key] = ['invalid_path'=>true]; continue; }
    $path = $root.'/public/images/product/'.$key;
    $exists = is_file($path);
    $size = $exists ? @getimagesize($path) : false;
    $result['image_files'][$key] = ['exists'=>$exists,'width'=>$size[0] ?? null,'height'=>$size[1] ?? null,'bytes'=>$exists ? filesize($path) : null];
}
$result['source_files'] = [];
foreach (['app/models/admin/Product.php','app/controllers/admin/ProductController.php','app/controllers/CategoryController.php','app/widgets/filter/Filter.php','app/widgets/filter/filter_tpl.php','app/controllers/ProductController.php','app/views/itscenter/Product/view.php','app/services/ProductAnalytics.php','app/services/CatalogSelection.php','app/services/SpecialTyreFeedService.php','vendor/ishop/core/App.php','app/views/itscenter/Cron/ymlfid-specshiny.php','app/views/itscenter/Cron/ymlfid-kvadro.php','app/views/itscenter/Cron/ymlfid-filtry.php','app/controllers/admin/PlaginsController.php','app/models/admin/PlaginsYandexTovars.php','app/views/itscenter/admin/Plagins/yandex-tovars.php'] as $file) {
    if (!is_file($root.'/'.$file)) continue;
    $code = file_get_contents($root.'/'.$file);
    $result['source_files'][$file] = ['sha256'=>hash('sha256',$code)];
    // Do not export authentication material from the unfinished connector.
    if ($file !== 'app/models/admin/PlaginsYandexTovars.php') $result['source_files'][$file]['text'] = $code;
    else {
        $result['source_files'][$file]['hardcoded_oauth'] = preg_match('/OAuth\s+[^\s\x27\x22]+/', $code) === 1;
        preg_match_all('~https://yandex\.ru/products/api/[^\s\x27\x22]+~', $code, $endpoints);
        preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $code, $methods);
        $result['source_files'][$file]['endpoints'] = array_values(array_unique($endpoints[0]));
        $result['source_files'][$file]['methods'] = $methods[1];
    }
}
echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR), "\n";
