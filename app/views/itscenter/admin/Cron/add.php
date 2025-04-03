<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">CRON задания</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/cron">Список CRON</a></li>
              <li class="breadcrumb-item active">Добавить CRON задание</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
<div class="row">
          <div class="col-12">
			<form action="<?=ADMIN;?>/cron/add" method="post" data-toggle="validator">
			<!-- Custom Tabs -->
            <div class="card">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Добавить CRON задание</h3>
				</div><!-- /.card-header -->
                <div class="card-body">
                    <div class="box-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name">Название задания</label>
							<div class="col-sm-9">
								<input type="text" name="name" class="form-control" id="name" placeholder="Название задания" required>                                
							</div>                                        
                        </div>			
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="alias">Url-путь к файлу</label>
							<div class="col-sm-9">
								<input type="text" name="alias" class="form-control" id="alias" placeholder="Url путь для запуска CRON">                                
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url_params">Системное имя</label>
							<div class="col-sm-9">
								<input type="text" name="url_params" class="form-control" id="url_params" placeholder="Системное имя" required>                                
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url_download">Имя файла которое будет создано</label>
							<div class="col-sm-9">
								<input type="text" name="url_download" class="form-control" id="url_download" placeholder="Например sitemap, tovars" required>                                
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>
                 			</select>
							</div>
                        </div>
					</div>
                <!-- /.tab-content -->				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-success">Добавить</button>
            </div>
            </form>
            <!-- ./card -->
			
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->
		
</section>
<!-- /.content -->
<?php }else{ ?>
<div class="alert alert-warning alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
		На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>