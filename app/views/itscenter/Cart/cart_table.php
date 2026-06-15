<?php
$cart = $_SESSION['cart'] ?? [];

$cartQty = (int)($_SESSION['cart.qty'] ?? 0);
$cartSum = (float)($_SESSION['cart.sum'] ?? 0);

$curL    = (string)($_SESSION['cart.currency']['symbol_left'] ?? '');
$curR    = (string)($_SESSION['cart.currency']['symbol_right'] ?? '');
$curValue = (float)($_SESSION['cart.currency']['value'] ?? 1);

$weight  = (float)($_SESSION['cart.weight'] ?? 0);
$volume  = (float)($_SESSION['cart.volume'] ?? 0);

// страховка: если корзина реально пустая — принудительно 0
if (empty($cart) || !is_array($cart)) {
    $cart = [];
    $cartQty = 0;
    $cartSum = 0.0;
    $weight  = 0.0;
    $volume  = 0.0;
}

$fmtMoney = static function(float $v) use ($curL, $curR): string {
    $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    return htmlspecialchars($curL . $s . $curR, ENT_QUOTES, 'UTF-8');
};

// promo state (для шага 2)
$promoState = $_SESSION['promo_state'] ?? null;
$promoOk  = (int)($promoState['ok'] ?? 0);
$promoMsg = (string)($promoState['msg'] ?? '');
unset($_SESSION['promo_state']);

// -------------------------
// PRELOAD: ids, wishlists, products, actions
// -------------------------
$userId = (int)($_SESSION['user']['id'] ?? 0);

// соберём product_id из корзины
$cartProductIds = [];
foreach ($cart as $k => $it) {
    $pid = (int)($it['id'] ?? $it['product_id'] ?? $k);
    if ($pid > 0) $cartProductIds[$pid] = $pid;
}
$cartProductIds = array_values($cartProductIds);

// избранное одним запросом
$wishlistedIds = [];
if ($userId > 0 && !empty($cartProductIds)) {
    $placeholders = implode(',', array_fill(0, count($cartProductIds), '?'));
    $params = array_merge([$userId], $cartProductIds);

    $rows = \R::getAll(
        "SELECT product_id
         FROM product_wishlists
         WHERE user_id = ?
           AND product_id IN ($placeholders)",
        $params
    );

    foreach ($rows as $r) {
        $wishlistedIds[(int)$r['product_id']] = true;
    }
}

// товары из БД
$productsMap = [];
if (!empty($cartProductIds)) {
    $productsMap = \R::loadAll('product', $cartProductIds);
}

// акции из actions (самая свежая)
$actionsMap = [];
if (!empty($cartProductIds)) {
    $dateNow = date("Y-m-d H:i:s");
    $placeholders = implode(',', array_fill(0, count($cartProductIds), '?'));
    $params = array_merge([$dateNow], $cartProductIds);

    $rows = \R::getAll("
        SELECT a.*
        FROM actions a
        WHERE a.hide = 'show'
          AND a.date_end > ?
          AND a.product_id IN ($placeholders)
        ORDER BY a.product_id ASC, a.id DESC
    ", $params);

    foreach ($rows as $r) {
        $pid = (int)$r['product_id'];
        if (!isset($actionsMap[$pid])) {
            $actionsMap[$pid] = $r;
        }
    }
}

// -------------------------
// PRECOMPUTE subtotal/discount (единственный источник для правого блока)
// subtotalServer = сумма "как без скидок" (old/base/rrs), но НЕ ниже current
// discountServer = subtotalServer - cartSum (включая промокод)
// -------------------------
$sumCurServer = 0.0;
$sumOldServer = 0.0;

foreach ($cart as $rowKey => $item) {

    $isSet = !empty($item['set']);
    $qty   = max(0, (int)($item['qty'] ?? 0));
    if ($qty <= 0) continue;

    $productId = (int)($item['id'] ?? $item['product_id'] ?? $rowKey);
    $p = $productsMap[$productId] ?? null;

    // current price (после промо)
    if ($isSet) {
    $currentPrice = (float)($item['price'] ?? 0);
    } else {
        $currentPrice = (float)($item['price'] ?? 0);
    }

    $sumCurServer += $currentPrice * $qty;

    // old price logic
    $actionRow = $actionsMap[$productId] ?? null;

    // ВАЖНО: если price/price_rrs в БД в базовой валюте — умножение на curValue верно.
    // Если они уже в валюте витрины — убери "* $curValue" здесь.
    $basePrice = $p ? (float)($p->price ?? 0) : 0.0;
$rrs       = $p ? (float)($p->price_rrs ?? 0) : 0.0;

    $oldPrice = 0.0;
    if ($actionRow) {
        if ($basePrice > $currentPrice) $oldPrice = $basePrice;
    } elseif ($rrs > 0 && $rrs > $currentPrice) {
        $oldPrice = $rrs;
    } elseif ($basePrice > $currentPrice) {
        $oldPrice = $basePrice;
    }

    // subtotal считаем всегда не ниже текущей
    $sumOldServer += max($oldPrice, $currentPrice) * $qty;
}

$subtotalServer = round($sumOldServer, 2);
$totalServer    = round($cartSum, 2);

$discountServer = round($subtotalServer - $totalServer, 2);
if ($discountServer < 0.01) $discountServer = 0.0;

// -------------------------
// HIDDEN MARKERS (РОВНО 1 РАЗ)
// -------------------------
?>
<!-- скрытые значения для JS пересчёта -->
<span class="cart-qty d-none"><?= $cartQty ?></span>
<span class="cart-sum d-none"><?= htmlspecialchars($curL . $cartSum . $curR, ENT_QUOTES, 'UTF-8') ?></span>
<span class="cart-subtotal-val d-none"><?= $subtotalServer ?></span>
<span class="cart-discount-val d-none"><?= $discountServer ?></span>
<span class="cart-weight d-none"><?= $weight ?></span>
<span class="cart-volume d-none"><?= $volume ?></span>

<span class="promo-code d-none"><?= htmlspecialchars((string)($_SESSION['promocart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
<span class="promo-code-val d-none"><?= htmlspecialchars((string)($_SESSION['promocart'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
<span class="promo-applied d-none"><?= !empty($_SESSION['promocart']) ? '1' : '0' ?></span>

<span class="promo-state d-none"
      data-ok="<?= $promoOk ?>"
      data-msg="<?= htmlspecialchars($promoMsg, ENT_QUOTES, 'UTF-8') ?>"></span>

<div class="cart-list">
  <?php if (empty($cart)): ?>

    <div class="cart-empty text-center py-5">
      <div class="cart-no-title">В корзине нет товаров</div>
      <div class="cart-no-info">Найдите то, что вам нужно в каталоге или при помощи поиска</div>
      <div class="mt-3">
        <a class="btn btn-outline-primary" href="/">Вернуться к покупкам</a>
      </div>
    </div>

  <?php else: ?>

    <?php
    // NOTE: суммирование тут больше не нужно — мы всё посчитали выше.
    foreach ($cart as $rowKey => $item):

        $id = (string)$rowKey;
        $isSet = !empty($item['set']);

        $productId = (int)($item['id'] ?? $item['product_id'] ?? $id);

        // продукт из БД (может быть null)
        $p = $productsMap[$productId] ?? null;

        // избранное
        $isFav = ($userId > 0 && !empty($wishlistedIds[$productId]));
        $wishBtnClass  = $isFav ? 'btn-wishlist2' : 'btn-wishlist';
        $wishIconClass = $isFav ? 'fas fa-heart' : 'far fa-heart';

        // qty/min/max
        $min = max(1, (int)($item['min'] ?? 1));
        $max = (int)($item['max'] ?? PHP_INT_MAX);
        if ($max < 1) $max = PHP_INT_MAX;

        $qty = max(0, (int)($item['qty'] ?? 0));

        $alias = (string)($item['alias'] ?? '');
        $name  = (string)($item['name'] ?? '');
        $img   = (string)($item['img'] ?? '');

        // текущая цена
        if ($isSet) {
          $price = (float)($item['price'] ?? 0);
        } else {
          $price = (float)($item['price'] ?? 0);
        }
        $currentPrice = (float)$price;

        // акция из actions
        $actionRow = $actionsMap[$productId] ?? null;

        // базовая и rrs из product
        $basePrice = $p ? (float)($p->price ?? 0) : 0.0;
$rrs       = $p ? (float)($p->price_rrs ?? 0) : 0.0;

        // старая цена
        $oldPrice = 0.0;
        if ($actionRow) {
            if ($basePrice > $currentPrice) $oldPrice = $basePrice;
        } elseif ($rrs > 0 && $rrs > $currentPrice) {
            $oldPrice = $rrs;
        } elseif ($basePrice > $currentPrice) {
            $oldPrice = $basePrice;
        }

        $hasDiscount = ($oldPrice > 0 && $oldPrice > $currentPrice);

        // badges
        $isNew = $p ? (int)($p->new_product ?? 0) === 1 : false;
        $isHit = $p ? (int)($p->hit ?? 0) === 1 : false;

        // ссылка/картинка
        $href = '#';
        if ($alias !== '') $href = 'product/' . rawurlencode($alias);

        $imgSrc = 'images/product/mini/' . rawurlencode($img);

        $setNum = (int)($item['set'] ?? 0);

        // наличие: приоритет БД, fallback на max
        $stockQty = $p ? (int)($p->quantity ?? 0) : (($max === PHP_INT_MAX) ? 0 : (int)$max);
        $plusDisabled = ($stockQty > 0 && $qty >= $stockQty);

        // сохраняем “витринные” признаки, чтобы Step2 мог показать то же самое без БД
        $_SESSION['cart'][$rowKey]['old_price']   = (float)$oldPrice;
        $_SESSION['cart'][$rowKey]['has_discount']= (int)($hasDiscount ? 1 : 0);
        $_SESSION['cart'][$rowKey]['is_new']      = (int)($isNew ? 1 : 0);
        $_SESSION['cart'][$rowKey]['is_hit']      = (int)($isHit ? 1 : 0);

    ?>
      <div class="cart-item"
           data-product-id="<?= (int)$productId ?>"
           data-qty="<?= (int)$qty ?>"
           data-cur="<?= htmlspecialchars((string)$currentPrice, ENT_QUOTES, 'UTF-8') ?>"
           data-old="<?= htmlspecialchars((string)$oldPrice, ENT_QUOTES, 'UTF-8') ?>">

        <!-- 1) фото -->
        <div class="cart-item__photo">
          <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
          </a>
        </div>

        <!-- 2) инфо -->
        <div class="cart-item__info">
          <div class="cart-item__title">
            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
            </a>
          </div>

          <?php
          $promoApplied = !empty($item['promo_applied']);
          $completeDiscountActive = !empty($item['complete_discount_active']);
          ?>

          <div class="cart-item__badges">
            <?php if ($promoApplied): ?>
              <span class="c-badge badge-promo">Промокод</span>
            <?php elseif ($completeDiscountActive || $hasDiscount): ?>
              <span class="c-badge badge-sale">Скидка</span>
            <?php endif; ?>

            <?php if ($isNew): ?><span class="c-badge badge-new">Новинка</span><?php endif; ?>
            <?php if ($isHit): ?><span class="c-badge badge-hit">Хит продаж</span><?php endif; ?>
          </div>

          <?php if ($isSet): ?>
            <div class="cart-item__sub">Комплект № <?= $setNum ?></div>
          <?php endif; ?>

          <!-- действия -->
          <div class="cart-item__actions">

            <button id="wishlist-<?= $productId ?>"
                    class="<?= $wishBtnClass ?> cart-action cart-action--fav"
                    type="button"
                    data-id="<?= $productId ?>"
                    data-userid="<?= $userId ?>"
                    aria-label="Избранное">
              <i class="<?= $wishIconClass ?>"></i>
            </button>

            <?php $delClass = !$isSet ? 'del-item-cart' : 'del-item-complete-cart'; ?>
            <button type="button"
              class="cart-action cart-action--remove <?= $delClass ?>"
              data-id="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>"
              <?php if ($isSet): ?>data-complete-id="<?= $setNum ?>"<?php endif; ?>
              aria-label="Удалить">
              <i class="far fa-trash-alt"></i>
            </button>

          </div>
        </div>

        <!-- 3) цена -->
        <div class="cart-item__price">
          <div class="cart-price__now <?= $hasDiscount ? 'is-discount' : '' ?>">
            <?= $fmtMoney($currentPrice) ?>
          </div>
          <?php if ($hasDiscount): ?>
            <div class="cart-price__old"><?= $fmtMoney($oldPrice) ?></div>
          <?php endif; ?>
        </div>

        <!-- 4) кол-во -->
        <div class="cart-item__qty">
          <div class="qty-controls">
            <button type="button"
              class="qty-btn <?= !$isSet ? 'my-minus-cart' : 'my-minus-complete-cart' ?>"
              data-id="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>"
              <?php if ($isSet): ?>data-complete-id="<?= $setNum ?>"<?php endif; ?>
              aria-label="Уменьшить">–</button>

            <span class="qty-item"><?= $qty ?></span>

            <button type="button"
              class="qty-btn <?= !$isSet ? 'my-plus-cart' : 'my-plus-complete-cart' ?><?= $plusDisabled ? ' is-disabled' : '' ?>"
              data-id="<?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8') ?>"
              <?php if ($isSet): ?>data-complete-id="<?= $setNum ?>"<?php endif; ?>
              aria-label="Увеличить"
              <?= $plusDisabled ? 'disabled' : '' ?>>+</button>
          </div>

          <div class="cart-qty__stock">
            <?php if ($stockQty > 0): ?>
              В наличии: <b><?= $stockQty ?></b>
              <?php if ($qty >= $stockQty): ?>
                <span class="stock-limit"> • больше добавить нельзя</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="stock-limit">Нет в наличии</span>
            <?php endif; ?>
          </div>
        </div>

      </div>
    <?php endforeach; ?>

  <?php endif; ?>
</div>