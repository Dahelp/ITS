<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Промо-коды</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
              <li class="breadcrumb-item active">Промо-коды</li>
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
                <a href="<?=ADMIN;?>/plagins/promocode-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить промо-код</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список промо-кодов</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<?php if($promocode) { ?>
					<div class="table-responsive">
						<table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Промо-код</th>
                                <th>Значение, %</th>
								<th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
							<?php foreach($promocode as $promo): ?>
								<tr class="cont_td_znach">
									<td><?=$promo['promocode'];?></td>
									<td><?=$promo['value'];?></td>
									<td>
										<?php
											if($promo['hide'] == "show"){ echo "Активный"; }
											if($promo['hide'] == "hide"){ echo "Не активный"; }
										?>
									</td>
									<td><a href="<?=ADMIN;?>/plagins/promocode-edit?id=<?=$promo['id'];?>"><i class="fas fa-pencil-alt"></i></a></td>
								</tr>
							<?php endforeach; ?>                            
                        </tbody>
                    </table>
					</div>
					<?php }else{ ?>
						Промо-коды ещё не добавлены.
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</section>