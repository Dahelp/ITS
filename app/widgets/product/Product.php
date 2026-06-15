<?php

namespace app\widgets\product;

class Product
{
    public $product;
    public $tpl;
    public $curr;
    public $context = [];

    public function __construct($product, $curr, $tpl = '', array $context = [])
    {
        $this->tpl = $tpl ?: __DIR__ . '/product_tpl.php';
        $this->context = $context;
        $this->run($product, $curr);
    }

    protected function run($product, $curr)
    {
        $context = $this->context;
        require $this->tpl;
    }

    public static function buildContext(array $products): array
    {
        $productIds = [];
        $categoryIds = [];

        foreach ($products as $product) {
            $pid = (int)($product['id'] ?? $product->id ?? 0);
            $cid = (int)($product['category_id'] ?? $product->category_id ?? 0);

            if ($pid > 0) {
                $productIds[] = $pid;
            }
            if ($cid > 0) {
                $categoryIds[] = $cid;
            }
        }

        $productIds = array_values(array_unique(array_filter($productIds)));
        $categoryIds = array_values(array_unique(array_filter($categoryIds)));

        $context = [
            'categoryById' => [],
            'productInseoByCategoryId' => [],
            'seoNameByProductId' => [],
            'actionByProductId' => [],
            'wishlistedByProductId' => [],
            'reviewByProductId' => [],
            'modificationsByProductId' => [],
            'companyTip' => null,
            'companyTypepriceByCategoryId' => [],
        ];

        if (empty($productIds)) {
            return $context;
        }

        // categories
        if (!empty($categoryIds)) {
            $slots = implode(',', array_fill(0, count($categoryIds), '?'));

            $rows = \R::getAll(
                "SELECT id, name
                 FROM category
                 WHERE id IN ($slots)",
                $categoryIds
            );

            foreach ($rows as $row) {
                $context['categoryById'][(int)$row['id']] = $row;
            }

            $rows = \R::getAll(
                "SELECT category_id, name
                 FROM plagins_inseo
                 WHERE tip = 'product'
                   AND hide = 'show'
                   AND category_id IN ($slots)",
                $categoryIds
            );

            foreach ($rows as $row) {
                $context['productInseoByCategoryId'][(int)$row['category_id']] = $row;
            }
        }

        // seo names
        foreach ($products as $product) {
            $pid = (int)($product['id'] ?? $product->id ?? 0);
            $cid = (int)($product['category_id'] ?? $product->category_id ?? 0);

            if ($pid <= 0) {
                continue;
            }

            $seoTpl = (string)($context['productInseoByCategoryId'][$cid]['name'] ?? '');
            if ($seoTpl !== '') {
                $context['seoNameByProductId'][$pid] = (string)\ishop\App::seoreplace($seoTpl, $pid);
            } else {
                $context['seoNameByProductId'][$pid] = (string)($product['name'] ?? $product->name ?? '');
            }
        }

        // actions
        $slots = implode(',', array_fill(0, count($productIds), '?'));
        $params = $productIds;
        $params[] = date('Y-m-d H:i:s');

        $rows = \R::getAll(
            "SELECT product_id, type_id, znachenie
             FROM actions
             WHERE hide = 'show'
               AND product_id IN ($slots)
               AND date_end > ?",
            $params
        );

        foreach ($rows as $row) {
            $context['actionByProductId'][(int)$row['product_id']] = $row;
        }

        // reviews
        $rows = \R::getAll(
            "SELECT
                rp.product_id,
                COUNT(rp.review_id) AS review_count,
                COALESCE(SUM(r.point), 0) AS rating_sum
             FROM review_product rp
             JOIN review r ON r.id = rp.review_id
             WHERE rp.product_id IN ($slots)
             GROUP BY rp.product_id",
            $productIds
        );

        foreach ($rows as $row) {
            $pid = (int)$row['product_id'];
            $cnt = (int)$row['review_count'];
            $sum = (float)$row['rating_sum'];

            $context['reviewByProductId'][$pid] = [
                'count' => $cnt,
                'rating' => $cnt > 0 ? round($sum / $cnt, 1) : 0.0,
            ];
        }

        // modifications
        $rows = \R::getAll(
            "SELECT product_id, quantity, price
             FROM modification
             WHERE product_id IN ($slots)",
            $productIds
        );

        foreach ($rows as $row) {
            $pid = (int)$row['product_id'];
            if (!isset($context['modificationsByProductId'][$pid])) {
                $context['modificationsByProductId'][$pid] = [];
            }
            $context['modificationsByProductId'][$pid][] = $row;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId > 0) {
            $params = [$userId];
            foreach ($productIds as $pid) {
                $params[] = $pid;
            }

            $slots = implode(',', array_fill(0, count($productIds), '?'));

            $rows = \R::getAll(
                "SELECT product_id
                 FROM product_wishlists
                 WHERE user_id = ?
                   AND product_id IN ($slots)",
                $params
            );

            foreach ($rows as $row) {
                $context['wishlistedByProductId'][(int)$row['product_id']] = true;
            }

            $company = \R::getRow(
                "SELECT tip, id
                 FROM company
                 WHERE user_id = ?
                 LIMIT 1",
                [$userId]
            );

            $context['companyTip'] = $company['tip'] ?? null;
            $companyId = (int)($company['id'] ?? 0);

            if ($companyId > 0 && !empty($categoryIds)) {
                $slots = implode(',', array_fill(0, count($categoryIds), '?'));
                $params = array_merge([$companyId], $categoryIds);

                $rows = \R::getAll(
                    "SELECT category_id, znachenie
                     FROM company_typeprice
                     WHERE company_id = ?
                       AND category_id IN ($slots)",
                    $params
                );

                foreach ($rows as $row) {
                    $context['companyTypepriceByCategoryId'][(int)$row['category_id']] = $row;
                }
            }
        }

        return $context;
    }
}