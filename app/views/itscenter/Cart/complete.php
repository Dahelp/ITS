<?php $esc=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); ?>
<div class="container py-5">
  <h1>Заказ №<?= $esc($result['number'] ?? $result['order_id']) ?> принят</h1>
  <p>Менеджер свяжется с вами для согласования заказа в рабочее время ПН–ПТ с 09:00 до 17:00.</p>
  <p><?= !empty($result['sent']) ? 'Подтверждение отправлено на' : 'Заказ сохранён. Не удалось отправить подтверждение на' ?> <?= $esc($result['email']) ?>.</p>
  <?php if (!empty($result['guest'])): ?>
    <section class="bg-light rounded-3 p-4 my-4" aria-labelledby="account-title">
    <?php if (!empty($result['new'])): ?>
      <h2 id="account-title">Ваш заказ сохранён в личном кабинете</h2>
      <p>Чтобы просматривать заказы и быстрее оформлять следующие покупки, задайте пароль.</p>
      <p><?= !empty($result['setup_sent']) ? 'Ссылку отправили на' : 'Запросите ссылку для установки пароля на' ?> <?= $esc($result['email']) ?>. Ссылка действует 1 час.</p>
      <form method="post" action="<?= PATH ?>/user/resend-setup">
        <input type="hidden" name="csrf" value="<?= $esc($_SESSION['setup_resend_token'] ?? '') ?>">
        <button class="btn btn-primary" type="submit">Отправить ссылку повторно</button>
      </form>
      <a class="d-inline-block mt-3" href="<?= PATH ?>/user/recover">Восстановить доступ</a>
    <?php else: ?>
      <h2 id="account-title">Ваш заказ сохранён</h2>
      <p>Для доступа к заказам войдите в личный кабинет. Не помните пароль? Восстановите доступ по email.</p>
      <a class="btn btn-primary" href="<?= PATH ?>/user/login">Войти в кабинет</a>
      <a class="btn btn-outline-secondary" href="<?= PATH ?>/user/recover">Восстановить доступ</a>
    <?php endif; ?>
    </section>
  <?php else: ?>
    <p><a class="btn btn-primary" href="<?= PATH ?>/user/orders">Мои заказы</a></p>
  <?php endif; ?>
  <a href="<?= PATH ?>">Продолжить покупки</a>
</div>
