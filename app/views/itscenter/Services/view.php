
<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<?php if($type->hide_anons=="show") { ?>
					<li class="breadcrumb-item active"><a href="<?=$type->param_url?>"><?=$type->name;?></a></li>
				<?php } ?>
                <li class="breadcrumb-item active"><?=$find->name;?></li>
            </ol>
		</nav>
    </div>
</div>
<div class="contents">
    <div class="container">
		<div class="row">		
			<?php if(!empty($find)): 
				if($type->hide_clicks == "show") { \R::exec("UPDATE contents SET clicks = clicks+1 WHERE id = ?", [$find->id]); } ?>
			
				<div class="col-md-12">
					<div class="bg-light rounded-3">
						<article itemscope itemtype="http://schema.org/NewsArticle">
							<div class="register-top heading">
								<h1 itemprop="headline"><?=$find->name;?></h1>
							</div>
							<span itemprop="author" itemscope itemtype="http://schema.org/Person">
							<?php $shop_name = \ishop\App::$app->getProperty('shop_name'); ?>
							<span itemprop="name"><?=$shop_name?></span>
							</span>
							<?php if($type["hide_date_post"] == "show") { ?>
								<div class="cont_info_data">
									<time itemprop="datePublished" datetime="<?=date("c", strtotime($find["date_post"]))?>"><?php echo \ishop\App::contdate($find["date_post"]); ?></time>
								</div>
							<?php } ?>
							<meta itemprop="dateModified" content="<?=date("c", strtotime($find["date_last_modified"]))?>">							
							<div class="cont-inner">
								<?php if($find->img) { ?>
									<?php if($find->img_hide == "show") { ?>
										<div class="cont-img">
											<img src="images/contents/baseimg/<?=$find->img;?>" alt="" />
										</div>
									<?php } ?>
								<?php } ?>
								<div class="cont-desc" itemprop="articleBody">
									<?=$find->content;?>
								</div>
							</div>
							<div class="share">
								<div class="share-text">Поделиться:</div><div class="share-ya">
								<script src="https://yastatic.net/share2/share.js"></script>
								<div class="ya-share2" data-curtain data-services="vkontakte,odnoklassniki,telegram,whatsapp"></div>
								</div>
							</div>
						</article>	
					</div>					
				</div>
				<?php
					$curr = \ishop\App::$app->getProperty('currency');
					$cats = \ishop\App::$app->getProperty('cats');
				?>
				<!-- Related products-->
		  <?php if($related): ?>
		  <div class="related_prod">
          <section class="pb-5 mb-2 mb-xl-4 recomend-1">
            <h2 class="h3 pb-2 mb-grid-gutter text-center">Связанные товары</h2>
            <div class="review-wrap">

			<div class="wrap-container">
			<div class="inner-container">				

			<div class="swiper-container swiper1">
				<div class="swiper-wrapper">
				
				<?php foreach($related as $product): ?>
				
					<div class="swiper-slide">					                        
						
					            <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>
				            					
					</div>
					
				<?php endforeach; ?>
				
				</div>
						
				</div>
					
				</div>
					
				</div>
				
			</div>
			<div class="swiper-button-inner">
				<div class="swiper-button-next swiper-button-next-1"></div>
				<div class="swiper-button-prev swiper-button-prev-1"></div>
			</div>
          </section>
		  </div>
		  <?php endif; ?>
		  <!-- /Related products-->		  
		 
			<?php endif; ?>		
		</div>
	</div>	
</div>		
