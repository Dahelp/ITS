<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active">Каталог техники</li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--start-single-->
<div class="single contact">
    <div class="container">
		<div class="register-top heading">
			<h1>Каталог техники</h1>
		</div>
        <div class="technics-inner row">
			<?php foreach($technics as $tech) { ?>
			<a href="technics/type/<?=$tech["alias"]?>" title="<?=$tech["name"]?>" class="col-md-3">
				<div class="p_cat">
					<div class="cb-img">	
						<img src="images/technics_type/baseimg/<?=$tech["img"]?>" alt="<?=$tech["name"]?>" title="<?=$tech["name"]?>" width="150">
					</div>
					<div class="cb-span">
						<h2><?=$tech["name"]?></h2>
					</div>
				</div>
			</a>
			<?php } ?>
		</div>
    </div>
</div>
<!--end-single-->
