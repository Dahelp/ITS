<div class="container checkout-account-page">
 <section class="checkout-account-card" aria-labelledby="setup-title">
  <span class="checkout-account-eyebrow">Личный кабинет</span>
  <h1 id="setup-title">Задать пароль</h1>
  <p>Создайте пароль для входа в личный кабинет с вашими заказами.</p>
  <form method="post" action="<?= PATH ?>/user/setup-password" class="checkout-account-form">
    <input type="hidden" name="hash" value="<?= htmlspecialchars((string)($_GET['key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['setup_password_csrf'], ENT_QUOTES, 'UTF-8') ?>">
    <label for="setup-password" class="form-label">Новый пароль</label>
    <div data-password-field class="checkout-password">
      <div class="checkout-password__input"><input id="setup-password" type="password" name="password" autocomplete="new-password" class="form-control" minlength="6" maxlength="72" required><button type="button" class="checkout-password__toggle" data-password-toggle aria-controls="setup-password" aria-pressed="false">Показать</button></div>
      <button type="button" class="checkout-password__generate" data-password-generate>Сгенерировать пароль</button>
      <p class="checkout-password__status" data-password-status role="status"></p>
    </div>
    <button type="submit" class="btn btn-primary">Задать пароль</button>
  </form>
  <a class="d-inline-block mt-3" href="<?= PATH ?>/user/recover">Запросить новую ссылку</a>
 </section>
</div>
<script src="/js/account-password.js?v=<?= (int)filemtime(WWW.'/js/account-password.js') ?>" defer></script>
