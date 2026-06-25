<?php

namespace app\models\admin;

use ishop\App;
use app\models\AppModel;

class ContentsType extends AppModel
{
    public $attributes = [
        'name' => '',
        'param_url' => '',
        'hide' => '',
        'hide_anons' => '',
        'hide_clicks' => '',
        'hide_date_post' => '',
        'hide_rss' => '',
        'title' => '',
        'description' => '',
        'keywords' => '',
    ];

    public $rules = [
        'required' => [
            ['name'],
            ['param_url'],
        ],
    ];

    public function addClassContents($data)
    {
        $fileName = ucfirst($data['param_url']);
        $viewDir = APP . '/views/' . TEMPLATE . '/' . $fileName;

        if (!is_dir($viewDir)) {
            mkdir($viewDir, 0755, true);
        }

        $dir_view = $viewDir . '/view.php';

        if (($data['hide_anons'] ?? '') === 'show') {
            $dir_index = $viewDir . '/index.php';

            $phpContent_index = <<<'PHP'
<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i><span class="visually-hidden">Главная</span></a>
                </li>
                <li class="breadcrumb-item active"><?=htmlspecialchars($type->name, ENT_QUOTES, 'UTF-8');?></li>
            </ol>
        </nav>
    </div>
</div>

<?php
$typeName = trim((string)($type->name ?? ''));
$typeTitle = trim((string)($type->title ?? ''));
$pageTitle = $typeTitle !== '' ? $typeTitle : $typeName;
$typeUrl = rtrim(PATH, '/') . '/' . trim((string)$type->param_url, '/');

$itemList = [];
$position = 1;

if (!empty($conts)) {
    foreach ($conts as $item) {
        $itemUrl = $typeUrl . '/' . $item['alias'];
        $itemList[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'url' => $itemUrl,
            'name' => $item['name'],
        ];
    }
}

$collectionSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'url' => $typeUrl,
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $itemList,
    ],
];

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Главная',
            'item' => rtrim(PATH, '/'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $typeName,
            'item' => $typeUrl,
        ],
    ],
];
?>

<script type="application/ld+json"><?=json_encode($collectionSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>
<script type="application/ld+json"><?=json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>

<section class="main-articles content-type-archive py-4 py-md-5">
    <div class="container">
        <h1 class="mb-4"><?=htmlspecialchars($type->name, ENT_QUOTES, 'UTF-8');?></h1>

        <?php if (!empty($conts)): ?>
            <div class="cont-blok">
                <?php foreach($conts as $item): ?>
                    <div class="cont-one">
                        <div class="cont_ht">
                            <a class="cont_blok_img" href="<?= PATH ?>/<?=$type->param_url;?>/<?=$item['alias'];?>">
                                <?php if (!empty($item['img'])): ?>
                                    <img
                                        src="<?= PATH ?>/images/contents/baseimg/<?=$item['img'];?>"
                                        alt="<?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>"
                                        title="<?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>"
                                    >
                                <?php else: ?>
                                    <img
                                        src="<?= PATH ?>/images/no_image.jpg"
                                        alt="<?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>"
                                        title="<?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>"
                                    >
                                <?php endif; ?>
                            </a>

                            <div class="cont_info">
                                <?php if (($type['hide_date_post'] ?? '') === 'show'): ?>
                                    <div class="cont_info_data">
                                        <?= \ishop\App::contdate($item['date_post']); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="cont_info_name">
                                    <a href="<?= PATH ?>/<?=$type->param_url;?>/<?=$item['alias'];?>">
                                        <?=htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');?>
                                    </a>
                                </div>

                                <div class="cont_info_anons">
                                    <p><?=mb_strimwidth(strip_tags((string)$item['anons']), 0, 220, '...');?></p>
                                </div>

                                <div class="cont_info_bottom">
                                    <a class="cont_read_btn" href="<?= PATH ?>/<?=$type->param_url;?>/<?=$item['alias'];?>">
                                        Читать подробнее
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pb-4 pt-3">
                <?php if ($pagination->countPages > 1): ?>
                    <?=$pagination;?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-light border rounded-3">
                Материалы пока не добавлены.
            </div>
        <?php endif; ?>
    </div>
</section>
PHP;

            file_put_contents($dir_index, $phpContent_index);
        } else {
            @unlink($viewDir . '/index.php');
        }

        $dir_controller = APP . '/controllers/' . $fileName . 'Controller.php';

        $phpContent_view = <<<'PHP'
<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i><span class="visually-hidden">Главная</span></a>
                </li>

                <?php if ($type->hide_anons == 'show'): ?>
                    <li class="breadcrumb-item">
                        <a href="<?= PATH ?>/<?=$type->param_url?>"><?=htmlspecialchars($type->name, ENT_QUOTES, 'UTF-8');?></a>
                    </li>
                <?php endif; ?>

                <li class="breadcrumb-item active"><?=htmlspecialchars($find->name, ENT_QUOTES, 'UTF-8');?></li>
            </ol>
        </nav>
    </div>
</div>

<?php
$schemaType = 'Article';
$typeParam = mb_strtolower((string)($type->param_url ?? ''));

switch ($typeParam) {
    case 'news':
        $schemaType = 'NewsArticle';
        break;
    case 'pages':
    case 'page':
        $schemaType = 'WebPage';
        break;
    case 'services':
        $schemaType = 'WebPage';
        break;
    case 'articles':
    default:
        $schemaType = 'Article';
        break;
}

$shopName = \ishop\App::$app->getProperty('shop_name');
$logoUrl = rtrim(PATH, '/') . '/images/' . \ishop\App::$app->getProperty('og_logo');
$pageUrl = rtrim(PATH, '/') . '/' . trim((string)$type->param_url, '/') . '/' . $find->alias;
$imageUrl = !empty($find->img)
    ? rtrim(PATH, '/') . '/images/contents/baseimg/' . $find->img
    : $logoUrl;

$descRaw = '';
if (!empty($find->description)) {
    $descRaw = (string)$find->description;
} elseif (!empty($find->anons)) {
    $descRaw = strip_tags((string)$find->anons);
} else {
    $descRaw = trim(strip_tags((string)$find->content));
}
$descRaw = mb_substr($descRaw, 0, 300);

$entitySchema = [
    '@context' => 'https://schema.org',
    '@type' => $schemaType,
    'headline' => $find->name,
    'name' => $find->name,
    'url' => $pageUrl,
    'description' => $descRaw,
    'image' => [$imageUrl],
    'author' => [
        '@type' => 'Organization',
        'name' => $shopName,
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $shopName,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $logoUrl,
        ],
    ],
];

if (!empty($find->date_post)) {
    $entitySchema['datePublished'] = date('c', strtotime($find->date_post));
}
if (!empty($find->date_last_modified)) {
    $entitySchema['dateModified'] = date('c', strtotime($find->date_last_modified));
}

$breadcrumbItems = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Главная',
        'item' => rtrim(PATH, '/'),
    ],
];

if ($type->hide_anons == 'show') {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $type->name,
        'item' => rtrim(PATH, '/') . '/' . trim((string)$type->param_url, '/'),
    ];
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $find->name,
        'item' => $pageUrl,
    ];
} else {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $find->name,
        'item' => $pageUrl,
    ];
}

$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
];
?>

<script type="application/ld+json"><?=json_encode($entitySchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>
<script type="application/ld+json"><?=json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>

<section class="contents py-4 py-md-5">
    <div class="container">
        <div class="row">
            <?php if (!empty($find)):
                if ($type->hide_clicks == 'show') {
                    \R::exec('UPDATE contents SET clicks = clicks + 1 WHERE id = ?', [$find->id]);
                }
            ?>
                <div class="col-12">
                    <article class="bg-white rounded-4 shadow-sm p-3 p-md-4 p-lg-5">
                        <header class="mb-4">
                            <h1 class="mb-3"><?=htmlspecialchars($find->name, ENT_QUOTES, 'UTF-8');?></h1>

                            <?php if (($type['hide_date_post'] ?? '') === 'show'): ?>
                                <div class="cont_info_data">
                                    <time datetime="<?=date('c', strtotime($find['date_post']))?>">
                                        <?= \ishop\App::contdate($find['date_post']); ?>
                                    </time>
                                </div>
                            <?php endif; ?>
                        </header>

                        <?php if ($find->img && $find->img_hide == 'show'): ?>
                            <div class="cont-img mb-4">
                                <img class="img-fluid rounded-4" src="<?= PATH ?>/images/contents/baseimg/<?=$find->img;?>" alt="<?=htmlspecialchars($find->name, ENT_QUOTES, 'UTF-8');?>">
                            </div>
                        <?php endif; ?>

                        <div class="cont-desc">
                            <?=$find->content;?>
                        </div>
                    </article>
                </div>

                <?php
                    $curr = \ishop\App::$app->getProperty('currency');
                    $cats = \ishop\App::$app->getProperty('cats');
                ?>

                <?php if($related): ?>
                    <div class="related_prod">
                        <section class="pb-5 mb-2 mb-xl-4 recomend-1">
                            <h2 class="h3 pb-2 mb-grid-gutter text-center">Связанные товары</h2>

                            <div class="review-wrap">
                                <div class="wrap-container">
                                    <div class="inner-container">
                                        <div class="swiper-container swiper1">
                                            <div class="swiper-wrapper">
                                                <?php foreach($related as $product): ?>
                                                    <div class="swiper-slide">
                                                        <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="swiper-button-inner">
                                <div class="swiper-button-next swiper-button-next-1"></div>
                                <div class="swiper-button-prev swiper-button-prev-1"></div>
                            </div>
                        </section>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</section>
PHP;

        $phpContent_controller_tpl = <<<'PHP'
<?php

namespace app\controllers;

use ishop\App;
use ishop\libs\Pagination;

class {{CLASS}}Controller extends AppController
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
            ['{{PARAM_URL}}']
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
PHP;

$phpContent_controller = str_replace(
    ['{{CLASS}}', '{{PARAM_URL}}'],
    [$fileName, trim((string)$data['param_url'], '/')],
    $phpContent_controller_tpl
);

        $dir_route = CONF . '/routes.php';

        $FileSourse_del = file_get_contents($dir_route);
        $pattern = "#//" . preg_quote($fileName, '#') . "//.*?//And" . preg_quote($fileName, '#') . "//\s*#is";
        $FileSourse_del = preg_replace($pattern, '', $FileSourse_del);
        file_put_contents($dir_route, $FileSourse_del);

        $phpRoute = "//" . $fileName . "//
Router::add('^" . $data['param_url'] . "/(?P<alias>[a-z0-9-]+)/?$', ['controller' => '" . $fileName . "', 'action' => 'view']);
//And" . $fileName . "//
//  Add here";

        $FileSourse = file_get_contents($dir_route);
        $FileSourse = str_replace('//  Add here', $phpRoute, $FileSourse);

        file_put_contents($dir_view, $phpContent_view);
        file_put_contents($dir_controller, $phpContent_controller);
        file_put_contents($dir_route, $FileSourse);
    }

    public function checkUnique()
    {
        $type = \R::findOne('content_type', 'name = ? AND param_url = ?', [$this->attributes['name'], $this->attributes['param_url']]);
        if ($type) {
            if ($type->name == $this->attributes['name']) {
                $this->errors['unique'][] = 'Название типа контента уже существует';
            }
            if ($type->param_url == $this->attributes['param_url']) {
                $this->errors['unique'][] = 'Служебное URL уже существует';
            }
            return false;
        }
        return true;
    }
}
