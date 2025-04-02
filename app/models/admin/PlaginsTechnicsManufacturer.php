<?php

namespace app\models\admin;

use app\models\AppModel;

class PlaginsTechnicsManufacturer extends AppModel {

    public $attributes = [
		'name' => '',		
        'title' => '',        
        'keywords' => '',
        'description' => '',        
        'content' => '',      
        'alias' => '',
		'hide' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],            
        ],        
    ];
	
	public function getImg(){
        if(!empty($_SESSION['single'])){
            $this->attributes['img'] = $_SESSION['single'];
            unset($_SESSION['single']);
        }
    }
	
	public function uploadImg($name, $wmax, $hmax){
        $uploaddir = WWW . '/images/technics_manufacturer/baseimg/';

        $ext = strtolower(preg_replace("#.+\.([a-z]+)$#i", "$1", $_FILES[$name]['name'])); // расширение картинки
        $types = array("image/gif", "image/png", "image/jpeg", "image/pjpeg", "image/x-png"); // массив допустимых расширений
        if($_FILES[$name]['size'] > 1048576){
            $res = array("error" => "Ошибка! Максимальный вес файла - 1 Мб!");
            exit(json_encode($res));
        }
        if($_FILES[$name]['error']){
            $res = array("error" => "Ошибка! Возможно, файл слишком большой.");
            exit(json_encode($res));
        }
        if(!in_array($_FILES[$name]['type'], $types)){
            $res = array("error" => "Допустимые расширения - .gif, .jpg, .png");
            exit(json_encode($res));
        }
        $new_name = md5(time()).".$ext";
        $uploadfile = $uploaddir.$new_name;
		if($name == 'single'){
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $uploadfile)){
            
                $_SESSION['single'] = $new_name;
            
				self::resize($uploadfile, $uploadfile, $wmax, $hmax, $ext);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
		}
    }
	
	public function checkUnique(){
        $technics_types = \R::findOne('technics_manufacturer', 'name = ?', [$this->attributes['name']]);
        if($technics_types){
            if($technics_types->name == $this->attributes['name']){
                $this->errors['unique'][] = 'Название производителя техники уже существует';
            }
            return false;
        }
        return true;
    }
}