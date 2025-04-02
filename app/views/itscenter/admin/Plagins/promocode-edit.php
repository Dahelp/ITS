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
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/promocode">Список промокодов</a></li>
              <li class="breadcrumb-item active">Редактировать промокод</li>
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
                <form action="<?=ADMIN;?>/plagins/promocode-edit" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать промокод <?=h($promocode->promocode);?></h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="promocode">Промокод <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="promocode" id="promocode" value="<?=h($promocode->promocode);?>" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="value">Значение, % <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="value" id="value" value="<?=h($promocode->value);?>" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value="show" <?php if($promocode->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value="hide" <?php if($promocode->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="category_id">Показывать в категориях</label>
							<div class="col-sm-9">
								<?php foreach($category as $cat): ?>
									<div class="custom-control custom-checkbox">
										<?php 
											$catcheckbox = \R::getAll("SELECT category.name, category.id FROM category JOIN plagins_promocode_category ON category.id = plagins_promocode_category.category_id AND plagins_promocode_category.promocode_id = '".$promocode->id."' AND plagins_promocode_category.category_id = '".$cat->id."'");
											if(!empty($catcheckbox)){
												$checked = ' checked';
											}else{
												$checked = null;
											}
										?>
										<input class="custom-control-input" type="checkbox" id="customCheckbox<?=$cat->id;?>" value="<?=$cat->id;?>" name="category_id[]"<?=$checked;?>>
										<label style="font-weight:400" for="customCheckbox<?=$cat->id;?>" class="custom-control-label"><?=$cat->name;?></label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
                    </div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$promocode->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
                </form>        
	</div>
	</div>
</section>
<!-- /.content -->