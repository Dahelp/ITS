<?php

namespace app\models\admin;

use ishop\App;
use app\models\AppModel;

class ContentsPages extends AppModel{

    public $attributes = [
        'name' => '',
		'anons' => '',
		'content' => '',
		'type_id' => '',
		'hide' => '',
		'alias' => '',
		'title' => '',
		'description' => '',
		'keywords' => '',
		'img_hide' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
			['type_id'],			
        ],
		'integer' => [
            ['type_id'],
        ],
    ];
	
	public function editRelatedProduct($id, $data){
        $related_product = \R::getCol('SELECT related_id FROM content_related WHERE content_id = ?', [$id]);
        // если менеджер убрал связанные товары - удаляем их
        if(empty($data['related']) && !empty($related_product)){
            \R::exec("DELETE FROM content_related WHERE content_id = ?", [$id]);
            return;
        }
        // если добавляются связанные товары
        if(empty($related_product) && !empty($data['related'])){
            $sql_part = '';
            foreach($data['related'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO content_related (content_id, related_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['related'])){
            $result = array_diff($related_product, $data['related']);
            if(!empty($result) || count($related_product) != count($data['related'])){
                \R::exec("DELETE FROM content_related WHERE content_id = ?", [$id]);
                $sql_part = '';
                foreach($data['related'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO content_related (content_id, related_id) VALUES $sql_part");
            }
        }
    }
	
	public function getImg(){
        if(!empty($_SESSION['single'])){
            $this->attributes['img'] = $_SESSION['single'];
            unset($_SESSION['single']);
        }
    }
	
	public function uploadImg($name, $wmax, $hmax, $wmaxmini, $hmaxmini){

        $ext = strtolower(preg_replace("#.+\.([a-z]+)$#i", "$1", $_FILES[$name]['name'])); // расширение картинки
        $types = array("image/gif", "image/png", "image/jpeg", "image/pjpeg", "image/x-png"); // массив допустимых расширений
		$size = \R::findOne('options', 'alt_name = ?', [option_size_product]);
		$size_product = $size->znachenie * 1048576;
        if($_FILES[$name]['size'] > $size_product){
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
		
        $tmpdir = WWW . '/images/contents/tmp/'.$new_name.'';
        $basedir = WWW . '/images/contents/baseimg/'.$new_name.'';
		$minidir = WWW . '/images/contents/mini/'.$new_name.'';
		
		if($name == 'single'){
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
            
                $_SESSION['single'] = $new_name;
            
				self::resize($tmpdir, $basedir, $wmax, $hmax, $ext);
				self::resize($tmpdir, $minidir, $wmaxmini, $hmaxmini, $ext);
				@unlink($tmpdir);
				
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
		}
    }
	
	public function checkUnique(){
        $page = \R::findOne('contents', 'name = ?', [$this->attributes['name']]);
        if($page){
            if($page->name == $this->attributes['name']){
                $this->errors['unique'][] = 'Название контента уже существует';
            }
            return false;
        }
        return true;
    }
}