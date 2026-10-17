<?php
namespace app\services;

/** Durable, private spool; the webhook does not connect to a database or Firebase. */
class AvitoPushQueue
{
    private string $directory;
    public function __construct(string $directory) { $this->directory=$directory; }
    public function enqueue(array $event): void
    {
        if(!is_dir($this->directory) && !mkdir($this->directory,0700,true) && !is_dir($this->directory)) throw new \RuntimeException('Push spool unavailable');
        $path=$this->directory.'/'.hash('sha256',$event['chat_id'].':'.$event['message_id']).'.json';
        if(is_file($path)) return; // Includes completed tombstones until retention expires.
        $this->write($path,$event+['queued_at'=>time(),'attempts'=>0,'next_at'=>0,'targets'=>null,'delivered'=>[]]);
    }
    private function write(string $path,array $job): void
    {
        $temporary=tempnam($this->directory,'.push-');if(!$temporary) throw new \RuntimeException('Push spool write failed');
        try {
            if(file_put_contents($temporary,json_encode($job,JSON_THROW_ON_ERROR),LOCK_EX)===false || !rename($temporary,$path)) throw new \RuntimeException('Push spool write failed');
            @chmod($path,0600);
        } finally { if(is_file($temporary)) unlink($temporary); }
    }
    public function run(FcmPushSender $sender,int $maxSeconds=45): array
    {
        $stats=['sent'=>0,'invalid'=>0,'completed'=>0,'retry'=>0,'expired'=>0];
        if(!is_dir($this->directory)) return $stats;
        $lock=fopen($this->directory.'/worker.lock','c');
        if(!$lock || !flock($lock,LOCK_EX|LOCK_NB)) { if($lock) fclose($lock); return $stats+['busy'=>true]; }
        $start=microtime(true);
        try {
            foreach(new \DirectoryIterator($this->directory) as $file) {
                if(microtime(true)-$start>=$maxSeconds) break;
                if(!$file->isFile() || !preg_match('/\A[a-f0-9]{64}\.json\z/',$file->getFilename())) continue;
                $path=$file->getPathname();$job=json_decode((string)file_get_contents($path),true);
                if(!is_array($job)) continue;
                if(time()-(int)$job['queued_at']>86400) { unlink($path);$stats['expired']++;continue; }
                if(!empty($job['done']) || (int)$job['next_at']>time()) continue;
                // Stale queued alerts are not useful after one hour.
                if(time()-(int)$job['queued_at']>3600 || (isset($job['created_at']) && $job['created_at']>0 && time()-(int)$job['created_at']>3600)) { $job['done']=true;$this->write($path,$job);$stats['expired']++;continue; }
                $eligible=AvitoPushDevices::eligible();
                if($job['targets']===null) {
                    $job['targets']=[];
                    foreach($eligible as $hash=>$device) $job['targets'][$hash]=$device['session_hash'];
                    $this->write($path,$job);
                }
                foreach($job['targets'] as $hash=>$sessionHash) {
                    if(isset($job['delivered'][$hash])) continue;
                    if(microtime(true)-$start>=$maxSeconds) break;
                    // Recheck immediately before EACH delivery, including revocation/password changes.
                    $device=AvitoPushDevices::eligible()[$hash]??null;
                    if(!$device || !hash_equals($sessionHash,$device['session_hash'])) { $job['delivered'][$hash]='revoked';$this->write($path,$job);continue; }
                    try { $result=$sender->send($device['push_token'],$job); } catch(\Throwable $e) { $result='retry'; }
                    if($result==='sent'||$result==='gone') {
                        $job['delivered'][$hash]=$result;
                        if($result==='gone') { AvitoPushDevices::forgetInvalid($hash,$device['push_token']);$stats['invalid']++; } else $stats['sent']++;
                        $this->write($path,$job);
                    } else $stats['retry']++;
                }
                $job['attempts']++;
                $job['next_at']=time()+min(900,30*(2**min(5,$job['attempts'])));
                if(count($job['delivered'])===count($job['targets'])) { $job['done']=true;$stats['completed']++; }
                $this->write($path,$job);
            }
        } finally { flock($lock,LOCK_UN);fclose($lock); }
        return $stats;
    }
}
