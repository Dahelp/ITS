<?php
/** CLI-only retail price publication. Does not import products or supplier data. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$args = getopt('', ['root:', 'report-dir:', 'manifest:', 'apply']);
$root = realpath($args['root'] ?? dirname(__DIR__));
$dir = realpath($args['report-dir'] ?? __DIR__);
if (!$root || !$dir || str_starts_with($dir . '/', $root . '/public/')) throw new RuntimeException('Private report directory required');
$manifest = json_decode(file_get_contents($args['manifest'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
if (count($manifest) !== 55 || count(array_unique(array_column($manifest, 'article'))) !== 55) throw new RuntimeException('Expected 55 unique identities');
require $root . '/config/init.php';
require CONF . '/db_bootstrap.php';
$db = R::getPDO();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$fetch = static function ($sql, $params = []) use ($db) { $q=$db->prepare($sql); $q->execute($params); return $q->fetchAll(PDO::FETCH_ASSOC); };
$apply = isset($args['apply']);
$report = ['mode'=>$apply?'apply':'preview', 'products'=>[], 'changed_rows'=>0];
$db->beginTransaction();
try {
    $before=$fetch("SELECT * FROM product WHERE article LIKE 'TSEP-%' ORDER BY id FOR UPDATE");
    if (count($before)!==55) throw new RuntimeException('Chain count changed');
    $index=array_column($before,null,'article');
    $ids=implode(',',array_map('intval',array_column($before,'id')));
    $filters=$fetch("SELECT * FROM attribute_product WHERE product_id IN ($ids) ORDER BY product_id,attr_id FOR UPDATE");
    $plan=[];
    foreach ($manifest as $item) {
        $row=$index[$item['article']] ?? null;
        if (!$row || $row['alias']!==$item['alias']) throw new RuntimeException('Identity mismatch');
        $price=$item['price'];
        if ($price!==null && (!is_int($price) || $price<50 || $price%50!==0)) throw new RuntimeException('Invalid retail price');
        $expected=$price ?? 0;
        if ((float)$row['price']!==0.0 && (float)$row['price']!==(float)$expected) throw new RuntimeException('Unexpected existing retail price: '.$row['article']);
        if ((float)$row['price']!==(float)$expected) $plan[]=['id'=>$row['id'],'price'=>$expected];
        $report['products'][]=['article'=>$row['article'],'alias'=>$row['alias'],'price'=>$price];
    }
    if (count(array_filter($manifest,static fn($p)=>$p['price']!==null))!==47) throw new RuntimeException('Expected 47 priced products');
    if ($apply && $plan) {
        $backup=$dir.'/before.json';
        $handle=fopen($backup,'x');
        if (!$handle) throw new RuntimeException('Backup already exists');
        chmod($backup,0600);
        $data=json_encode(['products'=>$before,'filters'=>$filters],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        if (fwrite($handle,$data)!==strlen($data)) throw new RuntimeException('Backup write failed');
        fclose($handle);
        $q=$db->prepare('UPDATE product SET price=? WHERE id=?');
        foreach ($plan as $change) $q->execute([$change['price'],$change['id']]);
        $after=$fetch("SELECT * FROM product WHERE article LIKE 'TSEP-%' ORDER BY id");
        foreach ($after as $i=>$row) {
            $wanted=$before[$i];
            foreach ($manifest as $m) if ($m['article']===$row['article']) $wanted['price']=$m['price']??0;
            foreach ($row as $field=>$value) {
                if ($field==='price') { if ((float)$value!==(float)$wanted[$field]) throw new RuntimeException('Price verification failed'); }
                elseif ($value!==$wanted[$field]) throw new RuntimeException('Unexpected non-price field change: '.$field);
            }
        }
        if ($filters!==$fetch("SELECT * FROM attribute_product WHERE product_id IN ($ids) ORDER BY product_id,attr_id")) throw new RuntimeException('Filter links changed');
        $db->commit();
        $report['private_backup']=$backup;
    } else $db->rollBack();
    $report['changed_rows']=count($plan);
    $report['non_price_fields_unchanged']=true;
    $report['filters_unchanged']=true;
    $report['checked_at']=date(DATE_ATOM);
    file_put_contents($dir.'/'.($apply?'publication':'preview').'.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    echo json_encode(['changed_rows'=>count($plan),'priced'=>47,'on_request'=>8]).PHP_EOL;
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    file_put_contents($dir.'/failure.json',json_encode(['error'=>$e->getMessage()]));
    fwrite(STDERR,$e->getMessage().PHP_EOL); exit(1);
}
