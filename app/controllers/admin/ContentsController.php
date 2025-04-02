<?php

namespace app\controllers\admin;

use app\models\admin\ContentsPages;
use app\models\admin\ContentsType;
use app\models\AppModel;
use ishop\App;
use ishop\libs\Pagination;
use app\models\admin\PlaginsIndexnow;

class ContentsController extends AppController{
	
	public function addImageAction(){
        if(isset($_GET['upload'])){
            if($_POST['name'] == 'single'){
                $wmax = App::$app->getProperty('img_width_content');
                $hmax = App::$app->getProperty('img_height_content');
				$wmaxmini = App::$app->getProperty('mini_img_width');
                $hmaxmini = App::$app->getProperty('mini_img_height');
            }
            $name = $_POST['name'];
            $page = new ContentsPages();
            $page->uploadImg($name, $wmax, $hmax, $wmaxmini, $hmaxmini);
        }
    }

    public function typeContentAction(){
        $type_content = \R::findAll('content_type');
        $this->setMeta('Типы контента');
        $this->set(compact('type_content'));
    }
	
	public function typeAddAction(){
        if(!empty($_POST)){
            $type = new ContentsType();
            $data = $_POST;
            $type->load($data);
            if(!$type->validate($data) || !$type->checkUnique()){
                $type->getErrors();
                redirect();
            }
            if($type->save('content_type', false)){
				$type->addClassContents($data);
                $_SESSION['success'] = 'Тип добавлен';
                redirect();
            }
        }
        $this->setMeta('Новый тип контента');

    }
	
	public function typeEditAction(){		
		if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $type = new ContentsType();
            $data = $_POST;
            $type->load($data);
            if(!$type->validate($data)){
                $type->getErrors();
                redirect();
            }
            if($type->update('content_type', $id)){
                $type->addClassContents($data);
				$type = \R::load('content_type', $id);			
                \R::store($type);
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
        $id = $this->getRequestID();
        $type = \R::load('content_type', $id);		

        $this->setMeta("Редактирование типа контента {$type->name}");
        $this->set(compact('type'));
    }
	
	public function typeDeleteAction(){
        $id = $this->getRequestID();        
        $type = \R::load('content_type', $id);
		$contents = \R::findOne('contents', 'type_id = ?', [$id]);
        $del_contents = \R::load('contents', $contents["id"]);
		
		$uName = ucfirst($type["param_url"]);
		if($type["hide_anons"]=="show"){
		@unlink(APP . '/views/'.$uName.'/view.php');
		@unlink(APP . '/views/'.$uName.'/index.php');
		}else{
		@unlink(APP . '/views/'.$uName.'/view.php');
		}
		@unlink(APP . '/controllers/'.$uName.'Controller.php');
		rmdir(APP . '/views/'.$uName.'');
		$dir_route = CONF . '/routes.php';
		$FileSourse_del = file_get_contents($dir_route);
		$FileSourse_del = preg_replace("#
//".$uName."//.*//And".$uName."//#is", '', $FileSourse_del);
		file_put_contents($dir_route, $FileSourse_del);
		
		$related = \R::findAll('content_related', 'content_id = ?', [$contents["id"]]);
		foreach($related as $rel) {
			\R::exec("DELETE FROM content_related WHERE content_id = ?", [$rel->content_id]);
		}
		
		\R::trash($type);		
		\R::trash($del_contents);		
		
		
        $_SESSION['success'] = 'Тип контента '.$type["name"].' удален';
        redirect();
    }
	
	public function pagesAction(){		
        $contents = \R::getAll("SELECT contents.id, contents.name, contents.alias, contents.clicks, contents.date_post, content_type.name AS type_name, content_type.param_url FROM contents JOIN content_type ON content_type.id = contents.type_id ORDER BY contents.date_post");
        $this->setMeta('Страницы контента');
        $this->set(compact('contents'));
    }
	
	public function pageAddAction(){
        if(!empty($_POST)){
            $page = new ContentsPages();
            $data = $_POST;
            $page->load($data);
			$page->getImg();
            if(!$page->validate($data) || !$page->checkUnique()){
                $page->getErrors();
                redirect();
            }
            if($id = $page->save('contents')){	
				$alias = AppModel::createAlias('contents', 'alias', $data['name'], $id);
                $page = \R::load('contents', $id);
                if($data['alias']!=""){ $page->alias = $data['alias'];}
				else{$page->alias = $alias;}
                \R::store($page);
                $page->editRelatedProduct($id, $data);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','9','contents','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
				
				$type = \R::findOne('content_type', 'id = ?', [$page->type_id]);
				
				// API IndexNow
				$indexnow = new PlaginsIndexnow();
				$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
				foreach($inw as $in){					
					$search_engine .= $indexnow->indexNowEngine($in->url, $type->param_url, $page["alias"], $in->verification);
				}
				
                $_SESSION['success'] = 'Контент добавлен.'.$search_engine.'';
                redirect();
            }
        }
		
		$wmax = App::$app->getProperty('img_width_content');
        $hmax = App::$app->getProperty('img_height_content');
		
        $this->setMeta('Новый контент');
		$this->set(compact('wmax', 'hmax'));
    }
	
	public function pageEditAction(){
		
		if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $page = new ContentsPages();
            $data = $_POST;
            $page->load($data);
			$page->getImg();
            if(!$page->validate($data)){
                $page->getErrors();
                redirect();
            }
            if($page->update('contents', $id)){
				$page->editRelatedProduct($id, $data);
                $alias = AppModel::createAlias('contents', 'alias', $data['name'], $id);
                $page = \R::load('contents', $id);
				if($data['alias']!=""){ $page->alias = $data['alias'];}
				else{$page->alias = $alias;}
				$page->user_id = $_SESSION['user']['id'];
				$page->date_last_modified = date('Y-m-d H:m:s');
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','10','contents','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                \R::store($page);
				
				$type = \R::findOne('content_type', 'id = ?', [$page->type_id]);
				
				// API IndexNow
				$indexnow = new PlaginsIndexnow();
				$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
				foreach($inw as $in){					
					$search_engine .= $indexnow->indexNowEngine($in->url, $type->param_url, $page["alias"], $in->verification);
				}
				
                $_SESSION['success'] = 'Изменения сохранены.'.$search_engine.'';
            }
            redirect();
        }
        $id = $this->getRequestID();
        $page = \R::load('contents', $id);
		
		$wmax = App::$app->getProperty('img_width_content');
        $hmax = App::$app->getProperty('img_height_content');
		$related_product = \R::getAll('SELECT content_related.related_id, product.name FROM content_related JOIN product ON product.id = content_related.related_id WHERE content_related.content_id = ?', [$id]);
        $this->setMeta("Редактирование контента {$page->name}");
        $this->set(compact('page', 'related_product', 'wmax', 'hmax'));
		
	}
	
	public function deleteBaseimgAction(){
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $src = isset($_POST['src']) ? $_POST['src'] : null;
        if(!$id || !$src){
            return;
        }
        if(\R::exec("UPDATE contents SET img = '' WHERE id = ? AND img = ?", [$id, $src])){
            @unlink(WWW . "/images/contents/baseimg/$src");			
            exit('1');
        }
        return;
    }
	
	public function pageDeleteAction(){
        $id = $this->getRequestID();        
        $page = \R::load('contents', $id);
		@unlink(WWW . "/images/contents/baseimg/".$page["img"]."");
		$related = \R::findAll('content_related', 'content_id = ?', [$page["id"]]);
		foreach($related as $rel) {
			\R::exec("DELETE FROM content_related WHERE content_id = ?", [$rel->content_id]);
		}
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','11','contents','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
        
		$type = \R::findOne('content_type', 'id = ?', [$page->type_id]);
		
		// API IndexNow
		$indexnow = new PlaginsIndexnow();
		$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
		foreach($inw as $in){					
			$search_engine .= $indexnow->indexNowEngine($in->url, $type->param_url, $page["alias"], $in->verification);
		}
		
		\R::trash($page);		
		
        $_SESSION['success'] = 'Контент '.$page["name"].' удален. '.$search_engine.'';
        redirect();
    }

}