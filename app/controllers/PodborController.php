<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use app\models\Category;
use app\widgets\filter\Filter;
use ishop\App;
use ishop\libs\Pagination;

class PodborController extends AppController
{
    public function indexAction()
    {
        $alias = $this->route['alias'] ?? '';
        $category = \R::findOne('category', 'alias = ?', [$alias]);

        if (!$category) {
            throw new \Exception('Страница не найдена', 404);
        }

        $breadcrumbs = Breadcrumbs::getBreadcrumbs($category->id);

        $cat_model = new Category();
        $ids = $cat_model->getIds($category->id);
        $ids = !$ids ? $category->id : $ids . $category->id;

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perpage = (int) App::$app->getProperty('pagination');

        $sql_part = '';
        $sql_sort = '';
        $url_alias = $alias;

        $products = [];
        $total = 0;
        $pagination = null;

        $filter = Filter::getFilter();
        $selectedFilters = !empty($filter)
            ? array_values(array_filter(array_map('intval', explode(',', $filter))))
            : [];

        $hasFilter = !empty($filter);

        if ($hasFilter) {
            $cnt = Filter::getCountGroups($filter);

            $sql_part = " AND id IN (
                SELECT product_id
                FROM attribute_product
                WHERE attr_id IN ($filter)
                GROUP BY product_id
                HAVING COUNT(product_id) = $cnt
            )";

            if (!empty($_GET['sort'])) {
                switch ($_GET['sort']) {
                    case 'price':
                        $sql_sort = ' ORDER BY price ASC';
                        break;
                    case 'nal':
                        $sql_sort = ' ORDER BY stock_status_id DESC, quantity DESC';
                        break;
                    case 'rate':
                        $sql_sort = ' ORDER BY hit DESC, id DESC';
                        break;
                    default:
                        $sql_sort = '';
                        break;
                }
            }

            $count_where = "hide = 'show' AND category_id IN ($ids)$sql_part";
            $total = \R::count('product', $count_where);

            $pagination = new Pagination($page, $perpage, $total);
            $start = $pagination->getStart();

            $products = \R::find(
                'product',
                "$count_where$sql_sort LIMIT $start, $perpage"
            );
        }

        if ($this->isAjax()) {
            $this->loadView('filter', compact(
                'products',
                'total',
                'pagination',
                'ids',
                'category',
                'hasFilter'
            ));
            die;
        }

        $pdr_name = $category->name ?? 'Подбор';
        $title = $pdr_name;
        $description = $pdr_name;
        $keywords = '';

        if ((string)$category->id === '3') {
            $pdr_name = 'Подбор дисков на спецтехнику по параметрам';
            $title = 'Подбор дисков для спецтехники по параметрам';
            $description = 'Подбор колесных дисков для спецтехники по размеру, PCD, HUB, ET, количеству отверстий и модели техники. Доставка по Москве и регионам России.';
        } elseif ((string)$category->id === '31') {
            $pdr_name = 'Подбор камер на спецтехнику по параметрам';
            $title = 'Подбор камер для шин спецтехники по размеру';
            $description = 'Подбор камер для шин спецтехники, погрузчиков, экскаваторов, грузовой и индустриальной техники. Выбор по типоразмеру, вентилю и назначению.';
        } elseif ((string)$category->id === '34') {
            $pdr_name = 'Подбор шин на спецтехнику по параметрам';
            $title = 'Подбор шин для спецтехники по параметрам';
            $description = 'Удобный подбор шин для спецтехники по размеру, типу техники, бренду и характеристикам. Поможем выбрать шины для погрузчиков, экскаваторов, кранов и другой техники.';
        }

        $path_controller = !empty($this->route['controller']) ? '/' . mb_strtolower($this->route['controller']) : '';
        $path_alias = !empty($this->route['alias']) ? '/' . $this->route['alias'] : '';

        $this->setMeta(
            $title,
            $description,
            $keywords,
            App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            PATH . $path_controller . $path_alias
        );

        $this->set(compact(
            'products',
            'breadcrumbs',
            'pagination',
            'total',
            'url_alias',
            'category',
            'ids',
            'pdr_name',
            'hasFilter',
            'selectedFilters'
        ));
    }
}