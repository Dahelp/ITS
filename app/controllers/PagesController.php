<?php

namespace app\controllers;

use app\helpers\PrivacyPolicyContent;
use ishop\App;
use ishop\libs\Pagination;

class PagesController extends AppController
{
    public function viewAction()
    {
        $alias = rawurldecode($this->route['alias'] ?? '');
        $alias = trim((string)$alias);

        if ($alias === '') {
            throw new \Exception("Страница не найдена", 404);
        }

        $type = \R::findOne(
            'content_type',
            "param_url = ? AND hide = 'show'",
            ['pages']
        );

        if (!$type) {
            throw new \Exception("Страница не найдена", 404);
        }

        $find = \R::findOne(
            'contents',
            "alias = ? AND type_id = ? AND hide = 'show'",
            [$alias, (int)$type->id]
        );

        if (!$find) {
            throw new \Exception("Страница не найдена", 404);
        }

        if ($alias === 'privacy') {
            $privacyPolicyContent = PrivacyPolicyContent::html();

            if ($privacyPolicyContent !== '') {
                $find->content = $privacyPolicyContent;
                $find->title = 'Политика конфиденциальности';
                $find->description = 'Политика конфиденциальности и обработки персональных данных ООО «ИТС-Центр» на сайте its-center.ru.';
                $find->keywords = 'политика конфиденциальности, персональные данные, cookie, Яндекс.Метрика, ИТС-Центр';
            }
        }

        $related = \R::getAll("
            SELECT product.*
            FROM content_related
            JOIN product ON product.id = content_related.related_id
            WHERE content_related.content_id = ?
              AND product.hide = 'show'
        ", [(int)$find->id]);

        if ($find->img) {
            $find_img = PATH . "/images/contents/baseimg/" . $find->img;
        } else {
            $find_img = PATH . "/images/" . App::$app->getProperty('og_logo');
        }

        $canonical = rtrim(PATH, '/') . '/'
            . trim((string)$type->param_url, '/') . '/'
            . ltrim((string)$find->alias, '/');

        $this->setMeta(
            $find->title,
            $find->description,
            $find->keywords,
            App::$app->getProperty('shop_name'),
            $find_img,
            $canonical
        );

        $this->set(compact('find', 'type', 'related'));
    }

    public function indexAction()
    {
        $alias = strtok($_SERVER["REQUEST_URI"], '?');
        $alias = trim((string)$alias, '/');

        $type = \R::findOne(
            'content_type',
            "param_url = ? AND hide = 'show'",
            [$alias]
        );

        if (!$type) {
            throw new \Exception("Страница не найдена", 404);
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perpage = (int)App::$app->getProperty('pagination');

        $total = \R::count(
            'contents',
            "hide = ? AND type_id = ?",
            ['show', (int)$type->id]
        );

        $pagination = new Pagination($page, $perpage, $total);
        $start = (int)$pagination->getStart();

        $conts = \R::findAll(
            'contents',
            "hide = ? AND type_id = ? ORDER BY date_post DESC LIMIT ?, ?",
            ['show', (int)$type->id, $start, $perpage]
        );

        $canonical = rtrim(PATH, '/') . '/' . trim((string)$type->param_url, '/');

        $this->setMeta(
            $type->title,
            $type->description,
            $type->keywords,
            App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            $canonical
        );

        $this->set(compact('conts', 'type', 'pagination'));
    }
}
