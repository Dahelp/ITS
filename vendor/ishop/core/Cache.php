<?php

namespace ishop;

class Cache{

    use TSingletone;

    private ?string $epoch=null;
    private bool $initialized=false;
    private $redis=null;
    private function initialize():void {
        if($this->initialized)return;$this->initialized=true;
        $this->epoch=\app\services\HtmlPageCache::instance()->stamp();
        if(!is_dir(CACHE))@mkdir(CACHE,0700,true);
        // Only an explicitly configured account Redis is used. No guessed service.
        $host=getenv('ITS_REDIS_HOST');
        if($host&&class_exists('Redis'))try{
            $redis=new \Redis();$redis->connect($host,(int)(getenv('ITS_REDIS_PORT')?:6379),0.05);
            $redis->setOption(\Redis::OPT_READ_TIMEOUT,0.05);
            if(getenv('ITS_REDIS_PASSWORD'))$redis->auth(getenv('ITS_REDIS_USER')?[getenv('ITS_REDIS_USER'),getenv('ITS_REDIS_PASSWORD')]:getenv('ITS_REDIS_PASSWORD'));
            $redis->ping();$this->redis=$redis;
        }catch(\Throwable$ignored){$this->redis=null;}
    }
    private function redisKey($key):string { return 'itscenter:obj2:'.substr(hash('sha256',ROOT),0,12).':'.$this->epoch.':'.hash('sha256',(string)$key); }
    public function set($key,$data,$seconds=3600){
        $this->initialize();$seconds=(int)$seconds;
        if(!$this->epoch||$seconds<=0||$this->epoch!==\app\services\HtmlPageCache::instance()->stamp())return false;
        $raw=serialize(['data'=>$data,'end_time'=>time()+$seconds,'epoch'=>$this->epoch]);
        if(strlen($raw)>8388608)return false;
        if($this->redis)try{return $this->redis->setex($this->redisKey($key),$seconds,$raw);}catch(\Throwable$ignored){$this->redis=null;}
        $file=CACHE.'/'.md5((string)$key).'.txt';$tmp=@tempnam(CACHE,'.object-');if($tmp===false)return false;
        $ok=@file_put_contents($tmp,$raw)===strlen($raw)&&@rename($tmp,$file);if(!$ok)@unlink($tmp);return $ok;
    }
    public function get($key){
        $this->initialize();if(!$this->epoch||$this->epoch!==\app\services\HtmlPageCache::instance()->stamp())return false;$raw=false;
        if($this->redis)try{$raw=$this->redis->get($this->redisKey($key));}catch(\Throwable$ignored){$this->redis=null;}
        if($raw===false){$file=CACHE.'/'.md5((string)$key).'.txt';if(!is_file($file)||filesize($file)>8388608)return false;$raw=@file_get_contents($file);}
        if(!is_string($raw))return false;
        $content=@unserialize($raw,['allowed_classes'=>false]);
        if(!is_array($content)||($content['epoch']??null)!==$this->epoch||($content['end_time']??0)<=time()||!array_key_exists('data',$content))return false;
        return $content['data'];
    }
    public function delete($key){
        $this->initialize();if($this->redis)try{$this->redis->del($this->redisKey($key));}catch(\Throwable$ignored){$this->redis=null;}
        @unlink(CACHE.'/'.md5((string)$key).'.txt');\app\services\HtmlPageCache::instance()->invalidate();
    }

}
