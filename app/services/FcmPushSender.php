<?php
namespace app\services;

/** Minimal FCM HTTP v1 transport. No legacy server keys, redirects, or raw upstream logs. */
class FcmPushSender
{
    private array $credentials;
    private $http;
    private ?string $accessToken=null;
    private int $expires=0;
    public function __construct(array $credentials,?callable $http=null) { $this->credentials=$credentials;$this->http=$http ?: [$this,'request']; }
    private function request(string $url,array $headers,string $body): array
    {
        $curl=curl_init($url);
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
        $response=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
        return [$status,json_decode((string)$response,true) ?: []];
    }
    private static function base64(string $value): string { return rtrim(strtr(base64_encode($value),'+/','-_'),'='); }
    private function token(): string
    {
        if($this->accessToken!==null && $this->expires>time()+60) return $this->accessToken;
        $now=time();$header=self::base64('{"alg":"RS256","typ":"JWT"}');
        $claims=self::base64(json_encode(['iss'=>$this->credentials['client_email'],'scope'=>'https://www.googleapis.com/auth/firebase.messaging','aud'=>'https://oauth2.googleapis.com/token','iat'=>$now,'exp'=>$now+3600],JSON_THROW_ON_ERROR));
        $data=$header.'.'.$claims;
        if(!openssl_sign($data,$signature,$this->credentials['private_key'],OPENSSL_ALGO_SHA256)) throw new \RuntimeException('Push credential signing failed');
        [$status,$response]=($this->http)('https://oauth2.googleapis.com/token',['Content-Type: application/x-www-form-urlencoded'],http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$data.'.'.self::base64($signature)]));
        if($status!==200 || !is_string($response['access_token']??null)) throw new \RuntimeException('Push authentication failed');
        $this->accessToken=$response['access_token'];$this->expires=$now+min(3600,(int)($response['expires_in']??3600));
        return $this->accessToken;
    }
    public function send(string $deviceToken,array $event): string
    {
        $collapse=substr(hash('sha256',$event['chat_id']),0,32);
        $message=['token'=>$deviceToken,'notification'=>['title'=>'ИТС · Авито','body'=>'Новое сообщение покупателя. Откройте чат.'],
            'data'=>['type'=>'avito_message','chat_id'=>$event['chat_id'],'message_id'=>$event['message_id']],
            'android'=>['priority'=>'high','ttl'=>'3600s','notification'=>['channel_id'=>'avito_messages','tag'=>$collapse,'sound'=>'default']],
            'apns'=>['headers'=>['apns-priority'=>'10','apns-push-type'=>'alert','apns-collapse-id'=>$collapse,'apns-expiration'=>(string)(time()+3600)],'payload'=>['aps'=>['sound'=>'default']]]];
        [$status,$response]=($this->http)('https://fcm.googleapis.com/v1/projects/'.rawurlencode($this->credentials['project_id']).'/messages:send',['Content-Type: application/json','Authorization: Bearer '.$this->token()],json_encode(['message'=>$message],JSON_THROW_ON_ERROR));
        if($status===200 && !empty($response['name'])) return 'sent';
        foreach($response['error']['details']??[] as $detail) if(($detail['errorCode']??'')==='UNREGISTERED') return 'gone';
        if($status===401) { $this->accessToken=null; }
        // INVALID_ARGUMENT can be a malformed payload; never delete all devices for it.
        return 'retry';
    }
}
