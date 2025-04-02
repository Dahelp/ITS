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
		} 
		else {
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
              <li class="breadcrumb-item active">Редактировать правило</li>
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
                <form action="<?=ADMIN;?>/plagins/inseo-edit" method="post" data-toggle="validator">
                    <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактировать правило для <?=h($inseo->cat_name);?></h3>
              </div><!-- /.card-header -->
              <div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="tip">Тип SEO <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control Validate_Required" id="tip" name="tip" aria-required="true" onChange="Selected(this)" required>
									<option value="">Выберите тип</option>									
									<option value="category" <?php if($inseo->tip == "category") { echo "selected=\"selected\""; } ?>>Категория</option>
									<option value="product" <?php if($inseo->tip == "product") { echo "selected=\"selected\""; } ?>>Товары</option>
									<option value="attribute_group" <?php if($inseo->tip == "attribute_group") { echo "selected=\"selected\""; } ?>>Группа фильтров</option>
								</select>
							</div>
                        </div>
						<div id='Block1' style='display: <?php if($inseo->tip == "category" OR $inseo->tip == "product") { echo "block"; }else{ echo "none"; } ?>;'>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="category_id">Категория <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<?php 								
								new \app\widgets\menu\Menu([
                                'tpl' => WWW . '/menu/select.php',
                                'container' => 'select',
                                'cache' => 0,
                                'cacheKey' => 'admin_select',
                                'class' => 'form-control',
                                'attrs' => [
                                    'name' => 'category_id',
                                    'id' => 'category_id',
                                ],
                            ]) ?>
							</div>
                        </div>
						</div>
						<div id='Block2' style='display: <?php if($inseo->tip == "attribute_group") { echo "block"; }else{ echo "none"; } ?>;'>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="category_id">Группа <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<select name="group_id" class="form-control" style="width: 100%;">										
										<?php $b = \R::findOne('attribute_group', 'id = ?', [$inseo->category_id]); ?>
										<option value= "<?=$b->id?>" selected="selected"><?=$b->title?></option>
										<?php 
											$attr_group = \R::getAll("SELECT title, id FROM `attribute_group` WHERE url_params !='' AND id !='".$b->id."'");
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
								<textarea class="form-control" name="name" id="name"><?=h($inseo->name);?></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="content">Описание страницы</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="content"><?=h($inseo->content);?></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="title">Тайтл страницы</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="title" id="title"><?=h($inseo->title);?></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="description">Описание (meta description)</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="description" id="description"><?=h($inseo->description);?></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (meta keywords)</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="keywords" id="keywords"><?=h($inseo->keywords);?></textarea>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value="show" <?php if($inseo->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value="hide" <?php if($inseo->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
                 			</select>
							</div>
                        </div>
                    </div>				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <input type="hidden" name="id" value="<?=$inseo->id;?>">
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
                </form>        
	</div>
	</div>
</section>
<!-- /.content -->