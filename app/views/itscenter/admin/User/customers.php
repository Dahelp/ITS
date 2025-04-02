<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Пользователи</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список пользователей</li>
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
                <a href="<?=ADMIN;?>/user/add-customer" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить пользователя</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список пользователей</h3>
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
<!-- /.content -->

<script>
var dataSet = [
<?php foreach($users as $item) {
	if($item['date_last_visit'] !="0000-00-00 00:00:00") {
		$datelast = \ishop\App::contdatetime($item['date_last_visit']);
	}else{
		$datelast = "";
	}
	$option = "<a href='".ADMIN."/user/edit-customer?id=".$item["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/user/delete-customer?id=".$item["id"]."'><i class='fas fa-times-circle text-danger'></i></a>";
    $set .= '[ "'.$item["id"].'", "'.$item["email"].'", "'.$item["name"].'", "'.$item["groups"].'", "'.$datelast.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
    var table = $('#example').DataTable( {		
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 5 ] }],
		"aaSorting": [[ 0, "desc" ]],
        data: dataSet,
        columns: [
            { title: "ID" },
			{ title: "Email" },
			{ title: "Имя" },
            { title: "Группа" },
			{ title: "Последний визит" },
			{ title: "Действия", "width": "60px" }
        ],        		
    } );


} );
</script>
<?php }else{ ?>
<div class="alert alert-warning alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
		На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>