<?php

namespace app\models\admin;

use app\models\AppModel;

class PlaginsInseo extends AppModel{

    public $attributes = [
        'tip' => '',
        'category_id' => '',
		'name' => '',
		'content' => '',
        'title' => '',		
        'description' => '',
		'keywords' => '',
		'hide' => '',
    ];

    public $rules = [
        'required' => [
			['hide'],
			['tip'],
        ],        
    ];

	public function checkUnique(){
        $attribute = \R::findOne('plagins_inseo', 'tip = ? AND category_id = ?', [$this->attributes['tip'], $this->attributes['category_id']]);
        if($attribute){
            if($attribute->tip == $this->attributes['tip'] AND $attribute->category_id == $this->attributes['category_id']){
                $this->errors['unique'][] = 'Правило уже существует';
            }
            return false;
        }
        return true;
    }
	
}