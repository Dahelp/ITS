<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use app\models\Category;
use app\widgets\filter\Filter;
use ishop\App;
use ishop\libs\Pagination;

class CategoryController extends AppController
{
    public function viewAction()
{
    $alias = $this->route['alias'] ?? null;
    $filterAlias = $this->route['filter_alias'] ?? null;

    $category = \R::findOne('category', 'alias = ?', [$alias]);
    if (!$category) {
        throw new \Exception('Страница не найдена', 404);
    }

    if (empty($filterAlias) && (int)($category->type_id ?? 0) === 1) {
        $hasChildren = \R::count(
            'category',
            "parent_id = ? AND (hide = 'show' OR hide = '' OR hide IS NULL)",
            [(int)$category->id]
        ) > 0;

        if ($hasChildren) {
            $this->redirectPermanent(rtrim(PATH, '/') . '/catalog/' . trim((string)$category->alias, '/'));
        }
    }

    if (!empty($filterAlias)) {
        $childCategory = \R::findOne(
            'category',
            'alias = ? AND parent_id = ?',
            [(string)$filterAlias, (int)$category->id]
        );

        if ($childCategory && !empty($childCategory->id)) {
            $category = $childCategory;
            $alias = (string)$childCategory->alias;
            $filterAlias = null;
            $this->route['alias'] = $alias;
            unset($this->route['filter_alias']);
        }
    }

    $breadcrumbs = Breadcrumbs::getBreadcrumbs(
        $category->id,
        null,
        $alias,
        mb_strtolower($this->route['controller'])
    );

    $catModel = new Category();
    $ids = $catModel->getIds($category->id);
    $ids = !$ids ? (string)$category->id : $ids . $category->id;

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page);

    $perpage = (int)App::$app->getProperty('pagination');

    $sqlPart = '';
    $sqlSort = "ORDER BY FIELD(`stock_status_id`, 1,3,2,0), name ASC";

    $selectedAttrId = null;
    $selectedAttrAlias = null;
    $selectedAttr = null;
    $selectedAttrGroup = null;

    $extraFilterIds = [];
    $allFilterIds = [];

    $filterLanding = null;
    $filterInseo = null;
    $categoryFilterSeo = null;
    $relatedSizes = [];
    $technicsLinks = [];
    $faqRows = [];
    $filterTopContent = '';
    $filterBottomContent = '';
    $filterImage = '';

    $legacyFilter = null;
    if (!empty($_GET['filter'])) {
        $legacyFilter = Filter::getFilter();
    }

    // основной фильтр из ЧПУ: /category/{category_alias}/{filter_alias}
    if (!empty($filterAlias)) {
        $selectedAttr = $this->findApplicableFilterValue((string)$filterAlias, (string)$ids);

        if (!$selectedAttr) {
            throw new \Exception('Фильтр не найден', 404);
        }

        $selectedAttrId = (int)$selectedAttr->id;
        $selectedAttrAlias = (string)$selectedAttr->alias;
        $allFilterIds[] = $selectedAttrId;

        $selectedAttrGroup = \R::findOne(
            'attribute_group',
            'id = ?',
            [$selectedAttr->attr_group_id]
        );

        if ($selectedAttr && !empty($selectedAttr->id)) {
            $categoryFilterSeo = $this->getCategoryFilterSeoRow(
                (int)$selectedAttr->id,
                (int)$category->id
            );

            if ($categoryFilterSeo) {
                if (
                    ($categoryFilterSeo['mode'] ?? '') === 'redirect'
                    && !empty($categoryFilterSeo['redirect_category_alias'])
                ) {
                    $target = $this->buildCategoryFilterUrl(
                        (string)$categoryFilterSeo['redirect_category_alias'],
                        (string)$selectedAttr->alias
                    );

                    $currentUrl = $this->buildCategoryFilterUrl(
                        (string)$category->alias,
                        (string)$selectedAttr->alias
                    );

                    if (rtrim($target, '/') !== rtrim($currentUrl, '/')) {
                        $this->redirectPermanent($target);
                    }
                }
            }
        }
    }

    // дополнительные фильтры из ?filter=
    if ($legacyFilter) {
        $extraFilterIds = array_filter(array_map('intval', explode(',', $legacyFilter)));
    }

    $namedFilterIds = $this->getNamedFilterIdsFromQuery();
    if (!empty($namedFilterIds)) {
        $extraFilterIds = array_values(array_unique(array_merge($extraFilterIds, $namedFilterIds)));
    }

    // убираем дубль основного фильтра
    if (!empty($selectedAttrId) && !empty($extraFilterIds)) {
        $extraFilterIds = array_values(array_diff($extraFilterIds, [$selectedAttrId]));
    }

    // общий набор фильтров
    $allFilterIds = array_values(array_unique(array_merge($allFilterIds, $extraFilterIds)));

    if (!empty($allFilterIds)) {
        $filterSql = implode(',', array_map('intval', $allFilterIds));
        $cnt = count($allFilterIds);

        $sqlPart = "AND product.id IN (
            SELECT product_id
            FROM attribute_product
            WHERE attr_id IN ($filterSql)
            GROUP BY product_id
            HAVING COUNT(DISTINCT attr_id) = $cnt
        )";
    }

    // fallback старого режима ?filter=94
    if (empty($selectedAttrId) && $legacyFilter) {
        $legacyIds = array_filter(array_map('intval', explode(',', $legacyFilter)));
        $firstId = $legacyIds[0] ?? 0;

        if ($firstId > 0) {
            $selectedAttr = \R::load('attribute_value', $firstId);

            if ($selectedAttr && !empty($selectedAttr->id)) {
                $selectedAttrId = (int)$selectedAttr->id;
                $selectedAttrAlias = (string)$selectedAttr->alias;

                $selectedAttrGroup = \R::findOne(
                    'attribute_group',
                    'id = ?',
                    [$selectedAttr->attr_group_id]
                );

                $sqlPart = "AND product.id IN (
                    SELECT product_id
                    FROM attribute_product
                    WHERE attr_id = {$selectedAttrId}
                )";
            }
        }
    }

    if (!empty($_GET['sort'])) {
        if ($_GET['sort'] === 'price') {
            $sqlSort = "ORDER BY price ASC";
        } elseif ($_GET['sort'] === 'nal') {
            $sqlSort = "ORDER BY stock_status_id DESC";
        } elseif ($_GET['sort'] === 'rate') {
            $sqlSort = "ORDER BY hit DESC";
        }
    }

    $total = \R::count('product', "hide = 'show' AND category_id IN ($ids) $sqlPart");
    if (!empty($selectedAttrId) && $total < 1) {
        throw new \Exception('Страница не найдена', 404);
    }

    $seoProductIds = [];

    if (!empty($selectedAttrId)) {
        $seoProductIds = \R::getCol(
            "SELECT product.id
            FROM product
            WHERE hide = 'show'
            AND category_id IN ($ids)
            $sqlPart"
        );
    }

    $minPrice = 0;

    if (!empty($selectedAttrId)) {
        $minPrice = (float)\R::getCell(
            "SELECT MIN(price)
            FROM product
            WHERE hide = 'show'
            AND category_id IN ($ids)
            AND price > 0
            AND stock_status_id = 1
            $sqlPart"
        );
    }

    $pagination = new Pagination($page, $perpage, $total);
    $start = (int)$pagination->getStart();

    $products = \R::find(
        'product',
        "hide = 'show' AND category_id IN ($ids) $sqlPart $sqlSort LIMIT $start, $perpage"
    );

    $quantity = [];

    foreach ($products as $product) {
        $productId = (int)($product['id'] ?? $product->id ?? 0);

        if ($productId <= 0) {
            continue;
        }

        $quantity[$productId] = isset($product['quantity'])
            ? (int)$product['quantity']
            : (int)($product->quantity ?? 0);
    }

    // Подкатегории и SEO-блоки заранее, без SQL во view
    $podcategory = \R::getAll(
        "SELECT * FROM category WHERE parent_id = ?",
        [$category->id]
    );

    $inseo = \R::findOne(
        'plagins_inseo',
        "tip = ? AND category_id = ? AND hide = 'show'",
        ['category', $category->id]
    );

    $inseo_prod = \R::findOne(
        'plagins_inseo',
        "tip = ? AND category_id = ? AND hide = 'show'",
        ['product', $category->id]
    );

    if (!empty($inseo->title)) {
        $title = \ishop\App::seoreplace($inseo->title, $category->id);
    } else {
        $title = $category->title;
    }

    if (!empty($inseo->description)) {
        $description = \ishop\App::seoreplace($inseo->description, $category->id);
    } else {
        $description = trim((string)($category->description ?? ''));
    }

    if ($description === '') {
        $categoryName = trim((string)($category->name ?? ''));

        if ($categoryName === '') {
            $categoryName = $this->mbUcfirst($this->getCategoryItemWord($category));
        }

        $description = $categoryName . ' купить в ИТС-Центре. Подбор по размеру, назначению, наличию и цене. Доставка по России.';
    }

    if (!empty($inseo->keywords)) {
        $keywords = \ishop\App::seoreplace($inseo->keywords, $category->id);
    } else {
        $keywords = $category->keywords;
    }

    $name = !empty($inseo?->name)
        ? \ishop\App::seoreplace($inseo->name, (int)$category->id)
        : ($category->name ?? '');

    // поиск минимальной цены фильтра
    $minPrice = 0;

    if (!empty($selectedAttrId)) {
        $minPrice = (float)\R::getCell(
            "SELECT MIN(price)
            FROM product
            WHERE hide = 'show'
            AND category_id IN ($ids)
            AND price > 0
            AND stock_status_id = 1
            $sqlPart"
        );
    }

    // SEO и контент основного фильтра внутри категории
    if (!empty($selectedAttr) && !empty($selectedAttr->id)) {
        if (!$selectedAttrGroup) {
            $selectedAttrGroup = \R::findOne(
                'attribute_group',
                'id = ?',
                [$selectedAttr->attr_group_id]
            );
        }

        if (!$categoryFilterSeo) {
            $categoryFilterSeo = $this->getCategoryFilterSeoRow(
                (int)$selectedAttr->id,
                (int)$category->id
            );
        }

        $filterInseo = \R::findOne(
            'plagins_inseo',
            "tip = ? AND category_id = ? AND hide = 'show'",
            ['attribute_group', $selectedAttr->attr_group_id]
        );

        $inseoContext = [
            'category' => $category,
            'filter' => $selectedAttr,
            'filter_group_url_params' => $selectedAttrGroup->url_params ?? '',
            'product_ids' => $seoProductIds,
            'min_price' => $minPrice,
        ];

        /*
        * SEO приоритет для /category/{category_alias}/{filter_alias}
        *
        * 1. attribute_value_category_canonical — ручное SEO конкретного фильтра в конкретной категории
        * 2. attribute_value — старые SEO-поля самого фильтра
        * 3. plagins_inseo attribute_group — INSEO шаблон группы фильтра
        * 4. Автоматический уникальный fallback с добавлением значения фильтра
        *
        * Важно:
        * На странице категории+фильтра НЕЛЬЗЯ оставлять description родительской категории,
        * иначе получаются дубли description.
        */

        $filterTitle = '';
        $filterDescription = '';
        $filterKeywords = '';
        $filterH1 = '';

        // 1. Ручное SEO связки категория + фильтр
        // Если поле заполнено вручную — используем его.
        // Переменные внутри ручного текста тоже подставляем.
        if (!empty($categoryFilterSeo['title'])) {
            $filterTitle = \ishop\App::renderInseo(
                (string)$categoryFilterSeo['title'],
                $inseoContext
            );
        }

        if (!empty($categoryFilterSeo['description'])) {
            $filterDescription = \ishop\App::renderInseo(
                (string)$categoryFilterSeo['description'],
                $inseoContext
            );
        }

        if (!empty($categoryFilterSeo['keywords'])) {
            $filterKeywords = \ishop\App::renderInseo(
                (string)$categoryFilterSeo['keywords'],
                $inseoContext
            );
        }

        if (!empty($categoryFilterSeo['seo_h1'])) {
            $filterH1 = \ishop\App::renderInseo(
                (string)$categoryFilterSeo['seo_h1'],
                $inseoContext
            );
        }

        // 2. INSEO группы фильтра
        // Используется только если нет ручного SEO для связки категория + фильтр.
        if ($filterInseo) {
            if (empty($filterTitle) && !empty($filterInseo->title)) {
                $filterTitle = \ishop\App::renderInseo(
                    (string)$filterInseo->title,
                    $inseoContext
                );
            }

            if (empty($filterDescription) && !empty($filterInseo->description)) {
                $filterDescription = \ishop\App::renderInseo(
                    (string)$filterInseo->description,
                    $inseoContext
                );
            }

            if (empty($filterKeywords) && !empty($filterInseo->keywords)) {
                $filterKeywords = \ishop\App::renderInseo(
                    (string)$filterInseo->keywords,
                    $inseoContext
                );
            }

            if (empty($filterH1) && !empty($filterInseo->name)) {
                $filterH1 = \ishop\App::renderInseo(
                    (string)$filterInseo->name,
                    $inseoContext
                );
            }
        }

        // 3. Автоматический SEO, если вручную и в INSEO ничего не заполнено
        if (empty($filterTitle)) {
            $filterTitle = $this->buildCategoryFilterTitle($category, $selectedAttr);
        }

        if (empty($filterH1)) {
            $categoryName = trim((string)($category->name ?? ''));
            $filterValue = trim((string)($selectedAttr->value ?? ''));

            if ($categoryName === '') {
                $categoryName = $this->mbUcfirst($this->getCategoryItemWord($category));
            }

            $filterH1 = trim($categoryName . ' ' . $filterValue);
        }

        if (empty($filterDescription)) {
            $filterDescription = $this->buildCategoryFilterDescription($category, $selectedAttr, $selectedAttrGroup);
        }

        if (empty($filterKeywords)) {
            $filterKeywords = $this->buildCategoryFilterKeywords($category, $selectedAttr);
        }

        $title = $filterTitle;
        $description = $filterDescription;
        $keywords = $filterKeywords;
        $name = $filterH1;

        $relatedSizes = \R::getAll(
            "SELECT DISTINCT
                av.id,
                av.value,
                av.alias,
                ag.url_params
            FROM attribute_value_related r
            JOIN attribute_value av
                ON av.id = r.related_attr_value_id
            JOIN attribute_group ag
                ON ag.id = av.attr_group_id
            JOIN attribute_value_category_canonical avcc
                ON avcc.attr_value_id = av.id
                AND avcc.category_id = ?
                AND avcc.is_active = 1
                AND avcc.mode = 'landing'
            WHERE r.attr_value_id = ?
            AND av.hide = 'show'
            AND EXISTS (
                    SELECT 1
                    FROM attribute_product ap
                    JOIN product p ON p.id = ap.product_id
                    WHERE ap.attr_id = av.id
                    AND p.hide = 'show'
                    AND p.category_id IN ($ids)
                    LIMIT 1
            )
            ORDER BY r.sort, av.value",
            [(int)$category->id, (int)$selectedAttr->id]
        );

        $technicsLinks = \R::getAll(
            "SELECT t.id, t.name, t.alias
            FROM attribute_value_technic at
            JOIN technics_type t ON t.id = at.technic_id
            WHERE at.attr_value_id = ? AND t.hide = 'show'
            ORDER BY at.sort, t.name",
            [$selectedAttr->id]
        );

        $faqRows = \R::getAll(
            "SELECT question, answer
            FROM attribute_value_category_faq
            WHERE attr_value_id = ?
            AND category_id = ?
            AND hide = 'show'
            ORDER BY sort, id
            LIMIT 20",
            [(int)$selectedAttr->id, (int)$category->id]
        );

        $filterTopContent = !empty($categoryFilterSeo['top_content'])
            ? \ishop\App::renderInseo((string)$categoryFilterSeo['top_content'], $inseoContext)
            : '';

        $filterBottomContent = !empty($categoryFilterSeo['content'])
            ? \ishop\App::renderInseo((string)$categoryFilterSeo['content'], $inseoContext)
            : '';

        $filterImage = !empty($categoryFilterSeo['img'])
            ? (string)$categoryFilterSeo['img']
            : '';

        $filterLanding = [
            'attr' => $selectedAttr,
            'group' => $selectedAttrGroup,
            'seo' => $categoryFilterSeo,
            'top_content' => $filterTopContent,
            'bottom_content' => $filterBottomContent,
            'img' => $filterImage,
        ];
    }

        /*
     * Хлебные крошки для страницы категории + фильтр:
     * /category/{category_alias}/{filter_alias}
     *
     * Важно:
     * - родительская категория должна быть ссылкой;
     * - текущий фильтр должен быть последней active-крошкой;
     * - берем $name до добавления "- Страница N".
     */
    if (!empty($selectedAttrId) && !empty($selectedAttrAlias)) {
        $filterBreadcrumbName = !empty($name)
            ? (string)$name
            : (!empty($selectedAttr->value) ? (string)$selectedAttr->value : (string)$selectedAttrAlias);

        $breadcrumbs = Breadcrumbs::getBreadcrumbs(
            $category->id,
            null,
            $alias,
            mb_strtolower($this->route['controller']),
            true
        );

        $breadcrumbs .= '<li class="breadcrumb-item active" aria-current="page">'
            . htmlspecialchars($filterBreadcrumbName, ENT_QUOTES, 'UTF-8')
            . '</li>';
    }

    // SEO для пагинации
    $isPaginatedPage = ($page > 1);

    if ($isPaginatedPage) {
        $baseSeoName = $name ?: ($category->name ?? '');
        $name = $baseSeoName . ' - Страница ' . $page;
        $title = $baseSeoName . ' - Страница ' . $page;
        $description = $baseSeoName . ' - Страница ' . $page;
    }

    $pathController = $this->route['controller'] ? '/' . mb_strtolower($this->route['controller']) : '';
    $pathAlias = $category->alias ? '/' . $category->alias : '';
    $pathFilter = $selectedAttrAlias ? '/' . $selectedAttrAlias : '';
    $canonicalUrl = PATH . $pathController . $pathAlias . $pathFilter;
    $metaRobots = '';

    if (
        !empty($selectedAttrId)
        && !empty($categoryFilterSeo['canonical_url'])
        && empty($_GET['filter'])
        && empty($_GET['sort'])
        && $page <= 1
    ) {
        $canonicalUrl = (string)$categoryFilterSeo['canonical_url'];
    }

    if (
        !empty($selectedAttrId)
        && !empty($categoryFilterSeo['robots'])
        && empty($_GET['filter'])
        && empty($_GET['sort'])
        && $page <= 1
    ) {
        $metaRobots = (string)$categoryFilterSeo['robots'];
    }

    if ($page > 1 && empty($_GET['filter']) && empty($_GET['sort'])) {
        $canonicalUrl .= '?page=' . $page;
    }

    $productWidgetContext = \app\widgets\product\Product::buildContext($products);

    $categoryFaqRows = \R::getAll(
        "SELECT question, answer
        FROM category_faq
        WHERE category_id = ?
        AND hide = 'show'
        AND question <> ''
        AND answer <> ''
        ORDER BY sort, id
        LIMIT 20",
        [(int)$category->id]
    );

    $relatedCategories = [];
    $categoryAlsoViewed = [];
    $categoryAlsoViewedWidgetContext = [];

    if ($page <= 1) {
        $relatedCategories = $this->buildRelatedCategoryLinks(
            $category,
            $selectedAttr,
            $selectedAttrAlias,
            $relatedSizes
        );

        $categoryAlsoViewed = $this->getCategoryAlsoViewedProducts($category, $selectedAttrId);
        $categoryAlsoViewedWidgetContext = !empty($categoryAlsoViewed)
            ? \app\widgets\product\Product::buildContext($categoryAlsoViewed)
            : [];
    }

    $this->setMeta(
        $title,
        $description,
        $keywords,
        App::$app->getProperty('shop_name'),
        PATH . '/images/' . App::$app->getProperty('og_logo'),
        $canonicalUrl
    );

    if ($this->isAjax()) {
        $this->loadView('filter', compact(
            'products',
            'total',
            'pagination',
            'ids',
            'inseo',
            'category',
            'quantity',
            'productWidgetContext'
        ));
        die;
    }

    $this->set(compact(
        'products',
        'breadcrumbs',
        'pagination',
        'total',
        'category',
        'ids',
        'inseo',
        'inseo_prod',
        'name',
        'selectedAttrId',
        'selectedAttrAlias',
        'selectedAttr',
        'selectedAttrGroup',
        'filterInseo',
        'categoryFilterSeo',
        'filterLanding',
        'relatedSizes',
        'technicsLinks',
        'faqRows',
        'extraFilterIds',
        'allFilterIds',
        'page',
        'podcategory',
        'quantity',
        'productWidgetContext',
        'categoryFaqRows',
        'metaRobots',
        'relatedCategories',
        'categoryAlsoViewed',
        'categoryAlsoViewedWidgetContext'
    ));
}

    protected function buildRelatedCategoryLinks($category, $selectedAttr = null, ?string $selectedAttrAlias = null, array $relatedSizes = []): array
    {
        $links = [];
        $categoryId = (int)($category->id ?? 0);
        $categoryAlias = trim((string)($category->alias ?? ''), '/');
        $selectedAlias = trim((string)($selectedAttrAlias ?? ($selectedAttr->alias ?? '')), '/');
        $selectedValue = trim((string)($selectedAttr->value ?? ''));

        foreach ($this->getRelatedProductCategoryRows($category) as $targetCategory) {
            if (count($links) >= 3) {
                break;
            }

            $targetAlias = trim((string)($targetCategory['alias'] ?? ''), '/');
            $targetName = trim((string)($targetCategory['name'] ?? ''));
            if ($targetAlias === '' || $targetName === '') {
                continue;
            }

            $links[] = [
                'name' => $targetName,
                'url' => PATH . '/category/' . rawurlencode($targetAlias),
            ];
        }

        $targetCategoryIds = $this->getCommercialTargetCategoryIds($category);

        if (!empty($targetCategoryIds)) {
            foreach ($this->getVisibleCategoryRowsByIds($targetCategoryIds) as $targetCategory) {
                if (count($links) >= 3) {
                    break;
                }

                $targetAlias = trim((string)($targetCategory['alias'] ?? ''), '/');
                $targetName = trim((string)($targetCategory['name'] ?? ''));
                if ($targetAlias === '' || $targetName === '') {
                    continue;
                }

                if ($selectedAlias !== '') {
                    $targetIds = $this->getCategoryIdsSql((int)$targetCategory['id']);
                    $hasProducts = (bool)\R::getCell(
                        "SELECT 1
                        FROM product p
                        JOIN attribute_product ap ON ap.product_id = p.id
                        JOIN attribute_value av ON av.id = ap.attr_id
                        WHERE p.hide = 'show'
                        AND p.category_id IN ($targetIds)
                        AND av.alias = ?
                        LIMIT 1",
                        [$selectedAlias]
                    );

                    if (!$hasProducts) {
                        continue;
                    }

                    $url = PATH . '/category/' . rawurlencode($targetAlias) . '/' . rawurlencode($selectedAlias);
                    if ($this->relatedLinkExists($links, $url)) {
                        continue;
                    }

                    $links[] = [
                        'name' => trim($targetName . ($selectedValue !== '' ? ' ' . $selectedValue : '')),
                        'url' => $url,
                    ];
                } else {
                    $url = PATH . '/category/' . rawurlencode($targetAlias);
                    if ($this->relatedLinkExists($links, $url)) {
                        continue;
                    }

                    $links[] = [
                        'name' => $targetName,
                        'url' => $url,
                    ];
                }
            }
        }

        if ($categoryAlias !== '' && !empty($relatedSizes)) {
            foreach ($relatedSizes as $relatedSize) {
                if (count($links) >= 3) {
                    break;
                }

                $alias = trim((string)($relatedSize['alias'] ?? ''), '/');
                $value = trim((string)($relatedSize['value'] ?? ''));
                if ($alias === '' || $value === '') {
                    continue;
                }

                $url = PATH . '/category/' . rawurlencode($categoryAlias) . '/' . rawurlencode($alias);
                if ($this->relatedLinkExists($links, $url)) {
                    continue;
                }

                $links[] = [
                    'name' => trim((string)($category->name ?? '') . ' ' . $value),
                    'url' => $url,
                ];
            }
        }

        if (count($links) < 3 && $categoryId > 0) {
            $parentId = (int)($category->parent_id ?? 0);
            if ($parentId > 0) {
                $siblings = \R::getAll(
                    "SELECT id, name, alias
                    FROM category
                    WHERE parent_id = ?
                    AND id <> ?
                    AND hide = 'show'
                    AND alias <> ''
                    ORDER BY id
                    LIMIT 6",
                    [$parentId, $categoryId]
                );

                foreach ($siblings as $sibling) {
                    if (count($links) >= 3) {
                        break;
                    }

                    $url = PATH . '/category/' . rawurlencode((string)$sibling['alias']);
                    if ($this->relatedLinkExists($links, $url)) {
                        continue;
                    }

                    $links[] = [
                        'name' => (string)$sibling['name'],
                        'url' => $url,
                    ];
                }
            }
        }

        $unique = [];
        foreach ($links as $link) {
            $url = (string)($link['url'] ?? '');
            if ($url === '' || isset($unique[$url])) {
                continue;
            }
            $unique[$url] = $link;
        }

        return array_slice(array_values($unique), 0, 3);
    }

    protected function relatedLinkExists(array $links, string $url): bool
    {
        foreach ($links as $link) {
            if (($link['url'] ?? '') === $url) {
                return true;
            }
        }

        return false;
    }

    protected function getCategoryAlsoViewedProducts($category, ?int $selectedAttrId = null): array
    {
        $targetCategoryIds = $this->getCommercialTargetCategoryIds($category);
        if (empty($targetCategoryIds)) {
            return [];
        }

        return $this->getRelatedProductsFromCategoryProducts($category, $targetCategoryIds, 5, $selectedAttrId);
    }

    protected function getRelatedProductsFromCategoryProducts($category, array $targetCategoryIds, int $limit, ?int $selectedAttrId = null): array
    {
        $sourceIds = $this->getCategoryIdsSql((int)($category->id ?? 0));
        $targetIds = implode(',', array_map('intval', $targetCategoryIds));
        if ($sourceIds === '' || $targetIds === '') {
            return [];
        }

        $sourceAttrJoin = '';
        $sourceAttrWhere = '';
        $params = [];
        if (!empty($selectedAttrId)) {
            $sourceAttrJoin = 'JOIN attribute_product src_ap ON src_ap.product_id = src.id';
            $sourceAttrWhere = 'AND src_ap.attr_id = ?';
            $params[] = (int)$selectedAttrId;
        }

        return \R::getAll(
            "SELECT p.*, COUNT(*) AS rel_score
            FROM related_product rp
            JOIN product src ON src.id = rp.product_id
            $sourceAttrJoin
            JOIN product p ON p.id = rp.related_id
            WHERE src.hide = 'show'
            AND src.category_id IN ($sourceIds)
            $sourceAttrWhere
            AND p.hide = 'show'
            AND p.price > 0
            AND p.stock_status_id = 1
            AND (
                (COALESCE(p.quantity, 0) - COALESCE(p.reserve, 0))
                + COALESCE((SELECT SUM(m.quantity) FROM modification m WHERE m.product_id = p.id), 0)
            ) > 0
            AND p.category_id IN ($targetIds)
            GROUP BY p.id
            ORDER BY rel_score DESC, p.quantity DESC, p.id DESC
            LIMIT $limit",
            $params
        );
    }

    protected function getRelatedProductCategoryRows($category): array
    {
        $sourceIds = $this->getCategoryIdsSql((int)($category->id ?? 0));
        if ($sourceIds === '') {
            return [];
        }

        $allowedIds = $this->getCommercialTargetCategoryIds($category);
        if (empty($allowedIds)) {
            return [];
        }
        $allowedSql = implode(',', array_map('intval', $allowedIds));

        return \R::getAll(
            "SELECT c.id, c.name, c.alias, COUNT(*) AS rel_score
            FROM related_product rp
            JOIN product src ON src.id = rp.product_id
            JOIN product p ON p.id = rp.related_id
            JOIN category c ON c.id = p.category_id
            WHERE src.hide = 'show'
            AND src.category_id IN ($sourceIds)
            AND p.hide = 'show'
            AND p.category_id IN ($allowedSql)
            AND c.hide = 'show'
            AND c.alias <> ''
            GROUP BY c.id
            ORDER BY rel_score DESC, c.id
            LIMIT 2"
        );
    }

    protected function getVisibleCategoryRowsByIds(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (empty($categoryIds)) {
            return [];
        }
        $ids = implode(',', $categoryIds);

        return \R::getAll(
            "SELECT id, name, alias
            FROM category
            WHERE id IN ($ids)
            AND hide = 'show'
            AND alias <> ''
            ORDER BY FIELD(id, $ids)"
        );
    }

    protected function getCommercialTargetCategoryIds($category): array
    {
        $id = (int)($category->id ?? 0);
        $parentId = (int)($category->parent_id ?? 0);

        $map = [
            18 => [26, 31, 32],
            9 => [27, 31, 32],
            19 => [28, 31, 32],
            20 => [30, 31, 32],
            26 => [18, 31, 32],
            27 => [9, 31, 32],
            28 => [19, 31, 32],
            30 => [20, 31, 32],
            31 => [18, 26, 9, 27, 19, 28, 20, 30],
            32 => [18, 26, 9, 27, 19, 28, 20, 30, 31],
            33 => [18, 19, 20, 26, 28, 30],
            1 => [31, 32, 26, 27, 28, 30],
            2 => [31, 32],
            3 => [18, 9, 19, 20, 31, 32],
            25 => [18, 9, 19, 20, 26, 27, 28, 30],
        ];

        if (isset($map[$id])) {
            return $map[$id];
        }

        if ($parentId === 1) {
            return [31, 32];
        }
        if ($parentId === 3) {
            return [31, 32, 18, 9, 19, 20];
        }
        if ($parentId === 25) {
            return [18, 9, 19, 20, 26, 27, 28, 30];
        }

        return [];
    }

    protected function getCategoryIdsSqlForRoots(array $rootIds): string
    {
        $all = [];
        foreach ($rootIds as $rootId) {
            $rootId = (int)$rootId;
            if ($rootId <= 0) {
                continue;
            }
            $ids = $this->getCategoryIdsSql($rootId);
            foreach (array_filter(array_map('intval', explode(',', $ids))) as $id) {
                $all[$id] = true;
            }
        }

        return implode(',', array_keys($all));
    }

    protected function getCategoryIdsSql(int $categoryId): string
    {
        $catModel = new Category();
        $ids = $catModel->getIds($categoryId);
        $ids = !$ids ? (string)$categoryId : $ids . $categoryId;

        return trim($ids, ',');
    }

    protected function isTyreCategory($category): bool
    {
        $id = (int)($category->id ?? 0);
        $parentId = (int)($category->parent_id ?? 0);

        return $id === 1 || $id === 2 || $parentId === 1;
    }

    protected function isDiskCategory($category): bool
    {
        $id = (int)($category->id ?? 0);
        $parentId = (int)($category->parent_id ?? 0);

        return $id === 3 || $parentId === 3;
    }

    protected function isCameraCategory($category): bool
    {
        $id = (int)($category->id ?? 0);
        $parentId = (int)($category->parent_id ?? 0);

        return $id === 25 || $parentId === 25;
    }

    protected function getCategoryItemWord($category): string
    {
        $alias = trim((string)($category->alias ?? ''), '/');

        $map = [
            'kamery-i-obodnye-lenty' => 'камеры и ободные ленты',
            'kamery' => 'камеры',
            'obodnye-lenty' => 'ободные ленты',

            'industrialnye-shiny' => 'шины',
            'shiny-dlya-minipogruzchikov' => 'шины',
            'shiny-dlya-kolesnyh-ekskavatorov' => 'шины',
            'shiny-dlya-vilochnyh-pogruzchikov' => 'шины',
            'shiny-dlya-shahtnoy-tehniki' => 'шины',
            'shiny-dlya-frontalnyh-pogruzchikov' => 'шины',
            'shiny-dlya-gruntovyh-katkov' => 'шины',
            'shiny-dlya-greyderov' => 'шины',
            'shiny-dlya-mobilnyh-kranov' => 'шины',
            'atv' => 'шины',
        ];

        return $map[$alias] ?? 'товары';
    }

    protected function getCategoryGenitiveWord($category): string
    {
        $alias = trim((string)($category->alias ?? ''), '/');

        $map = [
            'kamery-i-obodnye-lenty' => 'камер и ободных лент',
            'kamery' => 'камер',
            'obodnye-lenty' => 'ободных лент',

            'industrialnye-shiny' => 'шин',
            'shiny-dlya-minipogruzchikov' => 'шин',
            'shiny-dlya-kolesnyh-ekskavatorov' => 'шин',
            'shiny-dlya-vilochnyh-pogruzchikov' => 'шин',
            'shiny-dlya-shahtnoy-tehniki' => 'шин',
            'shiny-dlya-frontalnyh-pogruzchikov' => 'шин',
            'shiny-dlya-gruntovyh-katkov' => 'шин',
            'shiny-dlya-greyderov' => 'шин',
            'shiny-dlya-mobilnyh-kranov' => 'шин',
            'atv' => 'шин',
        ];

        return $map[$alias] ?? 'товаров';
    }

    protected function mbUcfirst(string $string): string
    {
        $string = trim($string);
        if ($string === '') {
            return $string;
        }

        $first = mb_substr($string, 0, 1, 'UTF-8');
        $rest  = mb_substr($string, 1, null, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8') . $rest;
    }

    protected function replaceWordByCase(string $sourceWord, string $replacement, string $matched): string
    {
        $firstChar = mb_substr($matched, 0, 1, 'UTF-8');
        $isUpper = ($firstChar === mb_strtoupper($firstChar, 'UTF-8'));

        return $isUpper ? $this->mbUcfirst($replacement) : $replacement;
    }

    protected function adaptFilterTextByCategory(string $text, $category): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        $itemWord = $this->getCategoryItemWord($category);
        $genitiveWord = $this->getCategoryGenitiveWord($category);

        $text = preg_replace_callback('/\bшины\b/ui', function ($m) use ($itemWord) {
            return $this->replaceWordByCase('шины', $itemWord, $m[0]);
        }, $text);

        $text = preg_replace_callback('/\bшин\b/ui', function ($m) use ($genitiveWord) {
            return $this->replaceWordByCase('шин', $genitiveWord, $m[0]);
        }, $text);

        return $text;
    }

    protected function findApplicableFilterValue(string $filterAlias, string $categoryIds)
    {
        $filterAlias = trim($filterAlias);
        $categoryIds = trim($categoryIds, ',');

        if ($filterAlias === '' || $categoryIds === '') {
            return null;
        }

        $rows = \R::getAll(
            "SELECT av.id, COUNT(DISTINCT p.id) AS product_count
             FROM attribute_value av
             INNER JOIN attribute_product ap ON ap.attr_id = av.id
             INNER JOIN product p ON p.id = ap.product_id
             WHERE av.alias = ?
               AND av.hide = 'show'
               AND p.hide = 'show'
               AND p.category_id IN ($categoryIds)
             GROUP BY av.id
             ORDER BY product_count DESC, av.id ASC
             LIMIT 1",
            [$filterAlias]
        );

        if (empty($rows[0]['id'])) {
            return null;
        }

        return \R::load('attribute_value', (int)$rows[0]['id']);
    }

    protected function getNamedFilterIdsFromQuery(): array
    {
        $ids = [];

        foreach ($_GET as $key => $rawValue) {
            if (!is_string($key) || strpos($key, 'filter_') !== 0) {
                continue;
            }

            $groupUrl = trim(substr($key, 7));
            if ($groupUrl === '') {
                continue;
            }

            $group = \R::findOne('attribute_group', 'url_params = ?', [$groupUrl]);
            if (!$group || empty($group->id)) {
                continue;
            }

            $values = is_array($rawValue) ? $rawValue : explode(',', (string)$rawValue);

            foreach ($values as $valueAlias) {
                $valueAlias = trim(rawurldecode((string)$valueAlias), '/');

                if ($valueAlias === '') {
                    continue;
                }

                $attrId = (int)\R::getCell(
                    "SELECT id
                    FROM attribute_value
                    WHERE attr_group_id = ?
                      AND alias = ?
                      AND hide = 'show'
                    LIMIT 1",
                    [(int)$group->id, $valueAlias]
                );

                if ($attrId > 0) {
                    $ids[] = $attrId;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function buildCategoryFilterTitle($category, $selectedAttr): string
    {
        $categoryName = trim((string)($category->name ?? ''));
        $filterValue = trim((string)($selectedAttr->value ?? ''));

        if ($categoryName === '') {
            $categoryName = $this->mbUcfirst($this->getCategoryItemWord($category));
        }

        if ($filterValue === '') {
            return $categoryName . ' купить в ИТС-Центре';
        }

        return $categoryName . ' ' . $filterValue . ' купить в ИТС-Центре';
    }

    protected function buildCategoryFilterDescription($category, $selectedAttr, $selectedAttrGroup = null): string
    {
        $categoryName = trim((string)($category->name ?? ''));
        $filterValue  = trim((string)($selectedAttr->value ?? ''));
        $filterAlias  = trim((string)($selectedAttr->alias ?? ''));

        $groupName = mb_strtolower(trim((string)(
            $selectedAttrGroup->name
            ?? $selectedAttrGroup->title
            ?? $selectedAttrGroup->url_params
            ?? ''
        )), 'UTF-8');

        if ($categoryName === '') {
            $categoryName = $this->mbUcfirst($this->getCategoryItemWord($category));
        }

        if ($filterValue === '') {
            $filterValue = $filterAlias;
        }

        $lowerCategoryName = mb_strtolower($categoryName, 'UTF-8');

        if ($filterValue === '') {
            return $categoryName . ' купить в ИТС-Центре. Подбор по размеру, назначению, наличию и цене. Доставка по России.';
        }

        // Производитель / бренд
        if (
            mb_stripos($groupName, 'производ') !== false ||
            mb_stripos($groupName, 'бренд') !== false ||
            mb_stripos($groupName, 'brand') !== false ||
            mb_stripos($groupName, 'manufacturer') !== false
        ) {
            return $categoryName . ' производителя ' . $filterValue . ' купить в ИТС-Центре. Подбор ' . $lowerCategoryName . ' бренда ' . $filterValue . ' по размеру, наличию и цене. Доставка по России.';
        }

        // Размер / типоразмер
        if (
            mb_stripos($groupName, 'размер') !== false ||
            mb_stripos($groupName, 'типоразмер') !== false ||
            mb_stripos($groupName, 'size') !== false
        ) {
            return $categoryName . ' размера ' . $filterValue . ' купить в ИТС-Центре. Подбор по типоразмеру, рисунку протектора, наличию и цене. Доставка по России.';
        }

        // Посадочный диаметр / диск
        if (
            mb_stripos($groupName, 'диск') !== false ||
            mb_stripos($groupName, 'посад') !== false ||
            mb_stripos($groupName, 'rim') !== false
        ) {
            return $categoryName . ' на диск ' . $filterValue . ' купить в ИТС-Центре. Подбор ' . $lowerCategoryName . ' по посадочному диаметру, наличию и цене. Доставка по России.';
        }

        $type = $this->detectFilterValueType($filterValue, $filterAlias);

        if ($type === 'size') {
            return $categoryName . ' размера ' . $filterValue . ' купить в ИТС-Центре. Подбор по типоразмеру, рисунку протектора, наличию и цене. Доставка по России.';
        }

        if ($type === 'rim') {
            return $categoryName . ' на диск ' . $filterValue . ' купить в ИТС-Центре. Подбор ' . $lowerCategoryName . ' по посадочному диаметру, наличию и цене. Доставка по России.';
        }

        if ($type === 'brand') {
            return $categoryName . ' производителя ' . $filterValue . ' купить в ИТС-Центре. Подбор ' . $lowerCategoryName . ' бренда ' . $filterValue . ' по размеру, наличию и цене. Доставка по России.';
        }

        return $categoryName . ' ' . $filterValue . ' купить в ИТС-Центре. Подбор ' . $lowerCategoryName . ' по параметру ' . $filterValue . ', наличию и цене. Доставка по России.';
    }

    protected function buildCategoryFilterKeywords($category, $selectedAttr): string
    {
        $categoryName = trim((string)($category->name ?? ''));
        $filterValue = trim((string)($selectedAttr->value ?? ''));

        if ($categoryName === '') {
            $categoryName = $this->getCategoryItemWord($category);
        }

        if ($filterValue === '') {
            return mb_strtolower($categoryName, 'UTF-8');
        }

        return mb_strtolower($categoryName . ', ' . $categoryName . ' ' . $filterValue . ', купить ' . $categoryName . ' ' . $filterValue, 'UTF-8');
    }

    protected function detectFilterValueType(string $value, string $alias = ''): string
    {
        $value = trim($value);
        $alias = trim($alias);

        $check = mb_strtolower($value . ' ' . $alias, 'UTF-8');

        /*
        * Размеры:
        * 25x8-12
        * 4.00-8
        * 17.5-25
        * 12-00r20
        * 275/70R22.5
        * 145/70-6
        * 13/80-20
        */
        if (preg_match('~\d+([.,/xх\-]\d+)+~ui', $check)) {
            return 'size';
        }

        /*
        * Посадочный диаметр:
        * 12-inches
        * 14-inches
        * 12 inches
        */
        if (preg_match('~\b\d+\s*[- ]?\s*inches\b~ui', $check)) {
            return 'rim';
        }

        /*
        * Если значение похоже на короткий бренд/производителя.
        * Например: EKKA, ATLAS.
        */
        if (
            preg_match('~^[a-zа-я0-9\-\s]{2,30}$~ui', $value)
            && !preg_match('~\d+([.,/xх\-]\d+)+~ui', $value)
            && !preg_match('~pokrytie|grunt|pes|pesch|рисунок|protektor|p\d+~ui', $check)
        ) {
            return 'brand';
        }

        return 'param';
    }

    protected function getCategoryFilterSeoRow(int $attrValueId, int $categoryId): ?array
    {
        $canonicalSelect = $this->tableHasColumn('attribute_value_category_canonical', 'canonical_url')
            ? 'avcc.canonical_url'
            : "'' AS canonical_url";
        $robotsSelect = $this->tableHasColumn('attribute_value_category_canonical', 'robots')
            ? 'avcc.robots'
            : "'' AS robots";

        $row = \R::getRow(
            "SELECT
                avcc.id,
                avcc.attr_value_id,
                avcc.category_id,
                avcc.is_active,
                avcc.mode,
                avcc.source,
                avcc.redirect_category_id,
                avcc.seo_h1,
                avcc.title,
                avcc.description,
                avcc.keywords,
                avcc.top_content,
                avcc.content,
                avcc.img,
                {$canonicalSelect},
                {$robotsSelect},
                rc.alias AS redirect_category_alias
            FROM attribute_value_category_canonical avcc
            LEFT JOIN category rc ON rc.id = avcc.redirect_category_id
            WHERE avcc.attr_value_id = ?
            AND avcc.category_id = ?
            AND avcc.is_active = 1
            LIMIT 1",
            [$attrValueId, $categoryId]
        );

        return !empty($row) ? $row : null;
    }

    protected function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;

        if (!array_key_exists($key, $cache)) {
            try {
                $cache[$key] = (bool)\R::getCell(
                    "SHOW COLUMNS FROM {$table} LIKE ?",
                    [$column]
                );
            } catch (\Throwable $e) {
                $cache[$key] = false;
            }
        }

        return $cache[$key];
    }
}
