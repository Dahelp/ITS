<?php

namespace app\models\admin;

use app\models\AppModel;
use ishop\App;

class FiltrsGroup extends AppModel
{
    public $attributes = [
        'title'                => '',
        'url_params'           => '',
        'seo_title'            => '',
        'seo_description'      => '',
        'seo_keywords'         => '',
        'page_name'            => '',
        'seo_content'          => '',
        'notproduct'           => '',
        'template'             => '0',
        'page_mode'            => 'standalone',
        'redirect_to_category' => '0',
        'canonical_source'     => 'none',
    ];

    public $rules = [
        'required' => [
            ['title'],
            ['url_params'],
        ],
    ];

    /**
     * Создание controller / views / route для группы фильтров
     */
    public function addClassGroup($data)
    {
        $urlParams = $this->normalizeUrlParams($data['url_params'] ?? '');
        if ($urlParams === '') {
            throw new \RuntimeException('Не заполнено системное имя url_params');
        }

        $fileName = $this->buildControllerNameFromUrlParams($urlParams);

        $viewDir        = APP . '/views/' . TEMPLATE . '/' . $fileName;
        $dirView        = $viewDir . '/view.php';
        $dirIndex       = $viewDir . '/index.php';
        $dirController  = APP . '/controllers/' . $fileName . 'Controller.php';
        $dirRoute       = CONF . '/routes.php';

        $this->ensureDirectory($viewDir);

        // ---------- INDEX ----------
        $phpContentIndex = <<<'PHP'
<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><?=h($type->page_name);?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="contents">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <?php if (!empty($groups)): ?>
                    <div class="register-top heading">
                        <h1><?=h($type->page_name);?></h1>
                    </div>

                    <div class="cont-inner">
                        <div class="group-filtr">
                            <?php foreach ($groups as $group): ?>
                                <div class="filtr-one">
                                    <a href="<?=h(\app\services\filters\FilterUrlHelper::buildBestCategoryFilterPath((int)$group['id'], (string)$group['alias'], (string)$type->url_params));?>" title="<?=h($group['value']);?>">
                                        <?php if (!empty($group['img'])): ?>
                                            <div class="filtrs-img">
                                                <img
                                                    src="images/filtrs/baseimg/<?=h($group['img']);?>"
                                                    alt="<?=h($group['value']);?>"
                                                    title="<?=h($group['value']);?>"
                                                    width="150"
                                                    height="120">
                                            </div>
                                        <?php endif; ?>
                                        <div class="filtrs-value"><?=h($group['value']);?></div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($type->seo_content)): ?>
                        <div class="catalog_text col-md-12">
                            <?=$type->seo_content;?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
PHP;

        $this->writeFile($dirIndex, $phpContentIndex);

        // ---------- VIEW ----------
        $phpContentView = <<<'PHP'
<!--prdt-starts-->
<div class="prdt">
    <div class="container">
        <!--start-breadcrumbs-->
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><a href="<?=h($params->url_params);?>"><?=h($params->title);?></a></li>
                <li class="breadcrumb-item active"><?=h($find->value);?></li>
            </ol>
        </nav>
        <!--end-breadcrumbs-->

        <section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3">
                <?php
                if (!empty($find->seo_h1)) {
                    echo $find->seo_h1;
                } elseif (!empty($inseo->name)) {
                    echo \ishop\App::seoreplacefilter($inseo->name, $find->id);
                } else {
                    echo h($find->value);
                }
                ?>
            </h1>
        </section>

        <?php if (!empty($find->top_content)): ?>
            <?php $alt = 'Шина размером ' . $find->value . ', установленная на колесной технике'; ?>
            <div class="catalog-top-block mb-4">
                <?php if (!empty($find->img)): ?>
                    <div class="catalog-top-image">
                        <img
                            src="/images/filtrs/baseimg/<?=h($find->img);?>"
                            alt="<?=h($alt);?>"
                            loading="lazy">
                    </div>
                <?php endif; ?>

                <div class="catalog-top-text">
                    <?=$find->top_content;?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($technicsLinks)): ?>
            <div class="tech-links mb-4">
                <div class="h4 mb-2">Подходит для техники</div>
                <div class="tech-links__items">
                    <?php foreach ($technicsLinks as $t): ?>
                        <a class="tech-links__item" href="/technics/type/<?=h($t['alias']);?>">
                            <?=h($t['name']);?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="prdt-top">
            <div class="col-md-12">
                <?php if (!empty($products)): ?>
                    <div class="row g-0 mx-n2 product-one">
                        <?php $curr = \ishop\App::$app->getProperty('currency'); ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
                                <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="clearfix"></div>

                        <div class="text-center">
                            <?php if ($pagination && $pagination->countPages > 1): ?>
                                <?=$pagination;?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($relatedSizes)): ?>
                        <div class="related-sizes">
                            <div class="h4">Также смотрят типоразмеры</div>
                            <div class="related-sizes__items">
                                <?php foreach ($relatedSizes as $r): ?>
                                    <a class="related-sizes__item" href="<?=h(\app\services\filters\FilterUrlHelper::buildBestCategoryFilterPath((int)$r['id'], (string)$r['alias'], (string)$r['url_params']));?>">
                                        <?=h($r['value']);?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($faqRows)): ?>
                        <div class="faq-block mb-4">
                            <div class="h4 mb-2">Вопросы и ответы</div>

                            <?php foreach ($faqRows as $f): ?>
                                <div class="faq-item mb-2">
                                    <div class="faq-q"><strong><?=h($f['question']);?></strong></div>
                                    <div class="faq-a"><?=nl2br(h($f['answer']));?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-warning product-note">
                        <?php if (empty($params->notproduct)): ?>
                            В этой категории товаров пока нет...
                        <?php else: ?>
                            <?=$params->notproduct;?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($products)): ?>
                    <?php
                    $ids = [];
                    foreach ($products as $p) {
                        if (is_object($p) && isset($p->id)) {
                            $ids[] = (int)$p->id;
                        } elseif (is_array($p) && isset($p['id'])) {
                            $ids[] = (int)$p['id'];
                        }
                    }
                    $ids = array_values(array_filter($ids));
                    $values = implode(',', $ids);
                    ?>

                    <div class="catalog_text">
                        <?php
                        if (!empty($find->content)) {
                            echo $find->content;
                        } elseif (!empty($inseo->content) && $values !== '') {
                            echo \ishop\App::seoreplacetiposize($inseo->content, $values);
                        }
                        ?>
                    </div>
                <?php endif; ?>

            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<!--product-end-->
PHP;

        $this->writeFile($dirView, $phpContentView);

        if (file_exists($dirController) && !is_writable($dirController)) {
            // Не трогаем существующий контроллер, чтобы не падать
            $skipControllerWrite = true;
        } else {
            $skipControllerWrite = false;
        }

        // ---------- CONTROLLER ----------
        $phpContentControllerTpl = <<<'PHP'
<?php

namespace app\controllers;

use app\helpers\SchemaHelper;
use app\models\Breadcrumbs;
use ishop\App;
use ishop\libs\Pagination;

class {{CLASS}}Controller extends AppController
{
    public function viewAction()
    {
        $alias = rawurldecode($this->route['alias'] ?? '');
        $alias = trim((string)$alias);

        if ($alias === '') {
            throw new \Exception('Страница не найдена', 404);
        }

        $params = \R::findOne(
            'attribute_group',
            'url_params = ?',
            ['{{URL_PARAMS}}']
        );

        if (!$params) {
            throw new \Exception('Группа фильтров не найдена', 404);
        }

        $find = \R::findOne(
            'attribute_value',
            "attr_group_id = ? AND alias = ? AND hide = 'show'",
            [(int)$params->id, $alias]
        );

        if (!$find) {
            throw new \Exception('Страница не найдена', 404);
        }

         /**
         * Прямой 301-редирект на каноническую category-страницу,
         * если он включён у группы фильтров.
         *
         * Условия:
         * - redirect_to_category = 1
         * - canonical_source = manual_map
         * - в attribute_value_category_canonical есть активная привязка
         */
        if (
            (int)$params->redirect_to_category === 1
            && trim((string)($params->canonical_source ?? 'none')) === 'manual_map'
        ) {
            $row = \R::getRow(
                "SELECT c.alias
                 FROM attribute_value_category_canonical avcc
                 INNER JOIN category c ON c.id = avcc.category_id
                 WHERE avcc.attr_value_id = ?
                   AND avcc.is_active = 1
                 LIMIT 1",
                [(int)$find->id]
            );

            if (!empty($row['alias'])) {
                $categoryAlias = trim((string)$row['alias'], '/');
                $valueAlias = trim((string)$find->alias, '/');

                if ($categoryAlias !== '' && $valueAlias !== '') {
                    $targetPath = '/category/' . $categoryAlias . '/' . $valueAlias;
                    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

                    if ($currentPath !== $targetPath) {
                        $target = rtrim(PATH, '/') . $targetPath;
                        header('Location: ' . $target, true, 301);
                        exit;
                    }
                }
            }
        }

        $breadcrumbs = Breadcrumbs::getBreadcrumbs($find->id);

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perpage = (int)App::$app->getProperty('pagination');

        $sqlSort = "ORDER BY FIELD(`stock_status_id`, 1,3,2,0), name ASC";
        if (!empty($_GET['sort'])) {
            if ($_GET['sort'] === 'price') {
                $sqlSort = "ORDER BY product.price ASC";
            } elseif ($_GET['sort'] === 'nal') {
                $sqlSort = "ORDER BY product.stock_status_id DESC";
            } elseif ($_GET['sort'] === 'rate') {
                $sqlSort = "ORDER BY product.hit DESC";
            }
        }

        $total = (int)\R::getCell(
            "SELECT COUNT(*)
             FROM attribute_product ap
             JOIN product p ON p.id = ap.product_id
             WHERE ap.attr_id = ? AND p.hide = 'show'",
            [$find->id]
        );

        $ids = \R::getAll(
            "SELECT ap.product_id
             FROM attribute_product ap
             JOIN product p ON p.id = ap.product_id
             WHERE ap.attr_id = ? AND p.hide = 'show'
             $sqlSort",
            [$find->id]
        );

        $relatedSizes = \R::getAll(
            "SELECT av.id, av.value, av.alias, ag.url_params
             FROM attribute_value_related r
             JOIN attribute_value av ON av.id = r.related_attr_value_id
             JOIN attribute_group ag ON ag.id = av.attr_group_id
             WHERE r.attr_value_id = ?
             ORDER BY r.sort, av.value",
            [$find->id]
        );

        $technicsLinks = \R::getAll(
            "SELECT t.id, t.name, t.alias
             FROM attribute_value_technic at
             JOIN technics_type t ON t.id = at.technic_id
             WHERE at.attr_value_id = ? AND t.hide = 'show'
             ORDER BY at.sort, t.name",
            [$find->id]
        );

        $faqRows = \R::getAll(
            "SELECT question, answer
             FROM attribute_value_faq
             WHERE attr_value_id = ? AND hide = 'show'
             ORDER BY sort, id
             LIMIT 20",
            [$find->id]
        );

        $products = [];
        $pagination = null;

        if (!empty($ids)) {
            $productIds = [];
            foreach ($ids as $row) {
                if (!empty($row['product_id'])) {
                    $productIds[] = (int)$row['product_id'];
                }
            }

            $productIds = array_values(array_unique(array_filter($productIds)));

            if (!empty($productIds)) {
                $idsStr = implode(',', $productIds);
                $pagination = new Pagination($page, $perpage, $total);
                $start = (int)$pagination->getStart();

                $products = \R::find(
                    'product',
                    "hide = 'show' AND id IN ($idsStr) $sqlSort LIMIT $start, $perpage"
                );
            }
        }

        $inseo = \R::findOne(
            'plagins_inseo',
            "tip = ? AND category_id = ? AND hide = 'show'",
            ['attribute_group', $find->attr_group_id]
        );

        if (!empty($find->title)) {
            $title = $find->title;
        } elseif ($inseo && !empty($inseo->title)) {
            $title = \ishop\App::seoreplacefilter($inseo->title, $find->id);
        } else {
            $title = '';
        }

        if (!empty($find->description)) {
            $description = $find->description;
        } elseif ($inseo && !empty($inseo->description)) {
            $description = \ishop\App::seoreplacefilter($inseo->description, $find->id);
        } else {
            $description = '';
        }

        if (!empty($find->keywords)) {
            $keywords = $find->keywords;
        } elseif ($inseo && !empty($inseo->keywords)) {
            $keywords = \ishop\App::seoreplacefilter($inseo->keywords, $find->id);
        } else {
            $keywords = '';
        }

        $canonical = rtrim(PATH, '/') . '/'
            . trim($params->url_params, '/') . '/'
            . ltrim($find->alias, '/');

        $this->setMeta(
            $title,
            $description,
            $keywords,
            App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            $canonical
        );

        $itemUrls = [];
        if (!empty($products)) {
            foreach ($products as $p) {
                $itemUrls[] = '/product/' . $p->alias;
            }
        }

        $pagePath = '/' . trim($params->url_params ?? '', '/') . '/' . ltrim($find->alias, '/');
        $pageUrl  = rtrim(PATH, '/') . $pagePath;

        $pageName = !empty($find->seo_h1)
            ? $find->seo_h1
            : (($inseo && !empty($inseo->name)) ? \ishop\App::seoreplacefilter($inseo->name, $find->id) : $find->value);

        $pageDesc = $description ?: ($find->description ?: $find->value);

        $jsonLdCollection = SchemaHelper::renderCollectionPageJsonLd(
            $pageUrl,
            $pageName,
            $pageDesc,
            $itemUrls,
            $pageUrl
        );

        $jsonLdFaq = '';
        if (!empty($faqRows)) {
            $jsonLdFaq = SchemaHelper::renderFaqPageJsonLd($faqRows);
        }

        $this->set(compact(
            'find',
            'products',
            'breadcrumbs',
            'pagination',
            'total',
            'params',
            'inseo',
            'jsonLdCollection',
            'relatedSizes',
            'technicsLinks',
            'faqRows',
            'jsonLdFaq'
        ));
    }

    public function indexAction()
    {
        $alias = trim((string)($_SERVER['REQUEST_URI'] ?? ''), '/');
        $alias = strtok($alias, '?');

        $type = \R::findOne('attribute_group', 'url_params = ?', [$alias]);
        if (!$type) {
            throw new \Exception('Страница не найдена', 404);
        }

        $groups = \R::findAll(
            'attribute_value',
            "attr_group_id = ?
             AND hide = 'show'
             AND id IN (
                SELECT ap.attr_id
                FROM attribute_product ap
                INNER JOIN product p ON p.id = ap.product_id
                WHERE p.hide = 'show'
             )
             ORDER BY value ASC",
            [(int)$type->id]
        );

        $canonical = rtrim(PATH, '/') . '/' . trim($type->url_params, '/');

        $this->setMeta(
            $type->seo_title,
            $type->seo_description,
            $type->seo_keywords,
            App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            $canonical
        );

        $this->set(compact('groups', 'type'));
    }
}
PHP;

        $phpContentController = str_replace(
            ['{{CLASS}}', '{{URL_PARAMS}}'],
            [$fileName, $urlParams],
            $phpContentControllerTpl
        );
        if (!$skipControllerWrite) {
            $this->writeFile($dirController, $phpContentController);
        }

        // ---------- ROUTES ----------
        $this->injectRoutes($dirRoute, $fileName, $urlParams);
    }

    /**
     * Проверка уникальности title и url_params
     */
    public function checkUnique($id = null)
    {
        $title = trim((string)($this->attributes['title'] ?? ''));
        $urlParams = $this->normalizeUrlParams($this->attributes['url_params'] ?? '');

        $params = [];
        $whereId = '';

        if ($id !== null) {
            $whereId = ' AND id != ?';
            $paramsTitle = [$title, (int)$id];
            $paramsUrl = [$urlParams, (int)$id];
        } else {
            $paramsTitle = [$title];
            $paramsUrl = [$urlParams];
        }

        $byTitle = \R::findOne('attribute_group', 'title = ?' . $whereId, $paramsTitle);
        if ($byTitle) {
            $this->errors['unique'][] = 'Название группы фильтров уже существует';
        }

        $byUrlParams = \R::findOne('attribute_group', 'url_params = ?' . $whereId, $paramsUrl);
        if ($byUrlParams) {
            $this->errors['unique'][] = 'Системное имя уже существует';
        }

        return empty($this->errors['unique']);
    }

    /**
     * Нормализация url_params
     */
    protected function normalizeUrlParams($value): string
    {
        $value = trim((string)$value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = trim($value, '/');

        return $value;
    }

    /**
     * Построение имени controller/view-папки из url_params
     * size-group => SizeGroup
     * size => Size
     */
    protected function buildControllerNameFromUrlParams(string $urlParams): string
    {
        $parts = preg_split('#[^a-z0-9]+#i', $urlParams);
        $parts = array_values(array_filter($parts));

        if (empty($parts)) {
            throw new \RuntimeException('Не удалось сформировать имя контроллера из url_params');
        }

        $result = '';
        foreach ($parts as $part) {
            $result .= ucfirst($part);
        }

        return $result;
    }

    /**
     * Создание директории, если её ещё нет
     */
    protected function ensureDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Не удалось создать директорию: {$dir}");
        }
    }

    /**
     * Безопасная запись файла
     */
    protected function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException("Не удалось создать директорию: {$dir}");
            }
        }

        $tmpPath = $path . '.tmp';

        if (file_exists($tmpPath)) {
            @chmod($tmpPath, 0666);
            @unlink($tmpPath);
        }

        $bytes = @file_put_contents($tmpPath, $content, LOCK_EX);
        if ($bytes === false) {
            throw new \RuntimeException("Не удалось записать временный файл: {$tmpPath}");
        }

        @chmod($tmpPath, 0666);

        if (file_exists($path)) {
            @chmod($path, 0666);
            if (!@unlink($path)) {
                @unlink($tmpPath);
                throw new \RuntimeException("Не удалось удалить старый файл перед заменой: {$path}");
            }
        }

        if (!@rename($tmpPath, $path)) {
            @unlink($tmpPath);
            throw new \RuntimeException("Не удалось переименовать временный файл в целевой: {$path}");
        }

        @chmod($path, 0666);
    }

    /**
     * Вставка route-блока в routes.php
     */
    protected function injectRoutes(string $routesFile, string $fileName, string $urlParams): void
    {
        $source = file_get_contents($routesFile);
        if ($source === false) {
            throw new \RuntimeException("Не удалось прочитать файл маршрутов: {$routesFile}");
        }

        // Удаляем старый блок этого контроллера, если он был
        $source = preg_replace(
            "#\n//" . preg_quote($fileName, '#') . "//.*?//And" . preg_quote($fileName, '#') . "//#is",
            '',
            $source
        );

        $routeBlock = "\n//{$fileName}//\n"
            . "Router::add('^" . preg_quote($urlParams, '#') . "/(?P<alias>[a-z0-9\\.\\-_]+)/?$', ['controller' => '{$fileName}', 'action' => 'view']);\n"
            . "//And{$fileName}//\n"
            . "//  Add here";

        $source = str_replace('//  Add here', $routeBlock, $source);

        $result = file_put_contents($routesFile, $source);
        if ($result === false) {
            throw new \RuntimeException("Не удалось записать маршруты в файл: {$routesFile}");
        }
    }
}
