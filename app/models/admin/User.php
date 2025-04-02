<?php

namespace app\models\admin;

use app\models\AppModel;

class User extends AppModel {

    public $attributes = [
        'id' => '',
        'password' => '',
        'name' => '',
        'email' => '',		
        'role' => '',
		'groups' => '',
		'telefon' => '',
		'admin_id' => '',
		'newsletter' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
            ['email'],
            ['role'],
			['groups'],
        ],
        'email' => [
            ['email'],
        ],
    ];

    public function checkUnique(){
        $user = \R::findOne('user', 'email = ? AND id <> ?', [$this->attributes['email'], $this->attributes['id']]);
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
	
	
    public static function isAdmin(){
        return (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'admin');
    }
		
	public function editNewsletterGroup($id, $data){
        $user_newsletter = \R::getCol('SELECT newsletter_id FROM user_newsletter WHERE user_id = ?', [$id]);
        // если удаление
        if(empty($data['unewslet']) && !empty($user_newsletter)){
            \R::exec("DELETE FROM user_newsletter WHERE user_id = ?", [$id]);
            return;
        }
        // если добавляются новые
        if(empty($user_newsletter) && !empty($data['unewslet'])){
            $sql_part = '';
            foreach($data['unewslet'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO user_newsletter (user_id, newsletter_id) VALUES $sql_part");
            return;
        }
        // если изменились - удалим и запишем новые
        if(!empty($data['unewslet'])){
            $result = array_diff($user_newsletter, $data['unewslet']);
            if(!empty($result) || count($user_newsletter) != count($data['unewslet'])){
                \R::exec("DELETE FROM user_newsletter WHERE user_id = ?", [$id]);
                $sql_part = '';
                foreach($data['unewslet'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO user_newsletter (user_id, newsletter_id) VALUES $sql_part");
            }
        }
    }

}