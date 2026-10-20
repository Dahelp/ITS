<?php $hpUser = !empty($_SESSION['user']['id']); ?>
<header class="hp-header" id="masthead"><div class="hp-header__inner" id="headerShell">
  <a class="hp-logo" href="/" aria-label="ИТС-Центр — на главную"><img src="/images/logo.svg" width="260" height="50" alt="ИТС-Центр"></a>
  <button id="catalogToggle" class="hp-catalog-button" type="button"><i class="far fa-bars" aria-hidden="true"></i><span>Каталог</span></button>
  <form action="/search" method="get" autocomplete="off" class="hp-search header-search__form" aria-label="Поиск по каталогу" onsubmit="return false;">
    <i class="far fa-search hp-search__hint" aria-hidden="true"></i><input class="typeahead header-search__input" id="typeahead" name="s" type="text" placeholder="Поиск по каталогу" aria-label="Поиск по каталогу"><button class="header-search__submit" type="button" aria-label="Найти"><i class="far fa-search" aria-hidden="true"></i></button>
  </form>
  <nav class="hp-account" aria-label="Личный кабинет и корзина">
    <?php if ($hpUser): ?><a href="/user/cabinet"><i class="far fa-user" aria-hidden="true"></i><span>Кабинет</span></a><?php else: ?><a href="#Modallogin" data-bs-toggle="modal" data-bs-target="#Modallogin"><i class="far fa-user" aria-hidden="true"></i><span>Войти</span></a><?php endif; ?>
    <?php if ($hpUser): ?><a href="/user/wishlist"><i class="far fa-heart" aria-hidden="true"></i><span>Избранное</span></a><?php else: ?><a href="#Modallogin" data-bs-toggle="modal" data-bs-target="#Modallogin"><i class="far fa-heart" aria-hidden="true"></i><span>Избранное</span></a><?php endif; ?>
    <a href="/cart/view" class="hp-header-cart js-open-cart"><i class="far fa-shopping-cart" aria-hidden="true"></i><span>Корзина</span><b id="cart-total" data-sum="<?= number_format((float)($_SESSION['cart.sum'] ?? 0), 0, '.', ' ') ?> ₽"><?= (int)($_SESSION['cart.qty'] ?? 0) ?></b></a>
  </nav>
</div></header>
