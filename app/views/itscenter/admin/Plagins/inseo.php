<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">InSEO</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
              <li class="breadcrumb-item active">Список правил InSEO</li>
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
                <a href="<?=ADMIN;?>/plagins/inseo-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить правило</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список правил</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">						
					<div class="table-responsive">
						<table class="table table-bordered">
							<thead>
								<tr>
									<th style="width: 10px">ID</th>
									<th>Раздел</th>
									<th>Название категории</th>
									<th style="width: 100px">Действия</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($inseo as $ins): ?>
									<tr class="cont_td_znach">
										<td><?=$ins['id'];?></td>
										<td><?php
											if($ins['tip'] == "product"){ echo "Товары"; }
											if($ins['tip'] == "category"){ echo "Категория"; }
											if($ins['tip'] == "attribute_group"){ echo "Фильтр"; } ?></td>
										<td>
											<?php 
												if($ins['tip'] == "product" OR $ins['tip'] == "category"){
													$cat_name = \R::findOne('category', "id = ?", [$ins['category_id']]); echo "".$cat_name["name"]."";
												}
												if($ins['tip'] == "attribute_group"){
													$cat_name = \R::findOne('attribute_group', "id = ?", [$ins['category_id']]); echo "".$cat_name["title"]."";
												}												
											?>
										</td>
										<td><a href="<?=ADMIN;?>/plagins/inseo-edit?id=<?=$ins['id'];?>"><i class="fas fa-pencil-alt"></i></a> <a class="delete" href="<?=ADMIN;?>/plagins/delete-inseo?id=<?=$ins['id'];?>"><i class="fas fa-times-circle text-danger"></i></a></td>
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
