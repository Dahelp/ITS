<?php
if (empty($_SESSION['user']['id'])) {
    return;
}

$user = \R::findOne('user', 'id = ?', [(int)$_SESSION['user']['id']]);
$manager = null;

if ($user && !empty($user->admin_id)) {
    $manager = \R::findOne('user', 'id = ?', [(int)$user->admin_id]);
}
?>

<div class="aiz-user-sidenav rounded overflow-auto c-scrollbar-light pb-5 pb-xl-0">
	<div class="p-4 text-xl-center mb-4 border-bottom bg-danger text-white position-relative">
		<span class="avatar avatar-md mb-3">
			<img src="images/avatar-place.png" class="image rounded-circle" alt="<?= h($user->name ?? 'Пользователь') ?>">
		</span>
		<h4 class="h5 fs-16 mb-1 fw-600"><?= h($user->name ?? '') ?></h4>
	</div>

	<div class="sidemnenu mb-3">
		<ul class="aiz-side-nav-list px-2 metismenu mb-3" data-toggle="aiz-side-menu">
			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/cabinet" aria-expanded="true">
					<i class="far fa-home-alt aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Личный кабинет</span>
				</a>
			</li>

			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/edit">
					<i class="far fa-user aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Персональные данные</span>
				</a>
			</li>

			<?php if (($user->groups ?? null) == 4): ?>
				<li class="aiz-side-nav-item">
					<a class="aiz-side-nav-link" href="user/company">
						<i class="far fa-address-card aiz-side-nav-icon"></i>
						<span class="aiz-side-nav-text">Компания</span>
					</a>
				</li>
			<?php endif; ?>

			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/orders">
					<i class="far fa-file-alt aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">История заказов</span>
				</a>
			</li>

			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/wishlist">
					<i class="far fa-heart aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Избранное</span>
				</a>
			</li>

			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" target="_blank" href="comparison">
					<i class="far fa-tasks aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Сравнения</span>
				</a>
			</li>

			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/pricelist">
					<i class="far fa-file-pdf aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Прайс-лист</span>
				</a>
			</li>

			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/dogovor">
					<i class="far fa-file-signature aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Договор</span>
				</a>
			</li>

			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/export">
					<i class="far fa-file-pdf aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Выгрузка товаров</span>
				</a>
			</li>

			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/newsletter">
					<i class="far fa-file-pdf aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Подписки</span>
				</a>
			</li>

			<li class="aiz-side-nav-item mt-3 pt-2" style="border-top: 1px solid #e9ecef;">
				<a class="aiz-side-nav-link text-danger" href="user/logout">
					<i class="far fa-sign-out-alt aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Выйти</span>
				</a>
			</li>
		</ul>
	</div>

	<div class="cab-blk">
		<div class="cab-manager p-3"><h4>Ваш менеджер</h4></div>
		<div class="cab-manager-info pb-2">
			<div class="cab-manager-img p-3">
				<img src="adminlte/dist/img/user2-160x160.jpg" alt="" title="" class="img-circle elevation-2" />
			</div>
			<div class="cab-manager-name"><?= h($manager->name ?? 'Не назначен') ?></div>
			<div class="cab-manager-telefon">+7 (495) 424-98-90</div>
		</div>
	</div>
</div>