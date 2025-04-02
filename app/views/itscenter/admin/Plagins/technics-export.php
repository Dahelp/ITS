<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Техника</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/technics">Техника</a></li>
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
                <a href="<?=ADMIN;?>/plagins/technics-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить технику</a>
				<a href="<?=ADMIN;?>/plagins/technics-add-type" class="btn btn-success"><i class="fa fa-fw fa-plus"></i> Добавить категорию</a>
				<a href="<?=ADMIN;?>/plagins/technics-add-manufacturer" class="btn btn-secondary"><i class="fa fa-fw fa-plus"></i> Добавить производителя</a>
				<a href="<?=ADMIN;?>/plagins/technics-import" class="btn btn-success"><i class="fad fa-fw fa-file-csv"></i> Импорт</a>
				<a href="<?=ADMIN;?>/plagins/technics-export" class="btn btn-primary"><i class="fad fa-fw fa-file-csv"></i> Экспорт</a>
            </div>
			<form method="post" action="<?=ADMIN;?>/plagins/technics-export" enctype="multipart/form-data" role="form" data-toggle="validator">
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
                 			</select>
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