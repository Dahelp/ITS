<?php $checkout = !empty($_SESSION['checkout_login']) && $_SESSION['checkout_login']['time'] > time()-3600; ?>
<div class="container py-5">
  <h1>Вход</h1>
  <?php if ($checkout): ?><p>После входа вы вернётесь к оформлению. Корзина и заполненные данные сохранены. Прикреплённые файлы потребуется выбрать повторно.</p><?php endif; ?>
  <form method="post" action="<?= PATH ?>/user/login" class="col-md-6">
    <input type="hidden" name="loginok" value="<?= md5(date('Y-m-d')) ?>">
    <input type="hidden" name="login_csrf" value="<?= htmlspecialchars($_SESSION['login_csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <label for="account-email" class="form-label">Электронная почта</label>
    <input id="account-email" type="email" name="email" autocomplete="username" class="form-control mb-3" required>
    <label for="account-password" class="form-label">Пароль</label>
    <input id="account-password" type="password" name="password" autocomplete="current-password" class="form-control mb-3" required>
    <button type="submit" class="btn btn-primary">Войти</button>
    <a class="ms-3" href="<?= PATH ?>/user/recover">Не помню пароль</a>
  </form>
  <?php if ($checkout): ?><p class="mt-3"><a href="<?= PATH ?>/cart/return-checkout">Продолжить как гость</a></p><?php endif; ?>
</div>
