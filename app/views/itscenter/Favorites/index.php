<section class="container py-4" id="favorites-page">
    <h1>Избранное</h1>
    <p>Сохраняйте понравившиеся товары, чтобы вернуться к ним позже.</p>
    <p id="favorites-guest-note" hidden>Гостевое избранное хранится в этом браузере. Сохраните его в аккаунт, чтобы открыть на другом устройстве.</p>
    <button type="button" class="btn btn-primary mb-3" id="favorites-save" hidden>Сохранить в аккаунт</button>
    <p id="favorites-status" role="status" aria-live="polite">Загружаем избранное…</p>
    <div class="row g-3" id="favorites-items"></div>
    <noscript>Для гостевого избранного включите JavaScript. <a href="<?= PATH ?>/user/wishlist">Избранное в аккаунте</a></noscript>
</section>
