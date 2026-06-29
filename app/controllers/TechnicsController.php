<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;

class TechnicsController extends AppController
{
    public function viewAction()
{
    $alias = trim((string)($this->route['alias'] ?? ''));
    if ($alias === '') {
        throw new \Exception('Страница не найдена', 404);
    }

    $technics = \R::findOne('technics', 'alias = ?', [$alias]);

    if (!$technics) {
        throw new \Exception('Страница не найдена', 404);
    }

    $type = \R::findOne('technics_type', 'id = ?', [$technics->type_id]);
    $manufacturer = \R::findOne('technics_manufacturer', 'id = ?', [$technics->manufacturer_id]);

    if (!$type) {
        throw new \Exception('Тип техники не найден', 404);
    }

    if (!$manufacturer) {
        throw new \Exception('Производитель не найден', 404);
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perpage = (int)App::$app->getProperty('pagination');

    $title = "Шины на {$type->name} {$manufacturer->name} {$technics->model}";
    $description = "Компания «ИТС-Центр» предлагает купить новую высококачественную и надежную резину на спецтехнику по выгодным ценам. {$type->name} {$manufacturer->name} {$technics->model} отличается удобным управлением, простотой обслуживания и долговечностью.";
    $keywords = "купить шины, на {$type->name}, {$manufacturer->name} {$technics->model}";

    $path_controller = $this->buildPathPart($this->route['controller'] ?? '', true);
    $path_alias = $this->buildPathPart($this->route['alias'] ?? '');

    $technics_img = !empty($technics->img)
        ? PATH . '/images/technics/mini/' . $technics->img
        : PATH . '/images/' . App::$app->getProperty('og_logo');

    $this->setMeta(
        $title,
        $description,
        $keywords,
        App::$app->getProperty('shop_name'),
        $technics_img,
        PATH . $path_controller . $path_alias
    );

    $groupedSizes = $this->getTechnicsSizesGrouped((int)$technics->id);

    $allSizes = array_merge(
        $groupedSizes['1'] ?? [],
        $groupedSizes['2'] ?? [],
        $groupedSizes['3'] ?? [],
        $groupedSizes['4'] ?? []
    );

    $preferredCategoryAlias = null;
    if (!empty($type->category)) {
        $preferredCategoryAlias = trim((string)$type->category, " \t\n\r\0\x0B/");
        if ($preferredCategoryAlias === '') {
            $preferredCategoryAlias = null;
        }
    }

    $sizeLinksMap = $this->getAvailableSizeUrls($allSizes, $preferredCategoryAlias);

    // Все size values одной техникой
    $sizeValues = [];
    foreach ($allSizes as $item) {
        $val = trim((string)($item['value'] ?? ''));
        if ($val !== '') {
            $sizeValues[] = $val;
        }
    }
    $sizeValues = array_values(array_unique($sizeValues));

    // value_id по текстовым размерам
    $attributeValueRows = [];
    if (!empty($sizeValues)) {
        $slots = implode(',', array_fill(0, count($sizeValues), '?'));
        $attributeValueRows = \R::getAll(
            "SELECT id, value
             FROM attribute_value
             WHERE value IN ($slots)",
            $sizeValues
        );
    }

    $attributeValueIds = [];
    foreach ($attributeValueRows as $row) {
        $attributeValueIds[] = (int)$row['id'];
    }
    $attributeValueIds = array_values(array_unique(array_filter($attributeValueIds)));

    // product ids по attr_id
    $productIds = [];
    if (!empty($attributeValueIds)) {
        $slots = implode(',', array_fill(0, count($attributeValueIds), '?'));
        $productIdRows = \R::getAll(
            "SELECT DISTINCT ap.product_id
             FROM attribute_product ap
             JOIN product p ON p.id = ap.product_id
             WHERE ap.attr_id IN ($slots)
               AND p.hide = 'show'",
            $attributeValueIds
        );

        foreach ($productIdRows as $row) {
            $productIds[] = (int)$row['product_id'];
        }
    }
    $productIds = array_values(array_unique(array_filter($productIds)));

    $products = [];
    if (!empty($productIds)) {
        $slots = implode(',', array_fill(0, count($productIds), '?'));
        $products = \R::find(
            'product',
            "hide = 'show' AND id IN ($slots)",
            $productIds
        );
    }

    // Комплекты без N+1
    $complete = [];
    $completeItemsById = [];

    if (!empty($productIds)) {
        $slots = implode(',', array_fill(0, count($productIds), '?'));

        $complete = \R::getAll(
            "SELECT c.*
             FROM plagins_complete_product cp
             JOIN plagins_complete c ON c.id = cp.complete_id
             WHERE cp.product_id IN ($slots)
             GROUP BY c.id
             ORDER BY c.id DESC",
            $productIds
        );

        $completeIds = [];
        foreach ($complete as $row) {
            $completeIds[] = (int)$row['id'];
        }
        $completeIds = array_values(array_unique(array_filter($completeIds)));

        if (!empty($completeIds)) {
            $slots = implode(',', array_fill(0, count($completeIds), '?'));
            $completeItems = \R::getAll(
                "SELECT
                    pcp.complete_id,
                    p.name,
                    p.price as price,
                    p.quantity,
                    pcp.product_id,
                    pcp.qty,
                    pcp.price as price_complete,
                    pcp.discount
                 FROM plagins_complete_product pcp
                 JOIN product p ON pcp.product_id = p.id
                 WHERE pcp.complete_id IN ($slots)
                 ORDER BY pcp.complete_id, p.name",
                $completeIds
            );

            foreach ($completeItems as $item) {
                $cid = (int)$item['complete_id'];
                $completeItemsById[$cid][] = $item;
            }
        }
    }

    $productWidgetContext = \app\widgets\product\Product::buildContext(array_values($products ?: []));

    $administr = null;
    $sessionUserId = (int)($_SESSION['user']['id'] ?? 0);
    if ($sessionUserId > 0) {
        $administr = \R::findOne('user', 'id = ?', [$sessionUserId]);
    }

    $this->set(compact(
        'technics',
        'type',
        'manufacturer',
        'page',
        'perpage',
        'groupedSizes',
        'sizeLinksMap',
        'preferredCategoryAlias',
        'products',
        'complete',
        'completeItemsById',
        'productWidgetContext',
        'administr'
    ));
}

    public function indexAction()
    {
        $technics = \R::getAll("SELECT * FROM technics_type WHERE hide = 'show'");

        $title = 'Подбор шин, дисков, камер и фильтров по типу техники';
        $description = 'Выберите тип техники — перейдите к каталогу шин, дисков, камер и фильтров, подходящих для вашей машины. Для погрузчиков, экскаваторов, грейдеров, катков, квадроциклов и другой спецтехники';

        $technics_img = PATH . '/images/' . App::$app->getProperty('og_logo');

        $this->setMeta(
            $title,
            $description,
            $keywords,
            App::$app->getProperty('shop_name'),
            $technics_img,
            PATH . '/technics'
        );

        $this->set(compact('technics'));
    }

    public function typeAction()
    {
        $alias = trim((string)($this->route['alias'] ?? ''));
        if ($alias === '') {
            throw new \Exception('Тип техники не найден', 404);
        }

        $type = \R::findOne('technics_type', 'alias = ?', [$alias]);

        if (!$type) {
            throw new \Exception('Тип техники не найден', 404);
        }

        $manufacturers = \R::getAll("
            SELECT 
                tm.name,
                tm.img,
                tm.alias
            FROM technics t
            INNER JOIN technics_manufacturer tm ON tm.id = t.manufacturer_id
            WHERE t.type_id = ?
            GROUP BY tm.id, tm.name, tm.img, tm.alias
            ORDER BY tm.name
        ", [(int)$type->id]);

        $title = "Подбор шин для {$type->seoname_2} по модели и производителю техники";
        $description = "Подобрать шины для {$type->seoname_2} по производителю техники, модели. Купить шины в ИТС-Центр с доставкой по всей России";
        $keywords = 'Подбор шин, шины по производителю, каталог производителей техники';

        $technics_img = PATH . '/images/' . App::$app->getProperty('og_logo');

        $this->setMeta(
            $title,
            $description,
            $keywords,
            App::$app->getProperty('shop_name'),
            $technics_img,
            PATH . '/technics/type/' . trim((string)$type->alias, '/')
        );

        $this->set(compact('type', 'manufacturers'));
    }

    public function manufacturerAction()
    {
        $alias = trim((string)($this->route['alias'] ?? ''));
        $type_alias = trim((string)($this->route['type'] ?? ''));

        if ($alias === '') {
            throw new \Exception('Производитель не найден', 404);
        }

        if ($type_alias === '') {
            throw new \Exception('Тип техники не найден', 404);
        }

        $manufacturer = \R::findOne('technics_manufacturer', 'alias = ?', [$alias]);
        $type = \R::findOne('technics_type', 'alias = ?', [$type_alias]);

        if (!$manufacturer) {
            throw new \Exception('Производитель не найден', 404);
        }

        if (!$type) {
            throw new \Exception('Тип техники не найден', 404);
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perpage = (int)App::$app->getProperty('pagination');

        $total = \R::count('technics', 'manufacturer_id = ? AND type_id = ?', [
            (int)$manufacturer->id,
            (int)$type->id
        ]);

        $pagination = new Pagination($page, $perpage, $total);
        $start = $pagination->getStart();

        $technics = \R::getAll("
            SELECT 
                t.model,
                t.img,
                t.alias
            FROM technics t
            WHERE t.manufacturer_id = ?
              AND t.type_id = ?
            ORDER BY t.model ASC
            LIMIT {$start}, {$perpage}
        ", [(int)$manufacturer->id, (int)$type->id]);

        $title = "Подбор шин на {$type->seoname_3} {$manufacturer->name}";
        $description = "Компания «ИТС-Центр» предлагает воспользоваться подбором шин по марке техники. Найти и купить шины на {$type->seoname_3} {$manufacturer->name} можно у нас, большой ассортимент шин на различную технику.";
        $keywords = "Подбор шин, найти шины по технике, каталог техники, шины на {$type->seoname_3} {$manufacturer->name}";

        $path_controller = $this->buildPathPart($this->route['controller'] ?? '', true);
        $path_type = $this->buildPathPart($this->route['type'] ?? '');
        $path_alias = $this->buildPathPart($this->route['alias'] ?? '');

        $technics_img = PATH . '/images/' . App::$app->getProperty('og_logo');

        $this->setMeta(
            $title,
            $description,
            $keywords,
            App::$app->getProperty('shop_name'),
            $technics_img,
            PATH . $path_controller . $path_type . $path_alias
        );

        $this->set(compact('manufacturer', 'technics', 'type', 'pagination', 'total'));
    }

    private function getTechnicsSizesGrouped(int $technicsId): array
    {
        $rows = \R::getAll("
            SELECT
                tt.tip_size,
                av.id AS value_id,
                av.value,
                av.alias
            FROM technics_tiposize tt
            INNER JOIN attribute_value av ON av.id = tt.value_id
            WHERE tt.technics_id = ?
            ORDER BY tt.id ASC
        ", [$technicsId]);

        $result = [
            '1' => [],
            '2' => [],
            '3' => [],
            '4' => [],
        ];

        foreach ($rows as $row) {
            $tip = (string)($row['tip_size'] ?? '');

            if ($tip === '') {
                continue;
            }

            if (!isset($result[$tip])) {
                $result[$tip] = [];
            }

            $result[$tip][] = [
                'tip_size' => $tip,
                'value_id' => (int)($row['value_id'] ?? 0),
                'value' => (string)($row['value'] ?? ''),
                'alias' => (string)($row['alias'] ?? ''),
            ];
        }

        return $result;
    }

    private function getAvailableSizeUrls(array $sizeRows, ?string $preferredCategoryAlias = null): array
    {
        if (!$sizeRows) {
            return [];
        }

        $result = [];

        foreach ($sizeRows as $row) {
            $sizeValueId = (int)($row['value_id'] ?? 0);
            $sizeAlias = trim((string)($row['alias'] ?? ''));
            $sizeValue = trim((string)($row['value'] ?? ''));

            if ($sizeValueId <= 0) {
                continue;
            }

            $result[$sizeValueId] = [
                'value_id' => $sizeValueId,
                'value' => $sizeValue,
                'alias' => $sizeAlias,
                'url' => '',
                'clickable' => false,
            ];

            if ($sizeAlias === '') {
                continue;
            }

            if ($preferredCategoryAlias && $preferredCategoryAlias !== 'kamery') {
                $existsInPreferred = \R::getCell("
                    SELECT p.id
                    FROM product p
                    INNER JOIN category c ON c.id = p.category_id
                    INNER JOIN attribute_product ap ON ap.product_id = p.id
                    WHERE c.alias = ?
                      AND c.alias <> 'kamery'
                      AND ap.attr_id = ?
                      AND p.hide = 'show'
                    LIMIT 1
                ", [$preferredCategoryAlias, $sizeValueId]);

                if ($existsInPreferred) {
                    $result[$sizeValueId]['url'] = PATH . '/category/' . $preferredCategoryAlias . '/' . $sizeAlias;
                    $result[$sizeValueId]['clickable'] = true;
                    continue;
                }
            }

            $fallbackCategoryAlias = \R::getCell("
                SELECT c.alias
                FROM product p
                INNER JOIN category c ON c.id = p.category_id
                INNER JOIN attribute_product ap ON ap.product_id = p.id
                WHERE ap.attr_id = ?
                  AND p.hide = 'show'
                  AND c.alias <> ''
                  AND c.alias <> 'kamery'
                ORDER BY c.parent_id DESC, c.id ASC
                LIMIT 1
            ", [$sizeValueId]);

            if ($fallbackCategoryAlias) {
                $result[$sizeValueId]['url'] = PATH . '/category/' . trim((string)$fallbackCategoryAlias, '/') . '/' . $sizeAlias;
                $result[$sizeValueId]['clickable'] = true;
            }
        }

        return $result;
    }

    private function buildPathPart($value, bool $toLower = false): string
    {
        $value = trim((string)$value, " \t\n\r\0\x0B/");

        if ($value === '') {
            return '';
        }

        if ($toLower) {
            $value = mb_strtolower($value);
        }

        return '/' . $value;
    }
}
