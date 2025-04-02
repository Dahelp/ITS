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
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/contents/type-content">Типы контента</a></li>
              <li class="breadcrumb-item active">Редактировать тип</li>
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
            <form action="<?=ADMIN;?>/contents/type-edit" method="post" data-toggle="validator">
                    <div class="card">
						<div class="card-header d-flex p-0">
							<h3 class="card-title p-3">Редактировать тип <?=h($type->name);?></h3>
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
											<label class="col-sm-3 col-form-label" for="name">Наименование</label>
											<div class="col-sm-9">
												<input type="text" name="name" class="form-control" id="name" placeholder="Наименование" required value="<?=h($type->name);?>">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="param_url">Системный URL</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" placeholder="например: article" value="<?=h($type->param_url);?>" disabled="">
												<input type="hidden" name="param_url" class="form-control" id="param_url" value="<?=h($type->param_url);?>">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide_anons">Просмотр списка</label>
											<div class="col-sm-9">
												<select name="hide_anons" class="form-control" style="width: 100%;">
													<option value= "show" <?php if($type->hide_anons == "show") { echo "selected=\"selected\""; } ?>>Да</option>
													<option value= "hide" <?php if($type->hide_anons == "hide") { echo "selected=\"selected\""; } ?>>Нет</option>                    			
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide_clicks">Включить статистику просмотров</label>
											<div class="col-sm-9">
												<select name="hide_clicks" class="form-control" style="width: 100%;">
													<option value= "show" <?php if($type->hide_clicks == "show") { echo "selected=\"selected\""; } ?>>Да</option>
													<option value= "hide" <?php if($type->hide_clicks == "hide") { echo "selected=\"selected\""; } ?>>Нет</option>                    			
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide_date_post">Показывать дату публикации</label>
											<div class="col-sm-9">
												<select name="hide_date_post" class="form-control" style="width: 100%;">
													<option value= "show" <?php if($type->hide_date_post == "show") { echo "selected=\"selected\""; } ?>>Да</option>
													<option value= "hide" <?php if($type->hide_date_post == "hide") { echo "selected=\"selected\""; } ?>>Нет</option>                    			
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
											<div class="col-sm-9">
												<select name="hide" class="form-control" style="width: 100%;">
													<option value= "show" <?php if($type->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
													<option value= "hide" <?php if($type->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>                    			
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide_rss">Активность контента YML-RSS</label>
											<div class="col-sm-9">
												<select name="hide_rss" class="form-control" style="width: 100%;">
													<option value= "show" <?php if($type->hide_rss == "show") { echo "selected=\"selected\""; } ?>>Да</option>
													<option value= "hide" <?php if($type->hide_rss == "hide") { echo "selected=\"selected\""; } ?>>Нет</option>                    			
												</select>
											</div>
										</div>
									</div>
								</div>
								<!-- /.tab-pane -->
								<div class="tab-pane" id="tab_2">
									<div class="box-body">
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
											<div class="col-sm-9">
												<input type="text" name="title" class="form-control" id="title" placeholder="Если пусто, то используется Название" value="<?=$type->title;?>">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
											<div class="col-sm-9">
												<input type="text" name="description" class="form-control" id="description" placeholder="SEO описание (130-150 символов)" value="<?=$type->description;?>">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
											<div class="col-sm-9">
												<input type="text" name="keywords" class="form-control" id="keywords" placeholder="Заполняются через запятую (4-6 фраз)" value="<?=$type->keywords;?>">
											</div>
										</div>								
									</div>
								</div><!-- /.tab-pane -->
							</div><!-- /.tab-content -->							
						</div><!-- /.card-body -->				
				</div>
				<div class="box-footer">
						<input type="hidden" name="id" value="<?=$type->id;?>">
						<button type="submit" class="btn btn-success">Сохранить</button>
				</div>
			</form>			        
        </div>
    </div>
</section>
<!-- /.content -->