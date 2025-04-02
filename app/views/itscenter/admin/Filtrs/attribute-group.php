<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Фильтры</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Группы фильтров</li>
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
				<a href="<?=ADMIN;?>/filtrs/group-add" class="btn btn-primary"><i class="fas fa-fw fa-plus"></i> Добавить группу</a>
			</div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Группы фильтров</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Наименование</th>
								<th>Системное имя</th>
                                <th>Действие</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($attrs_group as $item): ?>
                            <tr>
                                <td><?=$item->title;?></td>
								<td><?=$item->url_params;?></td>
                                <td>
                                    <a href="<?=ADMIN;?>/filtrs/group-edit?id=<?=$item->id;?>"><i class="fas fa-pencil-alt"></i></a>
                                    <a class="delete text-danger" href="<?=ADMIN;?>/filtrs/group-delete?id=<?=$item->id;?>"><i class="fas fa-times-circle text-danger"></i></a>
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