<?php
require dirname(__DIR__).'/vendor/autoload.php';
define('ROOT',sys_get_temp_dir().'/avito-deletion-'.bin2hex(random_bytes(5)));
class_alias(\RedBeanPHP\R::class,'R');R::setup('sqlite::memory:');R::freeze(true);
R::exec('CREATE TABLE avito_ad(id INTEGER PRIMARY KEY,avito_id TEXT,ad_external_id TEXT,status TEXT,avito_publication_status TEXT,avito_status_updated_at TEXT,deletion_requested_at TEXT)');
R::exec("INSERT INTO avito_ad VALUES(1,'111','ext1','active',NULL,NULL,'2026-10-21 10:00:00'),(2,'222','ext2','active',NULL,NULL,'2026-10-21 10:00:00'),(3,'333','ext3','active','old',NULL,NULL),(4,'444','ext4','active',NULL,NULL,'2026-10-21 10:00:00')");
R::exec('CREATE TABLE avito_complete_publication(ad_id INTEGER)');R::exec('INSERT INTO avito_complete_publication VALUES(1),(2)');
class DeleteTestClient extends \app\services\AvitoApiClient {
    public function __construct(){}
    public function api(string $method,string $path,array $options=[]):array{
        if($method!=='GET')throw new RuntimeException('Unexpected remote write');
        if($path==='/core/v1/accounts/self')return ['id'=>9];
        if(str_contains($path,'/111/'))return ['status'=>'old'];
        if(str_contains($path,'/222/'))return ['status'=>'active'];
        if(str_contains($path,'/444/'))throw new RuntimeException('HTTP 500');
        throw new RuntimeException('Unrequested ad');
    }
}
function delCheck($ok,$label){if(!$ok)throw new RuntimeException($label);}
$stats=\app\services\AvitoDeletion::reconcile(new DeleteTestClient());
delCheck($stats['deleted']===1&&$stats['deletions_pending']===2,'Fresh confirmation finalizes pending request');
delCheck(!R::getCell('SELECT COUNT(*) FROM avito_ad WHERE id=1'),'Confirmed ad removed');
delCheck(R::getCell('SELECT COUNT(*) FROM avito_complete_publication WHERE ad_id=1')==0,'Publication state removed');
delCheck(R::getCell('SELECT COUNT(*) FROM avito_ad WHERE id=2')==1,'Still active ad preserved');
delCheck(R::getCell('SELECT COUNT(*) FROM avito_ad WHERE id=3')==1,'No user deletion request means no deletion');
delCheck(R::getCell('SELECT COUNT(*) FROM avito_ad WHERE id=4')==1&&count($stats['errors'])===1,'API error never confirms deletion');
delCheck(R::getCell('SELECT COUNT(*) FROM avito_ad WHERE deletion_requested_at IS NOT NULL AND status!=\'archived\'')==0,'Pending ads excluded locally');
$backups=glob(ROOT.'/tmp/avito-deleted/*.snapshot');delCheck(count($backups)===1&&unserialize(file_get_contents($backups[0]))['id']==1,'Recovery snapshot saved');
foreach($backups as$file)unlink($file);rmdir(ROOT.'/tmp/avito-deleted');rmdir(ROOT.'/tmp');rmdir(ROOT);
echo "Avito deletion: fresh confirmation, active/failed/unrequested retention, cleanup and backup OK\n";
