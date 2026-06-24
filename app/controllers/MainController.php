<?php

namespace app\controllers;

use ishop\App;

class MainController extends AppController
{
    public function indexAction()
    {
        $brands = \R::find('brand', 'LIMIT 3');
        $hitCandidates = \R::getAll("
            SELECT p.*
                 , (
                    COALESCE(p.quantity, 0)
                    + COALESCE(m.modification_quantity, 0)
                    - COALESCE(p.reserve, 0)
                 ) AS available_quantity
                 , COALESCE(r.review_count, 0) AS review_count
                 , COALESCE(r.rating_avg, 0) AS rating_avg
                 , CASE WHEN a.product_id IS NULL THEN 0 ELSE 1 END AS has_action
            FROM product p
            LEFT JOIN (
                SELECT product_id, SUM(quantity) AS modification_quantity
                FROM modification
                GROUP BY product_id
            ) m ON m.product_id = p.id
            LEFT JOIN (
                SELECT
                    rp.product_id,
                    COUNT(rp.review_id) AS review_count,
                    AVG(rv.point) AS rating_avg
                FROM review_product rp
                JOIN review rv ON rv.id = rp.review_id
                GROUP BY rp.product_id
            ) r ON r.product_id = p.id
            LEFT JOIN (
                SELECT DISTINCT product_id
                FROM actions
                WHERE hide = 'show'
                  AND date_end > NOW()
            ) a ON a.product_id = p.id
            WHERE p.hit = '1'
              AND p.hide = 'show'
              AND (
                  COALESCE(p.quantity, 0)
                  + COALESCE(m.modification_quantity, 0)
                  - COALESCE(p.reserve, 0)
              ) > 0
            ORDER BY
                has_action DESC,
                p.new_product DESC,
                rating_avg DESC,
                review_count DESC,
                available_quantity DESC,
                p.id DESC
            LIMIT 30
        ");
        $hits = $this->diversifyHits($hitCandidates, 10);
        $articles = \R::find('contents', "type_id = '9' AND hide = 'show' ORDER BY id DESC LIMIT 4");

        $hitsWidgetContext = \app\widgets\product\Product::buildContext($hits);

        /* SEO */
        $title = trim((string)\R::getCell(
            "SELECT znachenie FROM options WHERE tip = ? AND alt_name = ? LIMIT 1",
            ['seo', 'option_name']
        ));

        $desc = trim((string)\R::getCell(
            "SELECT znachenie FROM options WHERE tip = ? AND alt_name = ? LIMIT 1",
            ['seo', 'option_description']
        ));

        $keywords = trim((string)\R::getCell(
            "SELECT znachenie FROM options WHERE tip = ? AND alt_name = ? LIMIT 1",
            ['seo', 'option_keywords']
        ));

        $shopName = (string)(App::$app->getProperty('shop_name') ?? 'ИТС-Центр');
        $ogLogo = App::$app->getProperty('og_logo');
        $ogImage = !empty($ogLogo) ? PATH . '/images/' . $ogLogo : '';

        $this->setMeta(
            $title,
            $desc,
            $keywords,
            $shopName,
            $ogImage,
            PATH
        );
        /* SEO */

        $this->set(compact('brands', 'hits', 'articles', 'hitsWidgetContext'));
    }

    private function diversifyHits(array $products, int $limit): array
    {
        $buckets = [];

        foreach ($products as $product) {
            $key = $this->hitDiversityKey($product);
            $buckets[$key][] = $product;
        }

        $result = [];
        $lastKey = null;
        $lastMeta = null;

        while (count($result) < $limit && !empty($buckets)) {
            $selectedKey = null;
            $selectedSize = -1;
            $selectedPenalty = PHP_INT_MAX;

            foreach ($buckets as $key => $items) {
                if ($key === $lastKey && count($buckets) > 1) {
                    continue;
                }

                $penalty = $this->hitDiversityPenalty($items[0], $lastMeta);
                $size = count($items);
                if ($penalty < $selectedPenalty || ($penalty === $selectedPenalty && $size > $selectedSize)) {
                    $selectedKey = $key;
                    $selectedSize = $size;
                    $selectedPenalty = $penalty;
                }
            }

            if ($selectedKey === null) {
                $selectedKey = array_key_first($buckets);
            }

            $selectedProduct = array_shift($buckets[$selectedKey]);
            $result[] = $selectedProduct;
            $lastKey = $selectedKey;
            $lastMeta = $this->hitDiversityMeta($selectedProduct);

            if (empty($buckets[$selectedKey])) {
                unset($buckets[$selectedKey]);
            }
        }

        return $result;
    }

    private function hitDiversityKey(array $product): string
    {
        $meta = $this->hitDiversityMeta($product);

        return $meta['category_id'] . ':' . $meta['brand_id'] . ':' . $meta['model'];
    }

    private function hitDiversityMeta(array $product): array
    {
        $categoryId = (int)($product['category_id'] ?? 0);
        $brandId = (int)($product['brand_id'] ?? 0);
        $model = trim((string)($product['model'] ?? ''));

        if ($model === '') {
            $name = trim((string)($product['name'] ?? ''));
            $nameParts = preg_split('/\s+/', $name);
            $model = implode(' ', array_slice($nameParts ?: [], 1, 2));
        }

        $model = mb_strtolower($model);

        return [
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'model' => $model,
        ];
    }

    private function hitDiversityPenalty(array $product, ?array $lastMeta): int
    {
        if ($lastMeta === null) {
            return 0;
        }

        $meta = $this->hitDiversityMeta($product);
        $penalty = 0;

        if ($meta['category_id'] === $lastMeta['category_id']) {
            $penalty += 100;
        }
        if ($meta['brand_id'] === $lastMeta['brand_id']) {
            $penalty += 30;
        }
        if ($meta['model'] === $lastMeta['model']) {
            $penalty += 20;
        }

        return $penalty;
    }
}
