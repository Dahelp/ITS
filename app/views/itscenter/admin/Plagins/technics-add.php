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
              <li class="breadcrumb-item active">Добавить технику</li>
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
                <form method="post" action="<?=ADMIN;?>/plagins/technics-add" role="form" data-toggle="validator">
				
					<div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Добавить технику</h3>
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
									<option value="" />Выберите категорию</option>
									<?php foreach($types as $type): ?>
										<option value="<?=$type["id"];?>"><?=$type["name"];?></option>
									<?php endforeach; ?>
								</select>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="manufacturer_id">Производитель <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control" name="manufacturer_id">
									<option value="" />Выберите производителя</option>
									<?php foreach($manufacturers as $manufacturer): ?>
										<option value="<?=$manufacturer["id"];?>"><?=$manufacturer["name"];?></option>
									<?php endforeach; ?>
								</select>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="model">Модель <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="model" id="model" value="<?= isset($_SESSION['form_data']['model']) ? $_SESSION['form_data']['model'] : '' ?>" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="content">Описание</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?php isset($_SESSION['form_data']['content']) ? $_SESSION['form_data']['content'] : null; ?></textarea>
							</div>
                        </div>				
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>                    			
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="position">Позиция</label>
							<div class="col-sm-9">
								<input type="text" name="position" class="form-control" id="position" placeholder="0" value="<?php isset($_SESSION['form_data']['position']) ? h($_SESSION['form_data']['position']) : null; ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
							<div class="col-sm-9">
                                        <div id="single" class="btn btn-success" data-url="plagins/technics-add-image" data-name="single" data-razdel="technics">Выбрать файл</div>
                                        <p><small>Рекомендуемые размеры: 600х450</small></p>
                                        <div class="single"></div>
                                    
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
                                <input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически" value="<?php isset($_SESSION['form_data']['alias']) ? h($_SESSION['form_data']['alias']) : null; ?>">
							</div>
                        </div>
						<div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
								<div class="col-sm-9">
                                <input type="text" name="title" class="form-control" id="title" placeholder="Название которое будет отображаться в поисковиках">
                            </div>
                        </div>						
                        <div class="form-group row">
							<label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
							<div class="col-sm-9">	
                                <input type="text" name="description" class="form-control" id="description" placeholder="Описание">
                            </div>
                        </div>
						<div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
								<div class="col-sm-9">
                                <input type="text" name="keywords" class="form-control" id="keywords" placeholder="Ключевые слова">
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
								<select name="size[]" class="form-control tiposize" id="size" multiple="multiple" data-placeholder="Выберите размер шины"></select>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="size_back">Размер шины (задние)</label>
							<div class="col-sm-9">
								<select name="size_back[]" class="form-control tiposize" id="size_back" multiple="multiple" data-placeholder="Выберите размер шины"></select>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="size_alt">Альтернативные размеры шин (передние)</label>
							<div class="col-sm-9">
								<select name="size_alt[]" class="form-control tiposize" id="size_alt" multiple="multiple" data-placeholder="Выберите размер шины"></select>
							</div>
						</div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="size_alt_back">Альтернативные размеры шин (задние)</label>
							<div class="col-sm-9">
								<select name="size_alt_back[]" class="form-control tiposize" id="size_alt_back" multiple="multiple" data-placeholder="Выберите размер шины"></select>
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
									<input type="text" name="url_video" class="form-control" id="url_video" placeholder="Укажите ссылку на видео" value="<?php isset($_SESSION['form_data']['url_video']) ? h($_SESSION['form_data']['url_video']) : null; ?>">
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
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
                <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
            </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->
		
</section>
<!-- /.content -->