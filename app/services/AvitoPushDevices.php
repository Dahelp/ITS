<?php
namespace app\services;

class AvitoPushDevices
{
    public static function ensureSchema(): void
    {
        \R::exec('CREATE TABLE IF NOT EXISTS avito_push_device (token_hash CHAR(64) PRIMARY KEY, session_hash CHAR(64) NOT NULL, user_id INT NOT NULL, push_token TEXT NOT NULL, platform VARCHAR(8) NOT NULL, updated_at INT NOT NULL, INDEX(session_hash), INDEX(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    public static function register(string $session, int $userId, string $token, string $platform): void
    {
        if(!in_array($platform,['android','ios'],true) || strlen($token)<20 || strlen($token)>4096 || preg_match('/[^\x21-\x7e]/',$token)) throw new \InvalidArgumentException('Некорректный токен устройства.');
        // One FCM token belongs to one login; changing employee/session transfers ownership.
        \R::begin();
        try {
            \R::exec('DELETE FROM avito_push_device WHERE session_hash=? OR token_hash=?',[hash('sha256',$session),hash('sha256',$token)]);
            \R::exec('INSERT INTO avito_push_device (token_hash,session_hash,user_id,push_token,platform,updated_at) VALUES (?,?,?,?,?,?)',[hash('sha256',$token),hash('sha256',$session),$userId,$token,$platform,time()]);
            \R::commit();
        } catch(\Throwable $e) { \R::rollback();throw $e; }
    }
    public static function unregister(string $session): void { \R::exec('DELETE FROM avito_push_device WHERE session_hash=?',[hash('sha256',$session)]); }
    public static function status(string $session): array
    {
        $device=\R::getRow('SELECT platform,updated_at FROM avito_push_device WHERE session_hash=?',[hash('sha256',$session)]);
        return ['registered'=>(bool)$device,'device'=>$device ?: null];
    }
    public static function eligible(): array
    {
        $rows=\R::getAll("SELECT d.token_hash,d.session_hash,d.push_token,d.platform,s.password_stamp,u.password FROM avito_push_device d INNER JOIN avito_mobile_session s ON s.token_hash=d.session_hash AND s.user_id=d.user_id INNER JOIN user u ON u.id=d.user_id INNER JOIN avito_chat_manager m ON m.user_id=u.id WHERE s.expires_at>? AND u.role IN ('admin','manager')",[time()]);
        $eligible=[];
        foreach($rows as $row) if(hash_equals($row['password_stamp'],hash('sha256',$row['password']))) {
            unset($row['password'],$row['password_stamp']);$eligible[$row['token_hash']]=$row;
        }
        return $eligible;
    }
    public static function forgetInvalid(string $hash,string $token): void
    {
        // A concurrent refresh/reassignment must not remove a newer registration.
        \R::exec('DELETE FROM avito_push_device WHERE token_hash=? AND push_token=?',[$hash,$token]);
    }
}
