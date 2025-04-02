<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
				<?=$breadcrumbs;?>
			</ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3"><?=$cats["name"]?></h1>			
        </section>
 
        <div class="prdt-top">
            <div class="col-md-12">                
				<div class="row menu-cat">
					<?php foreach($category as $cat): ?>
						<a href="<?php							
							if($cat->type_id == 1) { 
								$parent = \R::findOne('category', 'parent_id = ?', [$cat["id"]]);
								if($parent){ echo "catalog/".$cat["alias"].""; }else{ echo "category/".$cat["alias"].""; }
							}else{ echo "category/".$cat["alias"].""; } ?>" title="<?=$cat["name"]?>" class="col-md-3">
							<div class="p_cat">
								<div class="cb-img">
									<img src="/images/category/baseimg/<?=$cat["img"]?>" alt="<?=$cat["name"]?>" title="<?=$cat["name"]?>">
								</div>
								<div class="cb-span">
									<h2><?=$cat["name"]?></h2>
								</div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<!--product-end-->