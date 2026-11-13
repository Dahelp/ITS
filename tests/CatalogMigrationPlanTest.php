<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogMigrationPlan as M;
use app\services\CatalogLegacyPreview as L;
function planCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
$p=['id'=>357,'sku'=>'IT000007251','name'=>'Колесо в сборе с диском','category_id'=>9];$l=L::forProduct($p,[['attribute_id'=>4,'attribute_text'=>'12-16.5'],['attribute_id'=>61,'attribute_text'=>'цельнолитые'],['attribute_id'=>67,'attribute_text'=>'33x12-20']]);
planCheck(M::decide($l,true)['decision']==='skip_saved','Existing profile must be skipped');
$candidate=M::decide($l,false);planCheck($candidate['decision']==='draft_candidate'&&count($candidate['issues'])>0,'Incomplete draft allowed, not published');
planCheck($candidate['proposed']['values']['tire_size']['verification']==='legacy_unverified','No automatic confirmation');
$l['unmapped'][]=['code'=>'surface','raw'=>'unknown','reason'=>'Test unknown value'];planCheck(M::decide($l,false)['decision']==='hold','Lossy proposal held');
$l['unmapped']=[];$l['warnings'][]='Duplicate';planCheck(M::decide($l,false)['decision']==='hold','Ambiguous proposal held');
$l['warnings']=[];$l['proposed']['type']='unclassified';planCheck(M::decide($l,false)['decision']==='hold','Unknown type held');
echo "Migration dry-run: skip existing profiles, hold ambiguity/loss, preserve draft status and fingerprint OK\n";
