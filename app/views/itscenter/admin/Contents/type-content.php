<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Контент</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Типы контента</li>
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
				<a href="<?=ADMIN;?>/contents/type-add" class="btn btn-primary"><i class="fas fa-fw fa-plus"></i> Добавить тип</a>
			</div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Типы контента</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Наименование</th>
								<th>Системный URL</th>
                                <th>Действие</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($type_content as $item): ?>
                            <tr>
                                <td><?=$item->name;?></td>
								<td><?=$item->param_url;?></td>
                                <td>
									<a href="<?=ADMIN;?>/contents/type-edit?id=<?=$item["id"];?>"><i class="fas fa-pencil-alt"></i></a>
                                    <a class="delete text-danger" href="<?=ADMIN;?>/contents/type-delete?id=<?=$item->id;?>"><i class="fas fa-times-circle text-danger"></i></a>
									<a target="_blank" href="/<?=$item->param_url;?>"><i class="fas fa-eye"></i></a>
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