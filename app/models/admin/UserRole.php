<?php

namespace app\models\admin;

use app\models\AppModel;

class UserRole extends AppModel{

    public $attributes = [
        'name' => '',
        'alt_name' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
            ['alt_name'],
        ],        
    ];

}