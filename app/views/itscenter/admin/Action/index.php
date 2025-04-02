<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Акции</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список товаров по акции</li>
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
                <a href="<?=ADMIN;?>/action/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить товар в акцию</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список товаров по акции</h3>
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
<?php 
	$data_yestoday = date("Y-m-d H:i:s");
	foreach($actions as $action) {
	
	if($action['type'] == "%") {
		$skidka = $action['price']-($action['price'] / 100 * $action['znachenie']);
		$skidka = explode('.', $skidka);  
		$skidka = $skidka[0];
		$skidka = round($skidka, -1);
		$itog_skidka = $action['price']-$skidka; 												 
	}
	if($action['type'] == "руб.") {
		$itog_skidka = $action['znachenie'];
		$skidka = $action['price']-$action['znachenie'];
	}
	
	$option = "<a href='".ADMIN."/action/edit?id=".$action["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/action/delete?id=".$action["id"]."'><i class='fas fa-times-circle text-danger'></i></a> <a target='_blank' href='/product/".$action["alias"]."'><i class='fas fa-eye'></i></a>";
    $set .= '[ "'.$action['name'].'", "'.$action['article'].'", "'.$action['price'].'", "'.$action['znachenie'].' '.$action['type'].'", "'.$itog_skidka.' руб.", "'.$skidka.' руб.", "'.$action['quantity'].'", "'.$action['date_start'].'", "'.$action['date_end'].'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {

    var table = $('#example').DataTable( {
		"lengthChange": true,
		"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 9 ] }],
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],		        
        data: dataSet,
        columns: [
            { title: "Наименование" },
            { title: "Артикул" },
            { title: "Цена" },
            { title: "Скидка" },
            { title: "Размер скидки" },
			{ title: "Окончательная цена" },
			{ title: "Наличие" },
			{ title: "Дата начала акции" },
			{ title: "Дата окончании акции" },
			{ title: "Действия" }
        ],
		
        createdRow: function( row, data, dataIndex){
            if( data[8] < "<?=$data_yestoday;?>" ){
                $(row).css('background-color', '#fed8d8');
            } 
			
		}		
    } );


} );
</script>