<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Компании</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Компании</li>
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
                <a href="<?=ADMIN;?>/company/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить компанию</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список компаний</h3>
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
<?php foreach($company as $item) {
	$hide = $item['hide'] ? 'Aктивный' : 'Не активный';
	$user = \R::findOne('user', 'id = ?', [$item['user_id']]);
	$otuser = \R::findOne('user', 'id = ?', [$user->admin_id]);
	$data_create = \ishop\App::contdatetime($item['data_create']);
	$option = "<a href='".ADMIN."/company/edit?id=".$item["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/company/delete?id=".$item["id"]."'><i class='fas fa-times-circle text-danger'></i></a>";
	$word = "<a href='".ADMIN."/company/cardcompanyword?id=".$item['id']."'><i class='fad fa-file-word'></i></a>";
    $set .= '[ "'.$item["id"].'", "'.htmlspecialchars($item["comp_name"]).' ('.$item['inn'].')", "'.$user->name.'", "'.$data_create.'", "'.$hide.'", "'.$otuser->name.'", "'.$word.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
    var table = $('#example').DataTable( {		
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 7 ] }],
		"aaSorting": [[ 0, "desc" ]],
        data: dataSet,
        columns: [
            { title: "ID" },
			{ title: "Название компании" },
			{ title: "Контакт" },
            { title: "Дата создания" },
			{ title: "Статус" },
			{ title: "Ответственный" },
			{ title: "Карточка" },
			{ title: "Действия", "width": "60px" }
        ],        		
    } );


} );
</script>