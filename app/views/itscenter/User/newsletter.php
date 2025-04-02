<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active">Подписки</li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">				
				<?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
			</div>
			<div class="aiz-user-panel">
				<div class="card form-newsletter">
					<div class="card-header">
						<h5 class="mb-0 h6">Подписки</h5>
						<div class="card-tools">
							<div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
								<input type="checkbox" class="custom-control-input switch-newsletter" id="customSwitch3" <?php if($user->newsletter == 1) { echo "data-checked=\"1\""; }else{ echo "data-checked=\"0\""; }?> <?php if($user->newsletter == 1) { echo "checked"; }?>>
								<label class="custom-control-label" for="customSwitch3">Вкл/Выкл</label>
							</div>
						</div>
					</div>
					<div class="card-body">
					<?php if($newsletters): ?>
						<div class="form-group">
							<?php foreach($newsletters as $item) { 
								$newsletter = \R::getRow("SELECT * FROM newsletter, user_newsletter WHERE newsletter.id = user_newsletter.newsletter_id AND user_newsletter.user_id = ? AND user_newsletter.newsletter_id = ?", [$_SESSION['user']['id'], $item["id"]]);
							?>
								<div class="form-check">
									<input class="form-check-input newsletter_checked" data-newsletter_id="<?=$item["id"]?>" data-checked="<?php if($newsletter) { echo "1"; }else{ echo "0"; } ?>" type="checkbox" <?php if($newsletter) { echo "checked=\"\""; } ?>>
									<label class="form-check-label"><?=$item["name"]?></label>
								</div>
							<?php } ?>
						</div>
					<?php else: ?>
						<p class="text-danger">Подписок пока нет.</p>
					<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->