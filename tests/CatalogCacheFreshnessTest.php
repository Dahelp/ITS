<?php
require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/app/services/HtmlPageCache.php';
define('ROOT',dirname(__DIR__));
define('CACHE',ROOT.'/tmp/object-cache-test-'.bin2hex(random_bytes(5)));
use app\services\HtmlPageCache;
function verify($value,$message){if(!$value)throw new RuntimeException($message);}
$now=time();$page=new HtmlPageCache(CACHE.'/html',300,static function()use(&$now){return$now;});
$slot=new ReflectionProperty(HtmlPageCache::class,'instance');$slot->setAccessible(true);$slot->setValue(null,$page);
$page->invalidate();$one=new \ishop\Cache();verify($one->set('price',['price'=>100]),'initial write');verify($one->get('price')===['price'=>100],'read atomic object');
$page->beginMutation();
$during=new \ishop\Cache();verify($during->get('price')===false&&!$during->set('price',['price'=>999]),'uncommitted mutation cannot publish or read shared objects');
$page->endMutation();
$two=new \ishop\Cache();verify($two->get('price')===false,'new request cannot read old price');verify(!$one->set('price',['price'=>50]),'old renderer cannot publish after mutation');
verify($two->set('price',['price'=>200]),'new renderer publishes committed price');
file_put_contents(CACHE.'/'.md5('price').'.txt','invalid');verify($two->get('price')===false,'corruption is a miss');
$key=hash('sha256','clock');$gen=$page->stamp();$html='<!doctype html><html><head><title>Fixture</title></head><body>Public</body></html>';
$page->limitUntil($now+10);verify($page->write($key,$gen,$html,[]),'HTML stored until boundary');$now+=10;verify($page->read($key)===null,'HTML expires at scheduled change');
$limits=[];$rows=[['id'=>1,'start_at'=>null,'end_at'=>null],['id'=>2,'start_at'=>date('Y-m-d H:i:s',$now+20),'end_at'=>null],['id'=>3,'start_at'=>null,'end_at'=>date('Y-m-d H:i:s',$now-1)]];
$active=\app\services\CatalogCacheClock::activeBanners($rows,$now,static function($at)use(&$limits){$limits[]=$at;});
verify(array_column($active,'id')===[1]&&$limits===[$now+20],'future banner expires HTML before activation');
$active=\app\services\CatalogCacheClock::activeBanners($rows,$now+20,static function(){});verify(array_column($active,'id')===[1,2],'banner starts without database write');
$limits=[];$endRow=[['id'=>4,'start_at'=>null,'end_at'=>date('Y-m-d H:i:s',$now)]];
$active=\app\services\CatalogCacheClock::activeBanners($endRow,$now,static function($at)use(&$limits){$limits[]=$at;});
verify(count($active)===1&&$limits===[$now+1],'inclusive banner end expires in the following second');
verify(\app\services\CatalogCacheClock::activeBanners($endRow,$now+1,static function(){})===[],'banner disappears after its inclusive end');
echo "Freshness: object generation, mutation race, corruption, scheduled HTML expiry and banner activation passed\n";
