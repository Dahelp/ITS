<?php

namespace app\models\admin;

use app\models\AppModel;

class Company extends AppModel {

    public $attributes = [
		'comp_name' => '',
		'comp_short_name' => '',
        'user_id' => '',        
        'tip' => '',
        'url_address' => '',        
        'postal_address' => '',
		'ogrn' => '',
        'inn' => '',
		'kpp' => '',
		'bik' => '',        
        'raschet' => '',
        'korschet' => '',        
        'bank' => '',      
        'dir_name' => '',
		'nds' => '',        
        'dogovor' => '',        
        'hide' => 'show',
    ];

    public $rules = [
        'required' => [
            ['comp_name'],            
        ],        
    ];
	
	public function checkUnique(){
        $company = \R::findOne('company', 'comp_name = ?', [$this->attributes['comp_name']]);
        if($company){
            if($company->comp_name == $this->attributes['comp_name']){
                $this->errors['unique'][] = 'Название компании уже существует';
            }
            return false;
        }
        return true;
    }
	
	public function editCompanyTypeprice($id, $data){
		// удалим все и запишем новые
        if(!empty($data['company_priceopt'])){
            
			\R::exec("DELETE FROM company_typeprice WHERE company_id = ?", [$id]);
			$sql_part = '';
			foreach($data['company_priceopt'] as $v){
										
					$sql_part .= "($id, '".$v["category_id"]."', '".$v["znachenie"]."'),";
				
			}
			if($sql_part == ""){ } else {
				$sql_part = rtrim($sql_part, ',');
				\R::exec("INSERT INTO company_typeprice (company_id, category_id, znachenie) VALUES $sql_part");
			}
        }		
    }
}