<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use app\models\Cross;
use ishop\App;

class CrossController extends AppController {

    public function viewAction(){
		$alias = $this->route['alias'];
		$up_registr = App::upRegistrLetter($alias); //проверка на верхний регистр url
        //$cross = \R::findOne('plagins_cross', "cross_abbreviated_name = ?", [$alias]);
		$cross = \R::getRow("SELECT * FROM plagins_cross WHERE cross_abbreviated_name = '".$alias."'");
		$crossvendor = \R::findOne('plagins_cross_vendor', "id = ?", [$cross["vendor_id"]]);
		$product = \R::findOne('product', "id = ? AND hide != 'hide'", [$cross["product_id"]]);
        if(!$product){
            throw new \Exception('Страница не найдена', 404);
        }
		$oneclick = md5(date('Y-m-d'));
		$fio_click = $_POST["fio_click"];
		$tell_click = $_POST["tell_click"];
		$email_click = $_POST["email_click"];
		$prim_click = $_POST["prim_click"];
		$name_tovar = $_POST["name_tovar"];
		$product_id = $_POST["product_id"];
		$user_id = $_SESSION['user']['id'];
		$data_create = date('Y-m-d H:i:s');
		if($_POST["oneclick"] == "".$oneclick."") {
			if($_POST["politika"] == "pk"){
			$first = substr($tell_click, "0",5);			
            if($first != "+7 (9") { $this->errors['unique'][] = "Запрос не обработан! Вы робот? Если нет, попробуйте заполнить форму обратной связи еще раз!"; } else {				
				Product::mailZakazClick($name_tovar, $fio_click, $tell_click, $email_click, $prim_click);
				\R::exec("INSERT INTO mail_oneclick (`user_id`, `product_id`, `name`, `fio_click`, `tell_click`, `email_click`, `prim_click`, `data_create`, `hide_call`, `data_call`, `call_uid`, `hide_order`, `order_id`, `hide`) VALUES ('".$user_id."', '".$product_id."', '".$name_tovar."', '".$fio_click."', '".$tell_click."', '".$email_click."', '".$prim_click."', '".$data_create."', '', '', '', '', '', '0')");
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('1','1','product','".$product->id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
		        setcookie("click-mig", "1house", time()+3600);             
			}
		}else { $this->errors['unique'][] = "Запрос не обработан! Вы отказались принимать условия политики конфиденциальности на сайте. К сожалению, мы не сможем воспользоваться Вашими данными для ответа по запросу."; }
			redirect();
		}        

        // хлебные крошки
        $breadcrumbs = Breadcrumbs::getBreadcrumbs($product->category_id, $product->name);

        // связанные товары
        $related = \R::getAll("SELECT * FROM related_product JOIN product ON product.id = related_product.related_id WHERE related_product.product_id = ?", [$product->id]);

		// похожие товары
        $similar = \R::getAll("SELECT * FROM similar_product JOIN product ON product.id = similar_product.similar_id WHERE similar_product.product_id = ? AND product.hide = ? ORDER BY product.quantity DESC", [$product->id, 'show']);
		
        // запись в куки запрошенного товара
        $p_model = new Cross();
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
        $attribute_group = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON product_attribute.attribute_group_id = attribute.id WHERE product_attribute.product_id = ? GROUP BY attribute.attribute_group_id", [$product->id]);
		
		// аттрибуты товаров
        $attributs = \R::getAll("SELECT * FROM attribute JOIN product_attribute ON product_attribute.attribute_id = attribute.id WHERE product_attribute.product_id = ? ORDER BY attribute_position", [$product->id]);

        // галерея
        $gallery = \R::findAll('gallery', 'product_id = ?', [$product->id]);

        // модификации
        $mods = \R::findAll('modification', 'product_id = ?', [$product->id]);
		
		$title = "Купить аналог фильтра ".$cross["cross_name"]." ".$crossvendor->name." по низким ценам | ИТС-Центр";
		$description = "".$product->name." является аналогом для фильтра ".$crossvendor->name." с OEM номером ".$cross["cross_name"]." и соответствует всем характеристикам. Купить фильтр с доставкой по России можно в ИТС-Центре по низким ценам.";
		$keywords = "Купить ".$crossvendor->name.", фильтр ".$cross["cross_name"]." цена";
		
		$date = date("Y-m-d H:i:s");
		$action = \R::findOne('actions', "product_id = ? AND hide = 'show' AND date_end > '".$date."'", [$product->id]);
		
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		if($product->img){$product_img = "".PATH."/images/product/mini/".$product->img.""; }else{ $product_img = "".PATH."/images/".App::$app->getProperty('og_logo').""; }
		$this->setMeta($title, $description, $keywords, '' . App::$app->getProperty('shop_name') . '', ''.$product_img.'', ''.PATH.''.$path_controller.''.$path_alias.'');
		/*SEO*/
		
        $this->set(compact('product', 'related', 'similar', 'gallery', 'recentlyViewed', 'breadcrumbs', 'mods', 'attribute_group', 'attributs', 'cat_prod', 'vendor', 'inseo', 'action', 'cross', 'crossvendor'));
    }

}