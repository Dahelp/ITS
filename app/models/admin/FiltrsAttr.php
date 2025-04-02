<?php

namespace app\models\admin;

use app\models\AppModel;

class FiltrsAttr extends AppModel{

    public $attributes = [
        'value' => '',
		'alias' => '',
        'attr_group_id' => '',
		'content' => '',
		'title' => '',
		'description' => '',
        'keywords' => '',		
        'hide' => '',
    ];

    public $rules = [
        'required' => [
            ['value'],
            ['attr_group_id'],
			['hide'],
        ],
        'integer' => [
            ['attr_group_id'],
        ]
    ];
	
	public function getImg(){
        if(!empty($_SESSION['single'])){
            $this->attributes['img'] = $_SESSION['single'];
            unset($_SESSION['single']);
        }
    }
	
	public function uploadImg($name, $wmax, $hmax){
        $uploaddir = WWW . '/images/filtrs/baseimg/';

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
        $attribute = \R::findOne('attribute_value', 'value = ? AND attr_group_id = ?', [$this->attributes['value'], $this->attributes['attr_group_id']]);
        if($attribute){
            if($attribute->value == $this->attributes['value'] && $attribute->attr_group_id == $this->attributes['attr_group_id']){
                $this->errors['unique'][] = 'Это название фильтра уже существует';
            }
            return false;
        }
        return true;
    }
}