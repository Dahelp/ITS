<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Техника</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/technics">Список техники</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/technics-type?id=<?=$technics->type_id?>"><?php $type = \R::findOne('technics_type', 'id = ?', [$technics->type_id]); echo "".$type['name'].""; ?></a></li>
              <li class="breadcrumb-item active">Редактирование техники</li>
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
			<div class="menu_btn">
                <a target="_blank" href="/technics/<?=$technics["alias"]?>" class="btn btn-success"><i class="fad fa-eye"></i> Просмотр на сайте</a>
				<a href="<?=ADMIN;?>/plagins/technics-type?id=<?=$technics->type_id?>" class="btn btn-primary"><i class="fal fa-reply-all"></i></a>
            </div>			
            <form action="<?=ADMIN;?>/plagins/technics-edit" method="post" data-toggle="validator">
            <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактирование техники <?=$types->name;?> <?=$manufacturers->name;?> <?=$technics->model;?></h3>
                <ul class="nav nav-pills ml-auto p-2">
                  <li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>                  
                  <li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">SEO</a></li>
				  <li class="nav-item"><a class="nav-link" href="#tab_3" data-toggle="tab">Размер шин</a></li>
				  <li class="nav-item"><a class="nav-link" href="#tab_4" data-toggle="tab">Доп. параметры</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane active" id="tab_1">
                    <div class="box-body">
                                    <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="type_id">Категория техники <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control" name="type_id">								
									<option value= "<?=$type->id?>" selected="selected"><?=$type->name?></option>
									<?php $types = \R::getAll('SELECT id, name FROM technics_type WHERE id != ?', [$type->id]);
									$i=1;	foreach($types as $type_item => $item): ?>									
									<option value= "<?=$item["id"]?>"><?=$item["name"]?></option>
								<?php $i++; endforeach; ?>
								</select>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="manufacturer_id">Производитель <span class="text-danger">*</span></label>
							<div class="col-sm-9">								
								<select name="manufacturer_id" class="form-control" style="width: 100%;">
									<?php $m = \R::findOne('technics_manufacturer', 'id = ?', [$technics->manufacturer_id]); ?>
									<option value= "<?=$m->id?>" selected="selected"><?=$m->name?></option>
									<?php $manufacturer = \R::getAll('SELECT id, name FROM technics_manufacturer WHERE id != ?', [$m->id]);
									$i=1;	foreach($manufacturer as $manufacturer_item => $item): ?>									
									<option value= "<?=$item["id"]?>"><?=$item["name"]?></option>
								<?php $i++; endforeach; ?>
								</select>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="model">Модель <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="model" id="model" value="<?=h($technics->model);?>" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="content">Описание</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?=h($technics->content);?></textarea>
							</div>
                        </div>				
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">								
								<option value="show" <?php if($technics->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value="hide" <?php if($technics->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>                    	
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="position">Позиция</label>
							<div class="col-sm-9">
								<input type="text" name="position" class="form-control" id="position" placeholder="0" value="<?=h($technics->position);?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
							<div class="col-sm-9">
                                <div id="single" class="btn btn-success" data-url="plagins/technics-add-image" data-name="single" data-razdel="technics">Выбрать файл</div>
								<p><small>Рекомендуемые размеры: 600х450</small></p>
								<div class="single">
										<img src="/images/technics/baseimg/<?=$technics->img;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$technics->id;?>" data-src="<?=$technics->img;?>" data-razdel="plagins" data-plagins="technics" class="del-base">
								</div>                                    
								<div class="overlay">
									<i class="fa fa-refresh fa-spin"></i>
								</div>									
                            </div>
                        </div>
						</div>
                  </div>
                  <!-- /.tab-pane -->
                  <div class="tab-pane" id="tab_2">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="alias">Ссылка страницы</label>
							<div class="col-sm-9">
                                <input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически" value="<?=$technics->alias;?>">
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
                            <div class="col-sm-9">
								<input type="text" name="title" class="form-control" id="title" placeholder="Если пусто, то используется Название" value="<?=$technics->title;?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
                            <div class="col-sm-9">
								<input type="text" name="description" class="form-control" id="description" placeholder="SEO описание (130-150 символов)" value="<?=$technics->description;?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
                            <div class="col-sm-9">
								<input type="text" name="keywords" class="form-control" id="keywords" placeholder="Заполняются через запятую (4-6 фраз)" value="<?=$technics->keywords;?>">
							</div>
                        </div>
					</div>
                  </div>
                  <!-- /.tab-pane -->
                  <div class="tab-pane" id="tab_3">
                    <div class="box-body">
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="size">Размер шины</label>
							<div class="col-sm-9">
								<select name="size[]" class="form-control tiposize" id="size" multiple="multiple" data-placeholder="Выберите размер шины">
								<?php if(!empty($tipo_size)): ?>
                                    <?php foreach($tipo_size as $item): ?>
                                        <option value="<?=$item['id'];?>" selected><?=$item['value'];?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
								</select>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="size_back">Размер шины (задние)</label>
							<div class="col-sm-9">
								<select name="size_back[]" class="form-control tiposize" id="size_back" multiple="multiple" data-placeholder="Выберите размер шины">
								<?php if(!empty($tipo_sizeback)): ?>
                                    <?php foreach($tipo_sizeback as $item): ?>
                                        <option value="<?=$item['id'];?>" selected><?=$item['value'];?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
								</select>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="size_alt">Альтернативные размеры шин (передние)</label>
							<div class="col-sm-9">
								<select name="size_alt[]" class="form-control tiposize" id="size_alt" multiple="multiple" data-placeholder="Выберите размер шины">
								<?php if(!empty($tipo_sizealt)): ?>
                                    <?php foreach($tipo_sizealt as $item): ?>
                                        <option value="<?=$item['id'];?>" selected><?=$item['value'];?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
								</select>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="size_alt_back">Альтернативные размеры шин (задние)</label>
							<div class="col-sm-9">
								<select name="size_alt_back[]" class="form-control tiposize" id="size_alt_back" multiple="multiple" data-placeholder="Выберите размер шины">
								<?php if(!empty($tipo_sizealtback)): ?>
                                    <?php foreach($tipo_sizealtback as $item): ?>
                                        <option value="<?=$item['id'];?>" selected><?=$item['value'];?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
								</select>
							</div>
						</div>
					</div>
                  </div>
                  <!-- /.tab-pane -->
				  <div class="tab-pane" id="tab_4">
						<div class="box-body">						
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="url_video">Ссылка на видео</label>
								<div class="col-sm-9">
									<input type="text" name="url_video" class="form-control" id="url_video" placeholder="Укажите ссылку на видео" value="<?=$technics->url_video;?>">
								</div>
							</div>						
						</div>
					</div>
					<!-- /.tab-pane -->
                </div>
                <!-- /.tab-content -->
				
              </div><!-- /.card-body -->
			  
            </div>
			<div class="box-footer">
                    <input type="hidden" name="id" value="<?=$technics->id;?>">
                    <button type="submit" class="btn btn-success">Сохранить</button>
            </div>
            <!-- ./card -->
			</form>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->		
</section>
<!-- /.content -->