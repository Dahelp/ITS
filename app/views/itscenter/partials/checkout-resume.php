<?php $checkoutResumeUser = (int)($_SESSION['user']['id'] ?? 0); ?>
<aside id="checkout-site-resume" class="container alert alert-info my-3" aria-label="Незавершённое оформление" hidden
       data-draft-owner="<?= $checkoutResumeUser ? 'user-'.$checkoutResumeUser : 'guest' ?>"
       data-draft-generation="<?= htmlspecialchars((string)($_SESSION['checkout_draft_generation'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
       data-cart-qty="<?= (int)($_SESSION['cart.qty'] ?? 0) ?>">
    <p id="checkout-site-resume-text" role="status"></p>
    <a href="<?= PATH ?>/cart/view" class="btn btn-primary">Продолжить оформление</a>
    <button type="button" id="checkout-site-resume-dismiss" class="btn btn-outline-secondary">Позже</button>
</aside>
<script src="/js/checkout-progress.js?v=<?= (int)filemtime(WWW.'/js/checkout-progress.js') ?>" defer></script>
