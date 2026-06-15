<?php

namespace app\controllers;

use app\models\AppModel;
use app\widgets\currency\Currency;
use ishop\App;
use ishop\base\Controller;
use ishop\Cache;
use app\models\admin\PlaginsBanner;
use app\services\filters\FilterUrlHelper;

class AppController extends Controller
{
    protected array $errors = [];

    public function __construct($route)
    {
        parent::__construct($route);
        new AppModel();

        App::$app->setProperty('currencies', Currency::getCurrencies());
        App::$app->setProperty('currency', Currency::getCurrency(App::$app->getProperty('currencies')));
        App::$app->setProperty('cats', self::cacheCategory());

        $this->shareCommonData();
    }

    public static function cacheCategory()
    {
        $cache = Cache::instance();
        $cats = $cache->get('cats');

        if (!$cats) {
            $cats = \R::getAssoc("SELECT * FROM category");
            $cache->set('cats', $cats);
        }

        return $cats;
    }

    protected function shareCommonData(): void
    {
        $cache = Cache::instance();
        $banners = $cache->get('banners_front');

        if ($banners === false) {
            $now = date('Y-m-d H:i:s');

            $banners = \R::getAll("
                SELECT *
                FROM plagins_banner
                WHERE hide = 'show'
                  AND (start_at IS NULL OR start_at <= ?)
                  AND (end_at   IS NULL OR end_at   >= ?)
                ORDER BY position DESC, id DESC
            ", [$now, $now]);

            // кэшируем на 5 минут
            $cache->set('banners_front', $banners, 300);
        }

        App::$app->setProperty('banners_front', $banners);
    }

    protected function routePart(string $key): string
    {
        return !empty($this->route[$key]) ? (string)$this->route[$key] : '';
    }

    /**
     * Возвращает канонический URL category-фильтра для значения атрибута.
     *
     * Пример:
     * /category/shiny-dlya-minipogruzchikov/12-16.5
     *
     * Работает только если у группы:
     * - canonical_source = manual_map
     * и в таблице attribute_value_category_canonical есть активная привязка.
     */
    protected function getCanonicalCategoryFilterUrl($find, $group): ?string
    {
        if (empty($find) || empty($group)) {
            return null;
        }

        $groupId = (int)($group->id ?? 0);
        $attrValueId = (int)($find->id ?? 0);
        $valueAlias = trim((string)($find->alias ?? ''), '/');
        $canonicalSource = trim((string)($group->canonical_source ?? 'none'));

        if ($groupId <= 0 || $attrValueId <= 0 || $valueAlias === '') {
            return null;
        }

        if ($canonicalSource !== 'manual_map') {
            return null;
        }

        $row = \R::getRow(
            "SELECT c.alias
             FROM attribute_value_category_canonical avcc
             INNER JOIN category c ON c.id = avcc.category_id
             WHERE avcc.attr_value_id = ?
               AND avcc.is_active = 1
             LIMIT 1",
            [$attrValueId]
        );

        if (empty($row['alias'])) {
            return null;
        }

        $categoryAlias = trim((string)$row['alias'], '/');
        if ($categoryAlias === '') {
            return null;
        }

        return rtrim(PATH, '/') . '/category/' . $categoryAlias . '/' . $valueAlias;
    }

    /**
     * Проверяет, является ли текущая category/{category_alias}/{filter_alias}
     * канонической для данного значения атрибута.
     *
     * Если канонический URL не найден, возвращаем true,
     * чтобы не делать ложный редирект.
     */
    protected function isCanonicalCategoryFilterUrl(string $currentCategoryAlias, $find, $group): bool
    {
        $currentCategoryAlias = trim($currentCategoryAlias, '/');
        if ($currentCategoryAlias === '') {
            return true;
        }

        $target = $this->getCanonicalCategoryFilterUrl($find, $group);
        if (empty($target)) {
            return true;
        }

        $path = parse_url($target, PHP_URL_PATH);
        $path = trim((string)$path, '/');
        if ($path === '') {
            return true;
        }

        $parts = explode('/', $path);

        // ожидаем: category/{category_alias}/{filter_alias}
        if (count($parts) < 3) {
            return true;
        }

        if (($parts[0] ?? '') !== 'category') {
            return true;
        }

        $canonicalCategoryAlias = trim((string)($parts[1] ?? ''), '/');
        if ($canonicalCategoryAlias === '') {
            return true;
        }

        return $canonicalCategoryAlias === $currentCategoryAlias;
    }

    /**
     * Постоянный редирект 301
     */
    protected function redirectPermanent(string $url): void
    {
        $url = trim($url);
        if ($url === '') {
            return;
        }

        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $url);
        exit;
    }

    protected function getCategoryFilterRule(int $attrValueId, int $categoryId): ?array
    {
        if ($attrValueId <= 0 || $categoryId <= 0) {
            return null;
        }

        $row = \R::getRow(
            "SELECT
                avcc.mode,
                avcc.redirect_category_id,
                c.alias AS redirect_category_alias
            FROM attribute_value_category_canonical avcc
            LEFT JOIN category c ON c.id = avcc.redirect_category_id
            WHERE avcc.attr_value_id = ?
            AND avcc.category_id = ?
            AND avcc.is_active = 1
            LIMIT 1",
            [$attrValueId, $categoryId]
        );

        if (empty($row)) {
            return null;
        }

        return [
            'mode' => (string)($row['mode'] ?? 'landing'),
            'redirect_category_id' => !empty($row['redirect_category_id']) ? (int)$row['redirect_category_id'] : 0,
            'redirect_category_alias' => (string)($row['redirect_category_alias'] ?? ''),
        ];
    }

    protected function buildCategoryFilterUrl(string $categoryAlias, string $filterAlias): string
    {
        return FilterUrlHelper::buildCategoryFilterUrl($categoryAlias, $filterAlias);
    }

    protected function getDefaultCategoryFilterUrl($find): ?string
    {
        if (empty($find) || empty($find->id) || empty($find->alias)) {
            return null;
        }

        $row = \R::getRow(
            "SELECT c.alias
            FROM attribute_value_category_canonical avcc
            INNER JOIN category c ON c.id = avcc.category_id
            WHERE avcc.attr_value_id = ?
            AND avcc.is_active = 1
            ORDER BY
            CASE
                WHEN avcc.mode = 'landing' THEN 0
                WHEN avcc.mode = 'redirect' THEN 1
                ELSE 2
            END,
            avcc.id ASC
            LIMIT 1",
            [(int)$find->id]
        );

        if (empty($row['alias'])) {
            return null;
        }

        return rtrim(PATH, '/') . '/category/' . trim((string)$row['alias'], '/') . '/' . ltrim((string)$find->alias, '/');
    }

}
