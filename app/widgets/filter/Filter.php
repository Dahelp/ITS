<?php

namespace app\widgets\filter;

use ishop\Cache;

class Filter{

    public $groups;
    public $attrs;
    public $tpl;
    public $filter;
    public $ids;

    public function __construct($ids = null, $filter = [], $tpl = ''){
        $this->filter = is_array($filter) ? array_map('intval', $filter) : (empty($filter) ? [] : [(int)$filter]);
        $this->ids = $ids;
        $this->tpl = $tpl ?: __DIR__ . '/filter_tpl.php';
        $this->run($ids);
    }

    protected function run($ids = null){
        $cache = Cache::instance();

        $cacheSuffix = !empty($ids) ? '_' . md5((string)$ids) : '_all';
        $groupsKey = 'filter_group' . $cacheSuffix;

        $this->groups = $cache->get($groupsKey);
        if(!$this->groups){
            $this->groups = $this->getGroups($ids);
            $cache->set($groupsKey, $this->groups, 0);
        }

        // attrs не кешируем общим ключом, потому что они зависят от активных фильтров
        $this->attrs = $this->getAttrs($ids, $this->filter);

        echo $this->getHtml();
    }

    protected function getHtml(){
        ob_start();
        require $this->tpl;
        return ob_get_clean();
    }

    public function getGroups($ids = null){
        if(!empty($ids)){
            return \R::getAssoc("
                SELECT attribute_group.id, attribute_group.title
                FROM attribute_group
                INNER JOIN attribute_category
                    ON attribute_group.id = attribute_category.group_id
                WHERE attribute_category.category_id IN ($ids)
                GROUP BY attribute_group.id, attribute_group.title
                ORDER BY attribute_group.title
            ");
        }

        return \R::getAssoc("SELECT id, title FROM attribute_group ORDER BY title");
    }

    protected function getAttrs($ids = null, array $activeFilters = []){
        $attrs = [];

        foreach ($this->groups as $groupId => $groupTitle) {
            $otherFilters = [];

            // берём все активные фильтры, кроме текущей группы
            if (!empty($activeFilters)) {
                $rows = \R::getAll("
                    SELECT av.id, av.attr_group_id
                    FROM attribute_value av
                    WHERE av.id IN (" . implode(',', array_map('intval', $activeFilters)) . ")
                ");

                foreach ($rows as $row) {
                    if ((int)$row['attr_group_id'] !== (int)$groupId) {
                        $otherFilters[] = (int)$row['id'];
                    }
                }
            }

            $sqlJoin = '';
            $sqlWhereExtra = '';
            $having = '';

            if (!empty($otherFilters)) {
                $otherFilterSql = implode(',', array_map('intval', $otherFilters));
                $otherCount = count($otherFilters);

                $sqlJoin = "
                    INNER JOIN (
                        SELECT ap2.product_id
                        FROM attribute_product ap2
                        WHERE ap2.attr_id IN ($otherFilterSql)
                        GROUP BY ap2.product_id
                        HAVING COUNT(DISTINCT ap2.attr_id) = $otherCount
                    ) filtered_products ON filtered_products.product_id = p.id
                ";
            }

            if (!empty($ids)) {
                $sqlWhereExtra .= " AND p.category_id IN ($ids)";
            }

            $data = \R::getAll("
                SELECT
                    av.id,
                    av.value,
                    av.alias,
                    av.attr_group_id
                FROM attribute_value av
                INNER JOIN attribute_product ap ON av.id = ap.attr_id
                INNER JOIN product p ON p.id = ap.product_id
                $sqlJoin
                WHERE p.hide = 'show'
                  AND av.attr_group_id = " . (int)$groupId . "
                  $sqlWhereExtra
                GROUP BY av.id, av.value, av.alias, av.attr_group_id
                ORDER BY av.value
            ");

            foreach($data as $row){
                $attrs[(int)$row['attr_group_id']][(int)$row['id']] = [
                    'value' => $row['value'],
                    'alias' => $row['alias'],
                ];
            }
        }

        return $attrs;
    }

    public static function getFilter(){
        $filter = null;
        if(!empty($_GET['filter'])){
            $filter = preg_replace("#[^\d,]+#", '', $_GET['filter']);
            $filter = trim($filter, ',');
        }
        return $filter;
    }

    public static function getCountGroups($filter){
        $filters = explode(',', $filter);
        if (empty($filters)) {
            return 0;
        }

        $rows = \R::getAll("
            SELECT DISTINCT attr_group_id
            FROM attribute_value
            WHERE id IN (" . implode(',', array_map('intval', $filters)) . ")
        ");

        return count($rows);
    }
}