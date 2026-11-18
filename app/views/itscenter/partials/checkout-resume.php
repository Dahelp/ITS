<?php $checkoutResumeUser = (int)($_SESSION['user']['id'] ?? 0); ?>
<link rel="stylesheet" href="/css/checkout-account.css?v=<?= (int)filemtime(WWW.'/css/checkout-account.css') ?>">
<div class="container checkout-resume-shell">
<aside id="checkout-site-resume" class="checkout-reminder" aria-label="Незавершённое оформление" hidden
       data-draft-owner="<?= $checkoutResumeUser ? 'user-'.$checkoutResumeUser : 'guest' ?>"
       data-draft-generation="<?= htmlspecialchars((string)($_SESSION['checkout_draft_generation'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
       data-cart-qty="<?= (int)($_SESSION['cart.qty'] ?? 0) ?>">
    <p id="checkout-site-resume-text" role="status"></p>
    <div class="checkout-reminder__actions">
    <a href="<?= PATH ?>/cart/view" class="btn btn-primary">Продолжить оформление</a>
    <button type="button" id="checkout-site-resume-dismiss" class="btn btn-outline-secondary">Позже</button>
    </div>
</aside>
</div>
<script src="/js/checkout-progress.js?v=<?= (int)filemtime(WWW.'/js/checkout-progress.js') ?>" defer></script>
