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
              <li class="breadcrumb-item active">Редактирование кросс-номера</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
	<div class="row">
          <div class="col-12">
                <form action="<?=ADMIN;?>/plagins/cross-edit" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать кросс-номер <?=h($cross->cross_name);?></h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
                        <div class="form-group row">
							<?php 
								$last = \R::findOne('plagins_cross', 'ORDER BY cross_id DESC');  
								$cross_id = $last->cross_id + 1;
							?>
                            <label class="col-sm-3 col-form-label" for="cross_id">Код кросс-номера <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="cross_id" placeholder="<?=$cross_id?>" id="cross_id" value="<?=h($cross->cross_id);?>" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="product_id">Код товара (Артикул)<span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<?php 
									$product = \R::findOne('product', 'id = ?', [$cross->product_id]);									
								?>
								<input type="text" class="form-control" name="product_id" id="product_id" value="<?=h($product['article']);?>" required>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="cross_name">Название <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="cross_name" id="cross_name" value="<?=h($cross->cross_name);?>" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="cross_abbreviated_name">Краткое наименование <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="cross_abbreviated_name" id="cross_abbreviated_name" value="<?=h($cross->cross_abbreviated_name);?>" required>
                            </div>
                        </div>				
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="vendor_id">Производитель <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control" name="vendor_id">
									<option value="" />Выберите производителя</option>
									<?php foreach($vendors as $vendor): ?>
										<option value="<?=$vendor["id"];?>"<?php if($cross->vendor_id == $vendor["id"]) echo ' selected'; ?>><?=$vendor["name"];?></option>
									<?php endforeach; ?>
								</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="tip_cross">Тип кросса <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control" name="tip_cross">
									<option value="" />Выберите тип</option>
									<option value="1"<?php if($cross->tip_cross == '1') echo ' selected'; ?> />Внешняя часть</option>
									<option value="2"<?php if($cross->tip_cross == '2') echo ' selected'; ?> />Внутренняя часть</option>
									<option value="3"<?php if($cross->tip_cross == '3') echo ' selected'; ?> />Не определено</option>
									<option value="4"<?php if($cross->tip_cross == '4') echo ' selected'; ?> />Комплект из 2х частей</option>									
								</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="equipment_vendor">Производитель техники <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control" name="equipment_vendor">
									<option value="" />Выберите статус</option>
									<option value="1"<?php if($cross->equipment_vendor == '1') echo ' selected'; ?> />Да (OEM)</option>
									<option value="2"<?php if($cross->equipment_vendor == '2') echo ' selected'; ?> />Нет (Аналог)</option>					
								</select>
							</div>
                        </div>
                    </div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$cross->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
                </form>        
	</div>
	</div>
</section>
<!-- /.content -->