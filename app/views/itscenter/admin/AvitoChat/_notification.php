<?php
$avitoNotifyAllowed = false;
try {
    $avitoNotifyAllowed = \app\services\AvitoMessenger::allowed((int)($_SESSION['user']['id'] ?? 0));
} catch (\Throwable $e) { /* Optional widget must not break administrative pages. */ }
if ($avitoNotifyAllowed): ?>
<li class="nav-item" id="avito-chat-notification" data-endpoint="<?= htmlspecialchars(ADMIN.'/avito-chat/api?operation=unread', ENT_QUOTES, 'UTF-8') ?>">
    <a id="avito-chat-notification-link" class="nav-link" href="<?= ADMIN ?>/avito-chat" title="Чат Авито">
        <span aria-hidden="true">✉</span> Авито <span id="avito-chat-notification-count" class="badge badge-danger" hidden></span>
    </a>
<a id="avito-chat-notification-banner" href="<?= ADMIN ?>/avito-chat?unread=1" role="status" hidden></a>
<style>
#avito-chat-notification-banner{position:fixed;bottom:24px;right:24px;z-index:1060;max-width:calc(100vw - 48px);padding:16px 22px;border-radius:12px;background:#d83b15;color:#fff;font-weight:600;box-shadow:0 5px 22px #0004;border:2px solid #fff;text-decoration:none}
#avito-chat-notification-banner[hidden]{display:none}
#avito-chat-notification-link.avito-has-unread{color:#c32f0b;font-weight:700}
</style>
<script src="<?= PATH ?>/js/avito-chat-notifications.js?v=20261017-3" defer></script>
</li>
<?php endif; ?>
