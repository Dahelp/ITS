<?php

namespace app\models\admin;

use app\models\AppModel;
use app\helpers\Upload;

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
	
	

public function uploadImg($name, $wmax, $hmax, $wmaxmini, $hmaxmini) {
    $res = Upload::handle($name, [
        'max_mb'   => \R::findOne('options', 'alt_name = ?', [option_size_product])->znachenie ?? 5,
        'base_dir' => WWW,
        'variants' => [
            'multi' => [
                ['dir' => '/images/review/gallery', 'name'=>'{basename}.{ext}', 'w'=>$wmax,     'h'=>$hmax],
                ['dir' => '/images/review/mini',    'name'=>'{basename}.{ext}', 'w'=>$wmaxmini, 'h'=>$hmaxmini],
            ],
        ],
        'session' => ['multi'=>'multi'],
    ]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
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