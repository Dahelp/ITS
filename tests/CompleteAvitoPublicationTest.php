<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\AvitoApiClient;
use app\services\CompleteAvito;
use app\services\CompleteAvitoPublication;
use app\models\admin\Avito;

class PublicationFakeApi extends AvitoApiClient
{
    public $calls = [];
    public $status = 'active';
    public $failStock = false;
    public function __construct() {}
    public function api(string $method, string $path, array $options = []): array
    {
        $this->calls[] = [$method,$path,$options];
        if ($method === 'GET') { return ['status'=>$this->status]; }
        if (strpos($path,'update_price') !== false) { return ['result'=>['success'=>true]]; }
        if ($method === 'PUT') {
            $row = $options['json']['stocks'][0];
            if ($row['quantity'] <= 0) { throw new Exception('Zero stock must never be sent'); }
            return ['stocks'=>[['item_id'=>$row['item_id'],'success'=>!$this->failStock]]];
        }
        throw new Exception('Unexpected API request');
    }
}
function publicationCheck($ok, $label) { if (!$ok) { throw new RuntimeException($label); } }
$ad = ['id'=>140,'complete_id'=>25,'avito_id'=>2444178586,'ad_external_id'=>'2444178586',
    'status'=>'active','stock_quantity'=>8,'price_rub'=>42400];
$api = new PublicationFakeApi();
$now = strtotime('2026-10-16 12:00:00');
$state = CompleteAvitoPublication::reconcile($ad, [], $api, 123, $now);
publicationCheck($state['confirmed_hash'] === $state['state_hash'], 'available set is confirmed');
publicationCheck(count($api->calls) === 3 && !$state['needs_upload'], 'only positive price/stock updates');
$ad['stock_quantity']=0; $ad['status']='archived'; $api->calls=[];
$state = CompleteAvitoPublication::reconcile($ad, $state, $api, 123, $now+1);
publicationCheck($state['needs_upload'] && !$state['confirmed_hash'], 'active Avito listing must be archived by autoload');
publicationCheck(count($api->calls) === 1 && $api->calls[0][0] === 'GET', 'zero set sends neither price nor stock');
publicationCheck(count((new SimpleXMLElement(Avito::buildXml([$ad])))->Ad) === 0, 'zero set excluded even from manual XML');
$state['requested_at']=date('Y-m-d H:i:s',$now+1); $api->calls=[];
$state = CompleteAvitoPublication::reconcile($ad,$state,$api,123,$now+60);
publicationCheck(!$state['needs_upload'] && !$state['confirmed_hash'], 'accepted request is pending until Avito confirms');
$state = CompleteAvitoPublication::reconcile($ad,$state,$api,123,$now+1900);
publicationCheck($state['needs_upload'], 'unprocessed archive request is retried');
$api->status='old';
$state = CompleteAvitoPublication::reconcile($ad,$state,$api,123,$now+1901);
publicationCheck($state['confirmed_hash'] === $state['state_hash'], 'archive confirmed by remote old status');
$ad['stock_quantity']=8; $ad['status']='active'; $api->calls=[];
$state = CompleteAvitoPublication::reconcile($ad,$state,$api,123,$now+1902);
publicationCheck($state['needs_upload'] && !$state['confirmed_hash'], 'restocked set requests reactivation');
$api->status='active';
$state = CompleteAvitoPublication::reconcile($ad,$state,$api,123,$now+1903);
publicationCheck($state['confirmed_hash'] === $state['state_hash'], 'reactivation confirmed');
publicationCheck(count((new SimpleXMLElement(Avito::buildXml([$ad])))->Ad) === 1, 'restocked set included in XML');
$ad['stock_quantity']=7; $api->failStock=true;
$state = CompleteAvitoPublication::reconcile($ad,$state,$api,123,$now+1904);
publicationCheck(!$state['confirmed_hash'] && !empty($state['last_error']), 'API failure remains pending');
$api->failStock=false;
$state = CompleteAvitoPublication::reconcile($ad,$state,$api,123,$now+2300);
publicationCheck($state['confirmed_hash'] === $state['state_hash'], 'API failure recovers on next attempt');
$ad['status']='archived'; $ad['stock_quantity']=8;
publicationCheck(!CompleteAvito::canPublish($ad), 'moderation/hidden set stays excluded even with stock');
$draft=$ad; $draft['avito_id']=null; $draft['stock_quantity']=0; $draft['price_rub']=0;
$api->calls=[];
$state=CompleteAvitoPublication::reconcile($draft,[],$api,123,$now+2400);
publicationCheck($state['remote_status']==='unpublished' && $state['confirmed_hash']===$state['state_hash'] && !$api->calls,
    'unsubmitted archived draft requires no remote API or error');
$idMethod=new ReflectionMethod(Avito::class,'apiItemId');
$idMethod->setAccessible(true);
publicationCheck($idMethod->invoke(null,['avito_id'=>null,'ad_external_id'=>'its-complete-26'])===0,
    'external draft identifier must not become an unrelated Avito item ID');
publicationCheck($idMethod->invoke(null,['avito_id'=>'2444312908','ad_external_id'=>'its-complete-27'])===2444312908,
    'actual Avito item identifier retained');
echo "Publication tests passed: available -> zero -> archive confirmed -> restocked -> active; retries, API failures, XML guards\n";
