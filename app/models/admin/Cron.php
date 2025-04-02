<?php

namespace app\models\admin;

use app\models\AppModel;

class Cron extends AppModel {

    public $attributes = [
		'name' => '',
        'alias' => '',
		'url_params' => '',
		'url_download' => '',
		'hide' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],			
        ],
    ];

    public function checkUnique(){
		if($this->attributes['alias'] !=""){
			$cron = \R::findOne('cron', 'alias = ?', [$this->attributes['alias']]);
		
			if($cron){
				if($cron->alias == $this->attributes['alias']){
					$this->errors['unique'][] = 'CRON с таким url уже существует';
				}
				return false;
			}
		}
        return true;
	}

}