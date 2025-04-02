<?php

namespace app\models\admin;

use app\models\AppModel;

class Product extends AppModel {

    public $attributes = [
		'name' => '',
		'article' => '',
        'title' => '',
        'category_id' => '',
        'keywords' => '',
        'description' => '',
        'price' => '',
		'price_rrs' => '',
        'content' => '',
        'hide' => '',
        'hit' => '',
		'new_product' => '',
		'sale' => '',
		'unit' => '',
		'weight' => '',
		'volume' => '',
        'alias' => '',
		'brand_id' => '',
		'model' => '',
		'opt_price' => '',
		'stock_status_id' => '',
		'quantity' => '',
		'url_video' => '',
		'note' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
            ['category_id'],
            ['price'],
        ],
        'integer' => [
            ['category_id'],
        ],
    ];
	
    public function editRelatedProduct($id, $data){
        $related_product = \R::getCol('SELECT related_id FROM related_product WHERE product_id = ?', [$id]);
        // если менеджер убрал связанные товары - удаляем их
        if(empty($data['related']) && !empty($related_product)){
            \R::exec("DELETE FROM related_product WHERE product_id = ?", [$id]);
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
            \R::exec("INSERT INTO related_product (product_id, related_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['related'])){
            $result = array_diff($related_product, $data['related']);
            if(!empty($result) || count($related_product) != count($data['related'])){
                \R::exec("DELETE FROM related_product WHERE product_id = ?", [$id]);
                $sql_part = '';
                foreach($data['related'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO related_product (product_id, related_id) VALUES $sql_part");
            }
        }
    }
	
	public function editSimilarProduct($id, $data){
        $similar_product = \R::getCol('SELECT similar_id FROM similar_product WHERE product_id = ?', [$id]);
        // если менеджер убрал связанные товары - удаляем их
        if(empty($data['similar']) && !empty($similar_product)){
            \R::exec("DELETE FROM similar_product WHERE product_id = ?", [$id]);
            return;
        }
        // если добавляются связанные товары
        if(empty($similar_product) && !empty($data['similar'])){
            $sql_part = '';
            foreach($data['similar'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO similar_product (product_id, similar_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['similar'])){
            $result = array_diff($similar_product, $data['similar']);
            if(!empty($result) || count($similar_product) != count($data['similar'])){
                \R::exec("DELETE FROM similar_product WHERE product_id = ?", [$id]);
                $sql_part = '';
                foreach($data['similar'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO similar_product (product_id, similar_id) VALUES $sql_part");
            }
        }
    }
	
	public function editServiceProduct($id, $data){
        $service_product = \R::getCol('SELECT service_id FROM service_product WHERE product_id = ?', [$id]);
        // если менеджер убрал связанные товары - удаляем их
        if(empty($data['service']) && !empty($service_product)){
            \R::exec("DELETE FROM service_product WHERE product_id = ?", [$id]);
            return;
        }
        // если добавляются связанные товары
        if(empty($service_product) && !empty($data['service'])){
            $sql_part = '';
            foreach($data['service'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO service_product (product_id, service_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['service'])){
            $result = array_diff($service_product, $data['service']);
            if(!empty($result) || count($service_product) != count($data['service'])){
                \R::exec("DELETE FROM service_product WHERE product_id = ?", [$id]);
                $sql_part = '';
                foreach($data['service'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO service_product (product_id, service_id) VALUES $sql_part");
            }
        }
    }
	
	public function traverseArray($arr){
		$flag = false;
		foreach($arr as $value){
			if(is_array($value)){
				$flag = traverseArray($value);
				if($flag) return true;
			}else{
				if(isset($value) && $value != '') return true;
			}
		}
		return $flag;
	}

    public function editFilter($id, $data){        				
			
		\R::exec("DELETE FROM attribute_product WHERE product_id = ?", [$id]);
		$sql_parts = '';
		foreach($data['attrs'] as $s){					
			
			if($s !="") {
				$sql_parts .= "($s, $id),";
			}
		}
		$sql_parts = rtrim($sql_parts, ',');
		
		\R::exec("INSERT INTO attribute_product (attr_id, product_id) VALUES $sql_parts");
            
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
            \R::exec("INSERT INTO gallery (img, product_id) VALUES $sql_part");
            unset($_SESSION['multi']);
        }
    }
	
	public function getUnloadImg(){
        if(!empty($_SESSION['unload'])){
            $this->attributes['unload_img'] = $_SESSION['unload'];
            unset($_SESSION['unload']);
        }
    }
	
	public function editAttributeProduct($id, $data){
        // удалим все и запишем новые
        if(!empty($data['product_attribute'])){
            
                \R::exec("DELETE FROM product_attribute WHERE product_id = ?", [$id]);
                $sql_part = '';
                foreach($data['product_attribute'] as $v){
					if($v["text"] !="") {
						$attribute_group_id = \R::getCell('SELECT `attribute_group_id` FROM attribute WHERE `id` = ? LIMIT 1', [$v["attribute_id"]]);
						$sql_part .= "($id, '".$v["attribute_id"]."', '".$attribute_group_id."', '".$v["text"]."'),";
					}
                }
				if($sql_part == ""){ } else {
					$sql_part = rtrim($sql_part, ',');
					\R::exec("INSERT INTO product_attribute (product_id, attribute_id, attribute_group_id, attribute_text) VALUES $sql_part");
				}
        }
    }
	
	public function editModificationProduct($id, $data){
        // удалим все и запишем новые
        if(!empty($data['product_mods'])){
            
                \R::exec("DELETE FROM modification WHERE product_id = ?", [$id]);
                $sql_part = '';
                foreach($data['product_mods'] as $v){
					if($v["name_modification"] !="") {						
						$sql_part .= "($id, '".$v["article"]."', '".$v["name_modification"]."', '".$v["price"]."', '".$v["quantity"]."', '".$v["unit"]."'),";
					}
                }
				if($sql_part == ""){ } else {
					$sql_part = rtrim($sql_part, ',');
					\R::exec("INSERT INTO modification (product_id, article, name_modification, price, quantity, unit) VALUES $sql_part");
				}
        }else{
			\R::exec("DELETE FROM modification WHERE product_id = ?", [$id]);
		}
    }
	
	public function editTagsProduct($id, $data){
        if(!empty($data['name_tag'])){
            
            \R::exec("DELETE FROM product_tags WHERE product_id = ?", [$id]);
				
			$nametags = explode(", ", $data['name_tag']);
            $sql_tags = '';
			$k=0;
            foreach($nametags as $tag){
				$alias_tag = AppModel::createAlias('product_tags', 'alt_tg', $tag, $id);
                $sql_tags .= "(NULL, '".$id."', '".$tag."', '".$alias_tag."'),";
				$k++;
            }
            $sql_tags = rtrim($sql_tags, ',');
            \R::exec("INSERT INTO product_tags (id, product_id, name, alt_tg) VALUES $sql_tags");
            
        }else{
			\R::exec("DELETE FROM product_tags WHERE product_id = ?", [$id]);
			
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
        $tmp_name = md5(time()).".$ext";
		$new_name = md5(time()).".webp";
		
		$tmpdir = WWW . '/images/product/tmp/'.$tmp_name.'';
        $basedir = WWW . '/images/product/baseimg/'.$new_name.'';
		$galdir = WWW . '/images/product/gallery/'.$new_name.'';
		$minidir = WWW . '/images/product/mini/'.$new_name.'';
		$unldir = WWW . '/images/product/unload/'.$new_name.'';
		if($name == 'single'){
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
            
                $_SESSION['single'] = $new_name;
            
				self::resize($tmpdir, $basedir, $wmax, $hmax, 'webp');
				self::resize($tmpdir, $minidir, $wmaxmini, $hmaxmini, 'webp');
				@unlink($tmpdir);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
		}
		if($name == 'multi'){
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
                $_SESSION['multi'][] = $new_name;
				
				self::resize($tmpdir, $galdir, $wmax, $hmax, $ext);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
        }
		if($name == 'unload'){
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
            
                $_SESSION['unload'] = $new_name;
            
				self::resize($tmpdir, $unldir, $wmax, $hmax, $ext);
				@unlink($tmpdir);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
		}
    }

	public function uploadImgXml($img, $name, $wmax, $hmax, $wmaxmini, $hmaxmini){		
        $exp = explode("/", $img);
        $file_name = end($exp); //myimage.jpg
		$ext = substr($file_name, -3);
        $_FILES['fileprod']['name'] = md5($name).".$ext";
		
		$tmpdir = WWW . '/images/product/tmp/'.$_FILES['fileprod']['name'].'';
        $basedir = WWW . '/images/product/baseimg/'.$_FILES['fileprod']['name'].'';
		$minidir = WWW . '/images/product/mini/'.$_FILES['fileprod']['name'].'';		

		$ch = curl_init($img);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$html = curl_exec($ch);
		curl_close($ch); 

		if (file_put_contents($tmpdir, $html)) {        	
            
            $_SESSION['single'] = $_FILES['fileprod']['name'];
            
				self::resize($tmpdir, $basedir, $wmax, $hmax, $ext);
				self::resize($tmpdir, $minidir, $wmaxmini, $hmaxmini, $ext);				
				@unlink($tmpdir);

		}else{
			$_SESSION['error'] = "не удалось скопировать $file_name...\n";
		}
		
    }
	
	public function checkUnique(){
        $prod = \R::findOne('product', 'name = ?', [$this->attributes['name']]);
        if($prod){
            if($prod->name == $this->attributes['name']){
                $this->errors['unique'][] = 'Название товара уже существует';
            }
            return false;
        }
        return true;
    }
	
	public function checkUniqueArticle(){
        $prod = \R::findOne('product', 'article = ?', [$this->attributes['article']]);
        if($prod){
            if($prod->article == $this->attributes['article']){
                $this->errors['unique'][] = 'Товар с таким артикулом уже существует';
            }
            return false;
        }
        return true;
    }	
}