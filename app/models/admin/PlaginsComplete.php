<?php

namespace app\models\admin;

use app\models\AppModel;

class PlaginsComplete extends AppModel{

    public $attributes = [
        'name' => '',
        'category_id' => '',
		'content' => '',
		'title' => '',
        'description' => '',		
        'keywords' => '',
		'alias' => '',
		'hide' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],            
        ],        
    ];

	public function checkUnique(){
        $attribute = \R::findOne('plagins_complete', 'name = ?', [$this->attributes['name']]);
        if($attribute){
            if($attribute->name == $this->attributes['name']){
                $this->errors['unique'][] = 'Это название комплекта уже существует';
            }
            return false;
        }
        return true;
    }

	public function editProductComplete($id, $data){
        $complete_product = \R::getCol('SELECT complete_id FROM plagins_complete_product WHERE complete_id = ?', [$id]);
        // если менеджер убрал связанные товары - удаляем их
        if(empty($data['complete']) && !empty($complete_product)){
            \R::exec("DELETE FROM plagins_complete_product WHERE complete_id = ?", [$id]);
            return;
        }
        // если добавляются связанные товары
        if(empty($complete_product) && !empty($data['complete'])){
            $sql_part = '';
            foreach($data['complete'] as $v){                
                $sql_part .= "($id, '".$v["product_id"]."', '".$v["quantity"]."', '".$v["price"]."', '".$v["discount"]."', '".$v["discount_amount"]."'),";            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO plagins_complete_product (complete_id, product_id, qty, price, discount, discount_amount) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['complete'])){
            $result = array_diff($complete_product, $data['complete']);
            if(!empty($result) || count($complete_product) != count($data['complete'])){
                \R::exec("DELETE FROM plagins_complete_product WHERE complete_id = ?", [$id]);
                $sql_part = '';
                foreach($data['complete'] as $v){                   
                    $sql_part .= "($id, '".$v["product_id"]."', '".$v["quantity"]."', '".$v["price"]."', '".$v["discount"]."', '".$v["discount_amount"]."'),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO plagins_complete_product (complete_id, product_id, qty, price, discount, discount_amount) VALUES $sql_part");
            }
        }
    }
	
	public function getImg(){
        if(!empty($_SESSION['single'])){
            $this->attributes['img'] = $_SESSION['single'];
            unset($_SESSION['single']);
        }
    }

    public function saveGallery($id){
        if(!empty($_SESSION['multi'])){
            $sql_part = '';
            foreach($_SESSION['multi'] as $v){
                $sql_part .= "('$v', $id),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO plagins_complete_gallery (complete_id, img) VALUES $sql_part");
            unset($_SESSION['multi']);
        }
    }
	
	public function uploadImg($name, $wmax, $hmax, $wmaxmini, $hmaxmini){		
        $ext = strtolower(preg_replace("#.+\.([a-z]+)$#i", "$1", $_FILES[$name]['name'])); // расширение картинки
        $types = array("image/gif", "image/png", "image/jpeg", "image/pjpeg", "image/x-png"); // массив допустимых расширений
		$size = \R::findOne('options', 'alt_name = ?', [option_size_product]);
		$size_product = $size->znachenie * 1048576;
        if($_FILES[$name]['size'] > $size_product){
            $res = array("error" => "Ошибка! Максимальный вес файла - ".$size." Мб!");
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
		
		$tmpdir = WWW . '/images/complete/tmp/'.$new_name.'';
        $basedir = WWW . '/images/complete/baseimg/'.$new_name.'';
		$galdir = WWW . '/images/complete/gallery/'.$new_name.'';
		$minidir = WWW . '/images/complete/mini/'.$new_name.'';
		
		if($name == 'single'){
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
            
                $_SESSION['single'] = $new_name;
            
				self::resize($tmpdir, $basedir, $wmax, $hmax, $ext);
				self::resize($tmpdir, $minidir, $wmaxmini, $hmaxmini, $ext);
				@unlink($tmpdir);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
		}else{
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
                $_SESSION['multi'][] = $new_name;
				
				self::resize($tmpdir, $galdir, $wmax, $hmax, $ext);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
        }
    }
}