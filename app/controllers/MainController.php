<?php

namespace app\controllers;

use ishop\App;

class MainController extends AppController
{
    public function indexAction()
    {
        $brands = \R::find('brand', 'LIMIT 3');
        $hits = \R::getAll("
            SELECT p.*
            FROM product p
            LEFT JOIN (
                SELECT product_id, SUM(quantity) AS modification_quantity
                FROM modification
                GROUP BY product_id
            ) m ON m.product_id = p.id
            WHERE p.hit = '1'
              AND p.hide = 'show'
              AND (
                  COALESCE(p.quantity, 0)
                  + COALESCE(m.modification_quantity, 0)
                  - COALESCE(p.reserve, 0)
              ) > 0
            LIMIT 10
        ");
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
}
