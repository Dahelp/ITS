<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Обратный звонок</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Обратный звонок</li>
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
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список запросов на обратный звонок</h3>
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
<?php foreach($callback as $item) {
	$uname = \R::findOne('user', 'id = ?', [$item['admin_id']]); 
	if($uname>0){ $otvname = "<a href='".ADMIN."/user/edit-customer?id=".$uname["id"]."'>".$uname["name"]."</a>"; } else { $otvname = "Не назначен"; }
	$contdate = \ishop\App::contdatetime($item['date_create']);
	if($item['hide'] == "show"){ $active = "Активный"; }else{ $active = "Закрыт"; }
	if($item['status'] == "0"){ $status = "Новый"; }
	if($item['status'] == "1"){ $status = "Обрабатывается"; }
	if($item['status'] == "2"){ $status = "Создан заказ"; }
	$option = "<a href='".ADMIN."/callback/view?id=".$item["id"]."'><i class='fas fa-fw fa-eye'></i></a> <a class='delete' href='".ADMIN."/callback/delete?id=".$item["id"]."'><i class='fas fa-times-circle text-danger'></i></a>";
    $set .= '[ "'.$item["id"].'", "'.$item["phone"].'", "'.$contdate.'", "'.$status.'", "'.$active.'", "'.$otvname.'", "'.$option.'" ],';
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
			{ title: "Телефон" },
			{ title: "Дата" },
            { title: "Статус" },
			{ title: "Активность" },
			{ title: "Ответственный" },
			{ title: "Действия", "width": "60px" }
        ],
		
        createdRow: function( row, data, dataIndex){
            if( data[4] == "Закрыт"  ){
                $(row).css('background-color', '#fed8d8');
            }			
		}		
    } );


} );
</script>