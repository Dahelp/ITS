<?php

namespace app\models\admin;

use app\models\AppModel;

class UserGroup extends AppModel{

    public $attributes = [
        'name' => '',
        'role' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
            ['role'],
        ],        
    ];

}