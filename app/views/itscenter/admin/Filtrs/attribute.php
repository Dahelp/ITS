<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Фильтры</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item">Группы фильтров</a></li>
			  <li class="breadcrumb-item active">Фильтры</li>
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
                <a href="<?=ADMIN;?>/filtrs/attribute-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить фильтр</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Фильтры</h3>
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
<?php foreach($attrs as $item) {
	if($item['title'] !="") { $s1 = "25"; }else{ $s1 = 0; }
	if($item['description'] !="") { $s2 = "25"; }else{ $s2 = 0; }
	if($item['content'] !="") { $s3 = "50"; }else{ $s3 = 0; }
	$seo = $s1+$s2+$s3; 
	if($seo <= 25) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-danger progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 50) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 75) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-warning progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($seo == 100) { $itog_seo = "SEO $seo% <div class='progress progress-xs'><div class='progress-bar bg-success progress-bar-striped' role='progressbar' aria-valuenow='".$seo."' aria-valuemin='0' aria-valuemax='100' style='width: ".$seo."%'><span class='sr-only'>".$seo."% Complete (warning)</span></div></div>"; }
	if($item["url_params"] !="") { $url = " <a target='_blank' href='/".$item["url_params"]."/".$item["alias"]."'><i class='fas fa-eye'></i></a>"; }else{ $url = ""; }
	$option = "<a href='".ADMIN."/filtrs/attribute-edit?id=".$item["id"]."'><i class='fas fa-pencil-alt'></i></a> <a class='delete' href='".ADMIN."/filtrs/attribute-delete?id=".$item["id"]."'><i class='fas fa-times-circle text-danger'></i></a>$url";
    $set .= '[ "'.$item["value"].'", "'.$item["gname"].'", "'.$itog_seo.'", "'.$option.'" ],';
 } echo "".$set.""; ?>
 
];
 
$(document).ready(function() {
	
    var table = $('#example').DataTable( {
		"stateSave": true,
		"lengthChange": true,
		"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
		"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 2, 3 ] }],
        data: dataSet,
        columns: [
            { title: "Наименование" },
			{ title: "Группа" },
            { title: "SEO", "width": "120px" },
			{ title: "Действия", "width": "60px" }
        ]		
    } );


} );
</script>