<?php
$curr = \ishop\App::$app->getProperty('currency');

$comparison = $_SESSION['comparison'] ?? [];
$comparisonCount = (int)($_SESSION['comparison_count'] ?? 0);
$userId = (int)($_SESSION['user']['id'] ?? 0);
$cart = $_SESSION['cart'] ?? [];
$cat_id = (int)($cat_id ?? 0);

/*
|--------------------------------------------------------------------------
| Группировка сравнения по категориям
|--------------------------------------------------------------------------
| Сейчас comparison хранится так:
| $_SESSION['comparison'][product_id] = category_id
*/
$comparisonByCategory = [];
foreach ($comparison as $productId => $categoryId) {
    $productId = (int)$productId;
    $categoryId = (int)$categoryId;

    if ($productId > 0 && $categoryId > 0) {
        $comparisonByCategory[$categoryId][] = $productId;
    }
}

$activeCategoryIds = array_keys($comparisonByCategory);

/*
|--------------------------------------------------------------------------
| Какие товары показываем
|--------------------------------------------------------------------------
*/
if ($cat_id > 0) {
    $productIds = $comparisonByCategory[$cat_id] ?? [];
    $keys = $cat_id;
} else {
    $productIds = array_keys($comparison);
    $keys = $activeCategoryIds[0] ?? 0;
}

/*
|--------------------------------------------------------------------------
| Товары
|--------------------------------------------------------------------------
*/
$products = [];
if (!empty($productIds)) {
    $placeholders = \R::genSlots($productIds);
    $rows = \R::getAll("SELECT * FROM product WHERE id IN ($placeholders)", $productIds);

    foreach ($rows as $row) {
        $products[(int)$row['id']] = $row;
    }
}

$visibleProducts = [];
foreach ($productIds as $pid) {
    $pid = (int)$pid;
    if (!empty($products[$pid])) {
        $visibleProducts[$pid] = $products[$pid];
    }
}

/*
|--------------------------------------------------------------------------
| Атрибуты сравнения
|--------------------------------------------------------------------------
*/
$attr_names = [];
$attr_ids = [];
if ($keys > 0) {
    $attr_names = \R::getAll("
        SELECT attribute.id, attribute.attribute_name
        FROM attribute
        INNER JOIN attribute_comparison ON attribute_comparison.attribute_id = attribute.id
        WHERE attribute_comparison.category_id = ?
        ORDER BY attribute.attribute_position ASC
    ", [$keys]);

    $attr_ids = array_map('intval', array_column($attr_names, 'id'));
}

/*
|--------------------------------------------------------------------------
| Значения атрибутов товаров
|--------------------------------------------------------------------------
*/
$productAttributes = [];
if (!empty($visibleProducts) && !empty($attr_ids)) {
    $productIdsForAttrs = array_keys($visibleProducts);

    $attrSlots = \R::genSlots($attr_ids);
    $prodSlots = \R::genSlots($productIdsForAttrs);

    $attrRows = \R::getAll("
        SELECT product_id, attribute_id, attribute_text
        FROM product_attribute
        WHERE attribute_id IN ($attrSlots)
          AND product_id IN ($prodSlots)
    ", array_merge($attr_ids, $productIdsForAttrs));

    foreach ($attrRows as $row) {
        $productAttributes[(int)$row['product_id']][(int)$row['attribute_id']] = $row['attribute_text'];
    }
}

/*
|--------------------------------------------------------------------------
| Количество с учетом модификаций
|--------------------------------------------------------------------------
*/
$quantities = [];
foreach ($visibleProducts as $pid => $product) {
    $mods = \R::getAll("SELECT quantity FROM modification WHERE product_id = ?", [$pid]);
    $sumMods = 0;

    if ($mods) {
        foreach ($mods as $mod) {
            $sumMods += (int)$mod['quantity'];
        }
    }

    $quantities[$pid] = $sumMods + (int)$product['quantity'];
}

/*
|--------------------------------------------------------------------------
| Названия категорий
|--------------------------------------------------------------------------
*/
$categoryNames = [];
if (!empty($activeCategoryIds)) {
    $catSlots = \R::genSlots($activeCategoryIds);
    $catRows = \R::getAll("SELECT id, name FROM category WHERE id IN ($catSlots)", $activeCategoryIds);

    foreach ($catRows as $catRow) {
        $categoryNames[(int)$catRow['id']] = $catRow['name'];
    }
}
?>

<div class="prdt comparison-page">
    <div class="container">

        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i><span class="visually-hidden">Главная</span></a>
                </li>
                <li class="breadcrumb-item active">Сравнение товаров</li>
            </ol>
        </nav>

        <section class="comparison-page__head">
            <h1 class="comparison-page__title">Сравнение товаров</h1>
        </section>

        <?php if (!empty($visibleProducts)): ?>

            <div class="comparison-toolbar">
                <div class="comparison-toolbar__tabs">
                    <a href="/comparison"
                       class="comparison-chip <?= $cat_id === 0 ? 'is-active' : '' ?>">
                        Все (<?= $comparisonCount ?>)
                    </a>

                    <?php foreach ($comparisonByCategory as $categoryId => $ids): ?>
                        <?php if (!empty($categoryNames[$categoryId])): ?>
                            <a href="/comparison?cat_id=<?= (int)$categoryId ?>"
                               class="comparison-chip <?= $cat_id === (int)$categoryId ? 'is-active' : '' ?>">
                                <?= h($categoryNames[$categoryId]) ?> (<?= count($ids) ?>)
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="comparison-toolbar__actions">
                    <a href="/comparison/deletevse" class="btn btn-outline-danger comparison-clear-btn">
                        Очистить сравнение
                    </a>
                </div>
            </div>

            <div class="comparison-card comparison-content">
                <div class="table-responsive comparison-table-wrap">
                    <table class="comparison-table">
                        <tr>
                            <td class="comparison-table__labels">
                                <table class="comparison-inner comparison-inner--labels">
                                    <tr>
                                        <td class="compar-img"></td>
                                    </tr>
                                    <tr>
                                        <td class="compar-name">Наименование товара</td>
                                    </tr>
                                    <tr>
                                        <td class="compar-name">Наличие</td>
                                    </tr>
                                    <tr>
                                        <td class="compar-name">Цена</td>
                                    </tr>
                                    <tr>
                                        <td class="compar-name"></td>
                                    </tr>

                                    <?php foreach ($attr_names as $attr): ?>
                                        <tr>
                                            <td class="compar-attr"><?= h($attr['attribute_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </td>

                            <?php foreach ($visibleProducts as $product): ?>
                                <?php
                                $pid = (int)$product['id'];
                                $categoryId = (int)$product['category_id'];
                                $qty = (int)($quantities[$pid] ?? 0);

                                $prod_priceopt = [];
                                if ($userId > 0) {
                                    $prod_priceopt = \R::getRow(
                                        'SELECT company.tip, company_typeprice.znachenie
                                         FROM company
                                         INNER JOIN company_typeprice ON company.id = company_typeprice.company_id
                                         WHERE company.user_id = ? AND company_typeprice.category_id = ?',
                                        [$userId, $categoryId]
                                    );
                                }

                                $hasOpt = !empty($prod_priceopt) && (int)($prod_priceopt['tip'] ?? 0) === 2;
                                ?>
                                <td class="comparison-table__product close-compartd-<?= $pid ?>">
                                    <table class="comparison-inner">
                                        <tr>
                                            <td class="compar-img">
                                                <button
                                                    class="comparison-remove comparison-remove-btn"
                                                    data-id="<?= $pid ?>"
                                                    data-categoryid="<?= $categoryId ?>"
                                                    type="button"
                                                    aria-label="Удалить из сравнения">
                                                    <i class="fas fa-times"></i>
                                                </button>

                                                <a href="product/<?= h($product['alias']) ?>" title="<?= h($product['name']) ?>">
                                                    <img src="images/product/mini/<?= h($product['img']) ?>" alt="<?= h($product['name']) ?>">
                                                </a>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="compar-name compar-name--product">
                                                <a href="product/<?= h($product['alias']) ?>" title="<?= h($product['name']) ?>">
                                                    <?= h($product['name']) ?>
                                                </a>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="compar-name">
                                                <?php if ($qty > 0): ?>
                                                    <span class="comparison-stock comparison-stock--in">
                                                        В наличии: <?= $qty ?> шт.
                                                    </span>
                                                <?php else: ?>
                                                    <span class="comparison-stock comparison-stock--out">
                                                        Нет в наличии
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="compar-name fw-bold comparison-price">
                                                <?= $curr['symbol_left']; ?>
                                                <?= (float)$product['price'] * (float)$curr['value']; ?>
                                                <?= $curr['symbol_right']; ?>

                                                <?php if ($hasOpt): ?>
                                                    <div class="comparison-price__opt">
                                                        Опт:
                                                        <?= $curr['symbol_left']; ?>
                                                        <?php if (($prod_priceopt['znachenie'] ?? '') === ''): ?>
                                                            <?= (float)$product['opt_price'] * (float)$curr['value']; ?>
                                                        <?php else: ?>
                                                            <?php
                                                            $price_nds = round($product['price'] - ($product['price'] / 1.2), 0) * 6 * $curr['value'];
                                                            $price_opt = $price_nds - (($price_nds / 100) * (float)$prod_priceopt['znachenie']);
                                                            echo round($price_opt / 6) * 6;
                                                            ?>
                                                        <?php endif; ?>
                                                        <?= $curr['symbol_right']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="compar-name">
                                                <?php if (!empty($cart[$pid])): ?>
                                                    <a data-id="<?= $pid; ?>"
                                                       class="btn btn-danger pc-buy add-to-cart-link korzina-<?= $pid; ?> clear-korzina"
                                                       style="display:none;"
                                                       href="cart/add?id=<?= $pid; ?>"
                                                       data-max="<?= $qty ?>"
                                                       data-toggle="modal"
                                                       data-target="#exampleModalLive">
                                                        <i class="fas fa-cart-plus fs-base"></i> Купить
                                                    </a>
                                                    <button type="button"
															class="btn btn-success pc-in-cart vkorzine-<?= $pid ?> clear-vkorzine js-open-cart"
															data-bs-toggle="modal"
															data-bs-target="#exampleModalLive">
														В корзине
													</button>
                                                <?php else: ?>
                                                    <a data-id="<?= $pid; ?>"
                                                       class="btn btn-danger pc-buy add-to-cart-link korzina-<?= $pid; ?> clear-korzina"
                                                       href="cart/add?id=<?= $pid; ?>"
                                                       data-max="<?= $qty ?>"
                                                       data-toggle="modal"
                                                       data-target="#exampleModalLive">
                                                        <i class="fas fa-cart-plus fs-base"></i> Купить
                                                    </a>
                                                    <button type="button"
															class="btn btn-success pc-in-cart vkorzine-<?= $pid ?> clear-vkorzine js-open-cart"
															data-bs-toggle="modal"
															data-bs-target="#exampleModalLive"
															style="display:none;">
														В корзине
													</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <?php foreach ($attr_names as $attr): ?>
                                            <?php
                                            $attrId = (int)$attr['id'];
                                            $attrText = $productAttributes[$pid][$attrId] ?? '-';
                                            ?>
                                            <tr>
                                                <td class="compar-attr"><?= !empty($attrText) ? h($attrText) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="comparison-empty comparison-empty-main">
                Товары для сравнения не добавлены. Чтобы сравнить товары, нажмите на карточке товара значок
                <i class="far fa-chart-bar"></i>.
            </div>
        <?php endif; ?>

        <div class="no-compar-sess-block" style="display:none;">
            Товары для сравнения не добавлены. Чтобы сравнить товары, нажмите на карточке товара значок
            <i class="far fa-chart-bar"></i>.
        </div>

    </div>
</div>