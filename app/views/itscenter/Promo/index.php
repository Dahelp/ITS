
<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><?=$type->name;?></li>
            </ol>
		</nav>
    </div>
</div>
<div class="contents">
    <div class="container">
		<div class="row">
			<div class="cont-blok">
				<?php foreach($conts as $item) { ?>
					<div class="col-md-3 cont-one">
						<div class="cont_ht border border-grey">
							<div class="cont_blok_img">
								<?php if($item["img"] !="") { ?>
									<img src="images/contents/baseimg/<?=$item["img"]?>" alt="<?=$item["name"]?>" title="<?=$item["name"]?>" />
								<?php } else { ?>
									<img src="images/no_image.jpg" alt="" />
								<?php } ?>
							</div>
							<div class="cont_info">
								<?php if($type["hide_date_post"] == "show") { ?>
									<div class="cont_info_data">
										<?php echo \ishop\App::contdate($item["date_post"]); ?>
									</div>
								<?php } ?>
								<div class="cont_info_name">
									<a href="<?=$type->param_url;?>/<?=$item["alias"];?>"><?=$item["name"];?></a>
								</div>
								<div class="cont_info_anons">
									<?php echo mb_strimwidth($item["anons"], 0, 200, "...");?>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
			<div class="clearfix"></div>
			<div class="pb-4">                            
				<?php if($pagination->countPages > 1): ?>
					<?=$pagination;?>
				<?php endif; ?>
			</div>
		</div>
	</div>	
</div>
