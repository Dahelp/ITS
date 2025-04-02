<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Клиенты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список клиентов</li>
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
                <a href="<?=ADMIN;?>/user/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить клиента</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список клиентов</h3>
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
	$otuser = \R::findOne('user', 'id = ?', [$item['admin_id']]);
	$comp = \R::findOne('company', 'id = ?', [$item['comp_id']]);
	$option = "<a href='".ADMIN."/user/edit?id=".$item["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/user/delete?id=".$item["id"]."'><i class='fas fa-times-circle text-danger'></i></a>";
    $set .= '[ "'.$item["id"].'", "'.$item["email"].'", "'.htmlspecialchars($item["name"]).'<br /><span style=\"color:#999;font-size:14px\">'.htmlspecialchars($comp->comp_name).'</span>", "'.$item["groups"].'", "'.$otuser->name.'", "'.$datelast.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
    var table = $('#example').DataTable( {		
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 6 ] }],
		"aaSorting": [[ 0, "desc" ]],
        data: dataSet,
        columns: [
            { title: "ID" },
			{ title: "Email" },
			{ title: "Имя" },
            { title: "Группа" },
			{ title: "Ответственный" },
			{ title: "Последний визит" },
			{ title: "Действия", "width": "60px" }
        ],        		
    } );


} );
</script>