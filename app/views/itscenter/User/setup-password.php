<div class="container py-5">
  <h1>Задать пароль</h1>
  <p>Создайте пароль для входа в личный кабинет с вашими заказами.</p>
  <form method="post" action="<?= PATH ?>/user/setup-password" class="col-md-6">
    <input type="hidden" name="hash" value="<?= htmlspecialchars((string)($_GET['key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['setup_password_csrf'], ENT_QUOTES, 'UTF-8') ?>">
    <label for="setup-password" class="form-label">Новый пароль</label>
    <input id="setup-password" type="password" name="password" autocomplete="new-password" class="form-control mb-3" minlength="6" maxlength="72" required>
    <button type="submit" class="btn btn-primary">Задать пароль</button>
  </form>
  <a class="d-inline-block mt-3" href="<?= PATH ?>/user/recover">Запросить новую ссылку</a>
</div>
