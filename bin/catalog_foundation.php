<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);require$root.'/vendor/autoload.php';
$command=$argv[1]??'help';
if($command==='pilot'){
    $path=$argv[2]??'';if(!is_file($path))throw new RuntimeException('Укажите JSON-снимок read-only аудита.');
    $data=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$products=$data['products'];$attrs=$data['product_attribute'];$values=array_column($data['attribute_value'],null,'id');
    $selected=[];
    $pick=static function(string$reason,callable$test)use(&$selected,$products,$attrs):void{foreach($products as$p){if(isset($selected[$p['id']]))continue;$a=array_column(array_filter($attrs,static fn($r)=>(int)$r['product_id']===(int)$p['id']),'attribute_text','attribute_id');if($test($p,$a)){$selected[$p['id']]=['product'=>$p,'reason'=>$reason];return;}}throw new RuntimeException('Не найден пилот: '.$reason);};
    $pick('Взаимозаменяемый размер: пример владельца',static fn($p,$a)=>$p['sku']==='IT000007251');
    $pick('Пневматика с индексами',static fn($p,$a)=>$p['sku']==='IT000012032');
    $pick('Шина без остатка',static fn($p,$a)=>(int)$p['quantity']===0&&(int)$p['category_id']===18&&isset($a[61])&&str_contains($a[61],'пневмат'));
    $pick('Немаркая цельнолитая',static fn($p,$a)=>(int)$p['category_id']===18&&str_contains($p['name'],' NM '));
    $pick('Цельнолитая без NM',static fn($p,$a)=>str_contains($a[61]??'','цельнолит')&&!str_contains($p['name'],' NM ')&&(int)$p['category_id']===18);
    $pick('Радиальная OTR',static fn($p,$a)=>(int)$p['category_id']===20&&mb_strtolower($a[63]??'')==='радиальная');
    $pick('Звёздная норма',static fn($p,$a)=>preg_match('/^\*{1,3}$/D',$a[5]??''));
    $pick('Грузовая: составные индексы',static fn($p,$a)=>(int)$p['category_id']===36&&str_contains($a[70]??'','/'));
    $pick('Шина: неполные индексы',static fn($p,$a)=>(int)$p['category_id']===19&&!isset($a[70]));
    $pick('Камера: несколько совместимых размеров',static fn($p,$a)=>(int)$p['category_id']===31&&(str_contains($a[4]??'',',')||str_contains($a[67]??'',',')));
    $pick('Диск: ET = 0',static fn($p,$a)=>in_array((int)$p['category_id'],[26,27,28],true)&&isset($a[58])&&trim($a[58])==='0');
    $pick('Диск: отрицательный ET',static fn($p,$a)=>in_array((int)$p['category_id'],[26,27,28],true)&&isset($a[58])&&(float)$a[58]<0);
    $result=['mode'=>'dry_run_only','source_generated_at'=>$data['generated_at'],'generated_at'=>date(DATE_ATOM),'items'=>[]];
    foreach($selected as$id=>$item){$a=array_values(array_filter($attrs,static fn($r)=>(int)$r['product_id']===$id));$f=[];foreach($data['attribute_product']as$r)if((int)$r['product_id']===$id&&isset($values[$r['attr_id']]))$f[]=$values[$r['attr_id']];$entry=\app\services\CatalogLegacyPreview::forProduct($item['product'],$a,$f);$entry['reason']=$item['reason'];$result['items'][]=$entry;}
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";exit;
}
if($command==='install'&&in_array('--apply',$argv,true)){
    if(PHP_OS_FAMILY!=='Windows'&&!in_array('--allow-production',$argv,true))throw new RuntimeException('Установка на сервер требует отдельного разрешения и --allow-production.');
    $c=require$root.'/config/config_db.php';$db=new PDO($c['dsn'],$c['user'],$c['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    \app\services\CatalogPropertySchema::install($db);echo "Изолированная схема 2А установлена. Товары и старые таблицы не изменены.\n";exit;
}
echo "Stage 2A (no automatic installation):\n  install --apply  (local schema only)\n  pilot snapshot.json  (JSON to stdout; no DB connection)\n";
