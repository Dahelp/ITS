<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Группы пользователей</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/user/groups">Список групп</a></li>
              <li class="breadcrumb-item active">Новая группа</li>
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
                <form method="post" action="<?=ADMIN;?>/user/add-group" method="post" data-toggle="validator" id="add-group">
				
					<div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Добавить группу</h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name">Название <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="name" id="name" value="<?= isset($_SESSION['form_data']['name']) ? $_SESSION['form_data']['name'] : '' ?>" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="role">Альтернативное название (латиницей) <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="role" id="role" required>
							</div>
                        </div>						
                    </div>				
				</div><!-- /.card-body -->			  
            </div>                   

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
                <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->