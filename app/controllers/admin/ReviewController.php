<?php

namespace app\controllers\admin;

use app\models\admin\Review;
use app\models\AppModel;
use ishop\App;
use DataTables\Database;
use app\models\admin\PlaginsIndexnow;

class ReviewController extends AppController {

    public function indexAction(){
        $reviews = \R::getAll("SELECT * FROM review ORDER BY id DESC");	
		
        $this->setMeta('Список отзывов');
        $this->set(compact('reviews'));
    }
	
	public function productAction(){
		$product_id = $_GET["id"];
        $reviews = \R::getAll("SELECT * FROM review, review_product WHERE review.id = review_product.review_id AND review_product.product_id = '".$product_id."' ORDER BY review.id DESC");	
		
        $this->setMeta('Список отзывов');
        $this->set(compact('reviews'));
    }
	
	public function addImageAction(){
        if(isset($_GET['upload'])){
			if($_POST['name'] == 'multi'){
                $wmax = App::$app->getProperty('gallery_width');
                $hmax = App::$app->getProperty('gallery_height');
				$wmaxmini = App::$app->getProperty('mini_gallery_width');
                $hmaxmini = App::$app->getProperty('mini_gallery_height');
            }
            $name = $_POST['name'];
            $review = new Review();
            $review->uploadImg($name, $wmax, $hmax, $wmaxmini, $hmaxmini);
        }
    }
	
	public function deleteAction(){
        $id = $this->getRequestID();        
        $review = \R::load('review', $id);
        \R::trash($review);
		//delete img gallery review
		$gallery = \R::findOne('review_gallery', 'review_id = ?', [$id]);		
		@unlink(WWW . "/images/review/gallery/".$gallery["img"]."");
		@unlink(WWW . "/images/review/mini/".$gallery["img"]."");
        $del_gallery = \R::load('review_gallery', $gallery["id"]);
		\R::trash($del_gallery);
		
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','65','review','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
		
		$product = \R::findOne('product', 'id=?', [$review["product_id"]]);
		
		// API IndexNow
		$indexnow = new PlaginsIndexnow();
		$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
		foreach($inw as $in){					
			$search_engine .= $indexnow->indexNowEngine($in->url, 'product', $product["alias"], $in->verification);
		}
		
        $_SESSION['success'] = 'Отзыв '.$review["id"].' удален.'.$search_engine.'';
        redirect();
    }
	
	public function addAction(){
        if(!empty($_POST)){
            $review = new Review();
            $data = $_POST;
            $review->load($data);			
			$review->attributes["user_id"] = $_SESSION['user']['id'];
			
            if(!$review->validate($data)){
                $review->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($id = $review->save('review')){
				$review->editReviewProduct($id, $data);
				$review->saveGallery($id);
				$r = \R::load('review', $id);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','63','review','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
				
				$product = \R::findOne('product', 'id=?', [$r["product_id"]]);
				
				// API IndexNow
				$indexnow = new PlaginsIndexnow();
				$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
				foreach($inw as $in){					
					$search_engine .= $indexnow->indexNowEngine($in->url, 'product', $product->alias, $in->verification);
				}
				
                $_SESSION['success'] = 'Отзыв добавлен.'.$search_engine.'';
            }
            redirect();
        }
        $this->setMeta('Новый отзыв');
    }
	
	public function editAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $review = new Review();
            $data = $_POST;
            $review->load($data);			
			$review->attributes["date_last_modified"] = date('Y-m-d H:i:s');
            if(!$review->validate($data)){
                $review->getErrors();
                redirect();
            }
            if($review->update('review', $id)){
				$review->editReviewProduct($id, $data);
				$review->saveGallery($id);
				
				$r = \R::load('review', $id);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','64','review','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
				
				$product = \R::findOne('product', 'id=?', [$r["product_id"]]);

				// API IndexNow
				$indexnow = new PlaginsIndexnow();
				$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
				foreach($inw as $in){					
					$search_engine .= $indexnow->indexNowEngine($in->url, 'product', $product->alias, $in->verification);
				}
				
                $_SESSION['success'] = 'Изменения сохранены.'.$search_engine.'';
                redirect();
            }
        }

        $id = $this->getRequestID();
        $review = \R::load('review', $id);

		$product = \R::getAll('SELECT review_product.product_id, product.name, product.alias FROM review_product JOIN product ON product.id = review_product.product_id WHERE review_product.review_id = ?', [$id]);

		$gallery = \R::getCol('SELECT img FROM review_gallery WHERE review_id = ?', [$id]);
        $this->setMeta("Редактирование отзыва");
        $this->set(compact('review', 'product', 'gallery'));
    }
	
	public function deleteGalleryAction(){
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $src = isset($_POST['src']) ? $_POST['src'] : null;
        if(!$id || !$src){
            return;
        }
        if(\R::exec("DELETE FROM review_gallery WHERE review_id = ? AND img = ?", [$id, $src])){
            @unlink(WWW . "/images/review/gallery/$src");
			@unlink(WWW . "/images/review/mini/$src");
            exit('1');
        }
        return;
    }
}