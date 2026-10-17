<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\AvitoMobileAuth;
use app\services\AvitoPushConfig;
use app\services\AvitoPushDevices;
use app\services\AvitoPushQueue;
use app\services\FcmPushSender;
use app\services\AvitoWebhook;
class_alias(\RedBeanPHP\R::class,'R');R::setup('sqlite::memory:');
R::exec('CREATE TABLE user (id INTEGER PRIMARY KEY,email TEXT,name TEXT,role TEXT,password TEXT)');
R::exec('CREATE TABLE avito_chat_manager (user_id INTEGER PRIMARY KEY)');
R::exec('CREATE TABLE avito_mobile_session (token_hash TEXT PRIMARY KEY,user_id INTEGER,password_stamp TEXT,device_name TEXT,created_at INTEGER,expires_at INTEGER)');
R::exec('CREATE TABLE avito_mobile_throttle (bucket TEXT PRIMARY KEY,attempts INTEGER,expires_at INTEGER)');
R::exec('CREATE TABLE avito_push_device (token_hash TEXT PRIMARY KEY,session_hash TEXT,user_id INTEGER,push_token TEXT,platform TEXT,updated_at INTEGER)');
foreach([1,2] as $id) {R::exec('INSERT INTO user VALUES (?,?,?,?,?)',[$id,"u$id@example.test","User $id",'manager',password_hash('secret',PASSWORD_DEFAULT)]);R::exec('INSERT INTO avito_chat_manager VALUES (?)',[$id]);}
function pushCheck($ok,$message) {if(!$ok) throw new RuntimeException($message);}
class FakePushSender extends FcmPushSender {
    public array $sent=[]; public array $results=[]; public $afterSend=null;
    public function __construct() {}
    public function send(string $token,array $event): string { $this->sent[]=$token;if($this->afterSend) ($this->afterSend)();return $this->results[$token]??'sent'; }
}
$dir=sys_get_temp_dir().'/avito-push-'.bin2hex(random_bytes(6));$config=new AvitoPushConfig($dir);
function pushRemove(string $dir): void {if(!is_dir($dir)) return;foreach(new DirectoryIterator($dir) as $f) if(!$f->isDot()) {if($f->isDir()) pushRemove($f->getPathname());else unlink($f->getPathname());}rmdir($dir);}
try {
    $config->setup();pushCheck(!$config->ready(),'Disabled by default');
    $auth=new AvitoMobileAuth();
    $session1=$auth->login('u1@example.test','secret','Android','127.0.0.1')['access_token'];
    $session2=$auth->login('u2@example.test','secret','iPhone','127.0.0.1')['access_token'];
    $token1=str_repeat('A',64);$token2=str_repeat('B',64);
    AvitoPushDevices::register($session1,1,$token1,'android');AvitoPushDevices::register($session2,2,$token2,'ios');
    pushCheck(count(AvitoPushDevices::eligible())===2,'Both allowed employees eligible');
    pushCheck(!str_contains(json_encode(AvitoPushDevices::status($session1)),$token1),'Status hides FCM token');
    R::exec('DELETE FROM avito_chat_manager WHERE user_id=2');pushCheck(count(AvitoPushDevices::eligible())===1,'Disabled access blocks pushes');
    R::exec('INSERT INTO avito_chat_manager VALUES (2)');
    R::exec('UPDATE avito_mobile_session SET expires_at=1 WHERE user_id=2');pushCheck(count(AvitoPushDevices::eligible())===1,'Expired session blocks pushes');
    R::exec('UPDATE avito_mobile_session SET expires_at=? WHERE user_id=2',[time()+3600]);
    R::exec('UPDATE user SET password=? WHERE id=2',[password_hash('new',PASSWORD_DEFAULT)]);pushCheck(count(AvitoPushDevices::eligible())===1,'Password change blocks pushes');
    R::exec('UPDATE avito_mobile_session SET password_stamp=? WHERE user_id=2',[hash('sha256',R::getCell('SELECT password FROM user WHERE id=2'))]);
    AvitoPushDevices::register($session2,2,$token1,'ios');pushCheck(count(AvitoPushDevices::eligible())===1,'Same physical FCM token transfers ownership');
    AvitoPushDevices::register($session1,1,$token1,'android');AvitoPushDevices::register($session2,2,$token2,'ios');
    $queue=new AvitoPushQueue($dir.'/spool');$event=['chat_id'=>'chat-a','message_id'=>'message-a','created_at'=>time()];$sender=new FakePushSender();$sender->results[$token2]='retry';
    $queue->enqueue($event);$queue->enqueue($event);$stats=$queue->run($sender);
    pushCheck($stats['sent']===1&&$stats['retry']===1&&count($sender->sent)===2,'Dedup and per-device retry');
    $jobFile=(glob($dir.'/spool/*.json'))[0];$job=json_decode(file_get_contents($jobFile),true);$job['next_at']=0;file_put_contents($jobFile,json_encode($job));
    $sender->results=[];$queue->run($sender);
    pushCheck(count(array_filter($sender->sent,fn($t)=>$t===$token1))===1,'Successful device never resent on retry');
    $queue->enqueue($event);$queue->run($sender);pushCheck(count($sender->sent)===3,'Completed tombstone suppresses duplicate event');
    $sender2=new FakePushSender();$sender2->afterSend=function(){R::exec('DELETE FROM avito_chat_manager');};
    $queue->enqueue(['chat_id'=>'chat-b','message_id'=>'message-b']);$queue->run($sender2);pushCheck(count($sender2->sent)===1,'Access rechecked before every recipient');
    R::exec('INSERT INTO avito_chat_manager VALUES (1)');R::exec('INSERT INTO avito_chat_manager VALUES (2)');
    $sender3=new FakePushSender();$sender3->results=[$token1=>'gone',$token2=>'gone'];$queue->enqueue(['chat_id'=>'chat-c','message_id'=>'message-c']);$queue->run($sender3);
    pushCheck(!AvitoPushDevices::eligible(),'Unregistered FCM tokens removed');
    AvitoPushDevices::register($session1,1,$token1,'android');$auth->logout($session1,1);pushCheck(!AvitoPushDevices::eligible(),'Logout blocks delivery');
    // Webhook routing: only incoming messages, never bodies or own replies in spool.
    mkdir($dir.'/hook');file_put_contents($dir.'/hook/config.json',json_encode(['account_id'=>'123','secret'=>'test']));
    file_put_contents($dir.'/config.json','{"enabled":true}');file_put_contents($dir.'/service-account.json','{}');
    $hook=new AvitoWebhook($dir.'/hook',$config);
    $payload=['id'=>'push-hook','payload'=>['type'=>'message','value'=>['user_id'=>123,'author_id'=>456,'chat_id'=>'hook-chat','id'=>'hook-message','created'=>time(),'content'=>['text'=>'Private buyer data']]]];
    $hook->accept($payload);$spool=$dir.'/spool/'.hash('sha256','hook-chat:hook-message').'.json';pushCheck(is_file($spool)&&!str_contains(file_get_contents($spool),'Private buyer'),'Incoming metadata only queued');
    $payload['id']='own';$payload['payload']['value']['author_id']=123;$payload['payload']['value']['id']='own-message';$hook->accept($payload);
    pushCheck(!is_file($dir.'/spool/'.hash('sha256','hook-chat:own-message').'.json'),'Own replies never notify');
    // Cryptographic OAuth and FCM payload, using an ephemeral test-only key.
    $options=['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA];
    $opensslConfig=dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';if(is_file($opensslConfig)) $options['config']=$opensslConfig;
    $key=openssl_pkey_new($options);openssl_pkey_export($key,$private,null,$options);$public=openssl_pkey_get_details($key)['key'];$requests=[];
    $real=new FcmPushSender(['client_email'=>'test@example.test','project_id'=>'test-project','private_key'=>$private],function($url,$headers,$body) use(&$requests,$public){
        $requests[]=$url;
        if($url==='https://oauth2.googleapis.com/token') {
            parse_str($body,$form);[$h,$p,$s]=explode('.',$form['assertion']);$sig=base64_decode(strtr($s,'-_','+/'));pushCheck(openssl_verify($h.'.'.$p,$sig,$public,OPENSSL_ALGO_SHA256)===1,'JWT RSA signature valid');
            $claims=json_decode(base64_decode(strtr($p,'-_','+/')),true);pushCheck($claims['scope']==='https://www.googleapis.com/auth/firebase.messaging'&&$claims['aud']===$url,'Scoped OAuth audience');
            return [200,['access_token'=>'test-access','expires_in'=>3600]];
        }
        $message=json_decode($body,true)['message'];pushCheck($message['data']['chat_id']==='chat-a'&&!str_contains($body,'Private buyer'),'Generic notification with deep link');
        pushCheck($message['android']['notification']['channel_id']==='avito_messages'&&$message['apns']['headers']['apns-push-type']==='alert','Native channel and APNs alert');
        pushCheck($message['android']['notification']['icon']==='ic_notification'&&$message['android']['notification']['color']==='#003B4D','Branded Android small icon and accent');
        return [200,['name'=>'projects/test-project/messages/test']];
    });
    pushCheck($real->send($token1,$event)==='sent'&&$real->send($token2,$event)==='sent','FCM accepted');
    pushCheck(count(array_filter($requests,fn($u)=>$u==='https://oauth2.googleapis.com/token'))===1,'OAuth token reused within worker');
    echo "AvitoPush: OK (permissions, session binding, queue, retries, webhook, RSA OAuth, FCM)\n";
} finally {pushRemove($dir);}
