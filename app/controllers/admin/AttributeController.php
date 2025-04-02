<?php

namespace app\controllers\admin;

use app\models\admin\Attribute;
use ishop\App;

class AttributeController extends AppController {

    public function indexAction(){
        $attributes = \R::getAll("SELECT * FROM attribute ORDER BY attribute_name");
        $this->setMeta('Список аттрибутов');
        $this->set(compact('attributes'));
    }
	
	public function deleteAction(){
        $id = $this->getRequestID();        
        $attribute = \R::load('attribute', $id);        
		\R::exec('DELETE FROM attribute_comparison WHERE attribute_id = ?', [$id]);
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','57','attribute_comparison','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
		\R::trash($attribute);
        $_SESSION['success'] = 'Атрибут '.$attribute["attribute_name"].' удален';
        redirect();
    }
	
	public function addAction(){
        if(!empty($_POST)){
            $attribute = new Attribute();
            $data = $_POST;
            $attribute->load($data);            
            if(!$attribute->validate($data) || !$attribute->checkUnique()){
                $attribute->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($id = $attribute->save('attribute')){                
                $p = \R::load('attribute', $id);             
                \R::store($p);
				
				//создание категорий групп
				if($_POST['category_id'] !="") {
					$sql_part = '';
					foreach($_POST['category_id'] as $cat_id){
						$cat_id = (int)$cat_id;
						$sql_part .= "(".$cat_id.", ".$id."),";
					}
					$sql_part = rtrim($sql_part, ',');
					\R::exec("INSERT INTO attribute_comparison (category_id, attribute_id) VALUES $sql_part");
				}
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','55','attribute_comparison','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                $_SESSION['success'] = 'Атрибут добавлен';
            }
            redirect();
        }
		$category = \R::findAll('category');
        $this->setMeta('Новый атрибут');
		$this->set(compact('category'));
    }

    public function editAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $attribute = new Attribute();
            $data = $_POST;
            $attribute->load($data);
            if(!$attribute->validate($data)){
                $attribute->getErrors();
                redirect();
            }
            if($attribute->update('attribute', $id)){
                $attribute = \R::load('attribute', $id);               
                \R::store($attribute);
				//удаление категорий групп
				\R::exec("DELETE FROM attribute_comparison WHERE attribute_id = ?", [$id]);
				//создание категорий групп
				if($_POST['category_id'] !="") {
					$sql_part = '';
					foreach($_POST['category_id'] as $cat_id){
						$cat_id = (int)$cat_id;
						$sql_part .= "($cat_id, $id),";
					}
					$sql_part = rtrim($sql_part, ',');
					\R::exec("INSERT INTO attribute_comparison (category_id, attribute_id) VALUES $sql_part");
				}
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','56','attribute_comparison','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                $_SESSION['success'] = 'Изменения сохранены';
                redirect();
            }
        }

        $id = $this->getRequestID();
        $attribute = \R::load('attribute', $id);
		$category = \R::findAll('category');
        App::$app->setProperty('attribute_group_id', $attribute->id);        		
        $this->setMeta("Редактирование атрибута {$attribute->attribute_name}");
        $this->set(compact('attribute', 'category'));
    }
	
	public function importAction(){
		if($_POST["format"] == 1) {
			if ($_FILES['fileprod']['name'])
			{
				$file_type = substr($_FILES['fileprod']['name'], -3);
				$_FILES['fileprod']['name'] = mt_rand(1,100).rand(100,1054).mt_rand(10,150).".".$file_type;
				  
				if($_FILES['fileprod']['size'] > 1024*1*1024)
				{
					$_SESSION['success'] = "Ошибка размера файла!";
				}
				else if( !copy($_FILES['fileprod']['tmp_name'], "xls/".$_FILES['fileprod']['name']) )
				{
					$_SESSION['success'] = "Ошибка загрузки файла!";
				}
				else
				{
					$filecsv = $_FILES['fileprod']['name'];
					$data = File("xls/$filecsv");

					for ($i=1;$i<count($data);$i++) {

						list($a, $b, $c) = explode(";", $data[$i]);
						$b = str_replace(",",".",$b);				
						$c = str_replace(",",".",$c);
						$b = preg_replace('/\s+/', '', trim($b));
						$c = preg_replace('/\s+/', '', trim($c));
						
						$product = \R::findOne('product', 'article = ?', [$a]);					
						if($product['id'] !=""){
							if($b !="") {
								$attribute19 = \R::findOne('product_attribute', 'product_id = ? AND attribute_id = ?', [$product['id'], 19]);
								if($attribute19['id']){								
									\R::exec("UPDATE product_attribute SET attribute_text='".$b."' WHERE attribute_id = '19' AND product_id = '".$product['id']."'");
								}else{							
									\R::exec("INSERT IGNORE INTO product_attribute (product_id, attribute_id, attribute_group_id, attribute_text) VALUES ('".$product['id']."', '19', '1', '".$b."')");										
								}
							}
							if($c !="") {
								$attribute20 = \R::findOne('product_attribute', 'product_id = ? AND attribute_id = ?', [$product['id'], 20]);
								if($attribute20['id']){		
									\R::exec("UPDATE product_attribute SET attribute_text='".$c."' WHERE attribute_id = '20' AND product_id = '".$product['id']."'");
								}else{							
									\R::exec("INSERT IGNORE INTO product_attribute (product_id, attribute_id, attribute_group_id, attribute_text) VALUES ('".$product['id']."', '20', '1', '".$c."')");										
								}
							}
						}						
					}			
						
					@unlink("../public/xls/".$filecsv."");
					$_SESSION['success'] = 'Импорт аттрибутов завершён';
					redirect();
				}
			}
		}
		$this->setMeta('Импорт аттрибутов');
    }


}