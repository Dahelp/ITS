<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Контент</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/contents/pages">Список контента</a></li>
              <li class="breadcrumb-item active">Добавить контент</li>
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
			<form action="<?=ADMIN;?>/contents/page-add" method="post" data-toggle="validator">
				<div class="card">
					<div class="card-header d-flex p-0">
						<h3 class="card-title p-3">Добавить контент</h3>
						<ul class="nav nav-pills ml-auto p-2">
							<li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>								
							<li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">SEO</a></li>
							<li class="nav-item"><a class="nav-link" href="#tab_3" data-toggle="tab">Доп. параметры</a></li>
						</ul>
					</div><!-- /.card-header -->
					<div class="card-body">
						<div class="tab-content">
						  <div class="tab-pane active" id="tab_1">
							<div class="box-body">
									<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="name">Наименование</label>
										<div class="col-sm-9">
											<input type="text" name="name" class="form-control" id="name" placeholder="Наименование" required>
										</div>
									</div>
									<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="type_id">Тип контента</label>
										<div class="col-sm-9">
											<select name="type_id" class="form-control" style="width: 100%;">
											<option value= "" selected="selected">Выберите тип контента</option>
												<?php $type_sql = \R::getAll('SELECT id, name FROM content_type');
													$i=1; foreach($type_sql as $typs => $item): ?>									
												<option value= "<?=$item["id"]?>"><?=$item["name"]?></option>											
												<?php $i++; endforeach; ?>
											</select>
										</div>
									</div>
									<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="anons">Анонс</label>
										<div class="col-sm-9">
											<textarea class="form-control" name="anons" id="editor1" cols="80" rows="10"><?php isset($_SESSION['form_data']['anons']) ? $_SESSION['form_data']['anons'] : null; ?></textarea>
										</div>
									</div>
									<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="content">Подробное описание</label>
										<div class="col-sm-9">
											<textarea class="form-control" name="content" id="editor2" cols="80" rows="10"><?php isset($_SESSION['form_data']['content']) ? $_SESSION['form_data']['content'] : null; ?></textarea>
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
										<label class="col-sm-3 col-form-label" for="img">Изображение</label>
										<div class="col-sm-9">
												<div id="single" class="btn btn-success" data-url="contents/add-image" data-name="single" data-razdel="contents">Выбрать файл</div>
												<p><small>Рекомендуемые размеры: <?=$wmax?>х<?=$hmax?></small></p>
												<div class="single"></div>
											
											<div class="overlay">
												<i class="fa fa-refresh fa-spin"></i>
											</div>
										</div>
									</div>
									<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="img_hide">Отображение фото в контенте</label>
										<div class="col-sm-9">
											<select name="img_hide" class="form-control" style="width: 100%;">
												<option value= "" selected="selected">Выберите статус активности</option>
												<option value= "show">Активный</option>
												<option value= "hide">Не активный</option>                    			
											</select>
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
											<input type="text" name="title" class="form-control" id="title" placeholder="Если пусто, то используется Название" value="<?php isset($_SESSION['form_data']['title']) ? h($_SESSION['form_data']['title']) : null; ?>">
										</div>
									</div>
									<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
										<div class="col-sm-9">
											<input type="text" name="description" class="form-control" id="description" placeholder="SEO описание (130-150 символов)" value="<?php isset($_SESSION['form_data']['description']) ? h($_SESSION['form_data']['description']) : null; ?>">
										</div>
									</div>
									<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
										<div class="col-sm-9">
											<input type="text" name="keywords" class="form-control" id="keywords" placeholder="Заполняются через запятую (4-6 фраз)" value="<?php isset($_SESSION['form_data']['keywords']) ? h($_SESSION['form_data']['keywords']) : null; ?>">
										</div>
									</div>								
								</div>
							</div>
							<!-- /.tab-pane -->
							<div class="tab-pane" id="tab_3">
								<div class="box-body">
									<div class="form-group row">
									<label class="col-sm-3 col-form-label" for="related">Связанные товары</label>
									<div class="col-sm-9">
										<select name="related[]" class="form-control select2" id="related" multiple="multiple" data-placeholder="Выберите товары"></select>
									</div>
								</div>
								</div>
							  </div>
						</div>
						<!-- /.tab-content -->
						
					</div><!-- /.card-body -->
					  
				</div>
				<div class="box-footer">
					<button type="submit" class="btn btn-success">Добавить</button>
				</div>
			</form>
		</div>        
    </div>
</section>
<!-- /.content -->