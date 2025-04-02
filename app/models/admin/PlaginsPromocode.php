<?php

namespace app\models\admin;

use app\models\AppModel;

class PlaginsPromocode extends AppModel{
	
	public $attributes = [
		'promocode' => '',
		'value' => '',
        'hide' => '',
	
    ];

    public $rules = [
        'required' => [
            ['promocode'],
            ['value'],
            ['category_id'],
        ],
    ];

	
	public function checkUnique(){
        $promocode = \R::findOne('plagins_promocode', 'promocode = ?', [$this->attributes['promocode']]);
        if($promocode){
            if($promocode->promocode == $this->attributes['promocode']){
                $this->errors['unique'][] = 'Промокод уже существует';
            }
            return false;
        }
        return true;
    }
	
}