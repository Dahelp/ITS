<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Валюты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список валют</li>
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
                <a href="<?=ADMIN;?>/currency/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить валюту</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список валют</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10px">ID</th>
                                <th>Наименование</th>
                                <th>Код</th>
                                <th>Значение</th>
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($currencies as $currency): ?>
                                <tr>
                                    <td><?=$currency->id;?></td>
                                    <td><?=$currency->title;?></td>
                                    <td><?=$currency->code;?></td>
                                    <td><?=$currency->value;?></td>
                                    <td>
                                        <a href="<?=ADMIN;?>/currency/edit?id=<?=$currency->id;?>"><i class="fas fa-pencil-alt"></i></a>
                                        <a class="delete" href="<?=ADMIN;?>/currency/delete?id=<?=$currency->id;?>"><i class="fas fa-times-circle text-danger"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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