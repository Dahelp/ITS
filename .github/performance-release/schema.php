<?php
/** Additive online indexes only; no cleanup or blocking fallback. */
if(PHP_SAPI!=='cli')exit(1);
$root=realpath($argv[1]??'');$apply=($argv[2]??'')==='apply';
if(!$root)throw new RuntimeException('Invalid schema root');
require $root.'/config/init.php';$cfg=require $root.'/config/config_db.php';
$db=new PDO($cfg['dsn'],$cfg['user'],$cfg['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('SET SESSION lock_wait_timeout=5');
$plan=[
 'attribute_product'=>[['product_id','attr_id'],['attr_id','product_id']],
 'product_attribute'=>[['product_id','attribute_id']],
 'related_product'=>[['related_id','product_id']],
 'modification'=>[['product_id']],
 'attribute_value'=>[['attr_group_id','hide','id']],
 'attribute_category'=>[['category_id','group_id']],
 'actions'=>[['product_id','hide','date_end'],['hide','date_end']],
];
$report=['apply'=>$apply,'created'=>[],'covered'=>[],'planned'=>[]];
try{
 foreach($plan as$table=>$sets){
  $status=$db->query('SHOW TABLE STATUS WHERE Name='.$db->quote($table))->fetch();
  if(!$status||strtoupper($status['Engine'])!=='INNODB')throw new RuntimeException('Online indexing requires InnoDB: '.$table);
  $columns=array_column($db->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(),'Field');
  $indexes=[];foreach($db->query('SHOW INDEX FROM `'.$table.'`')->fetchAll()as$row){if($row['Sub_part']!==null)continue;$indexes[$row['Key_name']][(int)$row['Seq_in_index']]=$row['Column_name'];}
  foreach($indexes as&$index){ksort($index);$index=array_values($index);}unset($index);
  foreach($sets as$fields){
   foreach($fields as$field)if(!in_array($field,$columns,true))throw new RuntimeException('Missing index column: '.$table.'.'.$field);
   $covered=false;foreach($indexes as$index)if(array_slice($index,0,count($fields))===$fields)$covered=true;
   $name='perf_'.implode('_',$fields);$entry=['table'=>$table,'name'=>$name,'columns'=>$fields];
   if($covered){$report['covered'][]=$entry;continue;}
   if(isset($indexes[$name]))throw new RuntimeException('Index name collision: '.$table.'.'.$name);
   $report['planned'][]=$entry;
   if($apply){$db->exec('ALTER TABLE `'.$table.'` ADD INDEX `'.$name.'` (`'.implode('`,`',$fields).'`), ALGORITHM=INPLACE, LOCK=NONE');$report['created'][]=$entry;$indexes[$name]=$fields;}
  }
 }
}catch(Throwable$e){$report['error']=$e->getMessage();throw $e;}
finally{@mkdir(__DIR__.'/reports',0700,true);file_put_contents(__DIR__.'/reports/schema.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));}
echo 'Schema: '.count($report['created']).' created, '.count($report['covered'])." already covered\n";
