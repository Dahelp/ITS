<?php

namespace app\controllers\admin;

use app\models\AppModel;
use app\models\admin\Category;
use ishop\App;

class CategoryController extends AppController {

    public function indexAction(){
        $this->setMeta('Список категорий');
    }
	
	public function addImageAction(){
        if(isset($_GET['upload'])){
            $wmax = App::$app->getProperty('img_width');
            $hmax = App::$app->getProperty('img_height');            
            $name = $_POST['name'];
            $category = new Category();
            $category->uploadImg($name, $wmax, $hmax);
        }
    }

    public function deleteAction(){
        $id = $this->getRequestID();
        $children = \R::count('category', 'parent_id = ?', [$id]);
        $errors = '';
        if($children){
            $errors .= '<li>Удаление невозможно, в категории есть вложенные категории</li>';
        }
        $products = \R::count('product', 'category_id = ?', [$id]);
        if($products){
            $errors .= '<li>Удаление невозможно, в категории есть товары</li>';
        }
        if($errors){
            $_SESSION['error'] = "<ul>$errors</ul>";
            redirect();
        }
        $category = \R::load('category', $id);
		@unlink(WWW . "/images/category/baseimg/".$category["img"]."");
		$_SESSION['success'] = 'Категория удалена '.$category["name"].'';
        \R::trash($category);	
        $find_att = \R::findAll('attribute_category', 'category_id = ?', [$id]);
		foreach($find_att as $att) {
			$delete = \R::load('attribute_category', $att->id);
			\R::trash($delete);
		}

        redirect();
    }

    public function addAction(){
        if(!empty($_POST)){
            $category = new Category();
            $data = $_POST;
            $category->load($data);			
			$category->getImg();
            if(!$category->validate($data) || !$category->checkUnique()){
                $category->getErrors();
                redirect();
            }
            if($id = $category->save('category')){
                $alias = AppModel::createAlias('category', 'alias', $data['name'], $id);
                $cat = \R::load('category', $id);
                $cat->alias = $alias;
                \R::store($cat);
                $_SESSION['success'] = 'Категория добавлена';
            }
            redirect();
        }
        $this->setMeta('Новая категория');
    }

    public function editAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $category = new Category();
            $data = $_POST;
            $category->load($data);
			$category->attributes['sale'] = $category->attributes['sale'] ? '1' : '0';
			$products = \R::findAll('product', 'category_id = ?', [$id]);
			foreach($products as $product) {
			if($category->attributes['sale'] == "1") {
					\R::exec("UPDATE `product` SET `sale`='1', `price_rrs` = '".$product->price."' WHERE `id` = ?", [$product->id]);
				}else{
					\R::exec("UPDATE `product` SET `sale`='0', `price_rrs` = '' WHERE `id` = ?", [$product->id]);
				}
			}
			$category->getImg();
            if(!$category->validate($data)){
                $category->getErrors();
                redirect();
            }
            if($category->update('category', $id)){
                $alias = AppModel::createAlias('category', 'alias', $data['name'], $id);
                $category = \R::load('category', $id);
				if($data['alias']!=""){ $category->alias = $data['alias'];}
				else{$category->alias = $alias;}				
                \R::store($category);				
                $_SESSION['success'] = 'Изменения сохранены';
				redirect();
            }            
        }
        $id = $this->getRequestID();
        $category = \R::load('category', $id);
        App::$app->setProperty('parent_id', $category->parent_id);
        $this->setMeta("Редактирование категории {$category->name}");
        $this->set(compact('category'));
    }
	
	public function deleteBaseimgAction(){
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $src = isset($_POST['src']) ? $_POST['src'] : null;
        if(!$id || !$src){
            return;
        }
        if(\R::exec("UPDATE category SET img = '' WHERE id = ? AND img = ?", [$id, $src])){
            @unlink(WWW . "/images/category/baseimg/$src");			
            exit('1');
        }
        return;
    }
}