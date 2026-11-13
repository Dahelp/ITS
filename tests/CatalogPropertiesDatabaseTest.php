<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogProperties as P;
use app\services\CatalogPropertySchema as Schema;
if(PHP_OS_FAMILY!=='Windows')throw new RuntimeException('Local-only integration test.');
$config=require dirname(__DIR__).'/config/config_db.php';
if(!preg_match('/host=(localhost|127\.0\.0\.1)(;|$)/',$config['dsn']))throw new RuntimeException('Local DB required.');
$db=new PDO($config['dsn'],$config['user'],$config['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function dbCheck($v,$m){if(!$v)throw new RuntimeException($m);}
function dbReject(callable$f,string$m){try{$f();}catch(InvalidArgumentException|RuntimeException$e){return;}throw new RuntimeException('Not rejected: '.$m);}
Schema::install($db);Schema::install($db);$service=new P($db);dbCheck($service->installed(),'Installed');
$ids=[1,357];foreach($ids as$id)dbCheck(!$db->query('SELECT product_id FROM catalog_product_profile WHERE product_id='.$id)->fetchColumn(),'Existing pilot data: refuse to overwrite '.$id);
$fingerprint=static function()use($db){$parts=[];foreach(['product','product_attribute','attribute_product','plagins_inseo']as$table)$parts[$table]=$db->query('SELECT * FROM '.$table)->fetchAll();return hash('sha256',json_encode($parts,JSON_THROW_ON_ERROR));};
$baseline=$fingerprint();$revisionBaseline=(int)$db->query('SELECT COUNT(*) FROM catalog_property_revision')->fetchColumn();
$known=static fn($v)=>['state'=>'known','verification'=>'confirmed','value'=>$v,'source_ref'=>"Документ O'Reilly, стр. 1"];
try{
    $payload=['type'=>'tyre_solid','values'=>['tire_size'=>$known('12-16.5'),'tire_type'=>$known(['solid']),'rim_diameter_in'=>$known('16.5'),'non_marking'=>$known('0'),'manufacturer'=>$known("O'Reilly <script>literal</script>")],'sizes'=>[['size'=>'33x12-20','role'=>'equivalent','verification'=>'confirmed','source_ref'=>'Владелец, только этот товар','conditions'=>'']]];
    dbCheck($service->save(357,0,$payload,1)===1,'First save');
    $read=$service->read(357);dbCheck($service->readAllProfiles()[357]==$read,'Batch read equals individual profile');dbCheck($read['values']['tire_size']['value']==='12-16.5','Primary size retained');
    dbCheck((float)$read['values']['rim_diameter_in']['value']===16.5,'Primary diameter retained');
    dbCheck($read['sizes'][0]['size']==='33x12-20','Equivalent persisted');
    dbCheck($read['values']['tire_type']['value']===['solid'],'Typed option roundtrip');
    dbCheck($read['values']['manufacturer']['value']==="O'Reilly <script>literal</script>",'Prepared SQL preserves quotes');
    dbCheck((string)$read['values']['non_marking']['value']==='0','False roundtrip');
    dbReject(fn()=>$service->save(357,0,$payload,1),'Concurrent edit');
    dbCheck(count($service->history(357))===1,'Stale edit leaves no revision');
    dbReject(fn()=>$service->save(1,0,['type'=>'tyre_solid','values'=>['inflation_pressure_kpa'=>$known('800')]],1),'Invalid save rollback');
    dbCheck(!$db->query('SELECT product_id FROM catalog_product_profile WHERE product_id=1')->fetchColumn(),'Failed initial save rolls back profile');
    $valueId=$db->query("SELECT v.id FROM catalog_product_property v JOIN catalog_property p ON p.id=v.property_id WHERE v.product_id=357 AND p.code='tire_type'")->fetchColumn();
    $propertyId=$db->query("SELECT id FROM catalog_property WHERE code='tire_type'")->fetchColumn();
    $foreignOption=$db->query("SELECT o.id FROM catalog_property_option o JOIN catalog_property p ON p.id=o.property_id WHERE p.code='construction' LIMIT 1")->fetchColumn();
    $rejected=false;try{$q=$db->prepare('INSERT INTO catalog_product_property_option(value_id,property_id,option_id) VALUES(?,?,?)');$q->execute([$valueId,$propertyId,$foreignOption]);}catch(PDOException$e){$rejected=$e->getCode()==='23000';}dbCheck($rejected,'Composite FK rejects foreign-property option');
    $revision=(int)$service->history(357)[0]['id'];dbCheck($service->restore(357,$revision,1,1)===2,'Restore creates new version');
    $read=$service->read(357);dbCheck($read['type']==='unclassified'&&$read['sizes']===[]&&$read['values']['tire_size']['state']==='unknown','Restore clears fields that were originally absent');
    dbCheck(count($service->history(357))===2,'Restore is audited');
    dbReject(fn()=>$service->restore(1,$revision,0,1),'Cannot restore another product revision');
    $service->save(1,0,['type'=>'tyre_pneumatic','values'=>['ply_rating'=>$known('12')]],1);
    $integerRead=$service->read(1);dbCheck($integerRead['values']['ply_rating']['value']==='12','Integer DECIMAL storage reads as integer');
    dbCheck($service->readAllProfiles()[1]['values']['ply_rating']['value']==='12','Batch reader preserves integer format');
    $service->save(1,1,$integerRead,1);dbCheck($service->read(1)['version']===2,'Integer profile can be resaved');
    dbCheck($fingerprint()===$baseline,'Legacy product, SEO, filter and attributes unchanged');
    echo "Local DB: typed options, FK, quotes, false/unknown, atomic rollback, concurrency, history, restore and legacy isolation OK\n";
}finally{
    $db->beginTransaction();try{
        foreach($ids as$id){$q=$db->prepare('DELETE o FROM catalog_product_property_option o JOIN catalog_product_property v ON v.id=o.value_id WHERE v.product_id=?');$q->execute([$id]);foreach(['catalog_product_size','catalog_tire_service_rating','catalog_product_property','catalog_property_revision','catalog_product_profile']as$table){$q=$db->prepare('DELETE FROM '.$table.' WHERE product_id=?');$q->execute([$id]);}}
        $db->commit();
    }catch(Throwable$e){$db->rollBack();throw$e;}
}
dbCheck($fingerprint()===$baseline,'Legacy unchanged after cleanup');
dbCheck((int)$db->query('SELECT COUNT(*) FROM catalog_property_revision')->fetchColumn()===$revisionBaseline,'No test revisions left');
