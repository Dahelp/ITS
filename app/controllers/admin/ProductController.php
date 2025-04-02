<?php

namespace app\controllers\admin;

use app\models\admin\Product;
use app\models\admin\SSP;
use app\models\AppModel;
use ishop\App;
use ishop\libs\Pagination;
use DataTables\Database;
use app\models\admin\PlaginsIndexnow;
	
class ProductController extends AppController {

    public function indexAction(){
		$category_id = $_GET["category_id"];
		$category = \R::findOne('category', 'id = ?', [$category_id]);
        $this->setMeta('Список товаров');
		$this->set(compact('category'));
    }
	
	public function serverProcessingAction(){		
		//datatables server-side
		$category_id = $_GET["category_id"];
		if($_GET["category_id"]){ $where = " WHERE a.category_id = '".$category_id."'"; }
		$table = <<<EOT
		 (
			SELECT a.id, a.img, a.article, a.name as cat, a.price, a.hide, b.name FROM product a LEFT JOIN category b ON a.category_id = b.id$where 
		 ) temp
		EOT;
		$primaryKey = 'id';	 

		$columns = array(
			array( 'db' => 'id', 'dt' => 0 ),
			array( 'db' => 'img',  'dt' => 1,
				   'formatter' => function( $d, $row ) {
						return '<img src="/images/product/mini/'.$d.'" alt="" style="max-height: 70px">';
					} ),
			array( 'db' => 'article',   'dt' => 2 ),
			array( 'db' => 'cat', 'dt' => 3,
				'formatter' => function( $d, $row ) {
					$product = \R::findOne('product', 'name=?', [$d]);
					$count_review = \R::count('review_product', "product_id = ?", [$product["id"]]);
					$count_order = \R::count('order_product', "product_id = ?", [$product["id"]]);
					$count_bookmarks = \R::count('product_bookmarks', "product_id = ?", [$product["id"]]);
					
					return '<div class="table_product_name">'.$product["name"].'</div>
							<div class="table_product_count_info">
								<a target="_blank" href="'.ADMIN.'/review/product?id='.$product["id"].'" class="btn btn-secondary" title="Всего отзывов: '.$count_review.'"><i class="fas fa-star-half-alt"></i> '.$count_review.'</a>
								<a target="_blank" href="'.ADMIN.'/order/stat_product?id='.$product["id"].'" class="btn btn-purple" title="Покупок было: '.$count_order.'"><i class="fad fa-cart-plus"></i> '.$count_order.'</a>
								<a target="_blank" href="'.ADMIN.'/bookmarks/product?id='.$product["id"].'" class="btn btn-cyan" title="В закладках: '.$count_bookmarks.'"><i class="fad fa-bookmark"></i> '.$count_bookmarks.'</a>
							</div>';
				} ),
			array( 'db' => 'name',  'dt' => 4	),
			array( 'db' => 'id',   'dt' => 5,
					'formatter' => function( $d, $row ) {
						$product = \R::findOne('product', 'id=?', [$d]);
						$curr = \R::findOne('currency');
						if($product['quantity']>0){ $itog_nalichie = "<span class='text-success'>В наличии: ".$product['quantity']." ".$product['unit']."</span>"; }
						if($product['quantity']==0){
							$instok = \R::findOne('in_stock', 'product_id=?', [$d]);							
							if($instok['date_scheduling'] != '0000-00-00'){
								$itog_nalichie = "<span class='text-primary'>Ожидается поступление</span>";
							}else{
								$itog_nalichie = "<span class='text-danger'>Нет в наличии</span>";
							}
						}
						return ''.$curr['symbol_left'].''.$product['price'].' '.$curr['symbol_right'].'<br />'.$itog_nalichie.'';
					}
			),
			array( 'db' => 'id',   'dt' => 6, 
				'formatter' => function( $d, $row ) {
					$product = \R::findOne('product', 'id=?', [$d]);
					if($product['title'] !="") { $s1 = "20"; }else{ $s1 = 0; }
					if($product['description'] !="") { $s2 = "20"; }else{ $s2 = 0; }
					if($product['keywords'] !="") { $s3 = "20"; }else{ $s3 = 0; }
					if($product['content'] !="") { $s4 = "20"; }else{ $s4 = 0; }
					if($product['img'] !="") { $s5 = "20"; }else{ $s5 = 0; }
					$seo = $s1+$s2+$s3+$s4+$s5; 
					if($seo == 20) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
					if($seo == 40) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
					if($seo == 60) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
					if($seo == 80) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
					if($seo == 100) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-success progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
					$inseo = \R::findOne('plagins_inseo', 'category_id = ?', [$product['category_id']]);
					if($inseo['title'] !="") { $in1 = "20"; }else{ $in1 = 0; }
					if($inseo['description'] !="") { $in2 = "20"; }else{ $in2 = 0; }
					if($inseo['keywords'] !="") { $in3 = "20"; }else{ $in3 = 0; }
					if($inseo['content'] !="") { $in4 = "20"; }else{ $in4 = 0; }
					if($inseo['name'] !="") { $in5 = "20"; }else{ $in5 = 0; }
					$ins = $in1+$in2+$in3+$in4+$in5; 
					if($ins == 20) { $itog_inseo = "InSEO $ins% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$ins."' aria-valuemin='0' aria-valuemax='100' style='width: ".$ins."%'><span class='sr-only'>".$ins."% Complete (warning)</span></div></div>"; }
					if($ins == 40) { $itog_inseo = "InSEO $ins% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$ins."' aria-valuemin='0' aria-valuemax='100' style='width: ".$ins."%'><span class='sr-only'>".$ins."% Complete (warning)</span></div></div>"; }
					if($ins == 60) { $itog_inseo = "InSEO $ins% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$ins."' aria-valuemin='0' aria-valuemax='100' style='width: ".$ins."%'><span class='sr-only'>".$ins."% Complete (warning)</span></div></div>"; }
					if($ins == 80) { $itog_inseo = "InSEO $ins% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$ins."' aria-valuemin='0' aria-valuemax='100' style='width: ".$ins."%'><span class='sr-only'>".$ins."% Complete (warning)</span></div></div>"; }
					if($ins == 100) { $itog_inseo = "InSEO $ins% <div class='progress progress-xs'><div class='progress-bar bg-success progress-bar-striped' role='progressbar' aria-valuenow='".$ins."' aria-valuemin='0' aria-valuemax='100' style='width: ".$ins."%'><span class='sr-only'>".$ins."% Complete (warning)</span></div></div>"; }
											
					return ''.$itog_seo.''.$itog_inseo.''; 
				}
			),
			array( 'db' => 'id',   'dt' => 7, 
					'formatter' => function( $d, $row ) {
						$attributes = \R::count('product_attribute', "product_id = ?", [$d]);
						if($attributes > 0) { $attributes = "<span class='badge bg-success'>".$attributes."</span>"; }
						else { $attributes = "<span class='badge bg-danger'>".$attributes."</span>"; }
						$filters = \R::count('attribute_product', "product_id = ?", [$d]);
						if($filters > 0) { $filters = "<span class='badge bg-success'>".$filters."</span>"; }
						else { $filters = "<span class='badge bg-danger'>".$filters."</span>"; }
						$parametry = "".$attributes." Атрибуты<br>".$filters." Фильтры";
						
						return ''.$parametry.''; 
					} ),
			array( 'db' => 'id',   'dt' => 8, 
					'formatter' => function( $d, $row ) {
						$content_related = \R::count('content_related', "related_id = ?", [$d]);
						$count_related = \R::count('related_product', "product_id = ?", [$d]);
						$count_similar = \R::count('similar_product', "product_id = ?", [$d]);
						$related = \R::count('related_product', "related_id = ?", [$d]);
						$similar = \R::count('similar_product', "similar_id = ?", [$d]);
						$perelink = $related + $similar + $content_related;
						if($perelink > 0) { $perelink = "<span class='badge bg-success'>".$perelink."</span>"; }
						else { $perelink = "<span class='badge bg-danger'>".$perelink."</span>"; }
						if($count_related > 0) { $count_related = "<span class='badge bg-success'>".$count_related."</span>"; }
						else { $count_related = "<span class='badge bg-danger'>".$count_related."</span>"; }
						if($count_similar > 0) { $count_similar = "<span class='badge bg-success'>".$count_similar."</span>"; }
						else { $count_similar = "<span class='badge bg-danger'>".$count_similar."</span>"; }
						$ssilki = "".$count_related." Связанные<br>".$count_similar." Похожие<br>".$perelink." Ссылки";
						
						return ''.$ssilki.''; 
					} ),
			array( 'db' => 'hide',   'dt' => 9, 
					'formatter' => function( $d, $row ) { 
						if($d == 'show'){ $hide = 'Активный'; }
						if($d == 'hide'){ $hide = 'Не активный'; }
						if($d == 'lock'){ $hide = 'Закрыт от индексации'; }
						
						return $hide;
					}
			),
			array( 'db' => 'id',   'dt' => 10, 
					'formatter' => function( $d, $row ) {
						$product = \R::findOne('product', 'id=?', [$d]);
						return '<a href="'.ADMIN.'/product/edit?id='.$d.'"><i class="fas fa-pencil-alt"></i></a> <a class="delete" href="'.ADMIN.'/product/delete?id='.$d.'"><i class="fas fa-times-circle text-danger"></i></a> <a target="_blank" href="/product/'.$product['alias'].'"><i class="fas fa-eye"></i></a> <a target="_blank" href="'.ADMIN.'/product/copy?id='.$d.'"><i class="fas fa-copy"></i></a>'; 
					}
			)
		);
		 
		// SQL server connection information
		$sql_details = array(
			'user' => App::$app->getProperty('sql_user'),
			'pass' => App::$app->getProperty('sql_pass'),
			'db'   => App::$app->getProperty('sql_db'),
			'host' => App::$app->getProperty('sql_host')
		);
		$spp = new SSP();
		echo json_encode(
			$spp::simple( $_GET, $sql_details, $table, $primaryKey, $columns, null, "" )
		);
		die;
	}
	
	public function categoryAction(){
		$price = $this->getPrices();
			
        $this->setMeta('Список товаров по категориям');
        $this->set(compact('price'));
    }

    public function addImageAction(){
        if(isset($_GET['upload'])){
            if($_POST['name'] == 'single'){
                $wmax = App::$app->getProperty('img_width');
                $hmax = App::$app->getProperty('img_height');
				$wmaxmini = App::$app->getProperty('mini_img_width');
                $hmaxmini = App::$app->getProperty('mini_img_height');
            }
			if($_POST['name'] == 'multi'){
                $wmax = App::$app->getProperty('gallery_width');
                $hmax = App::$app->getProperty('gallery_height');
				$wmaxmini = App::$app->getProperty('mini_gallery_width');
                $hmaxmini = App::$app->getProperty('mini_gallery_height');
            }
			if($_POST['name'] == 'unload'){
				$wmax = App::$app->getProperty('img_width');
                $hmax = App::$app->getProperty('img_height');
				$wmaxmini = App::$app->getProperty('mini_img_width');
                $hmaxmini = App::$app->getProperty('mini_img_height');
			}
            $name = $_POST['name'];
            $product = new Product();
            $product->uploadImg($name, $wmax, $hmax, $wmaxmini, $hmaxmini);
        }
    }
	
	public function deleteAction(){
        $id = $this->getRequestID();        
        $product = \R::load('product', $id);
		@unlink(WWW . "/images/product/baseimg/".$product["img"]."");
		@unlink(WWW . "/images/product/mini/".$product["img"]."");
		\R::trash($product);
		$gallery = \R::findOne('gallery', 'product_id = ?', [$id]);		
		@unlink(WWW . "/images/product/gallery/".$gallery["img"]."");
        $del_gallery = \R::load('gallery', $gallery["id"]);
		\R::trash($del_gallery);
		
		$find_filter = \R::findAll('attribute_product', 'product_id = ?', [$id]);
		if($find_filter){
			foreach($find_filter as $filt) {
				$delete_filt = \R::load('attribute_product', $filt->id);
				\R::trash($delete_filt);
			}
		}
		$find_modific = \R::findAll('modification', 'product_id = ?', [$id]);
		if($find_modific){
			foreach($find_modific as $modific) {
				$delete_modific = \R::load('modification', $modific->id);
				\R::trash($delete_modific);
			}
		}
		$find_relate = \R::findAll('related_product', 'product_id = ?', [$id]);
		if($find_relate){
			foreach($find_relate as $relate) {
				$delete_relate = \R::load('related_product', $relate->id);
				\R::trash($delete_relate);
			}
		}
		$find_similar = \R::findAll('similar_product', 'product_id = ?', [$id]);
		if($find_similar){
			foreach($find_similar as $similar) {
				$delete_similar = \R::load('similar_product', $similar->id);
				\R::trash($delete_similar);
			}
		}
		$find_service = \R::findAll('service_product', 'product_id = ?', [$id]);
		if($find_service){
			foreach($find_service as $service) {
				$delete_service = \R::load('service_product', $service->id);
				\R::trash($delete_service);
			}
		}
		$find_attribute = \R::findAll('product_attribute', 'product_id = ?', [$id]);
		if($find_attribute){
			foreach($find_attribute as $attribute) {
				$delete_attribute = \R::load('product_attribute', $attribute->id);
				\R::trash($delete_attribute);
			}
		}
		$find_tags = \R::findAll('product_tags', 'product_id = ?', [$id]);
		if($find_tags){
			foreach($find_tags as $tags) {
				$delete_tags = \R::load('product_tags', $tags->id);
				\R::trash($delete_tags);
			}
		}
		$find_bookmarks = \R::findAll('product_bookmarks', 'product_id = ?', [$id]);
		if($find_bookmarks){
			foreach($find_bookmarks as $bookmarks) {
				$delete_bookmarks = \R::load('product_bookmarks', $bookmarks->id);
				\R::trash($delete_bookmarks);
			}
		}
		$find_content = \R::findAll('content_related', 'related_id = ?', [$id]);
		if($find_content){
			foreach($find_content as $content) {
				$delete_content = \R::load('content_related', $content->id);
				\R::trash($delete_content);
			}
		}
		$find_actions = \R::findAll('actions', 'product_id = ?', [$id]);
		if($find_actions){
			foreach($find_actions as $actions) {
				$delete_actions = \R::load('actions', $actions->id);
				\R::trash($delete_actions);
			}
		}
		$find_instock = \R::findAll('in_stock', 'product_id = ?', [$id]);
		if($find_instock){
			foreach($find_instock as $instock) {
				$delete_instock = \R::load('in_stock', $instock->stock_id);
				\R::trash($delete_instock);
			}
		}
		$find_oneclick = \R::findAll('mail_oneclick', 'product_id = ?', [$id]);
		if($find_oneclick){
			foreach($find_oneclick as $oneclick) {
				$delete_oneclick = \R::load('mail_oneclick', $oneclick->id);
				\R::trash($delete_oneclick);
			}
		}
		$find_request = \R::findAll('mail_request', 'product_id = ?', [$id]);
		if($find_request){
			foreach($find_request as $request) {
				$delete_request = \R::load('mail_request', $request->id);
				\R::trash($delete_request);
			}
		}
		$find_abbreviated = \R::findAll('product_abbreviated', 'product_id = ?', [$id]);
		if($find_abbreviated){
			foreach($find_abbreviated as $abbreviated) {
				$delete_abbreviated = \R::load('product_abbreviated', $abbreviated->id);
				\R::trash($delete_abbreviated);
			}
		}		
		$find_cross = \R::findAll('plagins_cross', 'product_id = ?', [$id]);
		if($find_cross){
			foreach($find_cross as $cross) {
				$delete_cross = \R::load('plagins_cross', $cross->id);
				\R::trash($delete_cross);
			}
		}
		//Отзывы
		$find_review = \R::findAll('review_product', 'product_id = ?', [$id]);
			if($find_review){
			foreach($find_review as $review) {
				
				// удаление связей отзывов и товаров
				$delete_review = \R::load('review_product', $review->id);
				\R::trash($delete_review);
				
				//удаление отзывов
				$find_review2 = \R::findAll('review', 'id = ?', [$review->review_id]);
				foreach($find_review2 as $review2) {
					$delete_review2 = \R::load('review', $review2->id);
					\R::trash($delete_review2);
				}
				
				//удаление галереи отзывов
				$find_review3 = \R::findAll('review_gallery', 'review_id = ?', [$review->review_id]);
				foreach($find_review3 as $review3) {
					$delete_review3 = \R::load('review_gallery', $review3->id);
					\R::trash($delete_review3);
				}
				
			}
		}
		//Комплекты
		$find_complete_product = \R::findAll('plagins_complete_product', 'product_id = ?', [$id]);
		if($find_complete_product){
			foreach($find_complete_product as $complete_product) {
				
				// удаление связей комплектов и товаров
				$delete_complete_product = \R::load('plagins_complete_product', $complete_product->id);
				\R::trash($delete_complete_product);
				
				//удаление комплектов
				$find_complete = \R::findAll('plagins_complete', 'id = ?', [$complete_product->complete_id]);
				foreach($find_complete as $complete) {
					$delete_complete = \R::load('plagins_complete', $complete->id);
					\R::trash($delete_complete);
				}
				
				//удаление галереи комплектов
				$find_complete_gallery = \R::findAll('plagins_complete_gallery', 'complete_id = ?', [$complete_product->complete_id]);
				foreach($find_complete_gallery as $complete_gallery) {
					$delete_complete_gallery = \R::load('plagins_complete_gallery', $complete_gallery->id);
					\R::trash($delete_complete_gallery);
				}
				
			}
		}
		
		//сохранение истории
		\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','12','product','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
        
		// API IndexNow
		$indexnow = new PlaginsIndexnow();
		$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
		foreach($inw as $in){					
			$search_engine .= $indexnow->indexNowEngine($in->url, 'product', $product["alias"], $in->verification);
		}
				
        $_SESSION['success'] = 'Товар ID '.$id.' удален.'.$search_engine.'';
        redirect();
    }

    public function editAction(){
        if(!empty($_POST)){
            $id = $this->getRequestID(false);
            $product = new Product();
            $data = $_POST;
            $product->load($data);
            $product->attributes['new_product'] = $product->attributes['new_product'] ? '1' : '0';
            $product->attributes['hit'] = $product->attributes['hit'] ? '1' : '0';
			$product->attributes['sale'] = $product->attributes['sale'] ? '1' : '0';
            $product->getImg();
			$product->getUnloadImg();
            if(!$product->validate($data)){
                $product->getErrors();
                redirect();
            }
			
            if($product->update('product', $id)){
				$isNonEmptyArray = $product->traverseArray($data['attrs']);
				if($isNonEmptyArray){ $product->editFilter($id, $data); }
                $product->editRelatedProduct($id, $data);
				$product->editSimilarProduct($id, $data);
				$product->editServiceProduct($id, $data);
				$product->editAttributeProduct($id, $data);
				$product->editModificationProduct($id, $data);
				$product->editTagsProduct($id, $data);
                $product->saveGallery($id);
                $alias = AppModel::createAlias('product', 'alias', $data['name'], $id);
                $product = \R::load('product', $id);
				if($data['alias']!=""){ $product->alias = $data['alias'];}
				else{$product->alias = $alias;}
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','5','product','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
                \R::store($product);
				
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
        $product = \R::load('product', $id);
        App::$app->setProperty('parent_id', $product->category_id);
        $filter = \R::getCol('SELECT attr_id FROM attribute_product WHERE product_id = ?', [$id]);
		$att_product = \R::getAll('SELECT * FROM product_attribute, attribute WHERE attribute.id = product_attribute.attribute_id AND product_attribute.product_id = ?', [$id]);
        $related_product = \R::getAll('SELECT related_product.related_id, product.name FROM related_product JOIN product ON product.id = related_product.related_id WHERE related_product.product_id = ?', [$id]);
		$similar_product = \R::getAll('SELECT similar_product.similar_id, product.name FROM similar_product JOIN product ON product.id = similar_product.similar_id WHERE similar_product.product_id = ?', [$id]);
		$service_product = \R::getAll('SELECT service_product.service_id, product.name FROM service_product JOIN product ON product.id = service_product.service_id WHERE service_product.product_id = ?', [$id]);		
		$tags_product = \R::getAll('SELECT name FROM product_tags WHERE product_id = ?', [$id]);
        $gallery = \R::getCol('SELECT img FROM gallery WHERE product_id = ?', [$id]);
		$attrs = $this->getAttrs();
		$groups = $this->getGroups($product->category_id);
		$mods = \R::getAll('SELECT * FROM modification WHERE product_id = ?', [$id]);
		$count_review = \R::count('review_product', "product_id = ?", [$id]);
		$count_order = \R::count('order_product', "product_id = ?", [$id]);
		$count_bookmarks = \R::count('product_bookmarks', "product_id = ?", [$id]);
        $this->setMeta("Редактирование товара {$product->name}");
        $this->set(compact('product', 'filter', 'related_product', 'similar_product', 'service_product', 'gallery', 'att_product', 'tags_product', 'attrs', 'groups', 'mods', 'count_review', 'count_order', 'count_bookmarks'));
    }
	
	public function copyAction(){
        if(!empty($_POST)){
            $product = new Product();
            $data = $_POST;
            $product->load($data);
            $product->attributes['new_product'] = $product->attributes['new_product'] ? '1' : '0';
            $product->attributes['hit'] = $product->attributes['hit'] ? '1' : '0';
			$product->attributes['sale'] = $product->attributes['sale'] ? '1' : '0';
            $product->getImg();

            if(!$product->validate($data) || !$product->checkUniqueArticle()){
                $product->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($id = $product->save('product')){
                $product->saveGallery($id);
                $alias = AppModel::createAlias('product', 'alias', $data['name'], $id);
                $p = \R::load('product', $id);
                $p->alias = $alias;
                \R::store($p);
                $isNonEmptyArray = $product->traverseArray($data['attrs']);
				if($isNonEmptyArray){ $product->editFilter($id, $data); }
                $product->editRelatedProduct($id, $data);
				$product->editSimilarProduct($id, $data);
				$product->editServiceProduct($id, $data);
				$product->editAttributeProduct($id, $data);
				$product->editModificationProduct($id, $data);
				$product->editTagsProduct($id, $data);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','4','product','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
				
				// API IndexNow
				$indexnow = new PlaginsIndexnow();
				$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
				foreach($inw as $in){					
					$search_engine .= $indexnow->indexNowEngine($in->url, 'product', $p->alias, $in->verification);
				}
		
                $_SESSION['success'] = 'Товар добавлен.'.$search_engine.'';
            }
            redirect();
        }

        $id = $this->getRequestID();
        $product = \R::load('product', $id);
        App::$app->setProperty('parent_id', $product->category_id);
        $filter = \R::getCol('SELECT attr_id FROM attribute_product WHERE product_id = ?', [$id]);
		$att_product = \R::getAll('SELECT * FROM product_attribute, attribute WHERE attribute.id = product_attribute.attribute_id AND product_attribute.product_id = ?', [$id]);
        $related_product = \R::getAll('SELECT related_product.related_id, product.name FROM related_product JOIN product ON product.id = related_product.related_id WHERE related_product.product_id = ?', [$id]);
		$similar_product = \R::getAll('SELECT similar_product.similar_id, product.name FROM similar_product JOIN product ON product.id = similar_product.similar_id WHERE similar_product.product_id = ?', [$id]);
		$service_product = \R::getAll('SELECT service_product.service_id, product.name FROM service_product JOIN product ON product.id = service_product.service_id WHERE service_product.product_id = ?', [$id]);		
		$tags_product = \R::getAll('SELECT name FROM product_tags WHERE product_id = ?', [$id]);
        $gallery = \R::getCol('SELECT img FROM gallery WHERE product_id = ?', [$id]);
		$attrs = $this->getAttrs();
		$groups = $this->getGroups($product->category_id);
        $this->setMeta("Копирование товара {$product->name}");
        $this->set(compact('product', 'filter', 'related_product', 'similar_product', 'service_product', 'gallery', 'att_product', 'tags_product', 'attrs', 'groups'));
    }

    public function addAction(){
        if(!empty($_POST)){
            $product = new Product();
            $data = $_POST;
            $product->load($data);
            $product->attributes['new_product'] = $product->attributes['new_product'] ? '1' : '0';
            $product->attributes['hit'] = $product->attributes['hit'] ? '1' : '0';
			$product->attributes['sale'] = $product->attributes['sale'] ? '1' : '0';
            $product->getImg();

            if(!$product->validate($data) || !$product->checkUniqueArticle()){
                $product->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($id = $product->save('product')){
                $product->saveGallery($id);
                $alias = AppModel::createAlias('product', 'alias', $data['name'], $id);
                $p = \R::load('product', $id);
                $p->alias = $alias;
                \R::store($p);
                $isNonEmptyArray = $product->traverseArray($data['attrs']);
				if($isNonEmptyArray){ $product->editFilter($id, $data); }
                $product->editRelatedProduct($id, $data);
				$product->editSimilarProduct($id, $data);
				$product->editServiceProduct($id, $data);
				$product->editAttributeProduct($id, $data);
				$product->editModificationProduct($id, $data);
				$product->editTagsProduct($id, $data);
				\R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','4','product','".$id."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')");
				
				// API IndexNow
				$indexnow = new PlaginsIndexnow();
				$inw = \R::findAll('plagins_indexnow', 'hide = ?', ['show']);
				foreach($inw as $in){					
					$search_engine .= $indexnow->indexNowEngine($in->url, 'product', $p->alias, $in->verification);
				}
				
                $_SESSION['success'] = 'Товар добавлен.'.$search_engine.'';
            }
            redirect();
        }
        $this->setMeta('Новый товар');
		$this->set(compact('attrs', 'groups'));
    }

    public function relatedProductAction(){
        /*$data = [
            'items' => [
                [
                    'id' => 1,
                    'text' => 'Товар 1',
                ],
                [
                    'id' => 2,
                    'text' => 'Товар 2',
                ],
            ]
        ];*/
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $data['items'] = [];
        $products = \R::getAssoc('SELECT id, name FROM product WHERE name LIKE ? LIMIT 15', ["%{$q}%"]);
        if($products){
            $i = 0;
            foreach($products as $id => $name){
                $data['items'][$i]['id'] = $id;
                $data['items'][$i]['text'] = $name;
                $i++;
            }
        }
        echo json_encode($data);
        die;
    }
	
	public function filtersAction(){
		$groups = \R::getAssoc('SELECT attribute_group.id, attribute_group.title FROM attribute_group, attribute_category WHERE attribute_category.group_id = attribute_group.id AND attribute_category.category_id = "'.$_POST['id'].'"');
		$data = \R::getAssoc('SELECT * FROM `attribute_value` ORDER BY value');
        $attrs = [];
        foreach($data as $k => $v){
            $attrs[$v['attr_group_id']][$k] = $v['value'];
        }
		foreach($groups as $group_id => $group_item){
			echo "<div class='form-group row' id='filter'>
					<label class='col-sm-3 col-form-label'>".$group_item."</label>					
				<div class='col-sm-9'>";
		if(!empty($attrs[$group_id])){
		    echo "<select class='form-control' name='attrs[".$group_id."]'>            
				<option value=''> Выбрать фильтр</option>";									
				foreach($attrs[$group_id] as $attr_id => $value){
					echo "<option value='".$attr_id."' ".$selected.">".$value."</option>";
				}                
				
			echo "</select>";
		}
			echo "</div>";
					
			echo "</div>";
		}		
		die;
	}

    public function deleteGalleryAction(){
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $src = isset($_POST['src']) ? $_POST['src'] : null;
        if(!$id || !$src){
            return;
        }
        if(\R::exec("DELETE FROM gallery WHERE product_id = ? AND img = ?", [$id, $src])){
            @unlink(WWW . "/images/product/gallery/$src");
            exit('1');
        }
        return;
    }
	
	public function deleteBaseimgAction(){
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $src = isset($_POST['src']) ? $_POST['src'] : null;
        if(!$id || !$src){
            return;
        }
        if(\R::exec("UPDATE product SET img = '' WHERE id = ? AND img = ?", [$id, $src])){
            @unlink(WWW . "/images/product/baseimg/$src");
			@unlink(WWW . "/images/product/mini/$src");
            exit('1');
        }
        return;
    }
	
	public function deleteUnloadAction(){
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $src = isset($_POST['src']) ? $_POST['src'] : null;
        if(!$id || !$src){
            return;
        }
        if(\R::exec("UPDATE product SET unload_img = '' WHERE id = ? AND unload_img = ?", [$id, $src])){
            @unlink(WWW . "/images/product/unload/$src");
            exit('1');
        }
        return;
    }
	
	public function getGroups($category_id){
        return \R::getAssoc('SELECT attribute_group.id, attribute_group.title FROM attribute_group, attribute_category WHERE attribute_category.group_id = attribute_group.id AND attribute_category.category_id = "'.$category_id.'"');
    }

    public static function getAttrs(){
        $data = \R::getAssoc('SELECT * FROM `attribute_value` ORDER BY value');
        $attrs = [];
        foreach($data as $k => $v){
            $attrs[$v['attr_group_id']][$k] = $v['value'];
        }
        return $attrs;
    }
	
	/* ====Каталог - получение массива=== */
    public function getPrices(){
		$query = \R::getAll('SELECT * FROM `category`');
		$price = [];
		foreach($query as $k => $row){
			if(!$row['parent_id'] AND $row['parent_id']=="0"){
				$price[$row['id']][] = $row['name'];
			}else{
				$price[$row['parent_id']]['sub'][$row['id']] = $row['name'];
			}
		}
    return $price;
	}


}