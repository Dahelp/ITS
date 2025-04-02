<?php $inseo_prod = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [product, $category->id]); ?>
<div class="banner-inner">
	<div class="container">
		<div class="bnr-catalog">
			<div class="col-md-2 bnr-bk1">
				<img src="images/banner-1.jpg" alt="" title="" />
			</div>
			<div class="col-md-7 bnr-bk2">
				<ul class="bnr-info">
					<li class="bnr-inner"><i class="far fa-credit-card"></i><span>Оплачивайте заказы переводом с расчётного счёта или банковской картой</span></li>
					<li class="bnr-inner"><i class="far fa-badge-percent"></i><span>Возмещайте НДС до 20%</span></li>
					<li class="bnr-inner"><i class="far fa-receipt"></i><span>Получайте комплект документов в ЭДО, на email или в бумажном виде</span></li>
				</ul>
			</div>
			<div class="col-md-3 bnr-bk3">
				<div class="bk3-p">Зарегистрируйте личный кабинет, получите больше возможностей</div>
				<div class="bnr-btn"><a href="user/signup" class="btn btn-danger">Добавить компанию</a></div>
			</div>
		</div>
	</div>
</div>

<!--prdt-starts-->
<div class="prdt">
    <div class="container" itemscope itemtype="https://schema.org/OfferCatalog">
		<!--start-breadcrumbs-->
		<nav class="pt-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
				<?=$breadcrumbs;?>
			</ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3" itemprop="name"><?php
					if($inseo->name) { 					
						echo $name = \ishop\App::seoreplace($inseo->name, $category->id);
					}
					else { echo $category->name; }
				?></h1>			
        </section>
		<img src="images/category/baseimg/<?=$category->img?>" itemprop="image" style="display:none">
		<?php $podcategory = \R::getAll("SELECT * FROM category WHERE parent_id =?", [$category->id]);
			if($podcategory) { ?>
		<section class="align-items-center podcats">
            <?php
				foreach($podcategory as $podcat) {
					echo "<div class=\"podssilka\"><a href=\"category/".$podcat["alias"]."\" title=\"\">".$podcat["name"]."</a></div>";
				}			
			?>			
        </section>
		<?php } ?>
		<!--<div class="fltr-info">В данном разделе каталога представлены модели шин предназначенных для установки на экскаваторы погрузчики и телескопические погрузчики различных моделей и различного применения. Выберите модель шины для просмотра технических характеристик:</div>-->
		<div>
		<?php
		if(!empty($ids)){
			$data = \R::getAssoc('SELECT attribute_value.id, attribute_value.value, attribute_value.attr_group_id FROM attribute_value, attribute_product, product WHERE attribute_value.id = attribute_product.attr_id AND product.id = attribute_product.product_id AND product.category_id IN ('.$ids.') GROUP BY attribute_value.value ORDER BY attribute_value.value');
        }else{
			$data = \R::getAssoc('SELECT attribute_value.id, attribute_value.value, attribute_value.attr_group_id FROM attribute_value, attribute_product WHERE attribute_value.id = attribute_product.attr_id GROUP BY attribute_value.value ORDER BY attribute_value.value');
        }
		$attrs = [];
        foreach($data as $k => $v){
            $attrs[$v['attr_group_id']][$k] = $v['value'];
        } 
		
		
		
		?>
		
		<section class="d-md-flex justify-content-between align-items-center pb-4">
			<div class="w_sidebar col-md-12 fltr">
                    <?php new \app\widgets\filter\Filter($ids);	?>
            </div>            
        </section>
		<div class="d-flex align-items-center col-md-12 pb-4 sort-inner">
              <div class="sort-inner"><div class="sort-name">Сортировать по:</div>

				<span class="nav-link" id="nal">Наличию</span>
                <span class="nav-link" id="price">Цене</span>
                <span class="nav-link" id="rate">Рейтингу</span>
              
				</div>
            </div>  
        <div class="prdt-top">
            <div class="col-md-12">                
				<?php if(!empty($products)): ?>
                    <div class="row g-0 mx-n2 product-one">	   
						<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
						<?php foreach($products as $product): ?>							
							<div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3" itemprop="itemListElement" itemscope itemtype="https://schema.org/Offer">
								<?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>
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
                    <h3>"Живого" наличия позиций в данном типоразмере нет. Для уточнения возможной поставки товара "Под заказ" просьба связаться с нашими менеджерами по тел.: +7(495)424-98-90 или написать нам на электронную почту: info@its-center.ru</h3>
                <?php endif; ?>
				<div class="catalog_text" itemprop="description"><?=$category->content?></div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
	<div class="clearfix"></div>
</div>
<!--product-end-->