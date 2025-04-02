<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="technics">Каталог техники</a></li>
				<li class="breadcrumb-item"><a href="technics/type/<?=$type["alias"]?>">Производители <?=$type["seoname_1"]?></a></li>
                <li class="breadcrumb-item active"><?php echo \ishop\App::upFirstLetter($type["seoname_3"]);?> <?=$manufacturer["name"]?></li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--start-single-->
<div class="single contact">
    <div class="container">
		<div class="register-top heading">
			<h1><?php echo \ishop\App::upFirstLetter($type["seoname_3"]);?> <?=$manufacturer["name"]?></h1>
		</div>
        <div class="technics-inner row">
			<?php foreach($technics as $item) { ?>
			<a href="technics/<?=$item["alias"]?>" title="<?=$item["model"]?>" class="col-md-3">
				<div class="p_cat">
					<div class="cb-img">
						<?php if($item["img"]) { ?>
							<img src="images/technics/mini/<?=$item["img"]?>" alt="<?=$item["model"]?>" title="<?=$item["model"]?>" width="150">
						<?php }else{ ?>
							<img src="images/no_image.jpg" alt="" title="" width="150">
						<?php } ?>
					</div>
					<div class="cb-span">
						<h2><?=$item["model"]?></h2>
					</div>
				</div>
			</a>
			<?php } ?>
			<div class="clearfix"></div>
			<div class="text-center">                            
				<?php if($pagination->countPages > 1): ?>
					<?=$pagination;?>
				<?php endif; ?>
			</div>
		</div>
		<?php if($manufacturer["content"]) { ?>
		
			<div class="catalog_text col-md-12">
				<?=$manufacturer["content"]?>
			</div>
		
		<?php } ?>
    </div>
</div>
<!--end-single-->