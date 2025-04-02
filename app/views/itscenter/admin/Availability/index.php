<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Заявки о поступлении товара</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Заявки о поступлении товара</li>
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
                    <h3 class="card-title">Список заявок о поступлении товара</h3>
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
<?php foreach($availability as $item) {
	$uname = \R::findOne('user', 'id = ?', [$item['user_id']]);
	if($uname>0){ $otvname = "<a href='".ADMIN."/user/edit?id=".$uname["id"]."'>".$uname["name"]."</a>"; } else { $otvname = "Без регистрации"; }
	$contdate = \ishop\App::contdatetime($item['data_create']);
	$product = \R::findOne('product', 'id = ?', [$item['product_id']]);
	if($item['status_nalichiya'] == "0"){ $status = "Нет в наличии"; }
	if($item['status_nalichiya'] == "1"){ $status = "В наличии"; }
	if($item['status_otpravki'] == "0"){ $status_otpravki = "Не отправлен"; }
	if($item['status_otpravki'] == "1"){ $status_otpravki = "Отправлен"; } 
	$option = "<a href='".ADMIN."/availability/view?id=".$item["id"]."'><i class='fas fa-fw fa-eye'></i></a> <a class='delete' href='".ADMIN."/availability/delete?id=".$item["id"]."'><i class='fas fa-times-circle text-danger'></i></a>";
    $set .= '[ "'.$item["id"].'", "'.$otvname.'", "'.$contdate.'", "'.$status.'", "'.$product["name"].'", "'.$item["data_postupleniya"].'", "'.$status_otpravki.'", "'.$item["data_mail"].'", "'.$option.'" ],';
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
			{ title: "Пользователь" },
			{ title: "Дата" },
            { title: "Статус наличия" },
			{ title: "Товар" },
			{ title: "Дата поступления" },
			{ title: "Стату отправки" },
			{ title: "Дата отправки" },
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