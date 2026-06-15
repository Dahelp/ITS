<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$cart = $_SESSION['cart'] ?? [];
$curL = (string)($_SESSION['cart.currency']['symbol_left'] ?? '');
$curR = (string)($_SESSION['cart.currency']['symbol_right'] ?? '');

$fmtMoney = static function(float $v) use ($curL, $curR): string {
    $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    return htmlspecialchars($curL . $s . $curR, ENT_QUOTES, 'UTF-8');
};

if (empty($cart) || !is_array($cart)) {
    echo '<div class="text-muted">Корзина пуста</div>';
    return;
}
?>

<div class="step2-items">
  <?php foreach ($cart as $rowKey => $item): ?>
    <?php
    $id   = (string)$rowKey;
    $pid  = (int)($item['product_id'] ?? (int)explode('-', (string)$id)[0]);

    $name  = (string)($item['name'] ?? '');
    $alias = (string)($item['alias'] ?? '');
    $img   = (string)($item['img'] ?? '');

    $isSet  = !empty($item['set']);
    $setNum = (int)($item['set'] ?? 0);
    $qty    = max(0, (int)($item['qty'] ?? 0));

    // "витринные" признаки (должны быть сохранены в cart_table)
    $isNew = !empty($item['is_new']);
    $isHit = !empty($item['is_hit']);

    // флаг промокода на позиции (должен проставляться в promocartItem)
    $promoApplied = !empty($item['promo_applied']);
    $basePrice    = (float)($item['base_price'] ?? 0); // цена ДО промо

    // ---- current price ----
    $currentPrice = (float)($item['price'] ?? 0);

    // ---- old price for display ----
    // 1) если промо применено: old = base_price (для зачёркивания)
    // 2) иначе: old = old_price (если сохранено в сессии из cart_table)
    $oldPrice = 0.0;
    $eps = 0.01;

    if ($promoApplied && $basePrice > 0 && $basePrice > $currentPrice + $eps) {
        $oldPrice = $basePrice;
    } else {
        $oldFromSession = (float)($item['old_price'] ?? 0);
        if ($oldFromSession > $currentPrice + $eps) {
            $oldPrice = $oldFromSession;
        }
    }

    $hasDiscount = ($oldPrice > $currentPrice + $eps);

    // Badge "Скидка" показываем только если есть скидка и это НЕ промокод
    $showSaleBadge = ($hasDiscount && !$promoApplied);

    $href   = $alias !== '' ? (PATH . '/product/' . rawurlencode($alias)) : '#';
    $imgSrc = $img !== '' ? (PATH . '/images/product/mini/' . rawurlencode($img)) : '';
  ?>

    <div class="step2-item">
      <div class="step2-item__img">
        <?php if ($imgSrc !== ''): ?>
          <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="">
          </a>
        <?php endif; ?>
      </div>

      <div class="step2-item__info">
        <div class="step2-item__title">
          <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
          </a>
        </div>

        <?php if ($isSet): ?>
          <div class="text-muted small">Комплект № <?= (int)$setNum ?></div>
        <?php endif; ?>

        <div class="step2-item__meta">
            <span class="text-muted small">Кол-во: <b><?= (int)$qty ?></b></span>

            <?php if ($showSaleBadge): ?>
                <span class="c-badge badge-sale ms-2">Скидка</span>
            <?php endif; ?>

            <?php if ($isNew): ?>
                <span class="c-badge badge-new ms-2">Новинка</span>
            <?php endif; ?>

            <?php if ($isHit): ?>
                <span class="c-badge badge-hit ms-2">Хит</span>
            <?php endif; ?>

            <?php if ($promoApplied): ?>
                <span class="c-badge badge-promo ms-2">Промокод</span>
            <?php endif; ?>
        </div>
      </div>

      <div class="step2-item__sum">
        <?php if ($hasDiscount): ?>
        <div class="step2-old-sum">
            <?= $fmtMoney($oldPrice * $qty) ?>
        </div>
        <?php endif; ?>

        <div class="fw-semibold"><?= $fmtMoney($currentPrice * $qty) ?></div>

        <div class="text-muted small">
            <?= $fmtMoney($currentPrice) ?> / шт
        </div>
      </div>
    </div>

  <?php endforeach; ?>
</div>