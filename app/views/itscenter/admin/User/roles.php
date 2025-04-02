<?php if($_SESSION['user']['groups'] == 1) { ?>
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
              <li class="breadcrumb-item active">Список ролей</li>
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
                <a href="<?=ADMIN;?>/user/add-role" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить роль</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список ролей</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10px">ID</th>
                                <th>Наименование</th>					
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($roles as $role): ?>
                                    <td><?=$role["id"];?></td>
                                    <td><?=$role["name"];?></td>					
                                    <td width="60"><a href="<?=ADMIN;?>/user/edit-role?id=<?=$role["id"];?>"><i class="fas fa-pencil-alt"></i></a> <a class="delete" href="<?=ADMIN;?>/user/delete-role?id=<?=$role["id"];?>"><i class="fas fa-times-circle text-danger"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                    </table>
					</div>
                </div>
                <div class="text-center">
                        <p>(<?=count($roles);?> групп из <?=$count;?>)</p>                        
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