<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Комплекты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
              <li class="breadcrumb-item active">Список комплектов</li>
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
                <a href="<?=ADMIN;?>/plagins/complete-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить комплект</a>
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Список комплектов</h3>
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
<?php foreach($complete as $compl) {

	if(!empty($compl["img"])) { $img = "<img src='/images/complete/mini/".$compl["img"]."' alt='' style='max-height: 50px'>"; }else{ $img = "<img src='/images/no_image.jpg' alt='' style='max-height: 50px'>"; }
	if($compl['hide'] == 'show') { $hide = "Активный"; }
	if($compl['hide'] == 'hide') { $hide = "Неактивный"; }
	if($compl['hide'] == 'lock') { $hide = "Закрыт от индексации"; }
	if($compl['title'] !="") { $s1 = "20"; }else{ $s1 = 0; }
	if($compl['description'] !="") { $s2 = "20"; }else{ $s2 = 0; }
	if($compl['keywords'] !="") { $s3 = "20"; }else{ $s3 = 0; }
	if($compl['content'] !="") { $s4 = "20"; }else{ $s4 = 0; }
	if($compl['img'] !="") { $s5 = "20"; }else{ $s5 = 0; }
	$seo = $s1+$s2+$s3+$s4+$s5; 
	if($seo == 20) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 40) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 60) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 80) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 100) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-success progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	$pcp = \R::getAll("SELECT product.name, plagins_complete_product.price, plagins_complete_product.qty FROM plagins_complete_product JOIN product ON product.id = plagins_complete_product.product_id WHERE plagins_complete_product.complete_id = ?", [$compl["id"]]);
	foreach($pcp as $p){
		$product[$compl["id"]] .= "".$p["name"]."<br />";
		$price_complete[$compl["id"]] += $p["price"]*$p["qty"];
	}
	$cat = \R::findOne('category', 'id = ?', [$compl['category_id']]);
	$option = "<a href='".ADMIN."/plagins/complete-edit?id=".$compl["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/plagins/complete-delete?id=".$compl["id"]."'><i class='fas fa-times-circle text-danger'></i></a> <a target='_blank' href='/complete/".$compl["alias"]."'><i class='fas fa-eye'></i></a>";
    $set .= '[ "'.$compl["id"].'", "'.$img.'", "'.$compl['name'].'", "'.$cat['name'].'", "'.$product[$compl["id"]].'", "'.$price_complete[$compl["id"]].'", "'.$itog_seo.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
    var table = $('#example').DataTable( {	
		"stateSave": true,
		"lengthChange": true,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],        
        data: dataSet,
        columns: [
            { title: "ID" },
			{ title: "Фото" },
            { title: "Название комплекта" },
			{ title: "Категория" },
            { title: "Товарные позиции" },
            { title: "Цена комплекта" },
			{ title: "SEO" },
			{ title: "Действия" }
        ]		
    } );


} );
</script>