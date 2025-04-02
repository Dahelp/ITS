<?php
namespace app\models;

class User extends AppModel {

    public $attributes = [
        'password' => '',
        'name' => '',
        'email' => '',
        'role' => 'user',
		'groups' => '3',
		'telefon' => '',
		'newsletter' => '',
    ];

    public $rules = [
        'required' => [            
            ['name'],
            ['email'],            
        ],
        'email' => [
            ['email'],
        ],
        'lengthMin' => [
            ['password', 6],
        ]
    ];

    public function checkUnique(){
        $user = \R::findOne('user', 'email = ?', [$this->attributes['email']]);
        if($user){
            if($user->email == $this->attributes['email']){
                $this->errors['unique'][] = 'Этот email уже занят';
            }
            return false;
        }
        return true;
    }
	
    public function login($isAdmin = false){
        $email = !empty(trim($_POST['email'])) ? trim($_POST['email']) : null;
        $password = !empty(trim($_POST['password'])) ? trim($_POST['password']) : null;
        if($email && $password){
            if($isAdmin){
                $user = \R::findOne('user', "email = ? AND role = 'admin'", [$email]);
            }else{
                $user = \R::findOne('user', "email = ?", [$email]);
            }
            if($user){
                if(password_verify($password, $user->password)){
                    foreach($user as $k => $v){
                        if($k != 'password') $_SESSION['user'][$k] = $v;
                    }
					$res = \R::exec("UPDATE user SET date_last_visit = '".date('Y-m-d H:i:s')."' WHERE email = '".$email."'");
					
                    return true;					
                }
            }
        }
        return false;
    }
	
	public function generate_code() {
                
		$hours = date("H"); // час       
		$minuts = substr(date("H"), 0 , 1);// минута 
		$mouns = date("m");    // месяц             
		$year_day = date("z"); // день в году

		$str = $hours . $minuts . $mouns . $year_day; //создаем строку
		$str = md5(md5($str)); //дважды шифруем в md5
		$str = strrev($str);// реверс строки
		$str = substr($str, 3, 6); // извлекаем 6 символов, начиная с 3		

		$array_mix = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
		srand ((float)microtime()*1000000);
		shuffle ($array_mix);		
		return implode("", $array_mix);
	}

    public static function checkAuth(){
        return isset($_SESSION['user']);
    }

    public static function isAdmin(){
        return (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'admin');
    }
	
	public function get_user_hash($hash){

		$res = \R::findOne('recover', 'hash = ?', [$hash]);
		$now = time();
		$times = $res["expire"] - $now;
		if($times < 0) {
			$_SESSION['error'] = 'Ссылка устарела или вы перешли по некорректной ссылке. Пройдите процедуру восстановления заново по <a href="user/recover">ссылке</a>';
			\R::exec("DELETE FROM recover WHERE expire < '".$now."'");
			return false;
		}
		return $res;
	}

		

}