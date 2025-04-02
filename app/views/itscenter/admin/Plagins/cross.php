<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Кросс-номера</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
              <li class="breadcrumb-item active">Кросс-номера</li>
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
                <a href="<?=ADMIN;?>/plagins/cross-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить кросс-номер</a>
				<a href="<?=ADMIN;?>/plagins/cross-vendor" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Производители</a>
				<a href="<?=ADMIN;?>/plagins/cross-import" class="btn btn-success"><i class="fad fa-fw fa-file-csv"></i> Импорт</a>
				<a href="<?=ADMIN;?>/plagins/cross-export" class="btn btn-primary"><i class="fad fa-fw fa-file-csv"></i> Экспорт</a>
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Список кросс-номеров</h3>
					<ul class="nav nav-pills ml-auto p-2">
					    <li class="nav-item"><a class="nav-link active" href="<?=ADMIN;?>/plagins/cross">Кроссы</a></li>
					    <li class="nav-item"><a class="nav-link" href="<?=ADMIN;?>/plagins/cross-vendor">Производители</a></li>			  
					</ul>
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
<script>
var dataSet = [
<?php foreach($crossing as $cross) {
	if($cross['tip_cross'] == '1') { $tip_cross = "Внешняя часть"; }
	if($cross['tip_cross'] == '2') { $tip_cross = "Внутренняя часть"; }
	if($cross['tip_cross'] == '3') { $tip_cross = "Не определено"; }
	if($cross['tip_cross'] == '4') { $tip_cross = "Комплект из 2х частей"; }
	if($cross['equipment_vendor'] == '1') { $equipment_vendor = "Да (OEM)"; }
	if($cross['equipment_vendor'] == '2') { $equipment_vendor = "Нет (Аналог)"; }
	$cross_urlname = strtolower($cross["cross_abbreviated_name"]);
	$option = "<a href='".ADMIN."/plagins/cross-edit?id=".$cross["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/plagins/delete-cross?id=".$cross["id"]."'><i class='fas fa-times-circle text-danger'></i></a> <a target='_blank' href='/cross/".$cross_urlname."'><i class='fas fa-eye'></i></a>";
    $set .= '[ "'.$cross["cross_id"].'", "'.$cross["cross_name"].'", "'.$cross['vendor'].'", "'.$cross['product_name'].'", "'.$tip_cross.'", "'.$equipment_vendor.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
	var hideFromExport = [6];
    var table = $('#example').DataTable( {		
		"lengthChange": true,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		dom: '<"blok-bottoms"B><"blok-table"lfrtip>',
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
				extend: 'csv',
				exportOptions: {
					columns: function ( idx, data, node ) {
					var isVisible = table.column( idx ).visible();
						var isNotForExport = $.inArray( idx, hideFromExport ) !== -1;
						return isVisible && !isNotForExport ? true : false; 
					}
				}
			},
			{
				extend: 'excel',
				exportOptions: {
					columns: function ( idx, data, node ) {
					var isVisible = table.column( idx ).visible();
						var isNotForExport = $.inArray( idx, hideFromExport ) !== -1;
						return isVisible && !isNotForExport ? true : false; 
					}
				}
			},
			{
				extend: 'pdf',
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
        data: dataSet,
        columns: [
            { title: "ID" },
            { title: "Кросс-номер" },
            { title: "Производитель" },
            { title: "Наименование товара" },
            { title: "Тип" },
            { title: "Производитель техники" },
			{ title: "Действия" }
        ]		
    } );


} );
</script>