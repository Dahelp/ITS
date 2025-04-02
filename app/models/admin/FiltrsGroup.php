<?php

namespace app\models\admin;

use ishop\App;
use app\models\AppModel;

class FiltrsGroup extends AppModel{

    public $attributes = [
        'title' => '',
		'url_params' => '',
		'seo_title' => '',
		'seo_description' => '',
		'seo_keywords' => '',
		'page_name' => '',
		'seo_content' => '',
		'notproduct' => '',
    ];

    public $rules = [
        'required' => [
            ['title'],
        ],
    ];
	
	public function addClassGroup($data){
		list($url1, $url2) = explode('-', $data["url_params"]);
        $fileName = "".ucfirst($url1)."".ucfirst($url2)."";
		mkdir(''. APP . '/views/' . TEMPLATE . '/'.$fileName.'', 0700); //создание папки
		$dir_view = APP . '/views/' . TEMPLATE . '/'.$fileName.'/view.php'; //путь файла контента		
		$dir_index = APP . '/views/' . TEMPLATE . '/'.$fileName.'/index.php'; //путь файла всех фильтров
			
			$phpContent_index = '
<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><?=$type->page_name;?></li>
            </ol>
		</nav>
    </div>
</div>
<div class="contents">
    <div class="container">
		<div class="row">
			<div class="col-md-12">
				<?php if(!empty($groups)): ?>
					<div class="register-top heading">
						<h1><?=$type->page_name;?></h1>
					</div>
					<div class="cont-inner">
						<div class="group-filtr">
						<?php foreach($groups as $group): ?>
							<div class="filtr-one">
								<a href="<?=$type->url_params;?>/<?php echo mb_strtolower($group["alias"]); ?>" title="<?=$group["value"]?>">
									<?php if($group->img) { ?><div class="filtrs-img"><img src="images/filtrs/baseimg/<?=$group["img"]?>" alt="<?=$group["value"]?>" title="<?=$group["value"]?>" width="150" height="120"></div><?php } ?>
									<div class="filtrs-value"><?=$group["value"]?></div>
								</a>
							</div>
						<?php endforeach; ?>
						</div>
					</div>
					<div class="catalog_text col-md-12">
						<?=$type->seo_content;?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>	
</div>
';
		file_put_contents($dir_index, $phpContent_index);
		
		
		$dir_controller = APP . '/controllers/'.$fileName.'Controller.php';	
		
		$phpContent_view = '
<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item active"><a href="<?=$params->url_params?>"><?=$params->title;?></a></li>				
                <li class="breadcrumb-item active"><?=$find->value;?></li>
            </ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3">
				<?php
					if($inseo->name) { 					
						echo $name = \ishop\App::seoreplacefilter($inseo->name, $find->id);
					}
					else { echo $find->name; }
				?>
			</h1>			
        </section>		
			<div class="prdt-top">
            <div class="col-md-12">                
				<?php if(!empty($products)): ?>					
                    <div class="row g-0 mx-n2 product-one">
                        <?php $curr = \ishop\App::$app->getProperty(\'currency\'); ?>
                        <?php foreach($products as $product): ?>
                            <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
					            <?php new \app\widgets\product\Product($product, $curr, \'product_tpl.php\'); ?>
				            </div>
                        <?php endforeach; ?>
                        <div class="clearfix"></div>
                        <div class="text-center">                            
                            <?php if($pagination->countPages > 1): ?>
                                <?=$pagination;?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning product-note"><?php if(!$params->notproduct) { ?>В этой категории товаров пока нет...<?php }else{ ?><?=$params->notproduct?><?php } ?></div>
                <?php endif; ?>
				<?php if(!empty($products)): ?>
					<?php foreach($products as $prod){ $value .= "".$prod["id"].","; } $values = rtrim($value,",");?>			
					<div class="catalog_text">
						<?php
							echo $find->content;
							if($inseo->content) { 					
								echo $content = \ishop\App::seoreplacetiposize($inseo->content, $values);
							}								
						?>							
					</div>
				<?php endif; ?>
            </div>
            <div class="clearfix"></div>
        </div>		
	</div>
</div>
<!--product-end-->		
';
		$phpContent_controller = '<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;

class ' . $fileName . 'Controller extends AppController {

    public function viewAction(){
		
		$alias = $this->route[\'alias\'];
		$up_registr = App::upRegistrLetter($alias);
		$find = \R::findOne(\'attribute_value\', \'alias = ?\', [$alias]);
		if(!$find){
            throw new \Exception(\'Страница не найдена\', 404);
        }
		$breadcrumbs = Breadcrumbs::getBreadcrumbs($find->id);
		$page = isset($_GET[\'page\']) ? (int)$_GET[\'page\'] : 1;
        $perpage = App::$app->getProperty(\'pagination\');
		
		if(!empty($_GET[\'sort\'])){
			if($_GET[\'sort\'] == "price") { $sql_sort = "ORDER BY product.price ASC"; }
			if($_GET[\'sort\'] == "nal") { $sql_sort = "ORDER BY product.stock_status_id DESC"; }
			if($_GET[\'sort\'] == "rate") { $sql_sort = "ORDER BY product.hit DESC"; }
		}else{
			$sql_sort = "ORDER BY FIELD(`stock_status_id`, 1,3,2,0), name ASC";
		}
		
        $total = \R::exec("SELECT product_id FROM attribute_product, product WHERE attribute_product.product_id = product.id AND attribute_product.attr_id = \'".$find->id."\' $sql_sort");
		$ids = \R::getAll("SELECT product_id FROM attribute_product, product WHERE attribute_product.product_id = product.id AND attribute_product.attr_id = \'".$find->id."\' $sql_sort");
		if($ids){
			foreach($ids as $ds){
				$prid .= "".$ds["product_id"].",";
			}
			$ids = rtrim($prid, \',\');
			$pagination = new Pagination($page, $perpage, $total);
			$start = $pagination->getStart();
			
			$products = \R::find(\'product\', "hide = \'show\' AND id IN ($ids) $sql_sort LIMIT $start, $perpage");
		}
        //InSEO
		$params = \R::findOne(\'attribute_group\', "id = ?", [$find["attr_group_id"]]);
		$inseo = \R::findOne(\'plagins_inseo\', "tip = ? AND category_id = ? AND hide = \'show\'", [attribute_group, $find["attr_group_id"]]);
		if($inseo->title) {
			$title = \ishop\App::seoreplacefilter($inseo->title, $find->id);
		}else{ $title = $find->title; }
		if($inseo->description) {
			$description = \ishop\App::seoreplacefilter($inseo->description, $find->id);
		}else{ $description = $find->description; }
		if($inseo->keywords) {
			$keywords = \ishop\App::seoreplacefilter($inseo->keywords, $find->id);
		}else{ $keywords = $find->keywords; }

		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$this->setMeta($title, $description, $keywords, \'\' . App::$app->getProperty(\'shop_name\') . \'\', \'\'.PATH.\'/images/\' . App::$app->getProperty(\'og_logo\') . \'\', \'\'.PATH.\'\'.$path_controller.\'\'.$path_alias.\'\');
		/*SEO*/
				

        $this->set(compact(\'find\', \'products\', \'breadcrumbs\', \'pagination\', \'total\', \'ids\', \'params\', \'inseo\'));

    }';
$phpContent_controller .= '
	public function indexAction(){
		$alias = $_SERVER[\'REQUEST_URI\'];
		$alias = str_replace(\'/\', \'\', $alias);
		$type = \R::findOne(\'attribute_group\', \'url_params = ?\', [$alias]);
		$groups = \R::findAll(\'attribute_value\', \'attr_group_id = ?\', [$type->id]);
		/*SEO*/
		if($this->route["controller"]){ $path_controller = "/".mb_strtolower($this->route["controller"]).""; }else{ $path_controller = ""; }
		if($this->route["alias"]){ $path_alias = "/".$this->route["alias"].""; }else{ $path_alias = ""; }
		$this->setMeta($type->seo_title, $type->seo_description, $type->seo_keywords, \'\' . App::$app->getProperty(\'shop_name\') . \'\', \'\'.PATH.\'/images/\' . App::$app->getProperty(\'og_logo\') . \'\', \'\'.PATH.\'\'.$path_controller.\'\'.$path_alias.\'\');
		/*SEO*/
        $this->set(compact(\'groups\', \'type\'));
	}
';
$phpContent_controller .= '
} ';
		$dir_route = CONF . '/routes.php';
		
		$FileSourse_del = file_get_contents($dir_route);
		$FileSourse_del = preg_replace("#
//".$fileName."//.*//And".$fileName."//#is", '', $FileSourse_del);
		file_put_contents($dir_route, $FileSourse_del);
		
		$phpRoute = "//".$fileName."//
Router::add('^".$data["url_params"]."/(?P<alias>[a-z0-9-]+)/?$', ['controller' => '".$fileName."', 'action' => 'view']);
//And".$fileName."//
//  Add here";
		
		$FileSourse = file_get_contents($dir_route);
		$FileSourse = str_replace('//  Add here',$phpRoute,$FileSourse);
		file_put_contents($dir_view, $phpContent_view);
		file_put_contents($dir_controller, $phpContent_controller);
		file_put_contents($dir_route, $FileSourse);
    }
	
	public function checkUnique(){
        $type = \R::findOne('attribute_group', 'title = ? AND url_params = ?', [$this->attributes['title'], $this->attributes['url_params']]);
        if($type){
            if($type->title == $this->attributes['title']){
                $this->errors['unique'][] = 'Название группы фильтров уже существует';
            }
			if($type->url_params == $this->attributes['url_params']){
                $this->errors['unique'][] = 'Системное имя уже существует';
            }
            return false;
        }
        return true;
    }
}