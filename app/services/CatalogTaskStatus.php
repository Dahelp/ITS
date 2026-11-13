<?php
namespace app\services;

/** Private local run receipt, not a scheduler. Page reads never create files. */
final class CatalogTaskStatus
{
    public function __construct(private string $directory) {}
    public static function lockPath(int $id,string $task): string
    {
        if($id<1)throw new \InvalidArgumentException('Invalid task ID');
        return sys_get_temp_dir().($task==='refresh-tovars-server'?'/its_inventory_api_':'/its_cron_task_').$id.'.lock';
    }
    private function path(int $id): string {if($id<1)throw new \InvalidArgumentException('Invalid task ID');return$this->directory.'/task-'.$id.'.json';}
    public function read(int $id): array
    {
        $file=$this->path($id);if(!is_file($file)||filesize($file)>65536)return[];
        $value=json_decode((string)file_get_contents($file),true);return is_array($value)&&(int)($value['id']??0)===$id?$value:[];
    }
    private function write(int $id,array $state): void
    {
        if(!is_dir($this->directory)&&!mkdir($this->directory,0700,true)&&!is_dir($this->directory))throw new \RuntimeException('Cannot create private run directory');
        $file=$this->path($id);$tmp=tempnam($this->directory,'.task-');if($tmp===false)throw new \RuntimeException('Cannot create run receipt');
        try{$json=json_encode($state,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);if(file_put_contents($tmp,$json,LOCK_EX)!==strlen($json)||!chmod($tmp,0640)||!rename($tmp,$file))throw new \RuntimeException('Cannot save run receipt');}finally{if(is_file($tmp))unlink($tmp);}
    }
    public function start(int $id,string $task,string $mode='live'): string
    {
        if(!preg_match('/^[a-z0-9][a-z0-9_-]*$/Di',$task))throw new \InvalidArgumentException('Invalid task');
        if(!in_array($mode,['live','canary','shadow','feed'],true))throw new \InvalidArgumentException('Invalid mode');
        $previous=$this->read($id);$run=bin2hex(random_bytes(12));$this->write($id,['id'=>$id,'task'=>$task,'mode'=>$mode,'run_id'=>$run,'state'=>'running','started_at'=>date(DATE_ATOM),'started_unix'=>time(),'finished_at'=>null,'last_success_at'=>$previous['last_success_at']??null,'stats'=>[]]);return$run;
    }
    public function finish(int $id,string $run,string $state,array $stats=[]): void
    {
        if(!in_array($state,['success','partial','error','interrupted'],true))throw new \InvalidArgumentException('Invalid state');
        $value=$this->read($id);if(($value['run_id']??'')!==$run)return;
        $value['state']=$state;$value['finished_at']=date(DATE_ATOM);$value['duration_seconds']=max(0,time()-(int)$value['started_unix']);
        $keys=['seen','api','cache','db_fallback','changed','updated','modifications_seen','modifications_updated','modifications_api','modifications_cache','offers','skipped'];$value['stats']=[];foreach($keys as$key)if(isset($stats[$key])&&is_numeric($stats[$key]))$value['stats'][$key]=(int)$stats[$key];
        if($state==='success'&&in_array($value['mode'],['live','feed'],true))$value['last_success_at']=$value['finished_at'];$this->write($id,$value);
    }
    public function observed(int $id): array
    {
        $value=$this->read($id);if(!$value)return ['state'=>'unknown'];
        if($value['state']==='running'){
            $path=self::lockPath($id,(string)$value['task']);$lock=is_file($path)?@fopen($path,'r+'):false;
            if($lock){if(flock($lock,LOCK_EX|LOCK_NB)){$value['state']='interrupted';flock($lock,LOCK_UN);}fclose($lock);}else$value['state']='interrupted';
        }return$value;
    }
}
