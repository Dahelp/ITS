<?php
require dirname(__DIR__).'/vendor/autoload.php';
define('CACHE',sys_get_temp_dir().'/avito-id-sync-'.bin2hex(random_bytes(5)));mkdir(CACHE);
class_alias(\RedBeanPHP\R::class,'R');R::setup('sqlite::memory:');R::freeze(true);
R::exec('CREATE TABLE avito_ad(id INTEGER PRIMARY KEY,ad_external_id TEXT,avito_id TEXT)');
R::exec("INSERT INTO avito_ad VALUES(1,'its-9328',NULL),(2,'its-complete-26','0'),(3,'existing','1234567890')");
class IdTestClient extends \app\services\AvitoApiClient {
    public int $calls=0;public $result=['items'=>[]];public bool $fail=false;
    public function __construct() {}
    public function api(string $method,string $path,array $options=[]):array {
        $this->calls++;if($method!=='GET'||!str_starts_with($path,'/autoload/v2/items/avito_ids?query='))throw new RuntimeException('Unexpected API write');
        if($this->fail)throw new RuntimeException('HTTP 429 private upstream body');
        return $this->result;
    }
}
function idsCheck($ok,$label){if(!$ok)throw new RuntimeException($label);}
$client=new IdTestClient();
$client->result=['items'=>[['ad_id'=>'its-9328','avito_id'=>null],['ad_id'=>'its-complete-26','avito_id'=>null]]];
$first=\app\services\AvitoIdSync::synchronize($client);
idsCheck($first['assigned']===0&&$first['pending']===2,'Not published yet');
$client->result=['items'=>[['ad_id'=>'its-9328','avito_id'=>8366469805],['ad_id'=>'its-complete-26','avito_id'=>null],['ad_id'=>'existing','avito_id'=>9999999999]]];
$next=\app\services\AvitoIdSync::synchronize($client);
idsCheck($next['assigned']===1&&$next['pending']===1,'ID assigned on a later request');
idsCheck(R::getCell('SELECT avito_id FROM avito_ad WHERE id=1')==='8366469805','ID persisted');
idsCheck(R::getCell('SELECT avito_id FROM avito_ad WHERE id=3')==='1234567890','Existing and unrequested ID never overwritten');
$client->fail=true;$failed=\app\services\AvitoIdSync::synchronize($client);
idsCheck(count($failed['errors'])===1&&str_contains($failed['errors'][0],'429')&&!str_contains($failed['errors'][0],'private'),'Error visible and sanitized');
idsCheck($failed['pending']===1,'Failed lookup stays pending');
$client->fail=false;$client->result=['items'=>[['ad_id'=>'its-complete-26','avito_id'=>null]]];
\app\services\AvitoIdSync::refreshPending(true,$client);$calls=$client->calls;
\app\services\AvitoIdSync::refreshPending(false,$client);
idsCheck($client->calls===$calls,'Automatic retry throttled');
\app\services\AvitoIdSync::refreshPending(true,$client);
idsCheck($client->calls===$calls+1,'Manual refresh bypasses throttle');
$lock=fopen(CACHE.'/avito-id-sync.lock','c');flock($lock,LOCK_EX);
idsCheck(!empty(\app\services\AvitoIdSync::refreshPending(true,$client)['busy']),'Concurrent request locked');
flock($lock,LOCK_UN);fclose($lock);
$client->result=['items'=>[['ad_id'=>'its-complete-26','avito_id'=>8482968268]]];
idsCheck(\app\services\AvitoIdSync::synchronize($client)['pending']===0,'Set receives a later assigned ID');
$calls=$client->calls;\app\services\AvitoIdSync::synchronize($client);idsCheck($client->calls===$calls,'No pending IDs makes no API request');
R::exec("INSERT INTO avito_ad VALUES(4,'12345',NULL)");$client->result=['items'=>[['ad_id'=>'12345','avito_id'=>1234567891]]];
idsCheck(\app\services\AvitoIdSync::synchronize($client)['assigned']===1,'Numeric external ID matched exactly');
foreach(glob(CACHE.'/*') as $file)unlink($file);rmdir(CACHE);
echo "Avito ID sync: delayed assignment, persistence, reserve IDs, errors, throttle, concurrency OK\n";
