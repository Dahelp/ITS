<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogDictionary as D;use app\services\CatalogProperties as P;
$c=require dirname(__DIR__).'/config/config_db.php';if(PHP_OS_FAMILY!=='Windows'||!preg_match('/host=(localhost|127\.0\.0\.1)(;|$)/',$c['dsn']))throw new RuntimeException('Local DB only');$db=new PDO($c['dsn'],$c['user'],$c['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
function dictCheck($ok,$msg){if(!$ok)throw new RuntimeException($msg);}function dictReject(callable$f,$msg){try{$f();}catch(InvalidArgumentException|RuntimeException$e){return;}throw new RuntimeException($msg);}
$fingerprint=static function()use($db){$o=[];foreach(['product','product_attribute','attribute_product','plagins_inseo','catalog_product_profile','catalog_product_property','catalog_product_property_option','catalog_product_size','catalog_tire_service_rating','catalog_property_revision']as$t)$o[$t]=hash('sha256',json_encode($db->query('SELECT * FROM '.$t)->fetchAll(PDO::FETCH_ASSOC)));return$o;};
$before=$fingerprint();D::install($db);$d=new D($db);P::useDictionary($db);$initial=$d->version();$code='test_dictionary_'.bin2hex(random_bytes(4));$rules=array_fill_keys(array_keys(P::types()),'not_applicable');$rules['tyre_solid']='optional';$rules['unclassified']='optional';$input=['create'=>'1','code'=>$code,'label'=>'Проверка <script>','kind'=>'option','unit'=>'','note'=>'Test','rules'=>$rules,'new_option_code'=>'a','new_option_label'=>'Первый'];
try{
 $d->save($input,$initial,1);dictCheck(isset(P::definitions()[$code]['options']['a']),'New option not available');dictCheck(P::applicability($code,'tyre_solid')==='optional','Rules not loaded');
 dictReject(fn()=>$d->save($input,$initial,1),'Stale version accepted');dictReject(fn()=>$d->save($input,$d->version(),1),'Duplicate code accepted');
 $input['create']='0';$input['new_option_code']='b';$input['new_option_label']='Второй';$input['option_labels']=['a'=>'Первый переименован'];$input['option_archived']=['a'=>'1'];$d->save($input,$d->version(),1);
 dictCheck(P::definitions()[$code]['options']['a']==='Первый переименован'&&P::definitions()[$code]['archived_options']['a'],'Archive metadata wrong');
 $service=new P($db);$saved=$service->read(357);$payload=['type'=>'tyre_solid','values'=>[$code=>['state'=>'known','verification'=>'candidate','value'=>['a'],'source_ref'=>'test']]];
 dictReject(fn()=>$service->save(357,$saved['version'],$payload,1),'New archived option accepted');
 $payload['values'][$code]['value']=['b'];$db->beginTransaction();$service->saveInTransaction(357,$saved['version'],$payload,1);dictCheck($service->read(357)['values'][$code]['value']===['b'],'New option roundtrip');
 $q=$db->prepare('SELECT definition_json FROM catalog_property WHERE code=?');$q->execute([$code]);$tempDef=json_decode($q->fetchColumn(),true);$tempDef['archived_options']['b']=true;$q=$db->prepare('UPDATE catalog_property SET definition_json=? WHERE code=?');$q->execute([json_encode($tempDef),$code]);P::useDictionary($db);
 $service->saveInTransaction(357,$service->read(357)['version'],$payload,1);dictCheck($service->read(357)['values'][$code]['value']===['b'],'Existing archived option lost');
 $db->rollBack();P::useDictionary($db);
 $q=$db->prepare('SELECT id FROM catalog_property WHERE code=?');$q->execute([$code]);$testProperty=(int)$q->fetchColumn();
 $hadProfile=(bool)$db->query('SELECT product_id FROM catalog_product_profile WHERE product_id=357')->fetchColumn();
 if(!$hadProfile)$db->exec("INSERT INTO catalog_product_profile(product_id,type_id,version,updated_at) SELECT 357,id,0,NOW() FROM catalog_type WHERE code='tyre_solid'");
 $q=$db->prepare("INSERT INTO catalog_product_property(product_id,property_id,value_state,verification,source_ref) VALUES(357,?,'known','candidate','local test')");$q->execute([$testProperty]);
 $bad=$input;$bad['new_option_code']='';$bad['new_option_label']='';$bad['rules']['tyre_solid']='not_applicable';
 try{dictReject(fn()=>$d->save($bad,$d->version(),1),'Applicability removed while known values exist');}finally{$q=$db->prepare('DELETE FROM catalog_product_property WHERE product_id=357 AND property_id=?');$q->execute([$testProperty]);if(!$hadProfile)$db->exec('DELETE FROM catalog_product_profile WHERE product_id=357');}
 $bad=$input;$bad['kind']='text';dictReject(fn()=>$d->save($bad,$d->version(),1),'Type changed');$bad=$input;$bad['unit']='mm';dictReject(fn()=>$d->save($bad,$d->version(),1),'Unit changed');
 dictCheck($before===$fingerprint(),'Product tables changed');dictCheck(count($d->history())>=2,'No history');echo "Dictionary integration: create, options, DB source, rules, archive rejection, rollback, version and unchanged products OK\n";
}finally{
 if($db->inTransaction())$db->rollBack();$q=$db->prepare('SELECT id FROM catalog_property WHERE code=?');$q->execute([$code]);$id=$q->fetchColumn();if($id){foreach(['catalog_property_rule','catalog_property_option']as$t){$q=$db->prepare('DELETE FROM '.$t.' WHERE property_id=?');$q->execute([$id]);}$q=$db->prepare('DELETE FROM catalog_property WHERE id=?');$q->execute([$id]);}$q=$db->prepare('DELETE FROM catalog_dictionary_revision WHERE property_code=?');$q->execute([$code]);P::useDictionary($db);
}
