<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Роли пользователей</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/user/roles">Список ролей</a></li>
              <li class="breadcrumb-item active">Редактирование роли</li>
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
                <form action="<?=ADMIN;?>/user/edit-role" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать роль <?=h($role->name);?></h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name">Название <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="name" id="name" value="<?=h($role->name);?>" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="alt_name">Альтернативное название (латиницей) <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="alt_name" id="alt_name" value="<?=h($role->alt_name);?>" required>
							</div>
                        </div>						
                    </div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$role->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>		
    </div>
	</div>
</section>
<!-- /.content -->