<?php

namespace app\controllers;

use app\models\admin\Review;
use app\models\Breadcrumbs;
use app\models\Product;
use ishop\App;

class ProductController extends AppController {

    public function viewAction(){
		if($_POST["addreview"]) {
			$review = new Review();
			$data["product_id"] = $_POST["product_id"];
			$data["content"] = $_POST["content"];
			$data["point"] = $_POST["point"];
			$data["date_post"] = date("Y-m-d");
			$data["hide"] = "show";
			$data["finger_up"] = NULL;
			$data["finger_down"] = NULL;
			$data["user_id"] = $_SESSION['user']['id'];
			$user = \R::load('user', $_SESSION['user']['id']);
			$data["uname"] = $user["name"];
			$review->load($data);
            if(!$review->validate($data)){
                $review->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }
			if($review->save('review')){                
                $_SESSION['success'] = 'Отзыв добавлен';
            }
            redirect();
		}
		
		$alias = $this->route['alias'];
		$up_registr = App::upRegistrLetter($alias); //проверка на верхний регистр url
		
        $product = \R::findOne('product', "alias = ? AND hide != 'hide'", [$alias]);
        if(!$product){
            throw new \Exception('Страница не найдена', 404);
        }
		$dtmd = md5(date('Y-m-d'));
		$fio_modal = $_POST["fio_modal"];
		$tell_modal = $_POST["tell_modal"];
		$email_modal = $_POST["email_modal"];
		$prim_modal = $_POST["prim_modal"];
		$name_tovar = $_POST["name_tovar"];
		$product_id = $_POST["product_id"];
		$user_id = $_SESSION['user']['id'];
		$data_create = date('Y-m-d H:i:s');
		if($_POST["oneclick"] == "".$dtmd."") {
			if($_POST["politika"] == "pk"){
				$first = substr($tell_modal, "0",5);			
				if($first != "+7 (9") { $this->errors['unique'][] = "Запрос не обработан! Вы робот? Если нет, попробуйте заполнить форму обратной связи еще раз!"; } else {				
					Product::mailZakazClick($name_tovar, $fio_modal, $tell_modal, $email_modal, $prim_modal);
					\R::exec("INSERT INTO mail_oneclick (`user_id`, `product_id`, `name`, `fio_click`, `tell_click`, `email_click`, `prim_click`, `data_create`, `hide_call`, `data_call`, `call_uid`, `hide_order`, `order_id`, `hide`) VALUES ('".$user_id."', '".$product_id."', '".$name_tovar."', '".$fio_modal."', '".$tell_modal."', '".$email_modal."', '".$prim_modal."', '".$data_create."', '', '', '', '', '', '0')");
					\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('1','1','product','".$product->id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
					setcookie("click-mig", "1house", time()+3600);             
				}
			}else { $this->errors['unique'][] = "Запрос не обработан! Вы отказались принимать условия политики конфиденциальности на сайте. К сожалению, мы не сможем воспользоваться Вашими данными для ответа по запросу."; }
			redirect();
		}
		if($_POST["request"] == "".$dtmd."") {			
			if($_POST["politika"] == "pk"){
				$first = substr($tell_modal, "0",5);		
				if($first != "+7 (9") { $this->errors['unique'][] = "Запрос не обработан! Вы робот? Если нет, попробуйте заполнить форму обратной связи еще раз!"; } else {	
					Product::mailRequest($name_tovar, $fio_modal, $tell_modal, $email_modal, $prim_modal);
					\R::exec("INSERT INTO `mail_request` (`user_id`, `product_id`, `name`, `fio`, `tell`, `email`, `note`, `data_create`, `hide_call`, `data_call`, `call_uid`, `hide_order`, `order_id`, `hide`) VALUES ('".$user_id."', '".$product_id."', '".$name_tovar."', '".$fio_modal."', '".$tell_modal."', '".$email_modal."', '".$prim_modal."', '".$data_create."', '', '', '', '', '', '0')");
					\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('1','62','product','".$product->id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
					setcookie("request-mig", "1house", time()+3600);             
				}
			}else { $this->errors['unique'][] = "Запрос не обработан! Вы отказались принимать условия политики конфиденциальности на сайте. К сожалению, мы не сможем воспользоваться Вашими данными для ответа по запросу."; }
			redirect();
		}
		if($_POST["availability"] == "".$dtmd."") {			
			if($_POST["politika"] == "pk"){					
					Product::mailAvailability($name_tovar, $email_modal, $user_id);
					\R::exec("INSERT INTO `mail_availability` (`user_id`, `email`, `product_id`, `data_create`, `status_nalichiya`, `data_postupleniya`, `status_otpravki`, `data_mail`) VALUES ('".$user_id."', '".$email_modal."', '".$product_id."', '".$data_create."', '0', '',  '0', '')");									
			}else { $this->errors['unique'][] = "Запрос не обработан! Вы отказались принимать условия политики конфиденциальности на сайте. К сожалению, мы не сможем воспользоваться Вашими данными для ответа по запросу."; }
			redirect();
		} 

        // хлебные крошки
        $breadcrumbs = Breadcrumbs::getBreadcrumbs($product->category_id, $product->name, $alias, mb_strtolower($this->route["controller"]));

        // связанные товары
        $related = \R::getAll("SELECT * FROM related_product JOIN product ON product.id = related_product.related_id WHERE related_product.product_id = ? AND product.hide = ? ORDER BY product.quantity DESC", [$product->id, 'show']);

		// похожие товары
        $similar = \R::getAll("SELECT * FROM similar_product JOIN product ON product.id = similar_product.similar_id WHERE similar_product.product_id = ? AND product.hide = ? ORDER BY product.quantity DESC", [$product->id, 'show']);
		
		// отзывы
        $review = \R::getAll("SELECT * FROM review_product JOIN review ON review.id = review_product.review_id WHERE review_product.product_id = ? ORDER BY review.date_post DESC", [$product->id]);
		
        // запись в куки запрошенного товара
        $p_model = new Product();
        $p_model->setRecentlyViewed($product->id);

        // просмотренные товары
        $r_viewed = $p_model->getRecentlyViewed();
        $recentlyViewed = null;
        if($r_viewed){
            $recentlyViewed = \R::find('product', 'id IN (' . \R::genSlots($r_viewed) . ') LIMIT 3', $r_viewed);
        }
		
		$cat_prod = \R::findOne('category', "id = ?", [$product->category_id]);
		$vendor = \R::findOne('brand', "id = ?", [$product->brand_id]);
		
		// группа аттрибутов товаров
        $attribute_group = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON product_attribute.attribute_group_id = attribute.id WHERE product_attribute.product_id = ? GROUP BY product_attribute.attribute_group_id", [$product->id]);
		
		// галерея
        $gallery = \R::findAll('gallery', 'product_id = ?', [$product->id]);

        // модификации
        $mods = \R::findAll('modification', 'product_id = ?', [$product->id]);
		
		//InSEO
		$inseo = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [product, $product->category_id]);
		if($product->title) { $title = $product->title; }else{ $title = \ishop\App::seoreplace($inseo->title, $product->id); }
		if($product->description) { $description = $product->description; }else{ $description = \ishop\App::seoreplace($inseo->description, $product->id); }
		if($product->keywords) { $keywords = $product->keywords; }else{ $keywords = \ishop\App::seoreplace($inseo->keywords, $product->id); }
		$date = date("Y-m-d H:i:s");
		$action = \R::findOne('actions', "product_id = ? AND hide = 'show' AND date_end > '".$date."'", [$product->id]);
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		if($product->img){$product_img = "".PATH."/images/product/mini/".$product->img.""; }else{ $product_img = "".PATH."/images/".App::$app->getProperty('og_logo').""; }
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.$product_img.'', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
		
        $this->set(compact('product', 'related', 'similar', 'gallery', 'recentlyViewed', 'breadcrumbs', 'mods', 'attribute_group', 'cat_prod', 'vendor', 'inseo', 'action', 'review'));
    }
	
	public function comparisonAction(){
		if($_GET) {
			$product_id = $_GET["product_id"];
			
			$_SESSION['comparison'][$product_id] = $product_id;			
		}
        
		$this->setMeta('Сравнение товаров');

	}
	
	public function addReviewAction(){
		
	}

}