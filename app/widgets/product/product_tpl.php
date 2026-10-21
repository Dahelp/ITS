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
$isCompleteCard = false;
$cardUrl = 'product/' . $alias;
$cardImageUrl = 'images/product/mini/' . $img;
require __DIR__ . '/card.php';
