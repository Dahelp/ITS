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
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/attribute">Список атрибутов</a></li>
              <li class="breadcrumb-item active">Добавить атрибут</li>
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
		    <form action="<?=ADMIN;?>/attribute/add" method="post" data-toggle="validator">
            <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Добавить атрибут</h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="attribute_name">Название</label>
								<div class="col-sm-9">
									<input type="text" name="attribute_name" class="form-control" id="attribute_name" placeholder="Название" value="<?php isset($_SESSION['form_data']['attribute_name']) ? h($_SESSION['form_data']['attribute_name']) : null; ?>" required>											
								</div>                                        
                         </div>
							<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="attribute_group_id">Родительская категория</label>
                            <div class="col-sm-9">
								<select name="attribute_group_id" class="form-control" style="width: 100%;">
									
									<option value="0">Самостоятельная категория</option>
									<?php $attributes_sql = \R::getAll('SELECT id, attribute_name FROM attribute WHERE attribute_group_id = "0"');
										$i=1; foreach($attributes_sql as $att_item => $item): ?>									
									<option value= "<?=$item["id"]?>" style="color:green"><?=$item["attribute_name"]?></option>
									<?php $attributes_group = \R::getAll('SELECT id, attribute_name FROM attribute WHERE attribute_group_id = "'.$item["id"].'"');
										$k=1; foreach($attributes_group as $att_group => $group): ?>
										<option value= "<?=$group["id"]?>"> -- <?=$group["attribute_name"]?></option>
									<?php $k++; endforeach; ?>	
								<?php $i++; endforeach; ?>
								</select>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="attribute_position">Позиция</label>
								<div class="col-sm-9">
									<input type="text" name="attribute_position" class="form-control" id="attribute_position" placeholder="0" value="0">											
								</div>                                        
                         </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="attribute_hide">Статус активности на сайте</label>
							<div class="col-sm-9">
							<select name="attribute_hide" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>                    			
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide_product">Статус активности в атрибутах товара</label>
							<div class="col-sm-9">
							<select name="hide_product" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>                    			
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="">Системное URL</label>
								<div class="col-sm-9">
									<input type="text" name="url_params" class="form-control" id="url_params" placeholder="Системное URL" value="<?php isset($_SESSION['form_data']['url_params']) ? h($_SESSION['form_data']['url_params']) : null; ?>">											
								</div>                                        
                        </div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="category_id">Показывать в категориях</label>
							<div class="col-sm-9">
								<?php foreach($category as $cat): ?>
									<div class="custom-control custom-checkbox">											
										<input class="custom-control-input" type="checkbox" id="customCheckbox<?=$cat->id;?>" value="<?=$cat->id;?>" name="category_id[]">
										<label style="font-weight:400" for="customCheckbox<?=$cat->id;?>" class="custom-control-label"><?=$cat->name;?></label>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div> 
				
              </div><!-- /.card-body -->			  
            </div>
			<div class="box-footer">
                <button type="submit" class="btn btn-primary btn_save">Добавить</button>
            </div>
            <!-- ./card -->
			</form>
          
          <!-- /.col -->
        </div>
        <!-- /.row -->

	</div>	
</section>
<!-- /.content -->
