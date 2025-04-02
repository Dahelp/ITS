<?php

namespace app\models\admin;

use app\models\AppModel;

class Review extends AppModel {

    public $attributes = [     
        'point' => '',
		'date_post' => '',
        'content' => '',        
        'uname' => '',      
		'hide' => '',        
        'finger_up' => '',      
        'finger_down' => '',		
    ];

    public $rules = [
        'required' => [
            ['product_id'],            
        ],        
    ];
	
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
		
		$tmpdir = WWW . '/images/review/tmp/'.$new_name.'';
		$galdir = WWW . '/images/review/gallery/'.$new_name.'';
		$minidir = WWW . '/images/review/mini/'.$new_name.'';

		if($name == 'multi'){
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
                $_SESSION['multi'][] = $new_name;
				
				self::resize($tmpdir, $galdir, $wmax, $hmax, $ext);
				self::resize($tmpdir, $minidir, $wmax, $hmax, $ext);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
        }		
    }
	
	public function editReviewProduct($id, $data){
        $review_product = \R::getCol('SELECT product_id FROM review_product WHERE review_id = ?', [$id]);
        // если менеджер убрал связанные товары - удаляем их
        if(empty($data['product_id']) && !empty($review_product)){
            \R::exec("DELETE FROM review_product WHERE review_id = ?", [$id]);
            return;
        }
        // если добавляются связанные товары
        if(empty($review_product) && !empty($data['product_id'])){
            $sql_part = '';
            foreach($data['product_id'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO review_product (review_id, product_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['product_id'])){
            $result = array_diff($review_product, $data['product_id']);
            if(!empty($result) || count($review_product) != count($data['product_id'])){
                \R::exec("DELETE FROM review_product WHERE review_id = ?", [$id]);
                $sql_part = '';
                foreach($data['product_id'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO review_product (review_id, product_id) VALUES $sql_part");
            }
        }
    }

	public function saveGallery($id){
        if(!empty($_SESSION['multi'])){
            $sql_part = '';
            foreach($_SESSION['multi'] as $v){
                $sql_part .= "('$v', $id),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO review_gallery (img, review_id) VALUES $sql_part");
            unset($_SESSION['multi']);
        }
    }
}