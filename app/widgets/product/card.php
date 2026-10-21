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

        <?php if (!$isCompleteCard): ?>
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
        <?php endif; ?>
    </div>

    <a class="pc-media" href="<?= $cardUrl ?>">
        <img itemprop="image"
             loading="lazy"
             src="<?= $cardImageUrl ?>"
             width="250"
             height="250"
             alt="<?= htmlspecialchars($seoName, ENT_QUOTES, 'UTF-8') ?>"
             title="<?= htmlspecialchars($seoName, ENT_QUOTES, 'UTF-8') ?>">
    </a>

    <div class="pc-body">

        <div class="pc-meta">
            <?= $categoryName ?>
        </div>

        <div class="pc-title">
            <a href="<?= $cardUrl ?>">
                <span itemprop="name"><?= htmlspecialchars($seoName, ENT_QUOTES, 'UTF-8') ?></span>
                <link itemprop="url" href="<?= $cardUrl ?>">
                <meta itemprop="priceCurrency" content="RUB">
            </a>
        </div>

        <?php if ($isCompleteCard): ?>
        <div class="pc-row pc-row--top"><span class="pc-sku">В комплекте: <?= (int)$pieces ?> шт.</span></div>
        <?php else: ?>
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

        <?php endif; ?>

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

            <?php if ($isCompleteCard): ?>
                <div class="pc-stock <?= $quantity > 0 ? 'pc-stock--ok' : 'pc-stock--no' ?>"><?= htmlspecialchars($stockText, ENT_QUOTES, 'UTF-8') ?></div>
            <?php elseif ($quantity > 0): ?>
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
            <?php if ($isCompleteCard): ?>
                <a class="btn btn-danger pc-buy" href="<?= $cardUrl ?>">Подробнее</a>
            <?php elseif ($quantity > 0): ?>

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
                <button class="btn btn-stock-notify pc-buy" type="button"
                        data-bs-toggle="modal" data-bs-target="#ModalAvailability"
                        data-product-id="<?= $pid ?>"
                        data-product-name="<?= htmlspecialchars($seoName, ENT_QUOTES, 'UTF-8') ?>"
                        data-product-url="<?= PATH ?>/product/<?= $alias ?>">Сообщить о поступлении</button>
            <?php endif; ?>
        </div>

    </div>
</div>
