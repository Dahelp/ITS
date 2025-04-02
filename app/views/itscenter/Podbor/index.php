<?php $inseo_prod = \R::findOne('plagins_inseo', "tip = ? AND category_id = ? AND hide = 'show'", [product, $category->id]); ?>
<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>							
                <li class="breadcrumb-item active"><?=$pdr_name?></li>
            </ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3"><?=$pdr_name?></h1>			
        </section>

		<!--<div class="fltr-info">В данном разделе каталога представлены модели шин предназначенных для установки на экскаваторы погрузчики и телескопические погрузчики различных моделей и различного применения. Выберите модель шины для просмотра технических характеристик:</div>-->
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
                        
                    </div>                
                <?php endif; ?>
				<div class="catalog_text"></div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<!--product-end-->