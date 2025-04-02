<?php

namespace app\models\admin;

use app\models\AppModel;

class UserCustomer extends AppModel{

   public $attributes = [
        'id' => '',
        'password' => '',
        'name' => '',
        'email' => '',		
        'role' => '',
		'groups' => '',
		'telefon' => '',
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

}