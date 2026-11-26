<?php
namespace app\services;

/** Bounded origin HTML cache. This class intentionally has no bootstrap/DB dependencies. */
final class HtmlPageCache
{
    private const MAX_HTML = 2097152;
    private const SHARD_BYTES = 1048576;
    private const SHARD_FILES = 48;
    private static ?self $instance = null;
    private string $dir;
    private int $ttl;
    private $clock;
    private $writer = null;
    private $renderLock = null;
    private bool $streamed = false;
    private array $mutationCallbacks = [];
    private ?int $notAfter = null;
    public function limitUntil(int $timestamp): void { if($timestamp>0)$this->notAfter=min($this->notAfter??PHP_INT_MAX,$timestamp); }
    public function stamp(): ?string
    {
        if(!$this->ready())return null;
        $guard=$this->guard();if(!$guard)return null;
        try{return $this->generation();}finally{flock($guard,LOCK_UN);fclose($guard);}
    }

    public function __construct(?string $dir = null, int $ttl = 300, ?callable $clock = null)
    {
        $this->dir = $dir ?? dirname(__DIR__, 2).'/tmp/html-page-cache';
        $this->ttl = max(1, min(300, $ttl));
        $this->clock = $clock ?? static fn() => time();
    }
    public static function instance(): self { return self::$instance ??= new self(); }

    public static function identity(array $server, array $cookies): ?string
    {
        if (!in_array($server['REQUEST_METHOD'] ?? '', ['GET','HEAD'], true)) return null;
        foreach (['HTTP_AUTHORIZATION','REDIRECT_HTTP_AUTHORIZATION','PHP_AUTH_USER','PHP_AUTH_DIGEST','REMOTE_USER','HTTP_X_REQUESTED_WITH','HTTP_RANGE'] as $key) if (!empty($server[$key])) return null;
        if (preg_match('~no-cache|no-store|max-age\s*=\s*0~i', (string)($server['HTTP_CACHE_CONTROL']??'')) || stripos((string)($server['HTTP_PRAGMA']??''),'no-cache')!==false) return null;
        $host = strtolower((string)($server['HTTP_HOST'] ?? ''));
        if (!preg_match('~^(?:its-center\.ru|its\.local|localhost(?::[0-9]{1,5})?)$~D', $host)) return null;
        foreach ($cookies as $name=>$value) {
            if (!is_string($value) || !preg_match('~^(?:its_traffic_source|cookie_accepted|_ga(?:_[A-Za-z0-9]+)?|_gid|_ym_[A-Za-z0-9_]+)$~D',(string)$name)) return null;
        }
        $uri = (string)($server['REQUEST_URI'] ?? '');
        if (strlen($uri)>512) return null;
        $parts = parse_url($uri);
        if (!$parts || isset($parts['host']) || isset($parts['fragment'])) return null;
        $path = $parts['path'] ?? '';
        if (array_intersect(explode('/',$path),['.','..'])) return null;
        if (!preg_match('~^/(?:catalog/[a-z0-9-]+|category/[a-z0-9-]+(?:/[a-z0-9._-]+){0,3}|product/[a-z0-9-]+)$~D', $path)) return null;
        $query = $parts['query'] ?? '';
        if ($query !== '' && (str_starts_with($path,'/product/') || !preg_match('~^page=([1-9][0-9]?)$~D',$query))) return null;
        $scheme = !empty($server['HTTPS']) && $server['HTTPS']!=='off' ? 'https' : ($server['REQUEST_SCHEME']??'http');
        if (!in_array($scheme,['http','https'],true)) return null;
        return hash('sha256', 'html-v1|'.$scheme.'|'.$host.'|'.$path.'|'.$query);
    }

    private function ready(): bool
    {
        if (!is_dir($this->dir) && !@mkdir($this->dir,0700,true) && !is_dir($this->dir)) return false;
        return is_writable($this->dir) && !is_file($this->dir.'/disabled');
    }
    private function atomic(string $file, string $content): bool
    {
        $tmp = @tempnam(dirname($file), '.html-');
        if ($tmp === false) return false;
        $ok = @file_put_contents($tmp,$content) === strlen($content) && @rename($tmp,$file);
        if (!$ok) @unlink($tmp);
        return $ok;
    }
    private function generation(): ?string
    {
        $value = @file_get_contents($this->dir.'/generation');
        if (is_string($value) && preg_match('/^[a-f0-9]{32}$/D',$value)) return $value;
        $lock = @fopen($this->dir.'/generation.lock','c');
        if (!$lock || !flock($lock,LOCK_EX)) { if ($lock) fclose($lock); return null; }
        $value = @file_get_contents($this->dir.'/generation');
        if (!is_string($value) || !preg_match('/^[a-f0-9]{32}$/D',$value)) {
            $value = bin2hex(random_bytes(16));
            if (!$this->atomic($this->dir.'/generation',$value)) $value = null;
        }
        flock($lock,LOCK_UN); fclose($lock);
        return $value;
    }
    public function invalidate(): bool
    {
        if (!$this->ready()) return false;
        if ($this->atomic($this->dir.'/generation',bin2hex(random_bytes(16)))) return true;
        // Missing generation never resolves to an old/default namespace.
        @unlink($this->dir.'/generation');
        @file_put_contents($this->dir.'/disabled','invalidation failed');
        return false;
    }
    public function beginMutation(): void
    {
        if ($this->writer || !$this->ready()) return;
        $lock = @fopen($this->dir.'/writers.lock','c');
        if (!$lock || !flock($lock,LOCK_SH)) { if ($lock) fclose($lock); $this->invalidate(); return; }
        $this->writer = $lock;
        $this->invalidate();
        register_shutdown_function(function (): void { $this->endMutation(); });
    }
    public function endMutation(): void
    {
        if (!$this->writer) return;
        foreach ($this->mutationCallbacks as $callback) $callback();
        $this->mutationCallbacks=[];
        // A second epoch change discards renders started during an open transaction.
        $this->invalidate();
        flock($this->writer,LOCK_UN); fclose($this->writer); $this->writer = null;
    }
    public function onMutationEnd(string $key, callable $callback): void { $this->mutationCallbacks[$key]=$callback; }
    private function guard()
    {
        $lock = @fopen($this->dir.'/writers.lock','c');
        if (!$lock) return null;
        $deadline=microtime(true)+0.01;
        while (!flock($lock,LOCK_EX|LOCK_NB)) {
            if (microtime(true)>=$deadline) { fclose($lock); return null; }
            usleep(500);
        }
        return $lock;
    }
    private function entry(string $key): string { return $this->dir.'/'.substr($key,0,2).'/'.$key.'.htmlcache'; }

    public function read(string $key): ?array
    {
        if (!$this->ready() || !preg_match('/^[a-f0-9]{64}$/D',$key)) return null;
        $guard = $this->guard(); if (!$guard) return null;
        try {
            $generation = $this->generation();
            $file = $this->entry($key);
            if (!$generation || !is_file($file) || filesize($file)>self::SHARD_BYTES) return null;
            $raw = @file_get_contents($file); if (!is_string($raw)) return null;
            $newline = strpos($raw,"\n"); if ($newline===false || $newline>8192) return null;
            $meta = json_decode(substr($raw,0,$newline),true);
            if (!is_array($meta) || ($meta['generation']??'')!==$generation || ($meta['expires']??0)<=($this->clock)() || !is_array($meta['headers']??null)) return null;
            $html = @gzdecode(substr($raw,$newline+1),self::MAX_HTML);
            if (!is_string($html) || hash('sha256',$html)!==($meta['hash']??'')) return null;
            return ['html'=>$html,'headers'=>$meta['headers'],'age'=>max(0,($this->clock)()-$meta['created'])];
        } finally { flock($guard,LOCK_UN); fclose($guard); }
    }
    public function write(string $key, string $generation, string $html, array $headers): bool
    {
        if (!$this->ready() || !preg_match('/^[a-f0-9]{64}$/D',$key) || strlen($html)>self::MAX_HTML) return false;
        $guard=$this->guard(); if (!$guard) return false;
        try {
            if ($this->generation()!==$generation) return false;
            $allowed=[];
            foreach ($headers as $line) {
                if (preg_match('~^(?:set-cookie|location|content-encoding|content-disposition|refresh):|^cache-control:.*(?:private|no-store)~i',$line)) return false;
                if (stripos($line,'Content-Type:')===0 && !preg_match('~^content-type:\s*text/html(?:;|$)~i',$line)) return false;
                if (stripos($line,'Vary:')===0 && !preg_match('~^vary:\s*accept-encoding\s*$~i',$line)) return false;
                if (preg_match('~^(?:content-type|x-robots-tag|content-language|link|content-security-policy|referrer-policy|x-content-type-options):~i',$line) && !preg_match('/[\r\n]/',$line)) $allowed[]=$line;
            }
            if (preg_match('~name=["\x27](?:lead_token|login_csrf)["\x27][^>]*value=["\x27][a-f0-9]{32,}["\x27]~i',$html)
                || preg_match('~data-favorites-token=["\x27][^"\x27]+["\x27]~i',$html)) return false;
            if (!preg_match('~<!doctype html~i',$html) || stripos($html,'</html>')===false || preg_match('~(?:Fatal error|Warning|Notice):~',$html)) return false;
            $now=($this->clock)();
            $expires=min($now+$this->ttl,strtotime('tomorrow',$now),$this->notAfter??PHP_INT_MAX);
            if($expires<=$now)return false;
            $meta=['generation'=>$generation,'created'=>$now,'expires'=>$expires,'headers'=>$allowed,'hash'=>hash('sha256',$html)];
            $compressed=gzencode($html,3); if ($compressed===false) return false;
            $json=json_encode($meta,JSON_UNESCAPED_SLASHES);if ($json===false) return false;
            $data=$json."\n".$compressed;
            if (strlen($data)>self::SHARD_BYTES) return false;
            $file=$this->entry($key);$shard=dirname($file);
            if (!is_dir($shard) && !@mkdir($shard,0700,true) && !is_dir($shard)) return false;
            $files=glob($shard.'/*.htmlcache')?:[];$bytes=0;$times=[];
            foreach ($files as $old) { if ($old===$file) continue; $bytes+=(int)@filesize($old);$times[$old]=(int)@filemtime($old); }
            asort($times);
            while ($times && (count($times)>=self::SHARD_FILES || $bytes+strlen($data)>self::SHARD_BYTES)) {
                $old=array_key_first($times);$size=(int)@filesize($old);if (!@unlink($old)) return false;unset($times[$old]);$bytes-=$size;
            }
            return $this->atomic($file,$data);
        } finally { flock($guard,LOCK_UN); fclose($guard); }
    }
    private function emit(array $entry, string $method): void
    {
        foreach ($entry['headers'] as $line) if (is_string($line) && !preg_match('/[\r\n]/',$line)) header($line,false);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-cache');
        header('X-Page-Cache: HIT'); header('X-Page-Cache-Age: '.$entry['age']);
        if ($method!=='HEAD') echo $entry['html'];
    }
    /** Returns true only when the caller must exit after an early cache hit. */
    public function handle(array $server, array $cookies): bool
    {
        $key=self::identity($server,$cookies);
        if (!$key || session_id()!=='' || session_status()===PHP_SESSION_ACTIVE || !$this->ready() || !function_exists('gzencode')) { header('X-Page-Cache: BYPASS'); return false; }
        $method=$server['REQUEST_METHOD'];
        if ($entry=$this->read($key)) { $this->emit($entry,$method); return true; }
        if ($method==='HEAD') { header('X-Page-Cache: MISS'); return false; }
        @mkdir($this->dir.'/locks',0700,true);
        $lock=@fopen($this->dir.'/locks/'.substr($key,0,3).'.lock','c');
        if (!$lock) { header('X-Page-Cache: BYPASS'); return false; }
        $deadline=microtime(true)+15;
        while (!flock($lock,LOCK_EX|LOCK_NB)) {
            if ($entry=$this->read($key)) { fclose($lock);$this->emit($entry,$method);return true; }
            if (microtime(true)>=$deadline) { fclose($lock);header('X-Page-Cache: BYPASS');return false; }
            usleep(50000);
        }
        if ($entry=$this->read($key)) { flock($lock,LOCK_UN);fclose($lock);$this->emit($entry,$method);return true; }
        $guard=$this->guard();
        if (!$guard) { flock($lock,LOCK_UN);fclose($lock);header('X-Page-Cache: BYPASS');return false; }
        $generation=$this->generation();
        flock($guard,LOCK_UN);fclose($guard);
        if (!$generation) { flock($lock,LOCK_UN);fclose($lock);header('X-Page-Cache: BYPASS');return false; }
        $this->renderLock=$lock;
        header('X-Page-Cache: MISS');
        ob_start(function (string $html, int $phase) use ($key,$generation): string {
            if ($phase & PHP_OUTPUT_HANDLER_CLEAN) $this->streamed=true;
            if (!($phase & PHP_OUTPUT_HANDLER_FINAL)) { $this->streamed=true;return $html; }
            try {
                $error=error_get_last();
                $fatal=$error && in_array($error['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR],true);
                if (!$this->streamed && !$fatal && http_response_code()===200 && session_id()==='' && session_status()!==PHP_SESSION_ACTIVE) $this->write($key,$generation,$html,headers_list());
            } catch (\Throwable $error) {
                error_log('HTML page cache write skipped: '.$error->getMessage());
            } finally { if ($this->renderLock) { flock($this->renderLock,LOCK_UN);fclose($this->renderLock);$this->renderLock=null; } }
            return $html;
        });
        return false;
    }
    public function disable(): void { if (!is_dir($this->dir)) @mkdir($this->dir,0700,true);file_put_contents($this->dir.'/disabled','manual'); }
    public function enable(): void { @unlink($this->dir.'/disabled');$this->invalidate(); }
}
