<?php

namespace app\models\admin;

use app\models\AppModel;

class PlaginsTechnics extends AppModel {

    public $attributes = [
		'type_id' => '',
		'manufacturer_id' => '',
        'model' => '',
        'content' => '',
		'position' => '',
		'title' => '',
		'description' => '',
        'keywords' => '',        
        'hide' => '',        
        'alias' => '',
		'url_video' => '',		
    ];

    public $rules = [
        'required' => [
            ['type_id'],
            ['manufacturer_id'],
            ['model'],
        ],
    ];
	
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
		
		$tmpdir = WWW . '/images/technics/tmp/'.$new_name.'';
        $basedir = WWW . '/images/technics/baseimg/'.$new_name.'';
		$minidir = WWW . '/images/technics/mini/'.$new_name.'';
		
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
	
	public function editSizeTechnics($id, $data){
        $technics_tiposize = \R::getCol('SELECT id FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?', [$id, 1]);
        // если менеджер убрал типоразмеры - удаляем их
        if(empty($data['size']) && !empty($technics_tiposize)){
            \R::exec("DELETE FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?", [$id, 1]);
            return;
        }
        // если добавляются связанные товары
        if(empty($technics_tiposize) && !empty($data['size'])){
            $sql_part = '';
            foreach($data['size'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, '1', $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['size'])){
            $result = array_diff($technics_tiposize, $data['size']);
            if(!empty($result) || count($technics_tiposize) != count($data['size'])){
                \R::exec("DELETE FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?", [$id, 1]);
                $sql_part = '';
                foreach($data['size'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, '1', $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
            }
        }
    }
	
	public function editSizeBackTechnics($id, $data){
        $technics_tiposize = \R::getCol('SELECT id FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?', [$id, 2]);
        // если менеджер убрал типоразмеры - удаляем их
        if(empty($data['size_back']) && !empty($technics_tiposize)){
            \R::exec("DELETE FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?", [$id, 2]);
            return;
        }
        // если добавляются связанные товары
        if(empty($technics_tiposize) && !empty($data['size_back'])){
            $sql_part = '';
            foreach($data['size_back'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, '2', $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['size_back'])){
            $result = array_diff($technics_tiposize, $data['size_back']);
            if(!empty($result) || count($technics_tiposize) != count($data['size_back'])){
                \R::exec("DELETE FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?", [$id, 2]);
                $sql_part = '';
                foreach($data['size_back'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, '2', $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
            }
        }
    }
	
	public function editSizeAltTechnics($id, $data){
        $technics_tiposize = \R::getCol('SELECT id FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?', [$id, 3]);
        // если менеджер убрал типоразмеры - удаляем их
        if(empty($data['size_alt']) && !empty($technics_tiposize)){
            \R::exec("DELETE FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?", [$id, 3]);
            return;
        }
        // если добавляются связанные товары
        if(empty($technics_tiposize) && !empty($data['size_alt'])){
            $sql_part = '';
            foreach($data['size_alt'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, '3', $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['size_alt'])){
            $result = array_diff($technics_tiposize, $data['size_alt']);
            if(!empty($result) || count($technics_tiposize) != count($data['size_alt'])){
                \R::exec("DELETE FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?", [$id, 3]);
                $sql_part = '';
                foreach($data['size_alt'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, '3', $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
            }
        }
    }
	
	public function editSizeAltBackTechnics($id, $data){
        $technics_tiposize = \R::getCol('SELECT id FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?', [$id, 4]);
        // если менеджер убрал типоразмеры - удаляем их
        if(empty($data['size_alt_back']) && !empty($technics_tiposize)){
            \R::exec("DELETE FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?", [$id, 4]);
            return;
        }
        // если добавляются связанные товары
        if(empty($technics_tiposize) && !empty($data['size_alt_back'])){
            $sql_part = '';
            foreach($data['size_alt_back'] as $v){
                $v = (int)$v;
                $sql_part .= "($id, '4', $v),";
            }
            $sql_part = rtrim($sql_part, ',');
            \R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
            return;
        }
        // если изменились связанные товары - удалим и запишем новые
        if(!empty($data['size_alt_back'])){
            $result = array_diff($technics_tiposize, $data['size_alt_back']);
            if(!empty($result) || count($technics_tiposize) != count($data['size_alt_back'])){
                \R::exec("DELETE FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?", [$id, 4]);
                $sql_part = '';
                foreach($data['size_alt_back'] as $v){
                    $v = (int)$v;
                    $sql_part .= "($id, '4', $v),";
                }
                $sql_part = rtrim($sql_part, ',');
                \R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
            }
        }
    }

	public function uploadImgXml($img, $name, $wmax, $hmax, $wmaxmini, $hmaxmini){		
        $exp = explode("/", $img);
        $file_name = end($exp); //myimage.jpg
		$ext = substr($file_name, -3);
        $_FILES['fileprod']['name'] = md5($name).".$ext";
		
		$tmpdir = WWW . '/images/technics/tmp/'.$_FILES['fileprod']['name'].'';
        $basedir = WWW . '/images/technics/baseimg/'.$_FILES['fileprod']['name'].'';
		$minidir = WWW . '/images/technics/mini/'.$_FILES['fileprod']['name'].'';		

        if (@copy($img, $tmpdir)) {		
            
            $_SESSION['single'] = $_FILES['fileprod']['name'];
            
				self::resize($tmpdir, $basedir, $wmax, $hmax, $ext);
				self::resize($tmpdir, $minidir, $wmaxmini, $hmaxmini, $ext);				
				@unlink($tmpdir);

		}else{
			$_SESSION['error'] = "не удалось скопировать $file_name...\n";
		}
		
    }
	
	public function checkUnique(){
        $technics = \R::findOne('technics', 'model = ? AND manufacturer_id = ?', [$this->attributes['model'], $this->attributes['manufacturer_id']]);
        if($technics){
            if($technics->model == $this->attributes['model']){
                $this->errors['unique'][] = 'Модель техники уже существует';
            }
            return false;
        }
        return true;
    }
	
	public function exportTechnicsXml($id){		
        
		if($id) {
			$technics = \R::getAll("SELECT technics.id, technics.img, technics.model, technics_manufacturer.name as manufacturer_name, technics_type.name as type_name FROM technics, technics_manufacturer, technics_type WHERE technics.manufacturer_id = technics_manufacturer.id AND technics.type_id = technics_type.id AND technics.id = '".$id."'");
		}else {
			$technics = \R::getAll("SELECT technics.id, technics.img, technics.model, technics_manufacturer.name as manufacturer_name, technics_type.name as type_name FROM technics, technics_manufacturer, technics_type WHERE technics.manufacturer_id = technics_manufacturer.id AND technics.type_id = technics_type.id");
		}
		$date = date("Y-m-d H:m");
		$fd = fopen("xml/export_technics.xml", 'w+') or die("не удалось создать файл");
		$text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
              <yml_catalog date=\"".$date."\">
              <shop>
                <name></name>
                <company></company>
                <url>".PATH."</url>
				<offers>";
				
		foreach($technics as $item) {
		
			$tiposize = \R::getAll("SELECT*FROM technics_tiposize WHERE technics_id = '".$item["id"]."' GROUP BY tip_size");		
		
			if($item["img"] != "") { $img = "".PATH."/images/technics/baseimg/".$item["img"].""; }
			else { $img = ""; }
		
			$text .= "<offer id=\"".$item["id"]."\" available=\"true\">
                      <tip>".$item["type_name"]."</tip>
                      <name>".$item["manufacturer_name"]."</name>					 
                      <model>".$item["model"]."</model>";
					  
			foreach($tiposize as $tipo) {		  
					
				if($tipo["tip_size"]=="1") { $tags = "size"; }
				if($tipo["tip_size"]=="2") { $tags = "size_back"; }
				if($tipo["tip_size"]=="3") { $tags = "size_alt"; }
				if($tipo["tip_size"]=="4") { $tags = "size_alt_back"; }	
				
				$values = \R::getAll("SELECT*FROM technics_tiposize, attribute_value WHERE technics_tiposize.value_id = attribute_value.id AND technics_tiposize.tip_size = '".$tipo["tip_size"]."' AND technics_tiposize.technics_id = '".$item["id"]."'");
				
				foreach($values as $value) {
					$sizes[$tipo["tip_size"]] .= "".$value["value"].", ";
				}
				$sizes[$tipo["tip_size"]] = rtrim($sizes[$tipo["tip_size"]], ', ');
				$text .= "<".$tags.">".$sizes[$tipo["tip_size"]]."</".$tags.">";
				
				}															
							  
            $text .= "<picture>$img</picture>
				</offer>";
					  
		}
		
		$text .= "</offers>
			</shop>
			</yml_catalog>";
			fwrite($fd, $text);
			fclose($fd);
		
		
		$_SESSION['success'] = 'Товар экспортирован! URL для скачивания '.PATH.'/xml/export_technics.xml';
		redirect();
					
    }
	
	public function editSizeTechnicsImport($id, $data){		
		$technics_tiposize = \R::getCol('SELECT id FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?', [$id, 1]);
        // если добавляются
        if(empty($technics_tiposize) && !empty($data['size'])){
            $sql_part = '';
            foreach($data['size'] as $v){				
				$v=str_replace("*","x",$v);
				$v = \R::findOne('attribute_value', 'value = ?', [$v]);
                $v = (int)$v["id"];				
				if( $v != 0 ){
					$sql_part .= "($id, '1', $v),";
				}
            }
            if($sql_part) { $sql_part = rtrim($sql_part, ',');
				\R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
			}
            return;
        }
    }
	
	public function editSizeBackTechnicsImport($id, $data){
        $technics_tiposize = \R::getCol('SELECT id FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?', [$id, 2]);

        // если добавляются
        if(empty($technics_tiposize) && !empty($data['size_back'])){
            $sql_part = '';
            foreach($data['size_back'] as $v){
				$v=str_replace("*","x",$v);
				$v = \R::findOne('attribute_value', 'value = ?', [$v]);
                $v = (int)$v["id"];
				if( $v != 0 ){
					$sql_part .= "($id, '2', $v),";
				}
            }
            if($sql_part) { $sql_part = rtrim($sql_part, ',');
				\R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
			}
            return;
        }
    }
	
	public function editSizeAltTechnicsImport($id, $data){
        $technics_tiposize = \R::getCol('SELECT id FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?', [$id, 3]);

        // если добавляются
        if(empty($technics_tiposize) && !empty($data['size_alt'])){
            $sql_part = '';
            foreach($data['size_alt'] as $v){
				$v=str_replace("*","x",$v);
                $v = \R::findOne('attribute_value', 'value = ?', [$v]);
                $v = (int)$v["id"];
				if( $v != 0 ){
					$sql_part .= "($id, '3', $v),";
				}
            }
            if($sql_part) { $sql_part = rtrim($sql_part, ',');
				\R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
			}
            return;
        }
    }
	
	public function editSizeAltBackTechnicsImport($id, $data){
        $technics_tiposize = \R::getCol('SELECT id FROM technics_tiposize WHERE technics_id = ? AND tip_size = ?', [$id, 4]);

        // если добавляются
        if(empty($technics_tiposize) && !empty($data['size_alt_back'])){
            $sql_part = '';
            foreach($data['size_alt_back'] as $v){
                $v=str_replace("*","x",$v);
                $v = \R::findOne('attribute_value', 'value = ?', [$v]);
				$v = (int)$v["id"];
				if( $v != 0 ){
					$sql_part .= "($id, '4', $v),";
				}
            }
            if($sql_part) { $sql_part = rtrim($sql_part, ',');
				\R::exec("INSERT INTO technics_tiposize (technics_id, tip_size, value_id) VALUES $sql_part");
			}
            return;
        }
    }
		
}