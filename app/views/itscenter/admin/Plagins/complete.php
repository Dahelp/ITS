<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Комплекты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
              <li class="breadcrumb-item active">Список комплектов</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
			<div class="menu_btn">
                <a href="<?=ADMIN;?>/plagins/complete-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить комплект</a>
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Список комплектов</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">					
					<div class="table-responsive">						
						<table id="example" class="table table-bordered display" width="100%"></table>                    
					</div>						
				</div>				
			</div>
		</div>
	</div>
</section>

<?php
// helper для бейджа по расширению
$extBadge = function($ext) {
    if (!$ext) return "<span class='badge bg-danger'>нет</span>";
    switch ($ext) {
        case 'webp': return "<span class='badge bg-success'>webp</span>";
        case 'jpg':
        case 'jpeg': return "<span class='badge bg-primary'>jpg</span>";
        case 'png':  return "<span class='badge bg-info'>png</span>";
        case 'gif':  return "<span class='badge bg-warning text-dark'>gif</span>";
        default:     return "<span class='badge bg-secondary'>".$ext."</span>";
    }
};

$rows = [];
foreach ($complete as $compl) {
    $cid = (int)$compl["id"];

    // --- миниатюра
    $thumbSrc = !empty($compl["img"]) ? "/images/complete/mini/" . $compl["img"] : "/images/no_image.jpg";
    $thumb    = "<img src='{$thumbSrc}' alt='' style='max-height:50px'>";

    // --- основная: расширение
    $mainExt = '';
    if (!empty($compl['img']) && $compl['img'] !== 'no_image.jpg') {
        $mainExt = strtolower(pathinfo($compl['img'], PATHINFO_EXTENSION));
    }
    $badgeMain = $extBadge($mainExt);

    // --- галерея комплектов: количество и форматы
    $gal = \R::getRow("
        SELECT 
            COUNT(*) AS cnt,
            GROUP_CONCAT(DISTINCT LOWER(SUBSTRING_INDEX(img,'.',-1)) SEPARATOR ',') AS exts
        FROM plagins_complete_gallery
        WHERE complete_id = ?
    ", [$cid]);
    $galCnt  = (int)($gal['cnt'] ?? 0);
    $galExts = trim((string)($gal['exts'] ?? ''), ',');
    if ($galCnt > 0) {
        $parts = array_values(array_filter(array_unique(array_map('trim', explode(',', $galExts)))));
        $extBadges = [];
        foreach ($parts as $e) { $extBadges[] = $extBadge($e); }
        $badgeGallery = "<span class='badge bg-success me-1'>галерея: {$galCnt}</span> " . implode(' ', $extBadges);
    } else {
        $badgeGallery = "<span class='badge bg-danger'>галерея: нет</span>";
    }

    // --- ячейка «Фото» с миниатюрой + бейджами
    $imgCell = '
      <div class="d-flex align-items-start">
        <div>'.$thumb.'</div>
        <div class="small" style="line-height:1.35; margin-left:10px">
          <div>основная: '.$badgeMain.'</div>
          <div>'.$badgeGallery.'</div>
        </div>
      </div>';

    // --- статусы скрытия
    if ($compl['hide'] == 'show') { $hide = "Активный"; }
    if ($compl['hide'] == 'hide') { $hide = "Неактивный"; }
    if ($compl['hide'] == 'lock') { $hide = "Закрыт от индексации"; }

    // --- грубый SEO-«прогресс» (как у тебя)
    $s1 = ($compl['title']       !== "") ? 20 : 0;
    $s2 = ($compl['description'] !== "") ? 20 : 0;
    $s3 = ($compl['keywords']    !== "") ? 20 : 0;
    $s4 = ($compl['content']     !== "") ? 20 : 0;
    $s5 = ($compl['img']         !== "") ? 20 : 0;
    $seo = $s1 + $s2 + $s3 + $s4 + $s5;

    if ($seo <= 40) {
        $itog_seo = "SEO {$seo}% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='{$seo}' aria-valuemin='0' aria-valuemax='100' style='width: {$seo}%'></div></div>";
    } elseif ($seo <= 80) {
        $itog_seo = "SEO {$seo}% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='{$seo}' aria-valuemin='0' aria-valuemax='100' style='width: {$seo}%'></div></div>";
    } else {
        $itog_seo = "SEO {$seo}% <div class='progress progress-xs'><div class='progress-bar bg-success progress-bar-striped' role='progressbar' aria-valuenow='{$seo}' aria-valuemin='0' aria-valuemax='100' style='width: {$seo}%'></div></div>";
    }

    // --- состав комплекта + цена
    $pcp = \R::getAll("
        SELECT product.name, plagins_complete_product.price, plagins_complete_product.qty
        FROM plagins_complete_product 
        JOIN product ON product.id = plagins_complete_product.product_id
        WHERE plagins_complete_product.complete_id = ?
    ", [$cid]);

    $productListHtml = '';
    $priceSum = 0;
    foreach ($pcp as $p) {
        $productListHtml .= htmlspecialchars($p["name"], ENT_QUOTES, 'UTF-8')."<br>";
        $priceSum += ((float)$p["price"]) * ((int)$p["qty"]);
    }

    $cat = \R::findOne('category', 'id = ?', [$compl['category_id']]);

    // --- короткий back (без «портянок» DataTables)
    $back = ADMIN . '/plagins/complete';

    // --- действия (+ фикс кавычек в confirm)
    $option = "
		<a href='".ADMIN."/plagins/complete-edit?id=".$compl["id"]."'><i class='fas fa-pencil-alt'></i></a>
		<a class='delete' href='".ADMIN."/plagins/complete-delete?id=".$compl["id"]."'><i class='fas fa-times-circle text-danger'></i></a>
		<a target='_blank' href='/complete/".$compl["alias"]."'><i class='fas fa-eye'></i></a>
		<a href='".ADMIN."/media/convert?section=complete&id=".(int)$compl['id']."&back=".urlencode('/admin/plagins/complete')."' title='Перекодировать изображения комплекта'>
			<i class='fas fa-recycle'></i>
		</a>
		";


    // --- строка для DataTables
    $rows[] = [
        (string)$cid,
        $imgCell,
        htmlspecialchars($compl['name'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($cat['name'] ?? '', ENT_QUOTES, 'UTF-8'),
        $productListHtml,
        number_format($priceSum, 0, '.', ' '),
        $itog_seo,
        $option,
    ];
}
?>
<script>
var dataSet = <?= json_encode($rows, JSON_UNESCAPED_UNICODE) ?>;

$(document).ready(function() {
  $('#example').DataTable({
    stateSave: true,
    lengthChange: true,
    lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "Все"]],
    data: dataSet,
    columns: [
      { title: "ID" },
      { title: "Фото" },
      { title: "Название комплекта" },
      { title: "Категория" },
      { title: "Товарные позиции" },
      { title: "Цена комплекта" },
      { title: "SEO" },
      { title: "Действия" }
    ],
    columnDefs: [
      { targets: [1,4,6,7], orderable: false, searchable: false }
    ]
  });
});
</script>
