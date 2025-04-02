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
              <li class="breadcrumb-item active">Редактировать атрибут</li>
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
		    <form action="<?=ADMIN;?>/attribute/edit" method="post" data-toggle="validator">
            <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать атрибут</h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="attribute_name">Название</label>
								<div class="col-sm-9">
									<input type="text" name="attribute_name" class="form-control" id="attribute_name" placeholder="Название" value="<?=h($attribute->attribute_name);?>" required>											
								</div>                                        
                         </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="attribute_group_id">Родительская категория</label>
                            <div class="col-sm-9">
								<select name="attribute_group_id" class="form-control" style="width: 100%;">
									<option value="0">Самостоятельная категория</option>
									<?php $b = \R::findOne('attribute', 'id = ?', [$attribute->attribute_group_id]); ?>									
									<?php $attributes_sql = \R::getAll('SELECT id, attribute_name FROM attribute WHERE attribute_group_id = "0"');
										$i=1; foreach($attributes_sql as $att_item => $item): ?>
									<option value= "<?=$item["id"]?>" style="color:green" <?php if($item["id"] == $b->id) { echo "selected=\"selected\""; }?>><?=$item["attribute_name"]?></option>
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
									<input type="text" name="attribute_position" class="form-control" id="attribute_position" placeholder="0" value="<?=h($attribute->attribute_position);?>">											
								</div>                                        
                         </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="attribute_hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="attribute_hide" class="form-control" style="width: 100%;">
								<option value= "show" <?php if($attribute->attribute_hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value= "hide" <?php if($attribute->attribute_hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>                    			
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide_product">Статус активности в атрибутах товара</label>
							<div class="col-sm-9">
							<select name="hide_product" class="form-control" style="width: 100%;">
								<option value= "show" <?php if($attribute->hide_product == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value= "hide" <?php if($attribute->hide_product == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>                     			
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="">Системное URL</label>
								<div class="col-sm-9">
									<input type="text" name="url_params" class="form-control" id="url_params" placeholder="Системное URL" value="<?=h($attribute->url_params);?>">											
								</div>                                        
                        </div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="category_id">Показывать в сравнениях</label>
							<div class="col-sm-9">
								<?php foreach($category as $cat): ?>
									<div class="custom-control custom-checkbox">
										<?php 
											$catcheckbox = \R::getAll("SELECT category.name, category.id FROM category JOIN attribute_comparison ON category.id = attribute_comparison.category_id AND attribute_comparison.attribute_id = '".$attribute->id."' AND attribute_comparison.category_id = '".$cat->id."'");
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
				<input type="hidden" name="id" value="<?=$attribute->id;?>">
                <button type="submit" class="btn btn-success btn_save">Сохранить</button>
            </div>
            <!-- ./card -->
			</form>
          
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->
	</div>	
</section>
<!-- /.content -->
