<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Производители техники</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/technics">Список техники</a></li>
              <li class="breadcrumb-item active">Производители техники</li>
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
                <a href="<?=ADMIN;?>/plagins/technics" class="btn btn-success"> Список техники</a>
				<a href="<?=ADMIN;?>/plagins/technics-add-manufacturer" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить производителя</a>				
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Производители техники</h3>
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
<?php foreach($manufacturer as $manufact) {
	if(!empty($manufact["img"])) { $img = "<img src='/images/technics_manufacturer/baseimg/".$manufact["img"]."' alt='' style='max-height: 50px'>"; }else{ $img = "<img src='/images/no_image.jpg' alt='' style='max-height: 50px'>"; }
	$option = "<a href='".ADMIN."/plagins/technics-edit-manufacturer?id=".$manufact["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/plagins/technics-manufacturer-delete?id=".$manufact["id"]."'><i class='fas fa-times-circle text-danger'></i></a> <a target='_blank' href='/technics-manufacturer/".$manufact["alias"]."'><i class='fas fa-eye'></i></a></a>";
    $set .= '[ "'.$manufact["id"].'", "'.$img.'", "'.$manufact["name"].'", "'.$option.'" ],';
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
			{ title: "Фото" }, 
            { title: "Производитель" },            
			{ title: "Действия" }
        ]		
    } );


} );
</script>