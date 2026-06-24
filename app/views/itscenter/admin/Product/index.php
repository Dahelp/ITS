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
$productUrl = function($badgeValue) use ($categoryId) {
	$params = [];
	if($categoryId){ $params['category_id'] = $categoryId; }
	if($badgeValue !== ''){ $params['badge'] = $badgeValue; }
	return ADMIN.'/product'.($params ? '?'.http_build_query($params) : '');
};
$ajaxParams = [];
if($categoryId){ $ajaxParams['category_id'] = $categoryId; }
if($activeBadge !== ''){ $ajaxParams['badge'] = $activeBadge; }
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
<section class="content">
    <div class="row">
        <div class="col-md-12">
			<div class="menu_btn">
                <a href="<?=ADMIN;?>/product/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить товар</a>
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Товары<?php if($category["name"]) { echo " категории ".$category["name"].""; }?></h3>
					<ul class="nav nav-pills ml-auto p-2">
					    <li class="nav-item"><a class="nav-link<?php if($_GET["category_id"]) { echo " active"; } ?>" href="<?=ADMIN;?>/product/category">Товары по категориям</a></li>
						<li class="nav-item"><a class="nav-link<?php if(!$_GET["category_id"]) { echo " active"; } ?>" href="<?=ADMIN;?>/product">Все товары</a></li>
					</ul>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="mb-3">
						<div class="btn-group flex-wrap" role="group" aria-label="Фильтр по отметкам товаров">
							<?php foreach($badgeLinks as $badgeValue => $badgeTitle): ?>
								<a class="btn btn-sm <?= $activeBadge === $badgeValue ? 'btn-primary' : 'btn-default'; ?>" href="<?= $productUrl($badgeValue); ?>"><?= $badgeTitle; ?></a>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="table-responsive">
						<table id="example" class="table table-bordered display" width="100%">
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
$(document).ready(function () {
	$('#example thead tr th').eq(8).after('<th>Отметки</th>');
	var hideFromExport = [1, 6, 7, 8, 9, 10, 11];
	var table = $('#example').DataTable({		
		"processing": true,
		"serverSide": true,
		"stateSave": true,
		"stateLoadParams": function (settings, data) {
			if (!data.columns || data.columns.length !== 12) {
				return false;
			}
		},
		"lengthChange": true,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 1, 4, 6, 7, 8, 11 ] }],
		dom: '<"blok-bottoms"B><"blok-table"lfrtip>',
		"ajax": {
            url: adminpath + '/product/server-processing<?= $ajaxQuery; ?>',		
        },
        buttons: [ 
			{
				extend: 'copyHtml5',
				exportOptions: {
					columns: function ( idx, data, node ) {
					var isVisible = table.column( idx ).visible();
						var isNotForExport = $.inArray( idx, hideFromExport ) !== -1;
						return isVisible && !isNotForExport ? true : false; 
					}
				},
				text: 'Копировать'
			},
			{
				extend: 'csvHtml5',
				bom: true,
				exportOptions: {
					columns: function ( idx, data, node ) {
					var isVisible = table.column( idx ).visible();
						var isNotForExport = $.inArray( idx, hideFromExport ) !== -1;
						return isVisible && !isNotForExport ? true : false; 
					}
				}
			},
			{
				extend: 'excelHtml5',
				exportOptions: {
					columns: function ( idx, data, node ) {
					var isVisible = table.column( idx ).visible();
						var isNotForExport = $.inArray( idx, hideFromExport ) !== -1;
						return isVisible && !isNotForExport ? true : false; 
					}
				}
			},
			{
				extend: 'pdfHtml5',
				exportOptions: {
					columns: function ( idx, data, node ) {
					var isVisible = table.column( idx ).visible();
						var isNotForExport = $.inArray( idx, hideFromExport ) !== -1;
						return isVisible && !isNotForExport ? true : false; 
					}
				}
			},
			{
				extend: 'print',
				exportOptions: {
					columns: function ( idx, data, node ) {
					var isVisible = table.column( idx ).visible();
						var isNotForExport = $.inArray( idx, hideFromExport ) !== -1;
						return isVisible && !isNotForExport ? true : false; 
					}
				}
			},
			{
				extend: 'colvis',
				text: 'Настроить колонки'				
			}

        ],		
		
		createdRow: function( row, data, dataIndex){
            if( data[9] == "Не активный"  ){
                $(row).css('background-color', '#fed8d8');
            } 
			if( data[9] == "Закрыт от индексации"  ){
                $(row).css('background-color', '#d7d6d6');
            } 
		}
	});
	table.on('draw', function () {
		$('#example tbody tr').each(function () {
			var status = $(this).find('td').eq(10).text();
			if (status === 'Не активный') {
				$(this).css('background-color', '#fed8d8');
			}
			if (status === 'Закрыт от индексации') {
				$(this).css('background-color', '#d7d6d6');
			}
		});
	});
	$('#example').on('change', '.product-badge-toggle', function () {
		var input = this;
		input.disabled = true;
		$.ajax({
			url: adminpath + '/product/badge-toggle',
			type: 'POST',
			dataType: 'json',
			data: {
				id: input.getAttribute('data-id'),
				field: input.getAttribute('data-field'),
				value: input.checked ? 1 : 0
			}
		}).done(function (res) {
			if (!res || !res.success) {
				input.checked = !input.checked;
				alert('Не удалось сохранить отметку товара');
			}
		}).fail(function () {
			input.checked = !input.checked;
			alert('Не удалось сохранить отметку товара');
		}).always(function () {
			input.disabled = false;
		});
	});
})
</script>
