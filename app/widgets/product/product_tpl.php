<?php
$pid = (int)($product['id'] ?? $product->id ?? 0);
$categoryId = (int)($product['category_id'] ?? $product->category_id ?? 0);

static $categoryCache = [];
static $productInseoCache = [];
static $actionCache = [];
static $wishlistCache = [];
static $reviewCache = [];
static $modificationCache = [];
static $companyCache = [];
static $companyTypeCache = [];

$userId = (int)($_SESSION['user']['id'] ?? 0);
$inCart = ($pid > 0) ? !empty($_SESSION['cart'][$pid]) : false;
$inCompare = ($pid > 0) ? !empty($_SESSION['comparison'][$pid]) : false;

// CATEGORY
if (!empty($context['categoryById'][$categoryId])) {
    $catData = $context['categoryById'][$categoryId];
    $categoryName = (string)($catData['name'] ?? '');
} else {
    if (!array_key_exists($categoryId, $categoryCache)) {
        $categoryCache[$categoryId] = $categoryId > 0
            ? \R::getRow("SELECT id, name FROM category WHERE id = ? LIMIT 1", [$categoryId])
            : [];
    }
    $catData = $categoryCache[$categoryId];
    $categoryName = (string)($catData['name'] ?? '');
}

// INSEO / SEO NAME
$seoName = '';
if (!empty($context['seoNameByProductId'][$pid])) {
    $seoName = (string)$context['seoNameByProductId'][$pid];
} else {
    $seoTpl = '';

    if (!empty($context['productInseoByCategoryId'][$categoryId])) {
        $seoTpl = (string)($context['productInseoByCategoryId'][$categoryId]['name'] ?? '');
    } else {
        if (!array_key_exists($categoryId, $productInseoCache)) {
            $productInseoCache[$categoryId] = $categoryId > 0
                ? \R::getRow(
                    "SELECT name
                     FROM plagins_inseo
                     WHERE tip = 'product' AND category_id = ? AND hide = 'show'
                     LIMIT 1",
                    [$categoryId]
                )
                : [];
        }
        $seoTpl = (string)($productInseoCache[$categoryId]['name'] ?? '');
    }

    $seoName = $seoTpl !== ''
        ? (string)\ishop\App::seoreplace($seoTpl, $pid)
        : (string)($product['name'] ?? $product->name ?? '');
}

// CURRENCY
$curr = $curr ?? ['symbol_left' => '', 'symbol_right' => '', 'value' => 1];
$curr['value'] = (float)($curr['value'] ?? 1);

// BASE PRICES
$base = (float)($product['price'] ?? $product->price ?? 0);
$rrs = (float)($product['price_rrs'] ?? $product->price_rrs ?? 0);
$final = $base;
$cross = null;

// ACTION
$action = null;
if (!empty($context['actionByProductId'][$pid])) {
    $action = $context['actionByProductId'][$pid];
} else {
    if (!array_key_exists($pid, $actionCache)) {
        $actionCache[$pid] = \R::getRow(
            "SELECT product_id, type_id, znachenie
             FROM actions
             WHERE product_id = ?
               AND hide = 'show'
               AND date_end > ?
             LIMIT 1",
            [$pid, date('Y-m-d H:i:s')]
        );
    }
    $action = $actionCache[$pid];
}

if (!empty($action) && (int)($action['product_id'] ?? 0) === $pid) {
    $typeId = (string)($action['type_id'] ?? '');
    $zn = (float)($action['znachenie'] ?? 0);

    if ($typeId === '1') {
        $final = round($base * (1 - ($zn / 100)), -1);
    } elseif ($typeId === '2') {
        $final = $base - $zn;
    }

    $final = max(0.0, (float)$final);
    $cross = $base;
} elseif ($rrs > 0 && $rrs > $base) {
    $final = $base;
    $cross = $rrs;
}

$hasDiscountBadge = false;
if (!empty($action) && (int)($action['product_id'] ?? 0) === $pid) {
    $hasDiscountBadge = true;
} elseif ($rrs > 0 && $rrs > $base) {
    $hasDiscountBadge = true;
}

// WISHLIST
$wishlisted = false;
if ($userId > 0 && $pid > 0) {
    if (isset($context['wishlistedByProductId'][$pid])) {
        $wishlisted = true;
    } else {
        $wishKey = $userId . ':' . $pid;
        if (!array_key_exists($wishKey, $wishlistCache)) {
            $wishlistCache[$wishKey] = (int)\R::count(
                'product_wishlists',
                'product_id = ? AND user_id = ?',
                [$pid, $userId]
            ) > 0;
        }
        $wishlisted = $wishlistCache[$wishKey];
    }
}

// REVIEW
$rwcount = 0;
$srew = 0.0;

if (!empty($context['reviewByProductId'][$pid])) {
    $rwcount = (int)($context['reviewByProductId'][$pid]['count'] ?? 0);
    $srew = (float)($context['reviewByProductId'][$pid]['rating'] ?? 0);
} else {
    if (!array_key_exists($pid, $reviewCache)) {
        $rwcountLocal = (int)\R::count('review_product', "product_id = ?", [$pid]);
        $reviewProd = \R::getAll(
            "SELECT SUM(review.point) as bal
             FROM review_product
             JOIN review ON review.id = review_product.review_id
             WHERE review_product.product_id = ?",
            [$pid]
        );
        $bal = (float)($reviewProd[0]['bal'] ?? 0);

        $reviewCache[$pid] = [
            'count' => $rwcountLocal,
            'rating' => $rwcountLocal > 0 ? ($bal / $rwcountLocal) : 0.0,
        ];
    }

    $rwcount = (int)$reviewCache[$pid]['count'];
    $srew = (float)$reviewCache[$pid]['rating'];
}

$srewText = number_format($srew, 1, '.', '');

// MODIFICATIONS
$modification = [];
if (isset($context['modificationsByProductId'][$pid])) {
    $modification = $context['modificationsByProductId'][$pid];
} else {
    if (!array_key_exists($pid, $modificationCache)) {
        $modificationCache[$pid] = \R::getAll(
            "SELECT quantity, price
             FROM modification
             WHERE product_id = ?",
            [$pid]
        );
    }
    $modification = $modificationCache[$pid];
}

$productQuantity = (int)($product['quantity'] ?? $product->quantity ?? 0);
$productReserve = (int)($product['reserve'] ?? $product->reserve ?? 0);

$quantity = $productQuantity - $productReserve;
$maxP = $final;

if (!empty($modification)) {
    $qtyMods = 0;
    $prices = [$base];

    foreach ($modification as $item) {
        $qtyMods += (int)($item['quantity'] ?? 0);
        $prices[] = (float)($item['price'] ?? 0);
    }

    $quantity = $qtyMods + $productQuantity - $productReserve;
    $maxP = max($prices);
}

$itog_qty = max(0, (int)$quantity);

// COMPANY
$companyTip = $context['companyTip'] ?? null;
$ucompanyZn = $context['companyTypepriceByCategoryId'][$categoryId]['znachenie'] ?? null;

if ($companyTip === null && $userId > 0 && empty($modification)) {
    if (!array_key_exists($userId, $companyCache)) {
        $companyCache[$userId] = \R::getRow(
            "SELECT tip, id
             FROM company
             WHERE user_id = ?
             LIMIT 1",
            [$userId]
        );
    }

    $companyTip = $companyCache[$userId]['tip'] ?? null;
    $companyId = (int)($companyCache[$userId]['id'] ?? 0);

    if ($companyId > 0) {
        $companyTypeKey = $companyId . ':' . $categoryId;

        if (!array_key_exists($companyTypeKey, $companyTypeCache)) {
            $companyTypeCache[$companyTypeKey] = \R::getRow(
                "SELECT znachenie
                 FROM company_typeprice
                 WHERE company_id = ? AND category_id = ?
                 LIMIT 1",
                [$companyId, $categoryId]
            );
        }

        $ucompanyZn = $companyTypeCache[$companyTypeKey]['znachenie'] ?? null;
    }
}

$alias = htmlspecialchars((string)($product["alias"] ?? $product->alias ?? ''), ENT_QUOTES, 'UTF-8');
$img = htmlspecialchars((string)($product['img'] ?? $product->img ?? ''), ENT_QUOTES, 'UTF-8');
$sku = htmlspecialchars((string)($product["sku"] ?? $product->sku ?? ''), ENT_QUOTES, 'UTF-8');
$stockStatusId = (int)($product['stock_status_id'] ?? $product->stock_status_id ?? 0);
$productHit = !empty($product["hit"] ?? $product->hit ?? null);
$productNew = !empty($product["new_product"] ?? $product->new_product ?? null);
$categoryName = htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8');
?>
<div class="card product-card card-static">

    <div class="pc-head">
        <div class="pc-badges">
            <?php if ($productHit): ?>
                <span class="pc-badge pc-badge--hit">Хит</span>
            <?php endif; ?>

            <?php if ($productNew): ?>
                <span class="pc-badge pc-badge--new">Новинка</span>
            <?php endif; ?>

            <?php if ($hasDiscountBadge): ?>
                <span class="pc-badge pc-badge--sale">Скидка</span>
            <?php endif; ?>
        </div>

        <div class="pc-actions">
            <?php if ($userId > 0): ?>
                <button
                    id="wishlist-<?= (int)$pid ?>"
                    class="pc-iconbtn js-wishlist <?= $wishlisted ? 'is-active' : '' ?>"
                    type="button"
                    data-id="<?= (int)$pid ?>"
                    data-userid="<?= (int)$userId ?>"
                    data-bs-toggle="tooltip"
                    data-bs-placement="left"
                    title="<?= $wishlisted ? 'В избранном' : 'Добавить в избранное' ?>"
                    aria-label="Избранное">
                    <i class="<?= $wishlisted ? 'fas fa-heart' : 'far fa-heart' ?>"></i>
                </button>
            <?php endif; ?>

            <button
                id="comparison-<?= (int)$pid ?>"
                class="pc-iconbtn js-compare <?= $inCompare ? 'is-active' : '' ?>"
                type="button"
                data-id="<?= (int)$pid ?>"
                data-categoryid="<?= (int)$categoryId ?>"
                data-bs-toggle="tooltip"
                data-bs-placement="left"
                title="<?= $inCompare ? 'В сравнении' : 'Добавить в сравнение' ?>"
                aria-label="Сравнение">
                <i class="far fa-chart-bar"></i>
            </button>
        </div>
    </div>

    <a class="pc-media" href="product/<?= $alias ?>">
        <img itemprop="image"
             loading="lazy"
             src="images/product/mini/<?= $img ?>"
             alt="<?= htmlspecialchars($seoName, ENT_QUOTES, 'UTF-8') ?>"
             title="<?= htmlspecialchars($seoName, ENT_QUOTES, 'UTF-8') ?>">
    </a>

    <div class="pc-body">

        <div class="pc-meta">
            <?= $categoryName ?>
        </div>

        <div class="pc-title">
            <a href="product/<?= $alias ?>">
                <span itemprop="name"><?= htmlspecialchars($seoName, ENT_QUOTES, 'UTF-8') ?></span>
                <span itemprop="description"></span>
                <link itemprop="url" href="product/<?= $alias ?>">
                <meta itemprop="priceCurrency" content="RUB">
            </a>
        </div>

        <div class="pc-row pc-row--top">
            <div class="pc-rating">
                <i class="fas fa-star"></i>
                <span class="pc-rating__score"><?= $srewText ?></span>
                <span class="pc-rating__count">· <?= $rwcount ?> отзывов</span>
            </div>

            <div class="pc-sku">
                Код: <?= $sku ?>
            </div>
        </div>

        <div class="pc-row pc-row--price">
            <div class="pc-price">
                <?php if (!empty($modification)): ?>
                    <div class="pc-price__main">
                        <span class="item_price">
                            <?= $curr['symbol_left']; ?> <?= ($maxP * $curr['value']); ?> <?= $curr['symbol_right']; ?>
                        </span>
                        <meta itemprop="price" content="<?= ($maxP * $curr['value']); ?>">
                    </div>
                <?php else: ?>
                    <div class="pc-price__main">
                        <span class="item_price">
                            <?= $curr['symbol_left']; ?> <?= ($final * $curr['value']); ?> <?= $curr['symbol_right']; ?>
                        </span>
                        <meta itemprop="price" content="<?= ($final * $curr['value']); ?>">
                    </div>

                    <?php if ($cross !== null && ($companyTip != 2)): ?>
                        <div class="pc-price__old">
                            <?= $curr['symbol_left']; ?> <?= ($cross * $curr['value']); ?> <?= $curr['symbol_right']; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if ($quantity > 0): ?>
                <div class="pc-stock pc-stock--ok">В наличии: <?= (int)$quantity ?> шт.</div>
            <?php else: ?>
                <div class="pc-stock pc-stock--no">
                    <?php
                    if ($stockStatusId === 3) {
                        echo 'Ожидается';
                    } elseif ($stockStatusId === 2) {
                        echo 'Под заказ';
                    } else {
                        echo 'Нет в наличии';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="pc-cta">
            <?php if ($quantity > 0): ?>

                <input class="form-control detail-quantity korzina-<?= $pid ?> clear-korzina"
                       name="quantity"
                       type="hidden"
                       value="1"
                       min="1"
                       max="<?= $itog_qty ?>"
                       data-max="<?= $itog_qty ?>"
                       data-min="1"
                       style="caret-color:transparent;">

                <?php if (!$inCart): ?>
                    <a data-id="<?= $pid ?>"
                       class="btn btn-danger pc-buy <?= !empty($modification) ? 'add-to-cart-mod' : 'add-to-cart-link' ?> korzina-<?= $pid ?> clear-korzina"
                       href="cart/add?id=<?= $pid ?>"
                       data-max="<?= $quantity ?>"
                       data-bs-toggle="modal"
                       data-bs-target="#exampleModalLive"
                       onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;">
                        Купить
                    </a>

                    <button type="button"
                            class="btn btn-success pc-in-cart vkorzine-<?= $pid ?> clear-vkorzine js-open-cart"
                            data-bs-toggle="modal"
                            data-bs-target="#exampleModalLive"
                            style="display:none;">
                        В корзине
                    </button>
                <?php else: ?>
                    <a data-id="<?= $pid ?>"
                       class="btn btn-danger pc-buy <?= !empty($modification) ? 'add-to-cart-mod' : 'add-to-cart-link' ?> korzina-<?= $pid ?> clear-korzina"
                       href="cart/add?id=<?= $pid ?>"
                       data-max="<?= $quantity ?>"
                       data-bs-toggle="modal"
                       data-bs-target="#exampleModalLive"
                       onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;"
                       style="display:none;">
                        Купить
                    </a>

                    <button type="button"
                            class="btn btn-success pc-in-cart vkorzine-<?= $pid ?> clear-vkorzine js-open-cart"
                            data-bs-toggle="modal"
                            data-bs-target="#exampleModalLive">
                        В корзине
                    </button>
                <?php endif; ?>

                <input type="hidden" class="modification" value="<?= $pid ?>" name="modification">
                <link itemprop="availability" href="http://schema.org/InStock">

            <?php else: ?>
                <link itemprop="availability" href="http://schema.org/OutOfStock">
                <button class="btn btn-outline-secondary pc-buy" type="button" disabled>Нет в наличии</button>
            <?php endif; ?>
        </div>

    </div>
</div>