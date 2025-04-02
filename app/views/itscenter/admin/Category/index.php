<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Категории</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список категорий</li>
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
                <a href="<?=ADMIN;?>/category/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить категорию</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список категорий</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10px">ID</th>
								<th style="width: 60px;text-align:center">Фото</th>
                                <th>Категория</th>
								<th>Ссылка</th>
                                <th style="width: 80px">Позиция</th>                             
								<th style="width: 80px;text-align:center">SEO</th>
                                <th style="width: 100px">Действия</th>
                            </tr>
					    </thead>
                        <tbody>
							<?php new \app\widgets\menu\Menu([
								'tpl' => WWW . '/menu/category_admin.php',
								'container' => 'div',
								'cache' => 0,
								'cacheKey' => 'admin_cat',
								'class' => 'list-group list-group-root well',
							]) ?>
						</tbody>
                    </table>
					</div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->