<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="technics">Каталог техники</a></li>
                <li class="breadcrumb-item active">Производители <?=$type["seoname_1"]?></li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--start-single-->
<div class="single contact">
    <div class="container">
		<div class="register-top heading">
			<h1>Производители <?=$type["seoname_1"]?></h1>
		</div>
        <div class="technics-inner row">
			<?php foreach($manufacturers as $item) { ?>
			<a href="technics/<?=$type["alias"]?>/<?=$item["alias"]?>" title="<?=$item["name"]?>" class="col-md-2">
				<div class="p_tcat">
					<div class="cb-img">	
						<img src="images/technics_manufacturer/baseimg/<?=$item["img"]?>" alt="<?=$item["name"]?>" title="<?=$item["name"]?>" width="150">
					</div>
					<div class="cb-span">
						<h2><?=$item["name"]?></h2>
					</div>
				</div>
			</a>
			<?php } ?>
		</div>
		<?php if($type["content"]) { ?>
		
			<div class="catalog_text col-md-12">
				<?=$type["content"]?>
			</div>
		
		<?php } ?>
    </div>
</div>
<!--end-single-->