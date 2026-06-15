<div class="collapse navbar-collapse mbl-menu-collapse" id="navbarNavAltMarkup">
	<div class="mbl-menu">
		<div class="mbl-close">
			<div class="mbl-cl-icon">
				<button class="navbar-toggler"
						type="button"
						data-bs-toggle="collapse"
						data-bs-target="#navbarNavAltMarkup"
						aria-controls="navbarNavAltMarkup"
						aria-expanded="false"
						aria-label="Закрыть меню">
					<i class="fal fa-times"></i>
				</button>
			</div>
		</div>

		<div class="mbl-info">
			<ul>
				<li><a href="/pages/kak-kupit" title="">Как купить</a></li>
				<li><a href="/promo" title="">Акции</a></li>
				<li><a href="/pages/about-us" title="">О компании</a></li>
				<li><a href="/pages/sotrudnichestvo" title="">Сотрудничество</a></li>
				<li><a href="/pages/contacts" title="">Адреса и контакты</a></li>
			</ul>
		</div>
	</div>
</div>

<!--top-header-->
<div class="top-header">
<?php
$tell_zv = $tell_zv ?? \ishop\App::options('option_telefon');
$telefon_href = str_replace(['(', ')', ' ', '-'], '', (string)$tell_zv);

$cmp = (int)($_SESSION['comparison_count'] ?? 0);
$cartQty = (int)($_SESSION['cart.qty'] ?? 0);

$userId = (int)($_SESSION['user']['id'] ?? 0);
$isAuth = $userId > 0;

$wishlistCount = 0;
if ($isAuth) {
    $wishlistCount = (int)\R::count('product_wishlists', 'user_id = ?', [$userId]);
}
?>
	<header id="masthead" class="site-header site-header--modern">
		<div class="container">
			<div class="header-shell" id="headerShell">

				<!-- Верхняя строка -->
				<div class="header-top" id="headerTop">

					<div class="header-logo">
						<?php if($this->route["controller"] != "Main"): ?>
							<a href="/" class="header-logo__link" aria-label="ИТС-Центр — на главную">
								<img src="/images/logo.svg" alt="ИТС-Центр" class="header-logo__img">
							</a>
						<?php else: ?>
							<div class="header-logo__link" aria-label="ИТС-Центр">
								<img src="/images/logo.svg" alt="ИТС-Центр" class="header-logo__img">
							</div>
						<?php endif; ?>
					</div>

					<div class="header-catalog">
						<button id="catalogToggle"
								type="button"
								class="header-catalog__btn">
							<i class="fas fa-bars"></i>
							<span>Каталог</span>
						</button>
					</div>

					<div class="header-search">
						<form action="/search" method="get" autocomplete="off" aria-label="Поиск по сайту" class="header-search__form" onsubmit="return false;">
							<input
								type="text"
								class="typeahead header-search__input"
								id="typeahead"
								name="s"
								placeholder="Искать шины, диски, камеры, фильтры, артикул или код"
								aria-label="Поиск по сайту"
							>
							<button type="button" class="header-search__submit" aria-label="Найти">
								<i class="fas fa-search"></i>
							</button>
						</form>
					</div>

					<div class="header-actions">

						<div class="header-actions__item">
							<?php if(!$isAuth): ?>
								<a href="javascript:;"
								   class="header-action-link"
								   data-bs-toggle="modal"
								   data-bs-target="#Modallogin">
									<i class="far fa-user"></i>
									<span>Войти</span>
								</a>
							<?php else: ?>
								<a href="/user/cabinet" class="header-action-link">
									<i class="far fa-user"></i>
									<span>Кабинет</span>
								</a>
							<?php endif; ?>
						</div>

						<div class="header-actions__item">
							<a href="/comparison" class="header-action-link">
								<i class="far fa-chart-bar"></i>
								<span>Сравнение</span>
								<span class="header-action-link__count" id="comparison_kol"><?= (int)$cmp ?></span>
							</a>
						</div>

						<?php if($isAuth): ?>
							<div class="header-actions__item">
								<a href="/user/wishlist" class="header-action-link">
									<i class="far fa-heart"></i>
									<span>Избранное</span>
									<span class="header-action-link__count" id="wishlist_kol"><?= (int)$wishlistCount ?></span>
								</a>
							</div>
						<?php endif; ?>

						<div class="header-actions__item">
							<a href="javascript:void(0);"
							   class="header-action-link js-open-cart"
							   data-bs-toggle="modal"
							   data-bs-target="#exampleModalLive">
								<i class="fas fa-shopping-cart"></i>
								<span>Корзина</span>
								<span class="header-action-link__count" id="cart-total"><?= $cartQty ?></span>
							</a>
						</div>

						<div class="header-actions__item header-actions__item--mobile-menu">
							<button class="header-action-link header-mobile-menu-btn"
									type="button"
									data-bs-toggle="collapse"
									data-bs-target="#navbarNavAltMarkup"
									aria-controls="navbarNavAltMarkup"
									aria-expanded="false"
									aria-label="Открыть меню">
								<i class="fas fa-bars"></i>
								<span>Меню</span>
							</button>
						</div>

					</div>
				</div>

				<!-- Нижняя строка -->
				<div class="header-bottom" id="headerBottom">
					<nav class="header-menu" aria-label="Основное меню">
						<a href="/pages/kak-kupit">Как купить</a>
						<a href="/promo">Акции</a>
						<a href="/pages/about-us">О компании</a>
						<a href="/pages/sotrudnichestvo">Сотрудничество</a>
						<a href="/pages/contacts">Контакты</a>
					</nav>

					<div class="header-contacts">
						<a href="mailto:info@its-center.ru" class="header-contacts__item">
							<i class="far fa-envelope"></i>
							<span>info@its-center.ru</span>
						</a>

						<a href="tel:<?=h($telefon_href)?>" class="header-contacts__item">
							<i class="fas fa-phone-alt"></i>
							<span><?=h($tell_zv)?></span>
						</a>

						<div class="header-contacts__item header-contacts__item--muted">
							<i class="far fa-clock"></i>
							<span>Пн–Пт: 9:00–17:00</span>
						</div>
					</div>
				</div>

			</div>
		</div>
	</header>
</div>