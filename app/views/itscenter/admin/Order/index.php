<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Заказы</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Заказы</li>
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
                <a href="<?=ADMIN;?>/order/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Создать заказ</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список заказов</h3>
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
<?php foreach($orders as $item) {
	$uname = \R::findOne('user', 'id = ?', [$item['admin_id']]); 
	$otvname = "<select class='form-control ordermanager' data-orderid='".$item["id"]."' name='manager_id'>";
	if($uname>0){ 
			$managers = \R::findAll('user', 'groups = ?', [2]); 
			if($managers) { $otvname .= "<option value='".$uname["id"]."'>".$uname["name"]."</option>"; }
			foreach($managers as $manager) { 
				$otvname .= "<option value='".$manager->id."'";
				if($order['admin_id'] == $manager->id) { $otvname .= "selected"; } 
				$otvname .= ">".$manager->name."</option>";
			 }			
		 }else{ $otvname .= "<option value=''>Не назначен</option>"; 
			foreach($managers as $manager) { 
				$otvname .= "<option value='".$manager->id."'";
				if($order['admin_id'] == $manager->id) { $otvname .= "selected"; } 
				$otvname .= ">".$manager->name."</option>";
			 }
		 }
	$otvname .= "</select>";	 
	$comp = \R::findOne('company', 'id = ?', [$item["comp_id"]]);
	if($comp>0) { $company_info = "".htmlspecialchars($comp["comp_short_name"])." (".$comp["inn"].")"; }else{ $company_info = "".$item["email"].""; }
	$contdate = \ishop\App::contdatetime($item['date']);
	$user_info = "<a href='".ADMIN."/user/edit?id=".$item['user_id']."'>".htmlspecialchars($item["name"])."</a>";
	$option = "<a href='".ADMIN."/order/view?id=".$item["id"]."'><i class='fas fa-fw fa-eye'></i></a> <a class='delete' href='".ADMIN."/order/delete?id=".$item["id"]."'><i class='fas fa-times-circle text-danger'></i></a>";
    $set .= '[ "'.$item["id"].'", "'.$item["inv"].'", "'.$user_info.'<br />'.$company_info.'", "'.$item["status_name"].'", "'.$curr['symbol_left'].' '.$item["sum"].' '.$curr['symbol_right'].'", "'.$contdate.'", "'.$otvname.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
    var table = $('#example').DataTable( {		
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 7 ] }],
		"aaSorting": [[ 0, "desc" ]],
		"stateSave": true,
        data: dataSet,
        columns: [
            { title: "ID" },
			{ title: "Номер заказа" },
			{ title: "Контакт" },
            { title: "Статус" },
			{ title: "Сумма" },
			{ title: "Дата создания" },
			{ title: "Ответственный" },
			{ title: "Действия", "width": "60px" }
        ],
		
        createdRow: function( row, data, dataIndex){
            if( data[3] == "Отменён"  ){
                $(row).css('background-color', '#fed8d8');
            } 
			if( data[3] == "Получен"  ){
                $(row).css('background-color', '#a2e8b3');
            }
			if( data[3] == "Ожидает оплаты"  ){
                $(row).css('background-color', '#d8f0fe');
            } 			
		}		
    } );


} );
</script>