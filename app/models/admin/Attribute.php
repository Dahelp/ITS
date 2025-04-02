<?php

namespace app\models\admin;

use app\models\AppModel;

class Attribute extends AppModel {

    public $attributes = [
		'attribute_group_id' => '',
		'attribute_name' => '',                
        'attribute_position' => '',
        'attribute_hide' => '',
		'hide_product' => '',
		'url_params' => '',
    ];

    public $rules = [
        'required' => [
            ['attribute_name'],            
        ],        
    ];

	public function checkUnique(){
        $attribute = \R::findOne('attribute', 'attribute_name = ? AND attribute_group_id = ?', [$this->attributes['attribute_name'], $this->attributes['attribute_group_id']]);
        if($attribute){
            if($attribute->attribute_name == $this->attributes['attribute_name'] && $attribute->attribute_group_id == $this->attributes['attribute_group_id']){
                $this->errors['unique'][] = 'Это название атрибута уже существует в этой категории';
            }
            return false;
        }
        return true;
    }

}