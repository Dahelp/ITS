<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">IndexNow</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/indexnow">Список IndexNow</a></li>
              <li class="breadcrumb-item active">Редактировать поисковую систему</li>
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
                <form action="<?=ADMIN;?>/plagins/indexnow-edit" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать поисковую систему <?=h($indexnow->search_engine);?></h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="search_engine">Поисковая система <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="search_engine" id="search_engine" value="<?=h($indexnow->search_engine);?>" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url">URL indexnow (домен)<span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="url" id="url" value="<?=h($indexnow->url);?>" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="verification">Код верификации <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="verification" id="verification" value="<?=h($indexnow->verification);?>" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value="show" <?php if($indexnow->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value="hide" <?php if($indexnow->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
                 			</select>
							</div>
                        </div>
                    </div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$indexnow->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
                </form>        
	</div>
	</div>
</section>
<!-- /.content -->