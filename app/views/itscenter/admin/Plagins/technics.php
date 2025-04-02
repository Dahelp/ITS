<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Техника</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
              <li class="breadcrumb-item active">Список техники</li>
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
				<a href="<?=ADMIN;?>/plagins/technics-add-type" class="btn btn-success"><i class="fa fa-fw fa-plus"></i> Добавить категорию</a>
				<a href="<?=ADMIN;?>/plagins/technics-add-manufacturer" class="btn btn-secondary"><i class="fa fa-fw fa-plus"></i> Добавить производителя</a>
				<a href="<?=ADMIN;?>/plagins/technics-import" class="btn btn-success"><i class="fad fa-fw fa-file-csv"></i> Импорт</a>
				<a href="<?=ADMIN;?>/plagins/technics-export" class="btn btn-primary"><i class="fad fa-fw fa-file-csv"></i> Экспорт</a>
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Техника</h3>
					<ul class="nav nav-pills ml-auto p-2">
					    <li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Категории</a></li>
						<li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">Производители</a></li>
					    <li class="nav-item"><a class="nav-link" href="#tab_3" data-toggle="tab">Вся техника</a></li>			  
					</ul>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="tab-content">
						<div class="tab-pane active" id="tab_1">
							
							<!-- Аккордеон -->
							<table class="table table-bordered">
								<thead>
									<tr>										
										<th>Категория</th>
										<th style="width: 40px">Количество</th>
										<th style="width: 100px">Действия</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($category as $key => $item): ?>
										<?php if(count($item) > 1): // если это родительская категория ?>										
										<tr data-widget="expandable-table" aria-expanded="false">
											<td>
												<i class="expandable-table-caret fas fa-caret-right fa-fw"></i>
												<label><?=$item[0]?></label>
											</td>
										</tr>
										<tr class="expandable-body">
											<td>
												<div class="p-0" style="">
													<table class="table table-hover">
														<tbody>														
														<?php foreach($item['sub'] as $key => $sub): ?>
															<?php $countTech = \R::count('technics', "type_id = '".$key."'"); ?>
															<tr>
																<td> - <a href="<?php echo "".ADMIN."/plagins/technics-type?id=".$key."";?>"><?=$sub?></a><span class="float-right badge bg-primary"><?=$countTech?></span></td>
															</tr>
														<?php endforeach; ?>
														</tbody>
													</table>
												</div>
											</td>
										</tr>
										<?php elseif($item[0]): // если самостоятельная категория ?>
										<?php 
											$countTech = \R::count('technics', "type_id = '".$key."'");
											$group = \R::findOne('technics_type', 'id = ?', [$key]);
										?>
										<tr>
											<td>
												<a href="<?php echo "".ADMIN."/plagins/technics-type?id=".$key."";?>"><?=$item[0]?></a>											
											</td>
											<td>
												<span class="badge bg-primary"><?=$countTech?></span>												 
											</td>
											<td>
												<a href="<?php echo "".ADMIN."/plagins/technics-edit-type?id=".$key."";?>"><i class="fas fa-pencil-alt"></i></a>
												<a class="delete" href="<?php echo "".ADMIN."/plagins/technics-type-delete?id=".$key."";?>"><i class="fas fa-times-circle text-danger"></i></a>
												<a target="_blank" href="/technics/type/<?=$group["alias"]?>"><i class="fas fa-eye"></i></a>
											</td>
										</tr>
										<?php endif; ?>
									<?php endforeach; ?>
								</tbody>
							</table>                    
							<!-- Аккордеон -->
						</div>
						<!-- /.tab-pane -->
						<div class="tab-pane" id="tab_2">
							<div class="table-responsive">						
								<table id="exampleManufacturer" class="table table-bordered display" width="100%"></table>                    
							</div>
						</div>
						<!-- /.tab-pane -->
						<div class="tab-pane" id="tab_3">
							<div class="table-responsive">						
								<table id="example" class="table table-bordered display" width="100%">
									<thead>
										<tr>
											<th>ID</th>
											<th>Фото</th>
											<th>Тип техники</th>
											<th>Марка</th>
											<th>Модель</th>									
											<th>Размер шин</th>
											<th>SEO</th>
											<th>Действия</th>
										</tr>
									</thead>
								</table>                    
							</div>
						</div>
						<!-- /.tab-pane -->
					</div>                                    
				</div>
			</div>
		</div>
	</div>
</section>

<script>
$(document).ready(function() {
	
	var hideFromExport = [6,7];
    var table = $('#example').DataTable( {	
		"stateSave": true,
		"lengthChange": true,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		dom: '<"blok-bottoms"B><"blok-table"lfrtip>',
		"ajax": {
            url: adminpath + '/plagins/server-processing-technics<?php if($_GET["id"]) { echo "?id=".$_GET["id"].""; } ?>',		
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

        ]
        		
    } );
} );

$(document).ready(function() {
	
	$('.dataTables_filter input').val('<?=$_GET["search"]?>') + '<?=$_GET["search"]?>';
	$('.dataTables_filter input').click();
	$('.dataTables_filter input').focus();

	//table.search( this.value ).draw();
} );	
</script>

<script>
var dataSetManufacturer = [
<?php foreach($manufacturers as $manufact) {
	if(!empty($manufact["img"])) { $img = "<img src='/images/technics_manufacturer/baseimg/".$manufact["img"]."' alt='' style='max-height: 50px'>"; }else{ $img = "<img src='/images/no_image.jpg' alt='' style='max-height: 50px'>"; }
	$option = "<a href='".ADMIN."/plagins/technics-edit-manufacturer?id=".$manufact["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/plagins/technics-manufacturer-delete?id=".$manufact["id"]."'><i class='fas fa-times-circle text-danger'></i></a> <a target='_blank' href='/technics-manufacturer/".$manufact["alias"]."'><i class='fas fa-eye'></i></a></a>";
    $setManufacturer .= '[ "'.$manufact["id"].'", "'.$img.'", "'.$manufact["name"].'", "'.$option.'" ],';
 } echo "".$setManufacturer.""; ?>
 
];
 
$(document).ready(function() {
	
	var hideFromExport = [6];
    var table = $('#exampleManufacturer').DataTable( {		
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
        data: dataSetManufacturer,
        columns: [
            { title: "ID" },
			{ title: "Фото" }, 
            { title: "Производитель" },            
			{ title: "Действия" }
        ]		
    } );


} );
</script>