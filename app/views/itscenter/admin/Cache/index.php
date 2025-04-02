<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Кэширование</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Кэширование</li>
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
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Кэш</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Описание</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Кэш категорий</td>
                                <td>Меню категорий на сайте. Кэшируется на 1 час</td>
                                <td><a class="delete" href="<?=ADMIN;?>/cache/delete?key=category"><i class="fas fa-times-circle text-danger"></i></a></td>
                            </tr>
                            <tr>
                                <td>Кэш фильтров</td>
                                <td>Кэш фильтров и групп фильтров. Кэшируется на 1 час</td>
                                <td><a class="delete" href="<?=ADMIN;?>/cache/delete?key=filter"><i class="fas fa-times-circle text-danger"></i></a></td>
                            </tr>
                        </tbody>
                    </table>
					</div>
                </div>
            </div>
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