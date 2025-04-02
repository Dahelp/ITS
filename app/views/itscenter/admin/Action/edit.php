<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Акции</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/action">Список акции</a></li>
              <li class="breadcrumb-item active">Редактировать акцию</li>
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
                <form action="<?=ADMIN;?>/action/edit" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать акцию на товар <?=h($product->name);?></h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="product_id">Наименование</label>
							<div class="col-sm-9">
								<input name="name" type="text" class="form-control" value="<?=h($product->name);?>" placeholder="Выберите товары" disabled>
								<input name="product_id" type="hidden" class="form-control" value="<?=h($action->product_id);?>" placeholder="Выберите товары">
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="type_id">Тип скидки</label>
							<div class="col-sm-9">
								<select name="type_id" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите тип скидки</option>
								<?php foreach($types as $type) { ?>
									<option value= "<?=$type["id"]?>" <?php if($action->type_id == $type["id"]) { echo "selected=\"selected\""; } ?>><?=$type["type"]?></option>
                    			<?php } ?>
                 			</select>                                
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="znachenie">Значение скидки</label>
							<div class="col-sm-9">
								<input type="text" name="znachenie" class="form-control" id="znachenie" placeholder="20" value="<?=$action["znachenie"]?>" required>                                
							</div>                                        
                        </div>						
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="date_start">Дата начала акции</label>
							<div class="col-sm-9">
								<div class="input-group">									
									<div class="input-group date" id="reservationdatetime" data-target-input="nearest">										
										<div class="input-group-append" data-target="#reservationdatetime" data-toggle="datetimepicker">
											<div class="input-group-text"><i class="far fa-calendar-alt"></i></div>
										</div>
										<input type="text" name="date_start" class="form-control form-right datetimepicker-input" value="<?=$action["date_start"]?>" data-target="#reservationdatetime">
									</div>
								</div>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="date_end">Дата окончания акции</label>
							<div class="col-sm-9">
								<div class="input-group">									
									<div class="input-group date" id="reservationdatetime2" data-target-input="nearest">										
										<div class="input-group-append" data-target="#reservationdatetime2" data-toggle="datetimepicker">
											<div class="input-group-text"><i class="far fa-calendar-alt"></i></div>
										</div>
										<input type="text" name="date_end" class="form-control form-right datetimepicker-input" value="<?=$action["date_end"]?>" data-target="#reservationdatetime2">
									</div>
								</div>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value="show" <?php if($action->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value="hide" <?php if($action->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
                 			</select>
							</div>
                        </div>
                    </div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$action->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
                </form>        
	</div>
	</div>
</section>
<!-- /.content -->