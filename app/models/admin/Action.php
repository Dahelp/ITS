<?php

namespace app\models\admin;

use app\models\AppModel;

class Action extends AppModel {
	
	public $attributes = [
		'product_id' => '',
		'type_id' => '',
        'znachenie' => '',
        'date_start' => '',
        'date_end' => '',
        'date_create' => '',
        'user_id' => '',
        'hide' => '',        		
    ];
	
	public $rules = [
        'required' => [
            ['product_id'],
            ['type_id'],
            ['znachenie'],
			['date_start'],
            ['date_end'],
            ['hide'],
        ],        
    ];
	
	public function checkUnique(){
        $act = \R::findOne('actions', 'product_id = ?', [$this->attributes['product_id']]);
        if($act){
            if($act->product_id == $this->attributes['product_id']){
                $this->errors['unique'][] = 'Товар уже добавлен в акцию';
            }
            return false;
        }
        return true;
    }
}