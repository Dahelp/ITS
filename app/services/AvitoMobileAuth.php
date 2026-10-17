<?php
namespace app\services;

/** Opaque device tokens; only their hashes are stored. Cookies never authorize this API. */
class AvitoMobileAuth
{
    public const TTL = 2592000;

    public static function ensureSchema(): void
    {
        \R::exec('CREATE TABLE IF NOT EXISTS avito_mobile_session (token_hash CHAR(64) PRIMARY KEY, user_id INT NOT NULL, password_stamp CHAR(64) NOT NULL, device_name VARCHAR(120) NOT NULL, created_at INT NOT NULL, expires_at INT NOT NULL, INDEX(user_id), INDEX(expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        \R::exec('CREATE TABLE IF NOT EXISTS avito_mobile_throttle (bucket CHAR(64) PRIMARY KEY, attempts INT NOT NULL, expires_at INT NOT NULL, INDEX(expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private function throttle(string $scope, int $limit): void
    {
        $key = hash('sha256', $scope); $now = time();
        $ignore = \R::getDatabaseAdapter()->getDatabase()->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'OR IGNORE' : 'IGNORE';
        \R::exec("INSERT $ignore INTO avito_mobile_throttle (bucket,attempts,expires_at) VALUES (?,0,?)", [$key,$now+900]);
        \R::exec('UPDATE avito_mobile_throttle SET attempts=CASE WHEN expires_at<=? THEN 1 ELSE attempts+1 END, expires_at=CASE WHEN expires_at<=? THEN ? ELSE expires_at END WHERE bucket=?', [$now,$now,$now+900,$key]);
        if ((int)\R::getCell('SELECT attempts FROM avito_mobile_throttle WHERE bucket=?',[$key]) > $limit) throw new \RuntimeException('Too many login attempts',429);
    }

    public function login(string $email, string $password, string $device, string $ip): array
    {
        $email = trim($email);
        if (strlen($email)>254 || strlen($password)>1024 || mb_strlen($device,'UTF-8')>120) throw new \InvalidArgumentException('Проверьте длину полей.');
        $this->throttle('ip:'.$ip,50);
        $this->throttle('email:'.mb_strtolower($email,'UTF-8'),10);
        $user = \R::getRow('SELECT id,name,role,password FROM user WHERE email=? LIMIT 1',[$email]);
        // Fixed dummy hash keeps unknown-account checks comparable in cost.
        $valid = password_verify($password, (string)($user['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'));
        if (!$valid || !$user || !AvitoMessenger::allowed((int)$user['id'])) throw new \RuntimeException('Неверные данные или доступ к чату отключён.',401);
        $token = bin2hex(random_bytes(32)); $now = time();
        \R::exec('DELETE FROM avito_mobile_session WHERE expires_at<=?',[$now]);
        \R::exec('DELETE FROM avito_mobile_throttle WHERE expires_at<=?',[$now]);
        \R::exec('INSERT INTO avito_mobile_session (token_hash,user_id,password_stamp,device_name,created_at,expires_at) VALUES (?,?,?,?,?,?)', [hash('sha256',$token),(int)$user['id'],hash('sha256',$user['password']),trim($device) ?: 'Телефон',$now,$now+self::TTL]);
        return ['access_token'=>$token,'token_type'=>'Bearer','expires_at'=>$now+self::TTL,'user'=>$this->profile($user)];
    }

    private function profile(array $user): array
    {
        return ['id'=>(int)$user['id'],'name'=>(string)$user['name'],'role'=>(string)$user['role']];
    }

    public function authenticate(string $token): array
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/D',$token)) throw new \RuntimeException('Требуется вход.',401);
        $user = \R::getRow('SELECT u.id,u.name,u.role,u.password,s.password_stamp FROM avito_mobile_session s INNER JOIN user u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires_at>?',[hash('sha256',$token),time()]);
        if (!$user || !hash_equals($user['password_stamp'],hash('sha256',$user['password']))) throw new \RuntimeException('Сессия истекла. Войдите заново.',401);
        if (!AvitoMessenger::allowed((int)$user['id'])) throw new \RuntimeException('Доступ к чату отключён.',403);
        return $this->profile($user);
    }

    public function logout(string $token, int $userId, bool $all = false): void
    {
        if ($all) \R::exec('DELETE FROM avito_mobile_session WHERE user_id=?',[$userId]);
        else \R::exec('DELETE FROM avito_mobile_session WHERE token_hash=? AND user_id=?',[hash('sha256',$token),$userId]);
    }

    public function sessions(int $userId, string $token): array
    {
        $rows = \R::getAll('SELECT token_hash,device_name,created_at,expires_at FROM avito_mobile_session WHERE user_id=? AND expires_at>? ORDER BY created_at DESC',[$userId,time()]);
        foreach ($rows as &$row) { $row['current'] = hash_equals($row['token_hash'],hash('sha256',$token)); unset($row['token_hash']); }
        return $rows;
    }
}
