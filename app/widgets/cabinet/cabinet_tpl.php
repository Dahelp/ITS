<?php $user = \R::findOne('user', 'id = ?', [$_SESSION['user']['id']]); ?>
<?php $manager = \R::findOne('user', 'id = ?', [$user->admin_id]); ?>
<div class="aiz-user-sidenav rounded overflow-auto c-scrollbar-light pb-5 pb-xl-0">
	<div class="p-4 text-xl-center mb-4 border-bottom bg-danger text-white position-relative">
		<span class="avatar avatar-md mb-3">
			<img src="images/avatar-place.png" class="image rounded-circle" onerror="this.onerror=null;this.src='https://new.apco.ru/public/assets/img/avatar-place.png';">
		</span>
		<h4 class="h5 fs-16 mb-1 fw-600"><?=$user->name?></h4>
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
			<?php if($user->groups == 4) { ?>
			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/company">
					<i class="far fa-address-card aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Компания</span>
				</a>
			</li><?php } ?>
			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/orders">
					<i class="far fa-file-alt aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">История заказов</span>
				</a>
			</li>
			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="user/bookmarks">
					<i class="far fa-heart aiz-side-nav-icon"></i>
					<span class="aiz-side-nav-text">Закладки</span>
				</a>
			</li>
			<li class="aiz-side-nav-item">
				<a class="aiz-side-nav-link" href="comparison">
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
		</ul>
	</div>
	<div class="cab-blk">
		<div class="cab-manager p-3"><h4>Ваш менеджер</h4></div>
		<div class="cab-manager-info pb-2">
			<div class="cab-manager-img p-3"><img src="adminlte/dist/img/user2-160x160.jpg" alt="" title="" class="img-circle elevation-2" /></div>
			<div class="cab-manager-name"><?=$manager->name?></div>
			<div class="cab-manager-telefon">+7 (495) 424-98-90</div>
		</div>
	</div>
</div>