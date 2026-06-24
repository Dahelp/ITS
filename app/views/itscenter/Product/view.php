<?php use app\helpers\Img; ?>

<?php
$AUTH       = $_SESSION['user'] ?? null;
$userId     = (is_array($AUTH) && isset($AUTH['id'])) ? (int)$AUTH['id'] : null;
$userName   = (is_array($AUTH) && isset($AUTH['name'])) ? (string)$AUTH['name'] : '';
$cart       = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
$comparison = (isset($_SESSION['comparison']) && is_array($_SESSION['comparison'])) ? $_SESSION['comparison'] : [];

$curr = \ishop\App::$app->getProperty('currency');

$hasAction = (isset($action) && is_object($action) && (int)$action->product_id === (int)$product->id);
$actionType = $hasAction ? (string)$action->type_id : null;
$actionVal  = $hasAction ? (float)$action->znachenie : 0.0;

$basePrice = (float)$product->price;
$price = $basePrice;

if ($hasAction) {
    if ($actionType === "1") {
        $price = $basePrice * (1 - ($actionVal / 100));
        $price = round($price, -1);
    } elseif ($actionType === "2") {
        $price = $basePrice - $actionVal;
    }
    $price = max(0, (float)$price);
}

$gallery = $gallery ?? [];
$related = $related ?? [];
$similar = $similar ?? [];
$services = $services ?? [];
$mods = $mods ?? [];
$review = $review ?? [];
$attribute_group = $attribute_group ?? [];
$attributesByGroup = $attributesByGroup ?? [];
$attributeValueMap = $attributeValueMap ?? [];
$cat_prod = $cat_prod ?? (object)['alias' => '', 'name' => '', 'id' => 0];
$vendor = $vendor ?? (object)['name' => ''];
$quickFilters = $quickFilters ?? [];
$technics = $technics ?? [];
$complete = $complete ?? [];
$completeItemsById = $completeItemsById ?? [];
$cross = $cross ?? [];
$crossAnalog = $crossAnalog ?? [];
$crossOem = $crossOem ?? [];
$reviewGalleryByReviewId = $reviewGalleryByReviewId ?? [];
$reviewStat = $reviewStat ?? ['bal' => 0.0, 'cnt' => 0, 'rating' => 0.0];
$administr = $administr ?? null;
$productWishlisted = !empty($productWishlisted);
$relatedWidgetContext = $relatedWidgetContext ?? [];
$similarWidgetContext = $similarWidgetContext ?? [];
$servicesWidgetContext = $servicesWidgetContext ?? [];

$sum_mods = 0;
foreach ($mods as $m) {
    $sum_mods += (int)$m->quantity;
}
$reserve = property_exists($product, 'reserve') ? (int)$product->reserve : 0;
$itog_qty = max(0, (int)$product->quantity - $reserve);
$quantity = (int)$product->quantity;

$comp_priceopt = $userId
    ? (\R::getRow('SELECT tip FROM company WHERE company.user_id = ?', [$userId]) ?: [])
    : [];
$prod_priceopt = $userId
    ? (\R::getRow(
        'SELECT company.tip, company_typeprice.znachenie
         FROM company, company_typeprice
         WHERE company.id = company_typeprice.company_id
           AND company.user_id = ?
           AND company_typeprice.category_id = ?',
        [$userId, $cat_prod->id]
    ) ?: [])
    : [];

$srew = (float)($reviewStat['rating'] ?? 0);
$rwcount = (int)($reviewStat['cnt'] ?? 0);
?>

<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <nav class="pt-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <?=$breadcrumbs;?>
            </ol>
        </nav>
    </div>
</div>
<!--end-breadcrumbs-->

<!--start-single-->
<div class="single contact">
    <div class="container">
        <section>
          <?php if (!empty($product->note)) { ?>
            <div class="alert alert-success product-note">
                <?=$product->note;?>
            </div>
          <?php } ?>

          <section class="product-hero-its">
            <div class="product-hero-grid">

              <aside class="product-col product-col--gallery">
                <div class="product-sticky-wrap">
                  <div class="product-card product-gallery-card">
                    <section class="slider">
                      <?php if (!empty($gallery)): ?>
                        <div id="slider" class="flexslider product-flexslider-main">
                          <ul class="slides">
                            <li>
                              <?= Img::productPicture('base', $product->img, $product->name, [
                                'eager' => true,
                                'classPicture' => 'main-photo'
                              ]) ?>
                            </li>
                            <?php foreach ($gallery as $item): ?>
                              <li data-slide-id="<?= (int)$item->id ?>">
                                <?= Img::productPicture('gallery', $item->img, $product->name . ' — фото') ?>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        </div>

                        <div id="carousel" class="flexslider product-flexslider-thumbs">
                          <ul class="slides">
                            <li>
                              <?= Img::productPicture('mini', $product->img, $product->name . ' — превью') ?>
                            </li>
                            <?php foreach ($gallery as $item): ?>
                              <li data-slide-id="<?= (int)$item->id ?>">
                                <?= Img::productPicture('gallery', $item->img, $product->name . ' — фото') ?>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        </div>
                      <?php else: ?>
                        <div id="slider" class="flexslider product-flexslider-main">
                          <ul class="slides">
                            <li>
                              <?= Img::productPicture('base', $product->img, $product->name, [
                                'eager' => true,
                                'classPicture' => 'main-photo'
                              ]) ?>
                            </li>
                          </ul>
                        </div>
                      <?php endif; ?>
                    </section>
                  </div>
                </div>
              </aside>

              <main class="product-col product-col--main">
                <div class="product-sticky-wrap">
                  <div class="product-card product-main-card">

                    <?php if ($userId && $administr && (string)$administr->groups === "1"): ?>
                      <div class="edit_prod product-edit-link">
                        <a target="_blank" href="<?= ADMIN ?>/product/edit?id=<?=$product->id?>">
                          <i class="far fa-edit"></i> Редактировать
                        </a>
                      </div>
                    <?php endif; ?>

                    <div class="product-main-topline">
                      <a class="product-meta" href="category/<?=$cat_prod->alias?>" title="<?=$cat_prod->name?>">
                        <?=$cat_prod->name?>
                      </a>
                      <?php if (!empty($vendor->name)): ?>
                        <div class="product-brand-chip">Бренд: <span><?=$vendor->name?></span></div>
                      <?php endif; ?>
                    </div>

                    <h1 class="product-title">
                      <?php
                      $seoH1 = trim((string)($product->seo_h1 ?? ''));

                      if ($seoH1 !== '') {
                          echo h(\ishop\App::seoreplace($seoH1, $product->id));
                      } elseif (isset($inseo) && is_object($inseo) && !empty($inseo->name)) {
                          echo h(\ishop\App::seoreplace($inseo->name, $product->id));
                      } else {
                          echo h($product->name);
                      }
                      ?>
                    </h1>

                    <div class="product-rating-row">
                      <span class="product-review">
                        <span class="rating">
                          <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($srew < $i): ?>
                              <span class="fa fa-stack"><i class="far fa-star fa-stack-2x"></i></span>
                            <?php else: ?>
                              <span class="fa fa-stack"><i class="fas fa-star fa-stack-2x"></i><i class="fa fa-star-o fa-stack-2x"></i></span>
                            <?php endif; ?>
                          <?php endfor; ?>
                        </span>
                        <span class="rating-count"><?=$rwcount?> отзывов</span>
                      </span>

                      <?php if (!empty($product->sku)): ?>
                        <div class="product-sku-inline">Артикул: <strong><?=$product->sku?></strong></div>
                      <?php endif; ?>
                    </div>

                    <?php if (!empty($product->note)) { ?>
                      <div class="alert alert-success product-note mb-4">
                        <?=$product->note;?>
                      </div>
                    <?php } ?>

                    <?php if ($quickFilters): ?>
                      <div class="product-quick-props">
                        <div class="product-block-title">Краткие характеристики</div>
                        <ul class="product-props-list">
                          <?php foreach ($quickFilters as $filter): ?>
                            <li>
                              <span class="prop-name"><?= $filter['title'] ?></span>
                              <span class="prop-value">
                                <?php if (!empty($filter['url_params'])) { ?>
                                  <a href="<?=h(\app\services\filters\FilterUrlHelper::buildCategoryFilterPath((string)$cat_prod->alias, mb_strtolower((string)$filter['alias'])))?>" title="<?=h($filter['value'])?>">
                                    <?=h($filter['value'])?>
                                  </a>
                                <?php } else { ?>
                                  <?=h($filter['value'])?>
                                <?php } ?>
                              </span>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                    <?php endif; ?>

                  </div>

                  <?php if (!empty($cat_prod->alias) || !empty($cat_prod->parent_id)): ?>
                    <div class="product-card product-help-card">
                      <div class="product-block-title">Полезные материалы</div>
                      <div class="Ps_content product-help-links">
                        <ul>
                          <?php if ($cat_prod->alias == "atv") { ?>
                            <li><a href="https://its-center.ru/articles/kak-uznat-razmer-shin-dlya-kvadrocikla" title="Как узнать размер шин для квадроцикла">Как узнать размер шин для квадроцикла?</a></li>
                          <?php } ?>

                          <?php if ($cat_prod->alias == "shiny-dlya-vilochnyh-pogruzchikov") { ?>
                            <li><a href="https://its-center.ru/articles/polnoe-rukovodstvo-po-vyboru-i-ekspluatacii-shin-dlya-vilochnyh-pogruzchikov" title="Полное руководство по выбору и эксплуатации шин для вилочных погрузчиков">Полное руководство по выбору и эксплуатации шин для вилочных погрузчиков</a></li>
                          <?php } ?>

                          <?php if ($cat_prod->alias == "shiny-dlya-minipogruzchikov") { ?>
                            <li><a href="https://its-center.ru/articles/rukovodstvo-po-vyboru-i-uhodu-za-shinami-dlya-mini-pogruzchikov" title="Руководство по выбору и уходу за шинами для мини-погрузчиков">Руководство по выбору и уходу за шинами для мини-погрузчиков</a></li>
                          <?php } ?>

                          <?php if ($cat_prod->alias == "shiny-dlya-frontalnyh-pogruzchikov") { ?>
                            <li><a href="https://its-center.ru/articles/rukovodstvo-po-shinam-dlya-frontalnyh-pogruzchikov-vybor-ekspluataciya-i-sovety" title="Руководство по шинам для фронтальных погрузчиков: выбор, эксплуатация и советы">Руководство по шинам для фронтальных погрузчиков: выбор, эксплуатация и советы</a></li>
                          <?php } ?>

                          <?php if ($cat_prod->alias == "shiny-dlya-ekskavatorov-pogruzchikov") { ?>
                            <li><a href="https://its-center.ru/articles/rukovodstvo-po-vyboru-i-ekspluatacii-shin-dlya-ekskavatorov-pogruzchikov" title="Руководство по выбору и эксплуатации шин для экскаваторов-погрузчиков">Руководство по выбору и эксплуатации шин для экскаваторов-погрузчиков</a></li>
                          <?php } ?>

                          <?php if ($cat_prod->alias == "shiny-dlya-kolesnyh-ekskavatorov") { ?>
                            <li><a href="https://its-center.ru/articles/rukovodstvo-po-vyboru-i-ekspluatacii-shin-dlya-kolesnyh-ekskavatorov" title="Руководство по выбору и эксплуатации шин для колесных экскаваторов">Руководство по выбору и эксплуатации шин для колесных экскаваторов</a></li>
                          <?php } ?>

                          <?php if ($cat_prod->alias == "shiny-dlya-gruntovyh-katkov") { ?>
                            <li><a href="https://its-center.ru/articles/rukovodstvo-po-shinam-dlya-gruntovyh-katkov" title="Руководство по шинам для грунтовых катков">Руководство по шинам для грунтовых катков</a></li>
                          <?php } ?>

                          <?php if ($cat_prod->parent_id == 1 || $cat_prod->parent_id == 2) { ?>
                            <li><a href="https://its-center.ru/articles/rekomendacii-po-ekspluatacii-shin-bezopasnost-i-dolgovechnost" title="Общие рекомендации по эксплуатации шин">Общие рекомендации по эксплуатации шин</a></li>
                          <?php } ?>
                        </ul>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </main>

              <aside class="product-col product-col--buy">
                <div class="product-sticky-wrap">
                  <div class="product-card product-buy-card">

                    <?php
                    if ($hasAction && $actionType === "2") {
                        $price = max(0, $basePrice - $actionVal);
                    }

                    $hasMods = !empty($mods);

                    $quantity = (int)($product->quantity ?? 0);
                    $reserve = (int)($product->reserve ?? 0);
                    $stockStatusId = (int)($product->stock_status_id ?? 0);
                    $itog_qty = max(0, $quantity - $reserve);
                    ?>

                    <?php if (!$hasMods): ?>
                        <div class="product-buy-top">
                            <div class="product-block-title">Цена</div>

                            <div class="item_price_2 product-price-box text-accent" id="base-price" data-base="<?= $price * $curr['value']; ?>">
                                <?php if ((isset($comp_priceopt["tip"]) ? (int)$comp_priceopt["tip"] : 0) != 2): ?>
                                    <?php if ($hasAction): ?>
                                        <div class="product-price-old">
                                            <?= $curr['symbol_left']; ?><?= ($basePrice * $curr['value']); ?><?= $curr['symbol_right']; ?>
                                        </div>
                                        <div class="product-price-current">
                                            <?= $curr['symbol_left']; ?><?= ($price * $curr['value']); ?><?= $curr['symbol_right']; ?>
                                        </div>

                                    <?php elseif ((($rrs = (float)$product->price_rrs) > 0) && $rrs > (float)$price): ?>
                                        <div class="product-price-current">
                                            <?= $curr['symbol_left']; ?><?= ($price * $curr['value']); ?><?= $curr['symbol_right']; ?>
                                        </div>
                                        <div class="product-price-old">
                                            <?= $curr['symbol_left']; ?><?= ($rrs * $curr['value']); ?><?= $curr['symbol_right']; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="product-price-current">
                                            <?= $curr['symbol_left']; ?><?= ($price * $curr['value']); ?><?= $curr['symbol_right']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="product-price-current">
                                        <?= $curr['symbol_left']; ?><?= $price * $curr['value']; ?><?= $curr['symbol_right']; ?>
                                    </div>
                                    <div class="product-price-opt">
                                        <span>Опт:</span>
                                        <strong>
                                            <?= $curr['symbol_left']; ?>
                                            <?php if (empty($prod_priceopt["znachenie"])): ?>
                                                <?= (float)$product->opt_price * $curr['value']; ?>
                                            <?php else: ?>
                                                <?php
                                                $price_nds = round($basePrice - ($basePrice / 1.2), 0) * 6 * $curr['value'];
                                                $price_opt = $price_nds - (($price_nds / 100) * (float)$prod_priceopt["znachenie"]);
                                                echo $opt = ceil($price_opt / 6) * 6;
                                                ?>
                                            <?php endif; ?>
                                            <?= $curr['symbol_right']; ?>
                                        </strong>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($quantity > 0): ?>
                                <div class="current_price_success product-price-date">Цена актуальна на: <?= h($product->data_edit_price ?? '') ?></div>
                            <?php else: ?>
                                <div class="current_price_warning product-price-date">Цену уточняйте у менеджера</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-stock-box">
                            <div class="product-block-title">Наличие</div>
                            <div class="vnalichie">
                                <?php
                                if ($itog_qty > 0) {
                                    if ($reserve > 0) {
                                        echo '<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> Свободное наличие: ' . $itog_qty . ' шт.</span> <span class="reserve-note">(В резерве: ' . $reserve . ' шт.)</span>';
                                    } else {
                                        echo '<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> В наличии: ' . $quantity . ' шт.</span>';
                                    }
                                } else {
                                    if ($stockStatusId === 2) {
                                        echo '<span class="nalich_no">Товар можно приобрести под заказ. Цена, наличие и срок доставки согласовываются с менеджером. Доставка 3-5 рабочих дней после поступления оплаты по счёту.</span>';
                                    } elseif ($stockStatusId === 3) {
                                        echo '<span class="nalich_postuplenie">Ожидается поступление. Уточняйте у менеджера.</span>';
                                    } elseif ($reserve > 0 && $quantity > 0) {
                                        echo '<span class="nalich_no"><i class="far fa-clock" aria-hidden="true"></i> Свободного остатка нет. Весь товар в резерве (' . $reserve . ' шт.).</span>';
                                    } else {
                                        echo '<span class="nalich_no"><i class="far fa-times-circle fa-tabls" aria-hidden="true"></i> Нет в наличии, о поступлении уточняйте у менеджера</span>';
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <div class="product-order-box">
                            <div class="product-block-title">Оформление заказа</div>
                            <div class="product-order-actions">
                                <?php if ($itog_qty > 0): ?>
                                    <?php if (isset($cart[$product->id])): ?>
                                        <input class="form-control detail-quantity me-2 korzina-<?= $product->id; ?> clear-korzina"
                                               style="display:none;caret-color:transparent;"
                                               name="quantity"
                                               type="number"
                                               value="1"
                                               min="1"
                                               max="<?= $itog_qty; ?>"
                                               data-max="<?= $itog_qty; ?>"
                                               data-min="1">

                                        <a data-id="<?= $product->id; ?>"
                                           class="btn btn-soft-primary me-2 add-to-cart-link korzina-<?= $product->id; ?> clear-korzina"
                                           style="display:none;"
                                           href="cart/add?id=<?= $product->id; ?>"
                                           data-max="<?= $itog_qty; ?>"
                                           data-bs-toggle="modal"
                                           data-bs-target="#exampleModalLive"
                                           onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;">
                                            <i class="fas fa-cart-plus"></i> В корзину
                                        </a>

                                        <button class="btn btn-warning one-click korzina-<?= $product->id; ?> clear-korzina"
                                                style="display:none;"
                                                type="submit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#Modalclick">
                                            Купить в 1 клик
                                        </button>

                                        <button href="cart/show"
                                                onclick="getCart(); return false;"
                                                class="btn btn-success vkorzine-<?= $product->id; ?> clear-vkorzine">
                                            В корзине
                                        </button>
                                    <?php else: ?>
                                        <input class="form-control detail-quantity me-2 korzina-<?= $product->id; ?> clear-korzina"
                                               name="quantity"
                                               type="number"
                                               value="1"
                                               min="1"
                                               max="<?= $itog_qty; ?>"
                                               data-max="<?= $itog_qty; ?>"
                                               data-min="1">

                                        <a data-id="<?= $product->id; ?>"
                                           class="btn btn-soft-primary me-2 add-to-cart-link korzina-<?= $product->id; ?> clear-korzina"
                                           href="cart/add?id=<?= $product->id; ?>"
                                           data-max="<?= $itog_qty; ?>"
                                           data-bs-toggle="modal"
                                           data-bs-target="#exampleModalLive"
                                           onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;">
                                            <i class="fas fa-cart-plus"></i> В корзину
                                        </a>

                                        <button class="btn btn-warning one-click korzina-<?= $product->id; ?> clear-korzina"
                                                type="submit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#Modalclick">
                                            Купить в 1 клик
                                        </button>

                                        <button href="cart/show"
                                                onclick="getCart(); return false;"
                                                class="btn btn-success vkorzine-<?= $product->id; ?> clear-vkorzine"
                                                style="display:none;">
                                            В корзине
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($stockStatusId === 2): ?>
                                        <button class="btn btn-warning" type="submit" data-bs-toggle="modal" data-bs-target="#ModalRequest">
                                            Оформить под заказ
                                        </button>
                                    <?php elseif ($stockStatusId === 3 || $stockStatusId === 0 || ($reserve > 0 && $quantity > 0)): ?>
                                        <button class="btn btn-success" type="submit" data-bs-toggle="modal" data-bs-target="#ModalAvailability">
                                            Сообщить о поступлении на email
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <?php
                        $modpriceList = [];
                        foreach ($mods as $mod) {
                            $modpriceList[] = (float)$mod->price;
                        }

                        $max_price = !empty($modpriceList)
                            ? max(array_merge([(float)$product->price], $modpriceList))
                            : (float)$product->price;

                        $quantity = (int)($product->quantity ?? 0) + (int)$sum_mods;
                        $reserve = (int)($product->reserve ?? 0);
                        $stockStatusId = (int)($product->stock_status_id ?? 0);
                        $itog_qty = max(0, $quantity - $reserve);
                        ?>

                        <div class="product-buy-top">
                            <div class="product-block-title">Цена</div>
                            <div class="item_price_2 product-price-box text-accent" id="base-price" data-base="<?= $max_price * $curr['value']; ?>">
                                <div class="product-price-current">
                                    <?= $max_price * $curr['value']; ?> <?= $curr['symbol_right']; ?>
                                </div>
                            </div>
                        </div>

                        <div class="product-stock-box">
                            <div class="product-block-title">Наличие</div>
                            <div class="vnalichie">
                                <?php
                                if ($itog_qty > 0) {
                                    if ($reserve > 0) {
                                        echo '<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> Свободное наличие: ' . $itog_qty . ' шт.</span> <span class="reserve-note">(В резерве: ' . $reserve . ' шт.)</span>';
                                    } else {
                                        echo '<span class="nalich_ok"><i class="fas fa-check" aria-hidden="true"></i> В наличии: ' . $quantity . ' шт.</span>';
                                    }
                                } else {
                                    if ($stockStatusId === 2) {
                                        echo '<span class="nalich_no">Товар можно приобрести под заказ. Цена, наличие и срок доставки согласовываются с менеджером. Доставка 3-5 рабочих дней после поступления оплаты по счёту.</span>';
                                    } elseif ($stockStatusId === 3) {
                                        echo '<span class="nalich_postuplenie">Ожидается поступление. Уточняйте у менеджера.</span>';
                                    } elseif ($reserve > 0 && $quantity > 0) {
                                        echo '<span class="nalich_no"><i class="far fa-clock" aria-hidden="true"></i> Свободного остатка нет. Весь товар в резерве (' . $reserve . ' шт.).</span>';
                                    } else {
                                        echo '<span class="nalich_no"><i class="far fa-times-circle fa-tabls" aria-hidden="true"></i> Нет в наличии, о поступлении уточняйте у менеджера</span>';
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <?php if ($itog_qty > 0): ?>
                            <div class="product-order-box">
                                <div class="product-block-title">Оформление заказа</div>
                                <div class="product-order-actions">
                                    <?php if (isset($cart[$product->id])): ?>
                                        <input class="form-control detail-quantity me-2 korzina-<?= $product->id; ?> clear-korzina"
                                               style="display:none;caret-color:transparent;"
                                               name="quantity"
                                               type="number"
                                               value="1"
                                               min="1"
                                               max="<?= $itog_qty; ?>"
                                               data-max="<?= $itog_qty; ?>"
                                               data-min="1">

                                        <a data-id="<?= $product->id; ?>"
                                           class="btn btn-soft-primary me-2 add-to-cart-mod korzina-<?= $product->id; ?> clear-korzina"
                                           style="display:none;"
                                           href="cart/add?id=<?= $product->id; ?>"
                                           data-max="<?= $itog_qty; ?>"
                                           data-bs-toggle="modal"
                                           data-bs-target="#exampleModalLive"
                                           onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;">
                                            <i class="fas fa-cart-plus"></i> В корзину
                                        </a>

                                        <button class="btn btn-warning one-click korzina-<?= $product->id; ?> clear-korzina"
                                                style="display:none;"
                                                type="submit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#Modalclick">
                                            Купить в 1 клик
                                        </button>

                                        <button href="cart/show"
                                                onclick="getCart(); return false;"
                                                class="btn btn-success vkorzine-<?= $product->id; ?> clear-vkorzine">
                                            В корзине
                                        </button>
                                    <?php else: ?>
                                        <?php $firstModId = !empty($mods) ? (int)$mods[array_key_first($mods)]->id : 0; ?>

                                        <input class="form-control detail-quantity me-2 korzina-<?= $product->id; ?> clear-korzina"
                                               name="quantity"
                                               type="number"
                                               value="1"
                                               min="1"
                                               max="<?= $itog_qty; ?>"
                                               data-max="<?= $itog_qty; ?>"
                                               data-min="1">

                                        <a data-id="<?= $product->id; ?>"
                                           data-modification="<?= $firstModId; ?>"
                                           class="btn btn-soft-primary me-2 add-to-cart-mod korzina-<?= $product->id; ?> clear-korzina"
                                           href="cart/add?id=<?= $product->id; ?>"
                                           data-max="<?= $itog_qty; ?>"
                                           data-bs-toggle="modal"
                                           data-bs-target="#exampleModalLive"
                                           onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;">
                                            <i class="fas fa-cart-plus"></i> В корзину
                                        </a>

                                        <button class="btn btn-warning one-click korzina-<?= $product->id; ?> clear-korzina"
                                                type="submit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#Modalclick">
                                            Купить в 1 клик
                                        </button>

                                        <button href="cart/show"
                                                onclick="getCart(); return false;"
                                                class="btn btn-success vkorzine-<?= $product->id; ?> clear-vkorzine"
                                                style="display:none;">
                                            В корзине
                                        </button>

                                        <input type="hidden" class="modification" value="<?= $firstModId; ?>" name="modification">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="product-order-box">
                                <div class="product-block-title">Оформление заказа</div>
                                <div class="product-order-actions">
                                    <?php if ($stockStatusId === 2): ?>
                                        <button class="btn btn-warning" type="submit" data-bs-toggle="modal" data-bs-target="#ModalRequest">
                                            Оформить под заказ
                                        </button>
                                    <?php elseif ($stockStatusId === 3 || $stockStatusId === 0 || ($reserve > 0 && $quantity > 0)): ?>
                                        <button class="btn btn-success" type="submit" data-bs-toggle="modal" data-bs-target="#ModalAvailability">
                                            Сообщить о поступлении на email
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="product-side-tools">
                      <button class="btn btn-primary product-kp-btn" id="btnpdf" type="button">
                        <i class="fas fa-print"></i> Коммерческое предложение
                      </button>

                      <div class="product-icon-actions">
                        <?php if ($userId): ?>
                          <button
                              id="wishlist-<?= (int)$product->id ?>"
                              class="pc-iconbtn js-wishlist <?= $productWishlisted ? 'is-active' : '' ?>"
                              type="button"
                              data-id="<?= (int)$product->id ?>"
                              data-userid="<?= (int)$userId ?>"
                              title="<?= $productWishlisted ? 'В избранном' : 'Добавить в избранное' ?>"
                              aria-label="Избранное">
                              <i class="<?= $productWishlisted ? 'fas fa-heart' : 'far fa-heart' ?>"></i>
                          </button>
                        <?php endif; ?>

                        <?php $inCompare = !empty($comparison[$product->id]); ?>
                        <button
                            id="comparison-<?= (int)$product->id ?>"
                            class="pc-iconbtn js-compare <?= $inCompare ? 'is-active' : '' ?>"
                            type="button"
                            data-id="<?= (int)$product->id ?>"
                            data-categoryid="<?= (int)$product->category_id ?>"
                            title="<?= $inCompare ? 'В сравнении' : 'Добавить в сравнение' ?>"
                            aria-label="Сравнение">
                            <i class="far fa-chart-bar"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </aside>

            </div>
          </section>

          <?php if ($complete): ?>
          <section class="complete-premium">
            <div class="complete-premium__head">
              <div>
                <h2>Купить комплект шин</h2>
                <p>Готовые комплекты с понятной ценой, быстрым оформлением и проверкой наличия по каждой позиции.</p>
              </div>
            </div>

            <div class="complete-premium__list">
              <?php foreach ($complete as $cpl):
                $prods = $completeItemsById[(int)$cpl["id"]] ?? [];

                $prod_id = '';
                $prod_qty = '';
                $prodid = '';

                $price_complete = 0;
                $discount_complete = 0;
                $itg_qty = 0;
                $vcomplecte = 0;

                foreach ($prods as $prod) {
                  $price_complete += ((float)$prod["price_complete"] * (int)$prod["qty"]);
                  $discount_complete += ((float)$prod["discount"] * (int)$prod["qty"]);
                  $prod_id .= $prod["product_id"] . ",";
                  $prod_qty .= $prod["qty"] . ",";

                  if ((int)$prod["quantity"] >= (int)$prod["qty"]) {
                    $quantity_ok = 1;
                    $prodid .= $prod["product_id"] . "-";
                  } elseif ((int)$prod["quantity"] > 0) {
                    $quantity_ok = 0;
                    $prodid .= $prod["product_id"] . "-";
                  } else {
                    $quantity_ok = 0;
                  }

                  $itg_qty += $quantity_ok;
                  $vcomplecte += (int)$prod["qty"];
                }

                $prod_id = rtrim($prod_id, ',');
                $prod_qty = rtrim($prod_qty, ',');
                $prodid = rtrim($prodid, '-');

                $isFullAvailable = ($itg_qty == count($prods));
                $isPartialAvailable = ($itg_qty > 0 && $itg_qty < count($prods));
                $isUnavailable = ($itg_qty == 0);

                $effective_discount_complete = ($isFullAvailable && $discount_complete > 0) ? $discount_complete : 0;
                $itog_price_complete = max(0, $price_complete - $effective_discount_complete);
                $discountLabel = ($effective_discount_complete > 0)
                    ? number_format($effective_discount_complete, 0, '.', ' ') . ' ' . $curr['symbol_right']
                    : '';

                $nm = htmlspecialchars($cpl['name'], ENT_QUOTES, 'UTF-8');
              ?>
                <article class="complete-premium__card">
                  <div class="complete-premium__main">

                    <div class="complete-premium__visual">
                      <div class="complete-premium__image">
                        <?= Img::picture(
                              ['fallback' => '/images/complete/mini/' . ltrim($cpl['img'], '/')],
                              $nm,
                              \ishop\App::$app->getProperty('mini_img_width') ?? 250,
                              \ishop\App::$app->getProperty('mini_img_height') ?? 250,
                              ['lazy' => true, 'classPicture' => 'complete-premium__img', 'attrs' => ['title' => $nm]]
                        ) ?>
                      </div>

                      <div class="complete-premium__meta">
                        <div class="complete-premium__badges">
                          <span class="complete-badge complete-badge--dark">Готовый комплект</span>
                          <span class="complete-badge complete-badge--light">В комплекте <?=$vcomplecte?> шт.</span>

                          <?php if ($isFullAvailable): ?>
                            <span class="complete-badge complete-badge--ok">Все позиции в наличии</span>
                          <?php elseif ($isPartialAvailable): ?>
                            <span class="complete-badge complete-badge--warn">Частично в наличии</span>
                          <?php else: ?>
                            <span class="complete-badge complete-badge--empty">Нет в наличии</span>
                          <?php endif; ?>
                        </div>

                        <h3><?=$cpl["name"]?></h3>

                        <?php if (!empty($cpl["description"])): ?>
                          <div class="complete-premium__desc"><?=$cpl["description"]?></div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="complete-premium__composition">
                      <button
                        class="complete-premium__toggle"
                        type="button"
                        aria-expanded="false"
                        aria-controls="complete-composition-<?=$cpl["id"]?>"
                      >
                        <span class="complete-premium__composition-title">Состав комплекта</span>
                        <span class="complete-premium__toggle-icon" aria-hidden="true">
                          <i class="fas fa-chevron-down"></i>
                        </span>
                      </button>

                      <div class="complete-premium__composition-drop" id="complete-composition-<?=$cpl["id"]?>" hidden>
                        <div class="complete-premium__composition-list">
                          <?php foreach ($prods as $prod): ?>
                            <div class="complete-comp-item">
                              <div class="complete-comp-item__name"><?=$prod["name"]?></div>
                              <div class="complete-comp-item__meta">
                                <span><?=$prod["qty"]?> шт.</span>
                                <span><?=number_format((float)$prod["price_complete"], 0, '.', ' ')?> <?=$curr['symbol_right'];?> / шт.</span>

                                <?php if ((int)$prod["quantity"] >= (int)$prod["qty"]): ?>
                                  <span class="is-ok">Достаточно</span>
                                <?php elseif ((int)$prod["quantity"] > 0): ?>
                                  <span class="is-warn">Частично</span>
                                <?php else: ?>
                                  <span class="is-empty">Нет</span>
                                <?php endif; ?>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>

                  </div>

                  <aside class="complete-premium__aside">
                    <div class="complete-summary">
                      <div class="complete-summary__title">Итого по комплекту</div>

                      <?php if ($effective_discount_complete > 0): ?>
                        <div class="complete-summary__old">
                          <?=number_format($price_complete, 0, '.', ' ')?> <?=$curr['symbol_right'];?>
                        </div>
                      <?php endif; ?>

                      <div class="complete-summary__price">
                        <?=number_format($itog_price_complete, 0, '.', ' ')?> <?=$curr['symbol_right'];?>
                      </div>

                      <?php if ($effective_discount_complete > 0): ?>
                        <div class="complete-summary__save">
                          Экономия: <?=$discountLabel;?>
                        </div>
                      <?php endif; ?>

                      <div class="complete-summary__status">
                        <?php if ($isFullAvailable): ?>
                          <span class="status-ok">Комплект доступен полностью</span>
                        <?php elseif ($isPartialAvailable): ?>
                          <span class="status-warn">Доступен не в полном составе</span>
                        <?php else: ?>
                          <span class="status-empty">Комплект недоступен</span>
                        <?php endif; ?>
                      </div>

                      <div class="complete-summary__actions">
                        <?php if ($isFullAvailable): ?>
                          <input class="form-control" style="display:none;" name="quantity" type="number" value="1" min="1" data-min="1">
                          <a
                            data-id="<?=$prodid;?>"
                            data-complete="1"
                            data-complete-id="<?=$cpl["id"];?>"
                            class="btn btn-danger add-to-cart-complete korzina-<?=$cpl["id"];?> clear-korzina"
                            href="cart/addcomplete?id=<?=$prodid;?>"
                            data-bs-toggle="modal"
                            data-bs-target="#exampleModalLive"
                            onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;"
                          >
                            <i class="fas fa-cart-plus"></i> Купить комплект
                          </a>
                        <?php elseif ($isPartialAvailable): ?>
                          <input class="form-control" style="display:none;" name="quantity" type="number" value="1" min="1" data-min="1">
                          <a
                            data-id="<?=$prodid;?>"
                            data-complete="0"
                            data-complete-id="<?=$cpl["id"];?>"
                            class="btn btn-warning add-to-cart-complete korzina-<?=$cpl["id"];?> clear-korzina"
                            href="cart/addcomplete?id=<?=$prodid;?>"
                            data-bs-toggle="modal"
                            data-bs-target="#exampleModalLive"
                            onclick="try{window.ym&&ym(87229051,'reachGoal','VKORZINU')}catch(e){}; return true;"
                          >
                            <i class="fas fa-cart-plus"></i> Купить неполный комплект
                          </a>
                        <?php else: ?>
                          <button class="btn btn-secondary" type="button" disabled>Нет в наличии</button>
                        <?php endif; ?>

                        <a class="btn btn-outline-dark" href="complete/<?=$cpl["alias"]?>">Подробнее о комплекте</a>
                      </div>
                    </div>
                  </aside>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

          <script>
          document.addEventListener('click', function(e) {
            var btn = e.target.closest('.complete-premium__toggle');
            if (!btn) return;

            var targetId = btn.getAttribute('aria-controls');
            var panel = document.getElementById(targetId);
            if (!panel) return;

            var expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            panel.hidden = expanded;
          });
          </script>

          <section class="desc-prod-inner bg-light shadow">

          <?php if (!empty($technics)): ?>
          <?php
          $size = $attributeValueMap[4] ?? '';

          $seoApplicationText = \app\helpers\SeoApplication::productShortText(
              (string)$size,
              $technics ?? [],
              $cat_prod ?? null
          );
          ?>

          <div class="seo-application-short p-3 pb-0">
              <p class="mb-2">
                  <?= h($seoApplicationText) ?>
              </p>
          </div>
      <?php endif; ?>


          <ul class="nav nav-pills p-3" id="pills-tab" role="tablist">

            <li class="nav-item">
              <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pills-harakteristics" type="button">Характеристики</button>
            </li>

            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-opisanie" type="button">Описание</button>
            </li>

            <?php if (!empty($technics)): ?>
            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-primenenie" type="button">Применяемость</button>
            </li>
            <?php endif; ?>

            <?php if ($cross): ?>
            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-analog" type="button">Аналоги</button>
            </li>
            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-oem" type="button">OEM номера</button>
            </li>
            <?php endif; ?>

            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-delivery" type="button">Доставка</button>
            </li>

            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-pay" type="button">Оплата</button>
            </li>

            <?php if ($product->url_video): ?>
            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-video" type="button">Видео</button>
            </li>
            <?php endif; ?>

            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-review" type="button">
                Отзывы <span class="badge bg-light text-dark"><?=count($review)?></span>
              </button>
            </li>

          </ul>


          <div class="tab-content p-3 border-top">

          <!-- Характеристики -->
          <div class="tab-pane fade show active" id="pills-harakteristics">
          <?php if (!empty($attribute_group)): ?>
          <table class="table table-bordered table-striped">
          <?php foreach ($attribute_group as $group): ?>
          <thead>
          <tr><td colspan="2"><strong><?=$group["attribute_name"]?></strong></td></tr>
          </thead>
          <tbody>
          <?php foreach ($attributesByGroup[(int)$group["attribute_group_id"]] ?? [] as $att): ?>
          <tr><td><?=$att["attribute_name"]?></td><td><?=$att["attribute_text"]?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <?php endforeach; ?>
          </table>
          <?php endif; ?>
          </div>


          <!-- Описание -->
          <div class="tab-pane fade" id="pills-opisanie">
          <?= empty($product->content)
              ? \ishop\App::seoreplace($inseo->content ?? '', $product->id)
              : $product->content; ?>
          </div>


         <?php if($technics) { ?>
              <div class="tab-pane fade" id="pills-primenenie" role="tabpanel" aria-labelledby="pills-primenenie-tab">
                <table class="table table-bordered table-striped">
                  <?php foreach($technics as $tech): ?>
                    <tr><td><?=$tech["name"]?></td><td><a href="technics/<?=$tech["alias"]?>" title="Посмотреть все шины для <?=$tech["name"]?> <?=$tech["model"]?>"><?=$tech["model"]?></a></td></tr>
                  <?php endforeach; ?>
                </table>
              </div>
              <?php } ?>


          <!-- Аналоги -->
          <?php if ($cross): ?>
          <div class="tab-pane fade" id="pills-analog">
          <table class="table table-bordered table-striped">
          <?php foreach ($crossAnalog as $analog): ?>
          <tr>
          <td><?=$analog["name"]?></td>
          <td><a href="cross/<?=rawurlencode(mb_strtolower($analog["cross_abbreviated_name"]))?>"><?=$analog["cross_name"]?></a></td>
          </tr>
          <?php endforeach; ?>
          </table>
          </div>

          <div class="tab-pane fade" id="pills-oem">
          <table class="table table-bordered table-striped">
          <?php foreach ($crossOem as $oem): ?>
          <tr>
          <td><?=$oem["name"]?></td>
          <td><a href="cross/<?=rawurlencode(mb_strtolower($oem["cross_abbreviated_name"]))?>"><?=$oem["cross_name"]?></a></td>
          </tr>
          <?php endforeach; ?>
          </table>
          </div>
          <?php endif; ?>


          <div class="tab-pane fade" id="pills-delivery">
          <?= \ishop\App::options('option_dostavka'); ?>
          </div>

          <div class="tab-pane fade" id="pills-pay">
          <?= \ishop\App::options('option_oplata'); ?>
          </div>

          <?php if ($product->url_video): ?>
          <div class="tab-pane fade" id="pills-video">
          <iframe class="video_frame"
          src="<?php parse_str(parse_url($product->url_video, PHP_URL_QUERY), $video); echo "https://www.youtube.com/embed/" . ($video['v'] ?? ''); ?>">
          </iframe>
          </div>
          <?php endif; ?>

          <div class="tab-pane fade review-tab" id="pills-review" role="tabpanel">

            <section class="container">
                <div class="row">
                    <div class="col-md-12">

                        <div class="panel">
                            <div class="panel-body">

                                <div class="reviews-top">

                                  <!-- ФОРМА -->
                                  <div class="review-box">

                                      <h5>Оставить отзыв</h5>

                                      <?php if ($userId) { ?>

                                      <form method="post" action="product/view">

                                          <textarea name="content" class="form-control mb-2" placeholder="Напишите ваш отзыв"></textarea>

                                          <div class="d-flex gap-2 align-items-center mb-2">

                                              <select name="point" class="form-control" style="max-width:120px;">
                                                  <option value="5">5 ★</option>
                                                  <option value="4">4 ★</option>
                                                  <option value="3">3 ★</option>
                                                  <option value="2">2 ★</option>
                                                  <option value="1">1 ★</option>
                                              </select>

                                              <input type="hidden" name="product_id" value="<?=$product->id?>">
                                          </div>

                                          <button name="addreview" class="btn btn-danger">
                                              Добавить отзыв
                                          </button>

                                      </form>

                                      <?php } else { ?>

                                      <p>Зарегистрируйтесь, чтобы оставить отзыв.</p>

                                      <?php } ?>

                                  </div>


                                  <!-- QR -->
                                  <div class="review-box review-box-gray">

                                      <h5>Отзывы в Яндекс Картах</h5>
                                      <p class="mb-2">ИТС-Центр</p>

                                      <div class="d-flex align-items-center gap-3">

                                          <div>
                                              <p class="small text-muted mb-1">
                                                  Сканируйте QR-код и оставьте отзыв
                                              </p>
                                          </div>

                                          <img src="/images/qr-yandex-card.jpg" width="90">

                                      </div>

                                  </div>

                              </div>

                                <!-- СПИСОК ОТЗЫВОВ -->
                                <?php if (!empty($review)): ?>

                                  <?php
                                  $rating = round($reviewStat['rating'], 1);
                                  $count  = (int)$reviewStat['cnt'];
                                  ?>

                                  <div class="reviews-summary">

                                      <div class="reviews-summary-left">
                                          <div class="reviews-rating-value"><?=$rating?></div>

                                          <div class="reviews-rating-stars">
                                              <?php for ($i = 1; $i <= 5; $i++): ?>
                                                  <?php if ($rating >= $i): ?>
                                                      <i class="fas fa-star"></i>
                                                  <?php elseif ($rating >= $i - 0.5): ?>
                                                      <i class="fas fa-star-half-alt"></i>
                                                  <?php else: ?>
                                                      <i class="far fa-star"></i>
                                                  <?php endif; ?>
                                              <?php endfor; ?>
                                          </div>

                                          <div class="reviews-count">
                                              <?=$count?> отзывов
                                          </div>
                                      </div>

                                      <div class="reviews-summary-right">
                                          <button class="btn btn-sm btn-outline-secondary filter-photo">
                                              📷 Только с фото
                                          </button>
                                      </div>

                                  </div>

                                  <div class="reviews-breakdown">

                                  <?php for ($i = 5; $i >= 1; $i--): ?>

                                  <?php
                                  $count = $ratingDistribution[$i] ?? 0;
                                  $percent = $totalReviews > 0 ? round($count / $totalReviews * 100) : 0;
                                  ?>

                                  <div class="reviews-breakdown-row">

                                      <div class="reviews-breakdown-stars">
                                          <?=$i?> <i class="fas fa-star"></i>
                                      </div>

                                      <div class="reviews-breakdown-bar">
                                          <div class="reviews-breakdown-fill" style="width: <?=$percent?>%"></div>
                                      </div>

                                      <div class="reviews-breakdown-count">
                                          <?=$count?>
                                      </div>

                                  </div>

                                  <?php endfor; ?>

                                  </div>

                                  <div class="reviews-list">

                                  <?php foreach ($review as $rw): ?>

                                  <?php
                                  $name  = trim($rw["uname"]);
                                  $first = mb_substr($name, 0, 1);
                                  $rest  = mb_substr($name, 1);
                                  ?>

                                  <?php $hasPhoto = !empty($reviewGalleryByReviewId[(int)$rw["id"]]); ?>

                                  <div class="review-card" data-photo="<?=$hasPhoto ? '1' : '0'?>">

                                      <!-- HEADER -->
                                      <div class="review-header">

                                          <!-- АВАТАР -->
                                          <div class="review-avatar">
                                              <?=$first?>
                                          </div>

                                          <!-- META -->
                                          <div class="review-meta">

                                              <div class="review-name">
                                                  <span class="first-letter"><?=$first?></span><?=$rest?>
                                              </div>

                                              <div class="review-rating">
                                                  <?php for ($i = 1; $i <= 5; $i++): ?>
                                                      <?php if ((int)$rw["point"] >= $i): ?>
                                                          <i class="fas fa-star"></i>
                                                      <?php else: ?>
                                                          <i class="far fa-star"></i>
                                                      <?php endif; ?>
                                                  <?php endfor; ?>
                                              </div>

                                          </div>

                                          <!-- ДАТА СПРАВА -->
                                          <div class="review-date">
                                              <?= \ishop\App::contdate($rw["date_post"]); ?>
                                          </div>

                                      </div>

                                      <!-- ТЕКСТ -->
                                      <div class="review-text">
                                          <?=$rw["content"]?>
                                      </div>
                                      <?php $gallery_review = $reviewGalleryByReviewId[(int)$rw["id"]] ?? []; ?>

                                      <?php if ($gallery_review): ?>

                                      <div class="review-images">

                                          <?php foreach ($gallery_review as $i => $gr): ?>

                                              <?php
                                              $thumbPath = '/images/review/mini/' . ltrim($gr['img'], '/');
                                              $fullPath  = '/images/review/gallery/' . ltrim($gr['img'], '/');
                                              ?>

                                              <a href="<?=$fullPath?>" data-fancybox="review-<?=$rw["id"]?>">
                                                  <img src="<?=$thumbPath?>" alt="Фото отзыва" loading="lazy">
                                              </a>

                                          <?php endforeach; ?>

                                      </div>

                                      <?php endif; ?>                    
                                  </div>

                                  <?php endforeach; ?>

                                  </div>

                                  <?php endif; ?>

                            </div> <!-- panel-body -->
                        </div> <!-- panel -->

                    </div>
                </div>
            </section>
            <script>
            document.addEventListener('click', function(e){

                if(e.target.classList.contains('filter-photo')){

                    let active = e.target.classList.toggle('active');

                    document.querySelectorAll('.review-card').forEach(card => {

                        if(active){
                            card.style.display = (card.dataset.photo === "1") ? 'block' : 'none';
                        }else{
                            card.style.display = 'block';
                        }

                    });

                    e.target.textContent = active ? '❌ Показать все' : '📷 Только с фото';
                }

            });
            </script>                                  
        </div>

        </div>

      <script>
      document.addEventListener('click', function(e){
      if(e.target.classList.contains('show-more-tech')){
      let tab = e.target.closest('.tab-pane');
      tab.querySelector('.seo-tech-hidden').classList.remove('d-none');
      e.target.classList.add('d-none');
      tab.querySelector('.hide-tech').classList.remove('d-none');
      }
      if(e.target.classList.contains('hide-tech')){
      let tab = e.target.closest('.tab-pane');
      tab.querySelector('.seo-tech-hidden').classList.add('d-none');
      e.target.classList.add('d-none');
      tab.querySelector('.show-more-tech').classList.remove('d-none');
      }
      });
      </script>

      </section>

          <?php if (!empty($related)): ?>
          <div class="related_prod">
            <div class="container">
              <section class="pb-5 mb-2 mb-xl-4 recomend-1">
                <h2 class="h3 pb-2 mb-grid-gutter text-center">Связанные товары</h2>
                <div class="review-wrap">
                  <div class="wrap-container">
                    <div class="inner-container">
                      <div class="swiper-container swiper1">
                        <div class="swiper-wrapper">
                          <?php foreach ($related as $item): ?>
                            <div class="swiper-slide">
                              <?php new \app\widgets\product\Product($item, $curr, 'product_tpl.php', $relatedWidgetContext ?? []); ?>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="swiper-button-inner">
                  <div class="swiper-button-next swiper-button-next-1"></div>
                  <div class="swiper-button-prev swiper-button-prev-1"></div>
                </div>
              </section>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($services)): ?>
          <div class="services_prod">
            <div class="container">
              <section class="pb-5 mb-2 mb-xl-4 recomend-1">
                <h2 class="h3 pb-2 mb-grid-gutter text-center">Услуги</h2>

                <div class="review-wrap">
                  <div class="wrap-container">
                    <div class="inner-container">
                      <div class="swiper-container swiper3">
                        <div class="swiper-wrapper">
                          <?php foreach ($services as $service): ?>
                            <div class="swiper-slide">
                              <?php new \app\widgets\product\Product($service, $curr, 'product_tpl.php', $servicesWidgetContext ?? []); ?>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="swiper-button-inner">
                  <div class="swiper-button-next swiper-button-next-3"></div>
                  <div class="swiper-button-prev swiper-button-prev-3"></div>
                </div>
              </section>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($similar)): ?>
          <div class="related_prod">
            <div class="container">
              <section class="pb-5 mb-2 mb-xl-4 recomend-1">
                <h2 class="h3 pb-2 mb-grid-gutter text-center">Похожие товары</h2>
                <div class="review-wrap">
                  <div class="wrap-container">
                    <div class="inner-container">
                      <div class="swiper-container swiper2">
                        <div class="swiper-wrapper">
                          <?php foreach ($similar as $item2): ?>
                            <div class="swiper-slide">
                              <?php new \app\widgets\product\Product($item2, $curr, 'product_tpl.php', $similarWidgetContext ?? []); ?>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="swiper-button-inner">
                  <div class="swiper-button-next swiper-button-next-2"></div>
                  <div class="swiper-button-prev swiper-button-prev-2"></div>
                </div>
              </section>
            </div>
          </div>
          <?php endif; ?>

        </section>
    </div>
</div>
<!--end-single-->

<!-- Modal Купить в 1 клик -->
<div class="modal fade" id="Modalclick" tabindex="-1" aria-labelledby="ModalclickLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content callback-modal">
            <div class="modal-header">
                <div class="modal-title" id="ModalclickLabel">Купить в 1 клик</div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"></button>
            </div>

            <form action="<?= PATH ?>/product/<?= h($product->alias) ?>" method="post" class="callback-form js-modal-validate" novalidate>
                <div class="modal-body">
                    <div class="availability-product mb-3">
                        <div class="availability-product__name">
                            <?= h($product->name) ?>
                        </div>

                        <input type="hidden" name="name_tovar" value="<?= h($product->name) ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$product->id ?>">
                    </div>

                    <div class="mb-3">
                        <label for="oneclick-fio-<?= (int)$product->id ?>" class="form-label">
                            Ф.И.О. <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="fio_modal"
                            id="oneclick-fio-<?= (int)$product->id ?>"
                            class="form-control"
                            placeholder="Укажите Ф.И.О."
                            value="<?= h($userName ?? '') ?>"
                            required
                        >

                        <div class="invalid-feedback">Укажите Ф.И.О.</div>
                    </div>

                    <div class="mb-3">
                        <label for="phone-input3" class="form-label">
                            Телефон <span class="text-danger">*</span>
                        </label>

                        <input
                            type="tel"
                            name="tell_modal"
                            id="phone-input3"
                            class="form-control"
                            placeholder="+7 (___) ___-__-__"
                            required
                        >

                        <div class="invalid-feedback">Укажите телефон.</div>
                    </div>

                    <div class="mb-3">
                        <label for="oneclick-email-<?= (int)$product->id ?>" class="form-label">
                            Эл. почта <span class="text-danger">*</span>
                        </label>

                        <input
                            type="email"
                            name="email_modal"
                            id="oneclick-email-<?= (int)$product->id ?>"
                            class="form-control"
                            placeholder="Укажите ваш e-mail"
                            required
                        >

                        <div class="invalid-feedback">Укажите корректный email.</div>
                    </div>

                    <div class="mb-3">
                        <label for="oneclick-comment-<?= (int)$product->id ?>" class="form-label">
                            Комментарий к заказу
                        </label>

                        <textarea
                            name="prim_modal"
                            id="oneclick-comment-<?= (int)$product->id ?>"
                            class="form-control"
                            rows="4"
                            placeholder="Комментарий к заказу (количество, время для обратного звонка или другую необходимую информацию)"
                        ></textarea>
                    </div>

                    <div class="form-check callback-agree">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            value="pk"
                            id="oneclick-agree-<?= (int)$product->id ?>"
                            name="politika"
                            required
                        >

                        <label class="form-check-label" for="oneclick-agree-<?= (int)$product->id ?>">
                            Я принимаю
                            <a href="<?= PATH ?>/pages/privacy" target="_blank" rel="noopener">
                                Политику конфиденциальности
                            </a>
                            и даю
                            <a href="<?= PATH ?>/pages/personal-data-consent" target="_blank" rel="noopener">
                                согласие на обработку персональных данных
                            </a>
                        </label>

                        <div class="invalid-feedback d-block">Нужно подтвердить согласие.</div>
                    </div>

                    <input type="hidden" name="oneclick" value="<?= md5(date('Y-m-d')) ?>">
                </div>

                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-danger callback-submit" disabled>
                        Отправить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Оформить товар под заказ -->
<div class="modal fade" id="ModalRequest" tabindex="-1" aria-labelledby="ModalRequestLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content callback-modal">
            <div class="modal-header">
                <div class="modal-title" id="ModalRequestLabel">Оформить товар под заказ</div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"></button>
            </div>

            <form action="<?= PATH ?>/product/<?= h($product->alias) ?>" method="post" class="callback-form js-modal-validate" novalidate>
                <div class="modal-body">
                    <div class="availability-product mb-3">
                        <div class="availability-product__name">
                            <?= h($product->name) ?>
                        </div>

                        <input type="hidden" name="name_tovar" value="<?= h($product->name) ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$product->id ?>">
                    </div>

                    <div class="mb-3">
                        <label for="request-fio-<?= (int)$product->id ?>" class="form-label">
                            Ф.И.О. <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="fio_modal"
                            id="request-fio-<?= (int)$product->id ?>"
                            class="form-control"
                            placeholder="Укажите Ф.И.О."
                            value="<?= h($userName ?? '') ?>"
                            required
                        >

                        <div class="invalid-feedback">Укажите Ф.И.О.</div>
                    </div>

                    <div class="mb-3">
                        <label for="phone-input2" class="form-label">
                            Телефон <span class="text-danger">*</span>
                        </label>

                        <input
                            type="tel"
                            name="tell_modal"
                            id="phone-input2"
                            class="form-control"
                            placeholder="+7 (___) ___-__-__"
                            required
                        >

                        <div class="invalid-feedback">Укажите телефон.</div>
                    </div>

                    <div class="mb-3">
                        <label for="request-email-<?= (int)$product->id ?>" class="form-label">
                            Эл. почта <span class="text-danger">*</span>
                        </label>

                        <input
                            type="email"
                            name="email_modal"
                            id="request-email-<?= (int)$product->id ?>"
                            class="form-control"
                            placeholder="Укажите ваш e-mail"
                            required
                        >

                        <div class="invalid-feedback">Укажите корректный email.</div>
                    </div>

                    <div class="mb-3">
                        <label for="request-comment-<?= (int)$product->id ?>" class="form-label">
                            Комментарий к заказу
                        </label>

                        <textarea
                            name="prim_modal"
                            id="request-comment-<?= (int)$product->id ?>"
                            class="form-control"
                            rows="4"
                            placeholder="Комментарий к заказу (количество товара, время для обратного звонка или другую необходимую информацию)"
                        ></textarea>
                    </div>

                    <div class="form-check callback-agree">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            value="pk"
                            id="request-agree-<?= (int)$product->id ?>"
                            name="politika"
                            required
                        >

                        <label class="form-check-label" for="request-agree-<?= (int)$product->id ?>">
                            Я принимаю
                            <a href="<?= PATH ?>/pages/privacy" target="_blank" rel="noopener">
                                Политику конфиденциальности
                            </a>
                            и даю
                            <a href="<?= PATH ?>/pages/personal-data-consent" target="_blank" rel="noopener">
                                согласие на обработку персональных данных
                            </a>
                        </label>

                        <div class="invalid-feedback d-block">Нужно подтвердить согласие.</div>
                    </div>

                    <input type="hidden" name="request" value="<?= md5(date('Y-m-d')) ?>">
                </div>

                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-danger callback-submit" disabled>
                        Отправить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal Сообщить о поступлении -->
<div class="modal fade" id="ModalAvailability" tabindex="-1" aria-labelledby="ModalAvailabilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content callback-modal">
            <div class="modal-header">
                <div class="modal-title" id="ModalAvailabilityLabel">Сообщить о поступлении товара на email</div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"></button>
            </div>

            <form action="<?= PATH ?>/product/<?= h($product->alias) ?>" method="post" class="callback-form js-modal-validate" novalidate>
                <div class="modal-body">
                    <div class="availability-product mb-3">
                        <div class="availability-product__name">
                            <?= h($product->name) ?>
                        </div>

                        <input type="hidden" name="name_tovar" value="<?= h($product->name) ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$product->id ?>">
                    </div>

                    <div class="mb-3">
                        <label for="availability-email-<?= (int)$product->id ?>" class="form-label">
                            Эл. почта <span class="text-danger">*</span>
                        </label>

                        <input
                            type="email"
                            name="email_modal"
                            id="availability-email-<?= (int)$product->id ?>"
                            class="form-control"
                            required
                            placeholder="Укажите ваш e-mail"
                        >

                        <div class="invalid-feedback">Укажите корректный email.</div>
                    </div>

                    <div class="form-check callback-agree">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            value="pk"
                            id="availability-agree-<?= (int)$product->id ?>"
                            name="politika"
                            required
                        >

                        <label class="form-check-label" for="availability-agree-<?= (int)$product->id ?>">
                            Я принимаю
                            <a href="<?= PATH ?>/pages/privacy" target="_blank" rel="noopener">
                                Политику конфиденциальности
                            </a>
                            и даю
                            <a href="<?= PATH ?>/pages/personal-data-consent" target="_blank" rel="noopener">
                                согласие на обработку персональных данных
                            </a>
                        </label>

                        <div class="invalid-feedback d-block">Нужно подтвердить согласие.</div>
                    </div>

                    <input type="hidden" name="availability" value="<?= md5(date('Y-m-d')) ?>">
                </div>

                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-danger callback-submit" disabled>
                        Отправить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
use app\helpers\PdfAssets;

$IMG   = PdfAssets::imagesFor($product);
$logo  = $IMG['logo'];
$logos = $IMG['logos'];
$images= $IMG['product'];
echo "\n<!-- PDFIMG Product: topLogo=".($logo?'ok':'no')." bottomLogo=".($logos?'ok':'no')." product=".($images?'ok':'no')." -->\n";

$attrsBody = [];
if (!empty($attribute_group)) {
    foreach ($attribute_group as $group) {
        $attrsBody[] = [
            ['text' => (string)$group['attribute_name'], 'fontSize' => 10, 'style' => 'tableHeader'],
            ['text' => '']
        ];

        $groupId = (int)($group['attribute_group_id'] ?? 0);
        $atts = $attributesByGroup[$groupId] ?? [];

        foreach ($atts as $att) {
            $attrsBody[] = [
                ['text' => (string)$att['attribute_name'], 'fontSize' => 10, 'style' => 'tableHeader'],
                ['text' => (string)$att['attribute_text'], 'fontSize' => 10, 'style' => 'tableHeader']
            ];
        }
    }
}

$base  = (float)$product->price_rrs > 0 ? (float)$product->price_rrs : (float)$product->price;
$final = (float)$product->price;

if (isset($action) && is_object($action) && (int)$action->product_id === (int)$product->id) {
    $type = (string)$action->type_id;
    $val  = (float)$action->znachenie;
    if ($type === '1') {
        $final = max(0, $final - ($final * ($val / 100)));
    } elseif ($type === '2') {
        $final = max(0, $final - $val);
    }
}

$hasDiscount = ($final < $base);
$discountBadge = '';
if ($hasDiscount) {
    $percent = $base > 0 ? round(100 - ($final / $base * 100)) : 0;
    if ($percent > 0) $discountBadge = '-' . $percent . '%';
}

$priceStr    = $curr['symbol_left'] . ' ' . number_format($final * $curr['value'], 0, '.', ' ') . ' ' . $curr['symbol_right'];
$oldPriceStr = $hasDiscount ? ($curr['symbol_left'] . ' ' . number_format($base * $curr['value'], 0, '.', ' ') . ' ' . $curr['symbol_right']) : '';

$kpData = [
    'title'         => 'Коммерческое предложение',
    'subtitle'      => $product->name,
    'sku'           => (string)$product->sku,
    'priceStr'      => $priceStr,
    'oldPriceStr'   => $oldPriceStr,
    'discountBadge' => $discountBadge,
    'hasDiscount'   => $hasDiscount,
    'attrsBody'     => $attrsBody,
    'images'        => ['logo' => $logo, 'logos' => $logos, 'product' => $images],
];
?>
<script id="kp-data-<?= (int)$product->id ?>" type="application/json">
<?= json_encode($kpData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>
</script>

<script src="/js/pdfmake.js"></script>
<script src="/js/vfs_fonts.js"></script>
<script src="/js/kp_template.js"></script>
<script>
  (function(){
    var dataEl = document.getElementById('kp-data-<?= (int)$product->id ?>');
    if (!dataEl) return;
    var data = JSON.parse(dataEl.textContent || '{}');
    KPTemplate.attach('#btnpdf', data, 'KP_<?= addslashes($product->alias) ?>.pdf');
  })();
</script>
