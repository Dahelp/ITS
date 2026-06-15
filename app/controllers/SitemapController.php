<?php

namespace app\controllers;
use ishop\App;

class SitemapController extends AppController {

    public function indexAction(){
		
		$sm_category = \R::getAll("SELECT alias, name, id FROM `category` WHERE hide='show' AND parent_id = '0'");
        $sm_category_children = [];
        $sm_category_parent = \R::getAll("SELECT alias, name, parent_id FROM `category` WHERE hide='show' AND parent_id <> '0' ORDER BY name");

        foreach ($sm_category_parent as $childCategory) {
            $parentId = (int)($childCategory['parent_id'] ?? 0);

            if ($parentId <= 0) {
                continue;
            }

            $sm_category_children[$parentId][] = $childCategory;
        }
        $filterLandingRobotsWhere = '';

        try {
            if (\R::getCell("SHOW COLUMNS FROM attribute_value_category_canonical LIKE 'robots'")) {
                $filterLandingRobotsWhere = " AND (avcc.robots IS NULL OR avcc.robots = '' OR avcc.robots NOT LIKE 'noindex%')";
            }
        } catch (\Throwable $e) {
            $filterLandingRobotsWhere = '';
        }

		$filterLandingRows = \R::getAll(
            "SELECT
                avcc.category_id,
                avcc.attr_value_id,
                c.alias AS category_alias,
                c.name AS category_name,
                av.alias AS value_alias,
                av.value AS value_name
            FROM attribute_value_category_canonical avcc
            INNER JOIN category c ON c.id = avcc.category_id
            INNER JOIN attribute_value av ON av.id = avcc.attr_value_id
            WHERE avcc.is_active = 1
              AND avcc.mode = 'landing'
              {$filterLandingRobotsWhere}
              AND c.hide = 'show'
              AND av.hide = 'show'
              AND c.alias <> ''
              AND av.alias <> ''
            ORDER BY c.name ASC, av.value ASC"
        );

        $sm_filter_landings = [];
        $catModel = new \app\models\Category();

        foreach ($filterLandingRows as $row) {
            $categoryId = (int)($row['category_id'] ?? 0);
            $attrValueId = (int)($row['attr_value_id'] ?? 0);

            if ($categoryId <= 0 || $attrValueId <= 0) {
                continue;
            }

            $categoryIds = $catModel->getIds($categoryId);
            $categoryIds = !$categoryIds ? (string)$categoryId : $categoryIds . $categoryId;
            $hasProducts = (int)\R::getCell(
                "SELECT COUNT(DISTINCT p.id)
                FROM product p
                INNER JOIN attribute_product ap ON ap.product_id = p.id
                WHERE p.hide = 'show'
                  AND p.category_id IN ($categoryIds)
                  AND ap.attr_id = ?",
                [$attrValueId]
            );

            if ($hasProducts < 1) {
                continue;
            }

            $sm_filter_landings[] = $row;
        }
					
		$this->setMeta('Карта сайта', 'Карта сайта its-center.ru');
		$this->set(compact('sm_category', 'sm_category_children', 'sm_filter_landings'));
    }
}
