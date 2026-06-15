<?php
$isFilterLanding = !empty($selectedAttr) && !empty($selectedAttr->id) && !empty($selectedAttrAlias);

$filterAttr = $filterLanding['attr'] ?? null;
$filterGroup = $filterLanding['group'] ?? null;
$filterTopContent = $filterLanding['top_content'] ?? '';
$categoryTopContent = !$isFilterLanding && !empty($category->top_content)
    ? trim((string)$category->top_content)
    : '';
$filterBottomContent = $filterLanding['bottom_content'] ?? '';

$categoryImage = '';
if (!empty($category->img)) {
    $categoryImageRel = 'images/category/baseimg/' . $category->img;
    $categoryImageAbs = WWW . '/' . $categoryImageRel;

    if (is_file($categoryImageAbs)) {
        $categoryImage = $categoryImageRel;
    }
}

$filterImage = '';
if (!empty($filterAttr) && !empty($filterAttr->img)) {
    $filterImageRel = 'images/filtrs/baseimg/' . $filterAttr->img;
    $filterImageAbs = WWW . '/' . $filterImageRel;

    if (is_file($filterImageAbs)) {
        $filterImage = $filterImageRel;
    }
}

$currentCategoryAlias = !empty($category->alias) ? (string)$category->alias : '';

$products = !empty($products) && is_array($products) ? $products : [];
$relatedSizes = !empty($relatedSizes) && is_array($relatedSizes) ? $relatedSizes : [];
$technicsLinks = !empty($technicsLinks) && is_array($technicsLinks) ? $technicsLinks : [];
$faqRows = !empty($faqRows) && is_array($faqRows) ? $faqRows : [];
$categoryFaqRows = !empty($categoryFaqRows) && is_array($categoryFaqRows) ? $categoryFaqRows : [];
$relatedCategories = !empty($relatedCategories) && is_array($relatedCategories) ? $relatedCategories : [];
$categoryAlsoViewed = !empty($categoryAlsoViewed) && is_array($categoryAlsoViewed) ? $categoryAlsoViewed : [];
$categoryAlsoViewedWidgetContext = !empty($categoryAlsoViewedWidgetContext) && is_array($categoryAlsoViewedWidgetContext) ? $categoryAlsoViewedWidgetContext : [];
$quantity = !empty($quantity) && is_array($quantity) ? $quantity : [];
$podcategory = !empty($podcategory) && is_array($podcategory) ? $podcategory : [];
$reviewData = !empty($reviewData) && is_array($reviewData) ? $reviewData : [];

/*
 * Единый FAQ для страницы:
 * - на фильтре показываем FAQ фильтра;
 * - на обычной категории показываем FAQ категории.
 */
$pageFaqRows = [];

if ($isFilterLanding && !empty($faqRows)) {
    $pageFaqRows = $faqRows;
} elseif (!$isFilterLanding && !empty($categoryFaqRows)) {
    $pageFaqRows = $categoryFaqRows;
}

$curr = \ishop\App::$app->getProperty('currency');
$currValue = !empty($curr['value']) ? (float)$curr['value'] : 1.0;
?>

<!--prdt-starts-->
<div class="prdt">
  <div class="container">
    <nav class="pt-4 breadcrumb-blok" aria-label="breadcrumb">
      <ol class="breadcrumb flex-lg-nowrap">
        <?=$breadcrumbs;?>
      </ol>
    </nav>

    <section class="align-items-center">
    <?php
    $pageH1 = $isFilterLanding
        ? (string)$name
        : (!empty($category->h1) ? (string)$category->h1 : (string)$name);
    ?>

    <h1 class="h2 mb-3 mb-md-0 me-3"><?= h($pageH1) ?></h1>
  </section>

    <?php if (!$isFilterLanding && !empty($podcategory)): ?>
      <section class="align-items-center podcats">
        <?php foreach ($podcategory as $podcat): ?>
          <?php
          $podcatAlias = (string)($podcat['alias'] ?? '');
          $podcatName = (string)($podcat['name'] ?? '');
          if ($podcatAlias === '' || $podcatName === '') {
              continue;
          }
          ?>
          <div class="podssilka">
            <a href="category/<?= h($podcatAlias) ?>" title="<?= h($podcatName) ?>">
              <?= h($podcatName) ?>
            </a>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>

      <?php if (($page ?? 1) <= 1 && !$isFilterLanding && !empty($categoryTopContent)): ?>
      <?php
        $alt = !empty($category->name)
            ? 'Категория ' . $category->name
            : $name;
        ?>
        <div class="catalog-top-block mb-4">
          <div class="catalog-top-text">
            <?=$categoryTopContent;?>
          </div>
        </div>
      <?php endif; ?>     

    <?php if (($page ?? 1) <= 1 && $isFilterLanding && !empty($filterTopContent)): ?>
      <?php
      $alt = !empty($filterAttr->value)
          ? 'Типоразмер ' . $filterAttr->value . ' в категории ' . ($category->name ?? '')
          : $name;
      ?>
      <div class="catalog-top-block mb-4">
        <?php if ($filterImage): ?>
          <div class="catalog-top-image">
            <img
              src="/<?= h($filterImage) ?>"
              alt="<?= h($alt) ?>"
              loading="lazy">
          </div>
        <?php endif; ?>

        <div class="catalog-top-text">
          <?=$filterTopContent;?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($isFilterLanding && !empty($technicsLinks)): ?>
      <div class="tech-links mb-4">
        <div class="h4 mb-2">Подходит для техники</div>
        <div class="tech-links__items">
          <?php foreach ($technicsLinks as $t): ?>
            <?php
            $techAlias = (string)($t['alias'] ?? '');
            $techName = (string)($t['name'] ?? '');
            if ($techAlias === '' || $techName === '') {
                continue;
            }
            ?>
            <a class="tech-links__item" href="/technics/type/<?= h($techAlias) ?>">
              <?= h($techName) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <section class="d-md-flex justify-content-between align-items-center pb-4">
      <div class="w_sidebar col-md-12 fltr"
           data-category-url="<?= PATH ?>/category/<?= h($category->alias ?? '') ?>">
        <?php new \app\widgets\filter\Filter($ids, $allFilterIds ?? []); ?>
      </div>
    </section>

    <div class="d-flex align-items-center col-md-12 pb-4 sort-inner">
      <div class="sort-inner">
        <div class="sort-name">Сортировать по:</div>
        <span class="nav-link" id="nal">Наличию</span>
        <span class="nav-link" id="price">Цене</span>
        <span class="nav-link" id="rate">Рейтингу</span>
      </div>
    </div>

    <div class="prdt-top">
      <div class="col-md-12">
        <?php if (!empty($products)): ?>
          <div class="row gx-3 gy-3 product-one">
            <?php foreach ($products as $product): ?>
              <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
                <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php', $productWidgetContext ?? []); ?>
              </div>
            <?php endforeach; ?>

            <div class="clearfix"></div>

            <div class="text-center">
              <?php if (!empty($pagination) && !empty($pagination->countPages) && $pagination->countPages > 1): ?>
                <?=$pagination;?>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <h3>
            "Живого" наличия позиций в данном типоразмере нет. Для уточнения возможной поставки товара
            "Под заказ" просьба связаться с нашими менеджерами по тел.: +7(495)424-98-90
            или написать нам на электронную почту: info@its-center.ru
          </h3>
        <?php endif; ?>

        <?php if (($page ?? 1) <= 1): ?>

          <?php if ($isFilterLanding && !empty($relatedSizes) && $currentCategoryAlias !== ''): ?>
            <div class="related-sizes mb-4">
              <div class="h4">Также смотрят типоразмеры</div>
              <div class="related-sizes__items">
                <?php foreach ($relatedSizes as $r): ?>
                  <?php
                  $relatedAlias = (string)($r['alias'] ?? '');
                  $relatedValue = (string)($r['value'] ?? '');

                  if ($relatedAlias === '' || $relatedValue === '') {
                      continue;
                  }

                  $relatedUrl = PATH . '/category/' . rawurlencode($currentCategoryAlias) . '/' . rawurlencode($relatedAlias);
                  ?>
                  <a class="related-sizes__item" href="<?= h($relatedUrl) ?>">
                    <?= h($relatedValue) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($isFilterLanding && !empty($filterBottomContent)): ?>
            <div class="catalog_text">
              <?=$filterBottomContent;?>
            </div>
          <?php elseif (!$isFilterLanding && !empty($category->content)): ?>
            <div class="catalog_text">
              <?=$category->content;?>
            </div>
          <?php endif; ?>

          <?php if (!empty($pageFaqRows)): ?>
            <section class="faq-block mb-4">
              <div class="h4 mb-3">Вопросы и ответы</div>

              <?php foreach ($pageFaqRows as $f): ?>
                <?php
                $question = trim((string)($f['question'] ?? ''));
                $answer = trim((string)($f['answer'] ?? ''));

                if ($question === '' || $answer === '') {
                    continue;
                }
                ?>
                <div class="faq-item mb-3">
                  <div class="faq-q mb-1">
                    <strong><?= h($question) ?></strong>
                  </div>
                  <div class="faq-a">
                    <?= nl2br(h($answer)) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </section>
          <?php endif; ?>

          <?php if (!empty($pageFaqRows)): ?>
            <?php
            $faqJsonItems = [];

            foreach ($pageFaqRows as $f) {
                $question = trim((string)($f['question'] ?? ''));
                $answer = trim((string)($f['answer'] ?? ''));

                if ($question === '' || $answer === '') {
                    continue;
                }

                $faqJsonItems[] = [
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => trim(strip_tags($answer)),
                    ],
                ];
            }
            ?>

            <?php if (!empty($faqJsonItems)): ?>
              <!-- JSON-LD: FAQPage -->
              <script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $faqJsonItems,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
              </script>
              <!-- /JSON-LD -->
            <?php endif; ?>
          <?php endif; ?>

          <?php if (!empty($relatedCategories)): ?>
            <section class="related-sizes mb-4">
              <div class="h4">Смежные категории</div>
              <div class="related-sizes__items">
                <?php foreach ($relatedCategories as $relatedCategory): ?>
                  <?php
                  $relatedCategoryName = trim((string)($relatedCategory['name'] ?? ''));
                  $relatedCategoryUrl = trim((string)($relatedCategory['url'] ?? ''));

                  if ($relatedCategoryName === '' || $relatedCategoryUrl === '') {
                      continue;
                  }
                  ?>
                  <a class="related-sizes__item" href="<?= h($relatedCategoryUrl) ?>">
                    <?= h($relatedCategoryName) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <?php if (!empty($categoryAlsoViewed)): ?>
            <div class="related_prod">
              <section class="pb-5 mb-2 mb-xl-4 recomend-1">
                <h2 class="h3 pb-2 mb-grid-gutter text-center">Также смотрят</h2>
                <div class="review-wrap">
                  <div class="wrap-container">
                    <div class="inner-container">
                      <div class="swiper-container swiper2">
                        <div class="swiper-wrapper">
                          <?php foreach ($categoryAlsoViewed as $item): ?>
                            <div class="swiper-slide">
                              <?php new \app\widgets\product\Product($item, $curr, 'product_tpl.php', $categoryAlsoViewedWidgetContext); ?>
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
          <?php endif; ?>

        <?php endif; ?>
      </div>

      <div class="clearfix"></div>
    </div>
  </div>

  <div class="clearfix"></div>

<?php
$jsonProducts = [];

foreach ($products as $product) {
    $productId = (int)($product['id'] ?? $product->id ?? 0);
    $productName = trim((string)($product['name'] ?? $product->name ?? ''));
    $productAlias = trim((string)($product['alias'] ?? $product->alias ?? ''));

    if ($productId <= 0 || $productName === '' || $productAlias === '') {
        continue;
    }

    $productUrl = PATH . '/product/' . $productAlias;

    $jsonProducts[] = [
        '@type' => 'ListItem',
        'position' => count($jsonProducts) + 1,
        'url' => $productUrl,
        'name' => $productName,
    ];
}
?>

<?php if (!empty($jsonProducts)): ?>
  <!-- JSON-LD: ItemList Carousel -->
  <script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => (string)$name,
    'url' => $canonicalUrl ?? (PATH . '/' . trim($_SERVER['REQUEST_URI'] ?? '', '/')),
    'numberOfItems' => count($jsonProducts),
    'itemListElement' => $jsonProducts,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
  <!-- /JSON-LD -->
<?php endif; ?>
</div>
<!--product-end-->
