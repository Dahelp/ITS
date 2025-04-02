<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Атрибуты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список атрибутов</li>
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
                <a href="<?=ADMIN;?>/attribute/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить атрибут</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список атрибутов</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10px">ID</th>
								<th>Наименование</th>
                                <th>Категория</th>
								<th style="width: 100px">На сайте</th>
								<th style="width: 100px">В товаре</th>
								<th style="width: 160px">Системное имя</th>
								<th style="width: 60px">Позиция</th>
                                <th style="width: 100px">Действия</th>
                            </tr>
					    </thead>
                        <tbody>
                            <?php foreach($attributes as $attribute): ?>
                                <tr class="cont_td_znach">
                                    <td><?=$attribute['id'];?></td>
									<td><?=$attribute['attribute_name'];?></td>
									<td><?php if($attribute['attribute_group_id'] !="0") { $at = \R::findOne('attribute', 'id = ?', [$attribute['attribute_group_id']]); $att_cat = "".$at["attribute_name"].""; }else{ $att_cat = "<strong>Самостоятельная категория</strong>"; } echo "$att_cat"; ?></td>
									<td><?php if($attribute['attribute_hide']== "show") { echo "<i class=\"fad fa-check-circle text-success\"></i>"; }; if($attribute['attribute_hide']== "hide") { echo "<i class=\"fad fa-check-circle text-danger\"></i>"; };?></td>
									<td><?php if($attribute['hide_product']== "show") { echo "<i class=\"fad fa-check-circle text-success\"></i>"; }; if($attribute['hide_product']== "hide") { echo "<i class=\"fad fa-check-circle text-danger\"></i>"; };?></td>
									<td><?php if($attribute['url_params']){ echo "{".$attribute['url_params']."}"; };?></td>                                   
                                    <td><?=$attribute['attribute_position'];?></td>
                                    <td><a href="<?=ADMIN;?>/attribute/edit?id=<?=$attribute['id'];?>"><i class="fas fa-pencil-alt"></i></a> <a class="delete" href="<?=ADMIN;?>/attribute/delete?id=<?=$attribute['id'];?>"><i class="fas fa-times-circle text-danger"></i></a></td>
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