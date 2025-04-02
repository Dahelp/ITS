<script>
function Selected(a) {
        var label = a.value;
        if (label=='category') {
            document.getElementById("Block1").style.display='block';
            document.getElementById("Block2").style.display='none';
        } else if (label=='product') {
            document.getElementById("Block1").style.display='block';
            document.getElementById("Block2").style.display='none';  
        } else if (label=='attribute_group') {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='block';      
		} else {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='none';
        }
         
}
</script>
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
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/inseo">Список правил InSEO</a></li>
              <li class="breadcrumb-item active">Добавить правило</li>
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
                <form method="post" action="<?=ADMIN;?>/plagins/inseo-add" role="form" data-toggle="validator">
				
					<div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Добавить правило</h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="tip">Тип SEO <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control Validate_Required" id="tip" name="tip" aria-required="true" onChange="Selected(this)" required>
									<option value="">Выберите тип</option>									
									<option value="category">Категория</option>
									<option value="product">Товары</option>
									<option value="attribute_group">Группа фильтров</option>
								</select>
							</div>
                        </div>
						<div id='Block1' style='display: none;'>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="category_id">Категория <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<?php new \app\widgets\menu\Menu([
									'tpl' => WWW . '/menu/select.php',
									'container' => 'select',
									'cache' => 0,
									'cacheKey' => 'admin_select',
									'class' => 'form-control',
									'attrs' => [
										'name' => 'category_id',
										'id' => 'category_id',
									],
									'prepend' => '<option>Выберите категорию</option>',
								]) ?>
								</div>
							</div>
						</div>
						<div id='Block2' style='display: none;'>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="group_id">Группа <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<select name="group_id" class="form-control" style="width: 100%;">										
										<option value= "" selected="selected">Выберите группу</option>
										<?php 
											$attr_group = \R::getAll("SELECT title, id FROM `attribute_group` WHERE url_params !=''");
											foreach($attr_group as $agr) { 
										?>
										<option value= "<?=$agr["id"]?>"><?=$agr["title"]?></option>										
										<?php } ?>
									</select>
								</div>
							</div>
						</div>						
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name">Заголовок страницы</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="name" id="name"></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="content">Описание страницы</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="content"></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="title">Тайтл страницы</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="title" id="title"></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="description">Описание (meta description)</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="description" id="description"></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (meta keywords)</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="keywords" id="keywords"></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности <span class="text-danger">*</span></label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;" required>
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>
                 			</select>
							</div>
                        </div>
                    </div>				
				</div><!-- /.card-body -->			  
            </div>                   

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
                <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
