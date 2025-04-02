<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Кросс-номера</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/cross">Кросс-номера</a></li>
              <li class="breadcrumb-item active">Импорт</li>
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
                <a href="<?=ADMIN;?>/plagins/cross-add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить кросс-номер</a>
				<a href="<?=ADMIN;?>/plagins/cross-vendor" class="btn btn-primary">Производители</a>
				<a href="<?=ADMIN;?>/plagins/cross-import" class="btn btn-success"><i class="fad fa-fw fa-file-csv"></i> Импорт</a>
				<a href="<?=ADMIN;?>/plagins/cross-export" class="btn btn-primary"><i class="fad fa-fw fa-file-csv"></i> Экспорт</a>
            </div>
			<form method="post" action="<?=ADMIN;?>/plagins/cross-import-confirmation" role="form" data-toggle="validator">
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Подтвердить импорт</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">						
					<div class="table-responsive">
						<table class="table table-bordered">
							<thead>
								<tr>
									<th>Кросс ID</th>
									<th>Артикул</th>
									<th>Кросс-номер</th>
									<th>Краткий номер</th>
									<th>Производитель</th>									
									<th>Тип</th>
									<th>Производитель техники</th>
									<th>Статус</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($cross as $item => $key):
										$last = \R::findOne('plagins_cross', 'ORDER BY cross_id DESC');  
										$cross_id = $last->cross_id + $item;
										$crossid = \R::findOne('plagins_cross', 'cross_abbreviated_name = ?', [$key['cross_abbreviated_name']]);
										?>
									<tr class="cont_td_znach">
										<td>
											<?php if($key['cross_id']){ echo $key['cross_id']; }else{ echo $cross_id; } ?>
											<input name="cross_id[]" value="<?php if($key['cross_id']){ echo $key['cross_id']; }else{ echo $cross_id; } ?>" type="hidden" />
										</td>
										<td>
											<?=$key['product_id'];?>
											<input name="product_id[]" value="<?=$key['product_id'];?>" type="hidden" />
										</td>
										<td>
											<?=$key['cross_name'];?>
											<input name="cross_name[]" value="<?=$key['cross_name'];?>" type="hidden" />
										</td>
										<td>
											<?=$key['cross_abbreviated_name'];?>
											<input name="cross_abbreviated_name[]" value="<?=$key['cross_abbreviated_name'];?>" type="hidden" />
										</td>
										<td>
											<?php if($key['vendor_id']) { ?><?=$key['manufacturer_name'];?> (ID:<?=$key['vendor_id'];?>)
											<input name="vendor_id[]" value="<?=$key['vendor_id'];?>" type="hidden" />
											<?php } ?>
										</td>
										<td>
											<?=$key['tip_cross'];?>
											<input name="tip_cross[]" value="<?=$key['tip_cross'];?>" type="hidden" />
										</td>
										<td>
											<?=$key['equipment_vendor'];?>
											<input name="equipment_vendor[]" value="<?=$key['equipment_vendor'];?>" type="hidden" />
										</td>
										<td>
											<?php if($crossid){ echo "<span class=\"text-primary\">Обновить</span>"; }else{ echo "<span class=\"text-success\">Новый</span>"; }?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>						
				</div>				
			</div>
			<div class="box-footer">
                    <button type="submit" class="btn btn-primary">Подтвердить</button>
            </div>
			</form>
		</div>
	</div>
</section>