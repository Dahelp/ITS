<?php

namespace app\models\admin;

use app\models\AppModel;

class Category extends AppModel {

    public $attributes = [
		'type_id' => '',
		'name' => '',
        'title' => '',
        'parent_id' => '',
        'keywords' => '',
        'description' => '',
        'alias' => '',
		'content' => '',
		'position' => '',
		'sale' => '',
		'hide' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
        ]
    ];
	
	public function getImg(){
        if(!empty($_SESSION['single'])){
            $this->attributes['img'] = $_SESSION['single'];
            unset($_SESSION['single']);
        }
    }

	public function uploadImg($name, $wmax, $hmax){
        $uploaddir = WWW . '/images/category/baseimg/';
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
        $category = \R::findOne('category', 'name = ?', [$this->attributes['name']]);
        if($category){
            if($category->name == $this->attributes['name']){
                $this->errors['unique'][] = 'Название категории уже существует';
            }
            return false;
        }
        return true;
    }

}