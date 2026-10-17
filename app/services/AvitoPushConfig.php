<?php
namespace app\services;

class AvitoPushConfig
{
    private string $directory;
    public function __construct(?string $directory = null) { $this->directory=$directory ?: ROOT.'/storage/avito-push'; }
    public function directory(): string { return $this->directory; }
    public function settings(): array
    {
        $file=$this->directory.'/config.json';
        return is_file($file) ? (json_decode((string)file_get_contents($file),true) ?: []) : [];
    }
    public function enabled(): bool { return ($this->settings()['enabled'] ?? false) === true; }
    public function ready(): bool { return $this->enabled() && is_file($this->directory.'/service-account.json'); }
    public function credentials(): array
    {
        if(!$this->ready()) throw new \RuntimeException('Push is not configured');
        $credentials=json_decode((string)file_get_contents($this->directory.'/service-account.json'),true);
        if(!is_array($credentials) || ($credentials['type']??'')!=='service_account'
            || !preg_match('/\A[a-z][a-z0-9-]{4,61}[a-z0-9]\z/',(string)($credentials['project_id']??''))
            || !filter_var($credentials['client_email']??'',FILTER_VALIDATE_EMAIL)
            || !is_string($credentials['private_key']??null)) throw new \RuntimeException('Invalid push service account');
        return $credentials;
    }
    public function setup(): void
    {
        if(!is_dir($this->directory) && !mkdir($this->directory,0700,true) && !is_dir($this->directory)) throw new \RuntimeException('Push storage unavailable');
        if(file_put_contents($this->directory.'/.htaccess',"Require all denied\n",LOCK_EX)===false) throw new \RuntimeException('Push protection unavailable');
        $file=$this->directory.'/config.json';
        if(!is_file($file)) {
            $handle=fopen($file,'x');
            if($handle) { fwrite($handle,"{\"enabled\":false}\n");fclose($handle);chmod($file,0600); }
        }
    }
}
