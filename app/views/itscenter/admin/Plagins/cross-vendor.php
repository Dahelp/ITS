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
              <li class="breadcrumb-item active">Список производителей</li>
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
                <a href="<?=ADMIN;?>/plagins/cross" class="btn btn-primary">Кросс-номера</a>
				<a href="<?=ADMIN;?>/plagins/cross-add-vendor" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить производителя</a>
				<a href="<?=ADMIN;?>/plagins/cross-import" class="btn btn-success"><i class="fad fa-fw fa-file-csv"></i> Импорт</a>
				<a href="<?=ADMIN;?>/plagins/cross-export" class="btn btn-primary"><i class="fad fa-fw fa-file-csv"></i> Экспорт</a>
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Список производителей</h3>
					<ul class="nav nav-pills ml-auto p-2">
					    <li class="nav-item"><a class="nav-link" href="<?=ADMIN;?>/plagins/cross">Кроссы</a></li>
					    <li class="nav-item"><a class="nav-link active" href="<?=ADMIN;?>/plagins/cross-vendor">Производители</a></li>		  
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
<?php foreach($vendors as $vendor) {

	$option = "<a href='".ADMIN."/plagins/cross-edit-vendor?id=".$vendor['id']."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/plagins/delete-cross-vendor?id=".$vendor['id']."'><i class='fas fa-times-circle text-danger'></i></a>";
	$countcross = \R::count('plagins_cross', "vendor_id = '".$vendor['id']."'");
    $set .= '[ "'.$vendor['id'].'", "'.$vendor['name'].'", "'.$countcross.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
    var table = $('#example').DataTable( {		
		"lengthChange": true,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		
        data: dataSet,
        columns: [
            { title: "ID" },
            { title: "Производитель" },
            { title: "Кроссы" },
			{ title: "Действия" }
        ]		
    } );


} );
</script>