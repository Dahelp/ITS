<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Отзывы</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список отзывов</li>
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
                <a href="<?=ADMIN;?>/review/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить отзыв</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список отзывов</h3>
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
<?php foreach($reviews as $review) {
	
	$products = \R::getAll("SELECT product.name, product.alias FROM review_product JOIN product ON product.id = review_product.product_id AND review_product.review_id = ?", [$review["id"]]);
	$gallery = \R::getAll("SELECT id FROM review_gallery WHERE review_id = ?", [$review["id"]]);
	foreach($products as $product) { 

		$product_name[$review["id"]] .= "".$product["name"]." <a target='_blank' href='/product/".$product["alias"]."'><i class='fas fa-eye'></i></a><br />";
	}
	if($gallery) { $photo[$review["id"]] = "<i class='fad fa-image-polaroid'></i>"; }
	if($review["hide"]=="show") { $hide = "Активный"; }else{ $hide = "Не активный"; }

	$contdate = \ishop\App::contdatetime($review["date_post"]);

	$content = str_replace(array("\r", "\n"), '', $review["content"]);
	$option = "<a href='".ADMIN."/review/edit?id=".$review["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/review/delete?id=".$review["id"]."'><i class='fas fa-times-circle text-danger'></i></a>";
    $set .= '[ "'.$review["id"].'", "'.$product_name[$review["id"]].'", "'.$photo[$review["id"]].'", "'.$contdate.'", "'.$review["uname"].'", "'.$content.'", "'.$review["point"].'", "'.$hide.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
    var table = $('#example').DataTable( {		
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 8 ] }],
		"aaSorting": [[ 0, "desc" ]],
		"stateSave": true,
        data: dataSet,
        columns: [
            { title: "ID" },
			{ title: "Товар" },
			{ title: "Фото" },
            { title: "Дата" },
			{ title: "Имя" },
			{ title: "Отзыв" },
			{ title: "Оценка" },
			{ title: "Статус" },
			{ title: "Действия", "width": "60px" }
        ],
		
        createdRow: function( row, data, dataIndex){
            if( data[7] == "Не активный"  ){
                $(row).css('background-color', '#fed8d8');
            }					
		}		
    } );


} );
</script>