<script>
function Selected(a) {
        var label = a.value;
        if (label==1) {
            document.getElementById("Block1").style.display='none';

        } else if (label==2) {
            document.getElementById("Block1").style.display='block';		
        }
		else {
            document.getElementById("Block1").style.display='none';
        }
         
}
</script>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Кросс-номера</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/cross">Кросс-номера</a></li>
              <li class="breadcrumb-item active">Экспорт</li>
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
                <a href="<?=ADMIN;?>/plagins/cross-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить кросс-номер</a>
				<a href="<?=ADMIN;?>/plagins/cross-add-vendor" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить производителя</a>
				<a href="<?=ADMIN;?>/plagins/cross-import" class="btn btn-success"><i class="fad fa-fw fa-file-csv"></i> Импорт</a>
				<a href="<?=ADMIN;?>/plagins/cross-export" class="btn btn-primary"><i class="fad fa-fw fa-file-csv"></i> Экспорт</a>
            </div>
			<form method="post" action="<?=ADMIN;?>/plagins/cross-export" enctype="multipart/form-data" role="form" data-toggle="validator">
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Экспорт</h3>
                </div>
                <!-- /.card-header -->				
                <div class="card-body">						
					<div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="format">Формат файла</label>
							<div class="col-sm-9">
							<select id="format" class="form-control" name="format">
								<option value= "" selected="selected">Выберите формат</option>
								<option value= "1">XML</option>
								<option value= "2">CSV</option>               			
                 			</select>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="dumps">Вывод данных</label>
							<div class="col-sm-9">
							<select id="dumps" class="form-control" name="dumps" aria-required="true" onChange="Selected(this)">
								<option value= "" selected="selected">Выберите формат</option>
								<option value= "1">Все кросс-номера</option>
								<option value= "2">ID товара</option>               			
                 			</select>
							</div>
                        </div>
						<div id="Block1" style="display: none;" class="form-group row">
                            <label class="col-sm-3 col-form-label" style="float: left;width: 25%;" for="product_id">ID товара</label>
								<div class="col-sm-9" style="float: left;">
									<input class="form-control" type="text" name="product_id" placeholder="ID товара">											
								</div>                                        
                        </div>			          
					</div>						
				</div>				
			</div>
			<div class="box-footer">
                    <button type="submit" class="btn btn-primary">Выгрузить</button>
            </div>
			</form>
		</div>
	</div>
</section>