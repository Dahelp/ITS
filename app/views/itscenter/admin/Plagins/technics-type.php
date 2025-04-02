<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Категория техники <?=$category["name"]?></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/technics">Список техники</a></li>
              <li class="breadcrumb-item active">Категория техники <?=$category["name"]?></li>
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
                <a href="<?=ADMIN;?>/plagins/technics-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить технику</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Категория техники <?=$category["name"]?></h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">					
					<div class="table-responsive">						
						<table id="example-<?=$category["id"]?>" class="table table-bordered display" width="100%"></table>                    
					</div>						                                    
				</div>
			</div>
		</div>
	</div>
</section>

<script>
var dataSet = [
<?php foreach($technics as $technic) {
	$tiposize = \R::count('technics_tiposize', 'technics_id = ?', [$technic["id"]]);
	if(!empty($technic["img"])) { $img = "<img src='/images/technics/mini/".$technic["img"]."' alt='' style='max-height: 50px'>"; }else{ $img = "<img src='/images/nof.jpg' alt='' style='max-height: 50px'>"; }
	if($technic['hide'] == 'show') { $hide = "Активный"; }
	if($technic['hide'] == 'hide') { $hide = "Неактивный"; }
	if($technic['hide'] == 'lock') { $hide = "Закрыт от индексации"; }
	if($technic['title'] !="") { $s1 = "20"; }else{ $s1 = 0; }
	if($technic['description'] !="") { $s2 = "20"; }else{ $s2 = 0; }
	if($technic['keywords'] !="") { $s3 = "20"; }else{ $s3 = 0; }
	if($technic['content'] !="") { $s4 = "20"; }else{ $s4 = 0; }
	if($technic['img'] !="") { $s5 = "20"; }else{ $s5 = 0; }
	$seo = $s1+$s2+$s3+$s4+$s5; 
	if($seo == 20) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 40) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 60) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 80) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 100) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-success progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }

	$option = "<a href='".ADMIN."/plagins/technics-edit?id=".$technic["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/plagins/technics-delete?id=".$technic["id"]."'><i class='fas fa-times-circle text-danger'></i></a> <a target='_blank' href='/technics/".$technic["alias"]."'><i class='fas fa-eye'></i></a> <a target='_blank' href='".ADMIN."/plagins/technics-copy?id=".$technic["id"]."'><i class='fas fa-copy'></i></a><a href='".ADMIN."/plagins/technics-exportxml?id=".$technic["id"]."'><i class='fad fa-fw fa-file-csv'></i></a>";
    $set .= '[ "'.$technic["id"].'", "'.$img.'", "'.$technic['cat'].'", "'.$technic['manufacturer_name'].'", "'.htmlspecialchars($technic['model']).'", "'.$tiposize.'", "'.$itog_seo.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
	var hideFromExport = [6,7];
    var table = $('#example-<?=$category["id"]?>').DataTable( {	
		"stateSave": true,
		"lengthChange": true,
		"cache": true,
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
            { title: "Фото" },
            { title: "Тип техники" },
            { title: "Марка техники" },
            { title: "Модель техники" },
            { title: "Размер шин" },
			{ title: "SEO" },
			{ title: "Действия" }
        ]		
    } );


} );
</script>