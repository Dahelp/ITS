<?php
$productId = (int)$product->id;
$inRelated = [];
$inSimilar = [];
$e = function($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$productLabel = function($row) use ($e) {
	$article = trim((string)($row['article'] ?? ''));
	return ($article !== '' ? $e($article).' - ' : '').$e($row['name'] ?? '');
};
$stockLabel = function($row) {
	$qty = (int)($row['quantity'] ?? 0);
	if($qty > 0){ return "<span class='text-success'>В наличии: {$qty}</span>"; }
	return "<span class='text-danger'>Нет в наличии</span>";
};
$renderProductTable = function($rows, $type, $emptyText) use ($e, $productId, $productLabel, $stockLabel) {
	ob_start();
	?>
	<div class="table-responsive">
		<table class="table table-bordered table-hover table-sm">
			<thead>
				<tr>
					<th style="width:70px">ID</th>
					<th style="width:130px">Артикул</th>
					<th>Товар</th>
					<th style="width:130px">Остаток</th>
					<th style="width:95px">Статус</th>
					<th style="width:90px">Действия</th>
				</tr>
			</thead>
			<tbody>
				<?php if(!empty($rows)): ?>
					<?php foreach($rows as $row): ?>
						<tr>
							<td><?= (int)$row['id']; ?></td>
							<td><?= $e($row['article'] ?? ''); ?></td>
							<td>
								<a target="_blank" href="<?= ADMIN; ?>/product/edit?id=<?= (int)$row['id']; ?>"><?= $productLabel($row); ?></a>
								<?php if(!empty($row['alias'])): ?>
									<a target="_blank" class="ml-2" href="/product/<?= $e($row['alias']); ?>"><i class="fas fa-eye"></i></a>
								<?php endif; ?>
							</td>
							<td><?= $stockLabel($row); ?></td>
							<td><?= ($row['hide'] ?? '') === 'show' ? 'Активный' : 'Скрыт'; ?></td>
							<td>
								<a class="btn btn-xs btn-danger delete" href="<?= ADMIN; ?>/product/link-delete?product_id=<?= $productId; ?>&type=<?= $type; ?>&link_id=<?= (int)$row['link_id']; ?>" title="Удалить связь">
									<i class="fas fa-times"></i>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr><td colspan="6" class="text-muted"><?= $e($emptyText); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
	return ob_get_clean();
};
$addForm = function($type, $direction, $placeholder) use ($productId) {
	ob_start();
	?>
	<form action="<?= ADMIN; ?>/product/link-add" method="post" class="mb-3 product-link-form">
		<input type="hidden" name="product_id" value="<?= $productId; ?>">
		<input type="hidden" name="type" value="<?= $type; ?>">
		<input type="hidden" name="direction" value="<?= $direction; ?>">
		<div class="product-link-row">
			<select name="items[]" class="form-control product-link-select" multiple data-placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?>"></select>
			<div class="product-link-actions">
				<button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить</button>
			</div>
		</div>
	</form>
	<?php
	return ob_get_clean();
};
?>
<style>
.product-link-row {
	display: flex;
	flex-wrap: nowrap;
	align-items: flex-start;
	gap: 8px;
	width: 100%;
}
.product-link-form .select2-container {
	flex: 1 1 auto;
	width: calc(100% - 112px) !important;
	min-width: 0;
}
.product-link-actions {
	flex: 0 0 104px;
	width: 104px;
}
.product-link-actions .btn {
	width: 100%;
	height: 38px;
	padding: .25rem .65rem;
	white-space: nowrap;
}
@media (max-width: 575.98px) {
	.product-link-row {
		flex-wrap: wrap;
	}
	.product-link-form .select2-container,
	.product-link-actions {
		flex-basis: 100%;
		width: 100% !important;
	}
}
</style>

<div class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-8">
				<h1 class="m-0">Перелинковка товара</h1>
				<div class="text-muted" style="margin-top:6px;">
					<strong><?= $e($product->article); ?></strong> <?= $e($product->name); ?>
				</div>
			</div>
			<div class="col-sm-4">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="<?= ADMIN; ?>">Главная</a></li>
					<li class="breadcrumb-item"><a href="<?= ADMIN; ?>/product">Товары</a></li>
					<li class="breadcrumb-item active">Перелинковка</li>
				</ol>
			</div>
		</div>
	</div>
</div>

<section class="content">
	<div class="row">
		<div class="col-md-12">
			<div class="menu_btn">
				<a href="<?= ADMIN; ?>/product" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> К списку товаров</a>
				<a href="<?= ADMIN; ?>/product/edit?id=<?= $productId; ?>" class="btn btn-primary"><i class="fas fa-pencil-alt"></i> Карточка товара</a>
				<a target="_blank" href="/product/<?= $e($product->alias); ?>" class="btn btn-default"><i class="fas fa-eye"></i> На сайте</a>
			</div>

			<div class="row">
				<div class="col-lg-6" id="related">
					<div class="card">
						<div class="card-header"><h3 class="card-title">Связанные товары в карточке</h3></div>
						<div class="card-body">
							<?= $addForm('related', 'out', 'Добавить связанные товары по артикулу или названию'); ?>
							<?= $renderProductTable($outRelated, 'related', 'Связанные товары не добавлены'); ?>
						</div>
					</div>
				</div>
				<div class="col-lg-6" id="similar">
					<div class="card">
						<div class="card-header"><h3 class="card-title">Похожие товары в карточке</h3></div>
						<div class="card-body">
							<?= $addForm('similar', 'out', 'Добавить похожие товары по артикулу или названию'); ?>
							<?= $renderProductTable($outSimilar, 'similar', 'Похожие товары не добавлены'); ?>
						</div>
					</div>
				</div>
			</div>

			<div class="row d-none" id="incoming">
				<div class="col-lg-6">
					<div class="card">
						<div class="card-header"><h3 class="card-title">Кто ссылается как связанный</h3></div>
						<div class="card-body">
							<?= $addForm('related', 'in', 'Добавить товар, который будет ссылаться на текущий'); ?>
							<?= $renderProductTable($inRelated, 'related', 'Нет входящих связей'); ?>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="card">
						<div class="card-header"><h3 class="card-title">Кто ссылается как похожий</h3></div>
						<div class="card-body">
							<?= $addForm('similar', 'in', 'Добавить товар, который будет показывать текущий как похожий'); ?>
							<?= $renderProductTable($inSimilar, 'similar', 'Нет входящих похожих товаров'); ?>
						</div>
					</div>
				</div>
			</div>

			<div class="card" id="content-links">
				<div class="card-header"><h3 class="card-title">Ссылки из контента</h3></div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-bordered table-hover table-sm">
							<thead>
								<tr>
									<th style="width:70px">ID</th>
									<th>Откуда</th>
									<th style="width:150px">Тип</th>
									<th style="width:95px">Статус</th>
									<th style="width:115px">Действия</th>
								</tr>
							</thead>
							<tbody>
								<?php if(!empty($contentLinks)): ?>
									<?php foreach($contentLinks as $item): ?>
										<tr>
											<td><?= (int)$item['content_id']; ?></td>
											<td><?= $e($item['name'] ?? ''); ?></td>
											<td><?= $e($item['type_name'] ?? ''); ?></td>
											<td><?= ($item['hide'] ?? '') === 'show' ? 'Активный' : 'Скрыт'; ?></td>
											<td>
												<a target="_blank" class="btn btn-xs btn-primary" href="<?= ADMIN; ?>/contents/page-edit?id=<?= (int)$item['content_id']; ?>" title="Открыть источник"><i class="fas fa-pencil-alt"></i></a>
												<?php if(!empty($item['param_url']) && !empty($item['alias'])): ?>
													<a target="_blank" class="btn btn-xs btn-default" href="/<?= $e($item['param_url']); ?>/<?= $e($item['alias']); ?>" title="На сайте"><i class="fas fa-eye"></i></a>
												<?php endif; ?>
												<a class="btn btn-xs btn-danger delete" href="<?= ADMIN; ?>/product/link-delete?product_id=<?= $productId; ?>&type=content&link_id=<?= (int)$item['link_id']; ?>" title="Удалить связь"><i class="fas fa-times"></i></a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else: ?>
									<tr><td colspan="5" class="text-muted">Контентные ссылки на товар не найдены</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
$(function () {
	$('.product-link-select').select2({
		language: 'ru',
		width: '100%',
		minimumInputLength: 1,
		placeholder: function(){
			return $(this).data('placeholder') || 'Начните вводить артикул или название';
		},
		ajax: {
			url: adminpath + '/product/related-product',
			delay: 250,
			dataType: 'json',
			data: function (params) {
				return { q: params.term, page: params.page };
			},
			processResults: function (data) {
				return { results: data.items };
			}
		}
	});
});
</script>
