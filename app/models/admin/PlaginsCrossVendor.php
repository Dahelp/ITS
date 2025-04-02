<?php

namespace app\models\admin;

use app\models\AppModel;

class PlaginsCrossVendor extends AppModel{

    public $attributes = [
        'name' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],            
        ],
    ];

	public function checkUnique(){
        $attribute = \R::findOne('plagins_cross_vendor', 'name = ?', [$this->attributes['name']]);
        if($attribute){
            if($attribute->name == $this->attributes['name']){
                $this->errors['unique'][] = 'Это название производителя уже существует';
            }
            return false;
        }
        return true;
    }
}