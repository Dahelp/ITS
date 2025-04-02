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
              <li class="breadcrumb-item active">Редактировать CRON задание</li>
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
                <form action="<?=ADMIN;?>/cron/edit" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать CRON задание <?=h($cron->name);?></h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name">Название задания</label>
							<div class="col-sm-9">
								<input type="text" name="name" class="form-control" id="name" value="<?=h($cron->name);?>" placeholder="Название задания" required>                                
							</div>                                        
                        </div>			
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="alias">Url-путь к файлу</label>
							<div class="col-sm-9">
								<input type="text" name="alias" class="form-control" id="alias" value="<?=h($cron->alias);?>" placeholder="Url путь для запуска CRON">                                
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url_params">Системное имя</label>
							<div class="col-sm-9">
								<input type="text" name="url_params" class="form-control" id="url_params" value="<?=h($cron->url_params);?>" placeholder="Системное имя" required>                                
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url_download">Имя файла которое будет создано</label>
							<div class="col-sm-9">
								<input type="text" name="url_download" class="form-control" id="url_download" value="<?=h($cron->url_download);?>" placeholder="Например sitemap, tovars" required>                                
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value="show" <?php if($cron->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value="hide" <?php if($cron->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
                 			</select>
							</div>
                        </div>
					</div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$cron->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
                </form>        
	</div>
	</div>
</section>
<!-- /.content -->
<?php }else{ ?>
<div class="alert alert-warning alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
		На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>