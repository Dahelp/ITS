<?php
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
if(!isset($_GET['category_id'])){ $_GET['category_id'] = ''; }
if(empty($category)){ $category = []; }
if(!isset($category['name'])){ $category['name'] = ''; }
$activeBadge = isset($badge) ? $badge : (isset($_GET['badge']) ? $_GET['badge'] : '');
$badgeLinks = [
	'' => 'Все товары',
	'new_product' => 'Новинки',
	'hit' => 'Лидеры продаж',
	'sale' => 'Распродажа',
];
$insight = \app\services\ProductAnalytics::normalize($insight ?? ($_GET['insight'] ?? 'all'));
$analyticsScope = $categoryId.'|'.$activeBadge.'|'.$insight;
$defaultAnalyticsOrder = in_array($insight, ['avito_missing_stock','avito_prepare'], true) ? [[13,'desc']] : [[0,'desc']];
$productUrl = function($badgeValue) use ($categoryId, $insight) {
	$params = [];
	if($categoryId){ $params['category_id'] = $categoryId; }
    if($insight !== 'all'){ $params['insight'] = $insight; }
	if($badgeValue !== ''){ $params['badge'] = $badgeValue; }
	return ADMIN.'/product'.($params ? '?'.http_build_query($params) : '');
};
$ajaxParams = [];
if($categoryId){ $ajaxParams['category_id'] = $categoryId; }
if($activeBadge !== ''){ $ajaxParams['badge'] = $activeBadge; }
$ajaxParams['insight'] = $insight;
$ajaxQuery = $ajaxParams ? '?'.http_build_query($ajaxParams) : '';
?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Товары</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список товаров</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content product-admin">
    <div class="row">
        <div class="col-md-12">
			<div class="menu_btn">
                <a href="<?=ADMIN;?>/product/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить товар</a>
            </div>
            <?php require __DIR__.'/_analytics.php'; ?>
            <div class="card product-list-card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Товары<?php if($category["name"]) { echo " категории ".$category["name"].""; }?></h3>
					<ul class="nav nav-pills ml-auto p-2">
					    <li class="nav-item"><a class="nav-link<?php if($_GET["category_id"]) { echo " active"; } ?>" href="<?=ADMIN;?>/product/category">Товары по категориям</a></li>
						<li class="nav-item"><a class="nav-link<?php if(!$_GET["category_id"]) { echo " active"; } ?>" href="<?=ADMIN;?>/product">Все товары</a></li>
					</ul>
                </div>
                <!-- /.card-header -->
                <div class="card-body pa-results" id="product-results">
                    <div class="pa-filter-bar">
                        <p><strong><?= htmlspecialchars(\app\services\ProductAnalytics::labels()[$insight],ENT_QUOTES,'UTF-8') ?></strong> <span class="text-muted">· <?= (int)($analytics[$insight] ?? 0) ?> товаров</span><?php if($insight !== 'all'): ?><a class="pa-filter-clear" href="<?= htmlspecialchars($analyticsUrl('all'),ENT_QUOTES,'UTF-8') ?>">Сбросить показатель</a><?php endif; ?></p>
                        <label for="product-analytics-order">Сначала
                            <select id="product-analytics-order" class="form-control form-control-sm">
                                <option value="" disabled>По выбранному столбцу</option><option value="0:desc">Новые товары</option><option value="13:desc">Больший свободный остаток</option><option value="14:asc">Неполное собственное SEO</option><option value="19:asc">Неполный шаблон InSEO</option><option value="15:asc">Меньше связанных товаров</option><option value="16:asc">Меньше похожих товаров</option><option value="17:asc">Меньше ссылок из материалов</option><option value="18:asc">Меньше входящих ссылок</option>
                            </select>
                        </label>
                    </div>
					<div class="mb-3">
						<div class="btn-group flex-wrap" role="group" aria-label="Фильтр по отметкам товаров">
							<?php foreach($badgeLinks as $badgeValue => $badgeTitle): ?>
								<a class="btn btn-sm <?= $activeBadge === $badgeValue ? 'btn-primary' : 'btn-default'; ?>" href="<?= $productUrl($badgeValue); ?>"><?= $badgeTitle; ?></a>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="product-table-wrap">
						<table id="example" class="table product-table" width="100%">
							<thead>
								<tr>
									<th>ID</th>
									<th>Фото</th>
									<th>Артикул</th>
									<th>Наименование</th>
									<th>Категория</th>									
									<th>Цена</th>
									<th>SEO/InSEO</th>
									<th>Атрибуты</th>
									<th>Перелинковка</th>
									<th>Статус</th>
									<th>Действия</th>
									<th>Avito</th><th>Свободный остаток</th><th>SEO, %</th><th>Связанные, шт.</th><th>Похожие, шт.</th><th>Материалы, шт.</th><th>Входящие, шт.</th><th>InSEO, %</th>
								</tr>
							</thead>
						</table>                    
					</div>                                   
				</div>
			</div>
		</div>
	</div>
</section>
<?php 


?>
<script>
window.productCatalogConfig = <?= json_encode([
    'ajax'=>ADMIN.'/product/server-processing'.$ajaxQuery,
    'scope'=>$analyticsScope, 'order'=>$defaultAnalyticsOrder,
], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
</script>
<script src="<?= PATH ?>/js/admin-product-catalog.js?v=<?= (int)filemtime(WWW.'/js/admin-product-catalog.js') ?>"></script>
