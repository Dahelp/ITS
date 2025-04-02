<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Категории</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/category">Список категорий</a></li>
              <li class="breadcrumb-item active">Редактирование</li>
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
            <form action="<?=ADMIN;?>/category/edit" method="post" data-toggle="validator">
                <!-- Custom Tabs -->
				<div class="card">
					<div class="card-header d-flex p-0">
						<h3 class="card-title p-3">Редактирование категории <?=$category->name;?></h3>
						<ul class="nav nav-pills ml-auto p-2">
							<li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>                  
							<li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">SEO</a></li>				  				  
						</ul>
					</div><!-- /.card-header -->
                        <div class="card-body">
							<div class="tab-content">
								<div class="tab-pane active" id="tab_1">
									<div class="box-body">
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="name">Наименование категории</label>
											<div class="col-sm-9">
												<input type="text" name="name" class="form-control" id="name" placeholder="Наименование категории" value="<?=h($category->name);?>" required>
											</div>                                        
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="parent_id">Родительская категория</label>
											<div class="col-sm-9">
                            					<?php new \app\widgets\menu\Menu([
                                					'tpl' => WWW . '/menu/select.php',
                               					 'container' => 'select',
                               					 'cache' => 0,
                               					 'cacheKey' => 'admin_select',
                               					 'class' => 'form-control',
                               					 'attrs' => [
                               					     'name' => 'parent_id',
                                					    'id' => 'parent_id',
                                					],
                                					'prepend' => '<option value="0">Самостоятельная категория</option>',
												]) ?>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="content">Подробное описание</label>
											<div class="col-sm-9">
												<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?=h($category->content);?></textarea>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="position">Позиция</label>
											<div class="col-sm-9">
												<input type="text" name="position" class="form-control" id="position" placeholder="0"  value="<?=h($category->position);?>">
                            				</div>
                        				</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
											<div class="col-sm-9">
											<select name="hide" class="form-control" style="width: 100%;">
												<option value="show" <?php if($category->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    							<option value="hide" <?php if($category->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
                    							<option value="lock" <?php if($category->hide == "lock") { echo "selected=\"selected\""; } ?>>Закрыт от индексации</option>
                 							</select>
											</div>
                        				</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="type_id">Шаблон категории</label>
											<div class="col-sm-9">
											<select name="type_id" class="form-control" style="width: 100%;">
												<option value="1" <?php if($category->type_id == "1") { echo "selected=\"selected\""; } ?>>Каталог</option>
                    							<option value="2" <?php if($category->type_id == "2") { echo "selected=\"selected\""; } ?>>Товары</option>                    						
                 							</select>
											</div>
                        				</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label">Зафиксировать цены РРЦ для категории (акция)</label>
											<div class="col-sm-9">												
												<div class="custom-control custom-checkbox">
													<input class="custom-control-input" type="checkbox" id="customCheckbox3" name="sale" <?=$category->sale ? ' checked' : null;?>>
													<label style="font-weight:400" for="customCheckbox3" class="custom-control-label">Распродажа (акция)</label>
												</div>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
											<div class="col-sm-9">
                                       			<div id="single" class="btn btn-success" data-url="category/add-image" data-name="single" data-razdel="category">Выбрать файл</div>
												<p><small>Рекомендуемые размеры: 600х450</small></p>
												<div class="single">
													<img src="/images/category/baseimg/<?=$category->img;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$category->id;?>" data-src="<?=$category->img;?>" data-razdel="category" class="del-base">
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
												<input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически"  value="<?=$category->alias;?>">
											</div>
                        				</div>
										<div class="form-group row">
                               				<label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
												<div class="col-sm-9">
                                				<input type="text" name="title" class="form-control" id="title" placeholder="Название которое будет отображаться в поисковиках" value="<?=h($category->title);?>">
                            				</div>
                        				</div>						
                        				<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
											<div class="col-sm-9">	
                                				<input type="text" name="description" class="form-control" id="description" placeholder="Описание" value="<?=h($category->description);?>">
                            				</div>
                        				</div>
										<div class="form-group row">
                               				<label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
											<div class="col-sm-9">
                                				<input type="text" name="keywords" class="form-control" id="keywords" placeholder="Ключевые слова" value="<?=h($category->keywords);?>">
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
                    <input type="hidden" name="id" value="<?=$category->id;?>">
					<button type="submit" class="btn btn-success">Сохранить</button>
				</div>
            </form>
            <!-- ./card -->			
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
    <!-- END CUSTOM TABS -->		
</section>
<!-- /.content -->