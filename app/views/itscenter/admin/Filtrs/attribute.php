<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0">Фильтры</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item">Группы фильтров</li>
          <li class="breadcrumb-item active">Фильтры</li>
        </ol>
      </div>
    </div>
  </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="menu_btn">
        <a href="<?=ADMIN;?>/filtrs/attribute-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить фильтр</a>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Фильтры</h3>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="example" class="table table-bordered display" width="100%"></table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- /.content -->

<?php
function notEmpty($v): bool {
    if (is_null($v)) return false;
    if (is_string($v)) return trim(strip_tags($v)) !== '';
    return !empty($v);
}

function calcSeoPercent(array $item): int
{
    $score = 0;

    // Веса (итого 100)
    $weights = [
        'title'       => 20,
        'description' => 15,
        'seo_h1'      => 10,
        'content'     => 20,
        'top_content' => 10,
        'related'     => 10,
        'technic'     => 5,
        'img'         => 5,
        'faq'         => 5,
    ];

    if (notEmpty($item['title'] ?? null))       $score += $weights['title'];
    if (notEmpty($item['description'] ?? null)) $score += $weights['description'];
    if (notEmpty($item['seo_h1'] ?? null))      $score += $weights['seo_h1'];

    if (notEmpty($item['content'] ?? null))     $score += $weights['content'];
    if (notEmpty($item['top_content'] ?? null)) $score += $weights['top_content'];

    if (notEmpty($item['img'] ?? null))         $score += $weights['img'];

    if (!empty($item['related_sizes_count']) && (int)$item['related_sizes_count'] > 0) $score += $weights['related'];
    if (!empty($item['technic_count']) && (int)$item['technic_count'] > 0)             $score += $weights['technic'];
    if (!empty($item['faq_count']) && (int)$item['faq_count'] > 0)                     $score += $weights['faq'];

    if ($score < 0) $score = 0;
    if ($score > 100) $score = 100;

    return (int)$score;
}

function seoBarHtml(int $seo): string
{
    if ($seo <= 40)      $cls = 'bg-danger';
    elseif ($seo <= 70)  $cls = 'bg-warning';
    elseif ($seo < 100)  $cls = 'bg-info';
    else                 $cls = 'bg-success';

    return "SEO {$seo}% <div class='progress progress-xs'>
        <div class='progress-bar {$cls} progress-bar-striped' role='progressbar'
            aria-valuenow='{$seo}' aria-valuemin='0' aria-valuemax='100'
            style='width: {$seo}%'>
            <span class='sr-only'>{$seo}% Complete</span>
        </div>
    </div>";
}

function routingBadgeHtml(array $item): string
{
    $total    = (int)($item['total_rules_count'] ?? 0);
    $landing  = (int)($item['landing_count'] ?? 0);
    $redirect = (int)($item['redirect_count'] ?? 0);

    if ($total <= 0) {
        return '<span class="badge badge-secondary">Не настроено</span>';
    }

    if ($landing > 0 && $redirect > 0) {
        return '<span class="badge badge-success">Landing: ' . $landing . ' / Redirect: ' . $redirect . '</span>';
    }

    if ($landing > 0) {
        return '<span class="badge badge-info">Landing: ' . $landing . '</span>';
    }

    if ($redirect > 0) {
        return '<span class="badge badge-warning">Redirect: ' . $redirect . '</span>';
    }

    return '<span class="badge badge-secondary">Есть правила</span>';
}

function productCountHtml(array $item): string
{
    $active = (int)($item['active_product_count'] ?? 0);
    $total = (int)($item['product_count'] ?? 0);
    $badge = $active > 0 ? 'badge-success' : 'badge-danger';
    $title = $active > 0 ? 'Активные товары' : 'Фильтр не привязан к активным товарам';

    $html = '<span class="badge ' . $badge . '" title="' . h($title) . '">' . $active . '</span>';

    if ($total !== $active) {
        $html .= '<br><small title="Всего привязанных товаров">всего: ' . $total . '</small>';
    }

    return $html;
}

function filterPhotoHtml(array $item): string
{
    $img = trim((string)($item['img'] ?? ''));

    if ($img === '') {
        return '<span class="badge badge-secondary">Нет</span>';
    }

    $relPath = 'images/filtrs/baseimg/' . $img;
    $absPath = WWW . '/' . $relPath;

    if (is_file($absPath)) {
        return '
            <a href="/' . h($relPath) . '" target="_blank" title="' . h($img) . '">
                <img src="/' . h($relPath) . '"
                     alt=""
                     style="width:42px;height:42px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
            </a>
            <div><span class="badge badge-success">OK</span></div>
        ';
    }

    return '
        <span class="badge badge-danger">Битое</span>
        <div style="font-size:11px;line-height:1.2;max-width:130px;word-break:break-all;">
            ' . h($img) . '
        </div>
    ';
}
?>

<?php
$rows = [];

foreach ($attrs as $item) {
    $seo = calcSeoPercent($item);
    $itog_seo = seoBarHtml($seo);
    $routeInfo = routingBadgeHtml($item);
    $productInfo = productCountHtml($item);

    $url = '';
    if (!empty($item['url_params'])) {
        $previewPath = !empty($item['preview_category_alias'])
            ? '/category/' . rawurlencode((string)$item['preview_category_alias']) . '/' . rawurlencode((string)$item['alias'])
            : \app\services\filters\FilterUrlHelper::buildBestCategoryFilterPath(
                (int)$item['id'],
                (string)$item['alias'],
                (string)$item['url_params']
            );

        $url = ' <a target="_blank" href="' . h($previewPath) . '"><i class="fas fa-eye"></i></a>';
    }

    $option = '<a href="' . ADMIN . '/filtrs/attribute-edit?id=' . $item['id'] . '"><i class="fas fa-pencil-alt"></i></a>
               <a class="delete" href="' . ADMIN . '/filtrs/attribute-delete?id=' . $item['id'] . '"><i class="fas fa-times-circle text-danger"></i></a>' . $url;

    $photoInfo = filterPhotoHtml($item);

    $rows[] = [
        (string)($item['value'] ?? ''),
        (string)($item['gname'] ?? ''),
        $photoInfo,
        $productInfo,
        $routeInfo,
        $itog_seo,
        $option,
    ];
}
?>

<script>
var dataSet = <?= json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

$(document).ready(function() {
  $('#example').DataTable({
    stateSave: true,
    lengthChange: true,
    lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "Все"]],
    aoColumnDefs: [{ bSortable: false, aTargets: [2, 3, 4, 5, 6] }],
    data: dataSet,
    columns: [
      { title: "Наименование" },
      { title: "Группа" },
      { title: "Фото", width: "90px" },
      { title: "Товары", width: "90px" },
      { title: "Маршрутизация", width: "170px" },
      { title: "SEO", width: "120px" },
      { title: "Действия", width: "60px" }
    ]
  });
});
</script>
