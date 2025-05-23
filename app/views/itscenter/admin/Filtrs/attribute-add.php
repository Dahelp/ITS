<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Фильтры</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/filtrs/attribute">Список фильтров</a></li>
              <li class="breadcrumb-item active">Новый фильтр</li>
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
                <form action="<?=ADMIN;?>/filtrs/attribute-add" method="post" data-toggle="validator" id="add">
                    <div class="card">
						<div class="card-header d-flex p-0">
							<h3 class="card-title p-3">Добавить фильтр</h3>
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
											<label class="col-sm-3 col-form-label" for="value">Наименование фильтра</label>
											<div class="col-sm-9">
												<input type="text" name="value" class="form-control" id="value" placeholder="Наименование фильтра" required>
											</div>
										</div>								
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="category_id">Группа</label>
											<div class="col-sm-9">
												<select name="attr_group_id" id="category_id" class="form-control">
													<option>Выберите группу</option>
													<?php foreach($group as $item): ?>
													<option value="<?=$item->id;?>"><?=$item->title;?></option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide">Статус активности <span class="text-danger">*</span></label>
											<div class="col-sm-9">
												<select class="form-control" name="hide">
													<option value="" />Выберите статус</option>
													<option value="show" />Да</option>
													<option value="hide" />Нет</option>					
												</select>
											</div>
										</div>
									</div>
								</div>
								<!-- /.tab-pane -->
								<div class="tab-pane" id="tab_2">
									<div class="box-body">
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="content">Описание</label>
											<div class="col-sm-9">
												<textarea name="content" id="editor1" cols="80" rows="10"></textarea>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="alias">Ссылка страницы</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="alias" id="alias" placeholder="Если пусто, создается автоматически"">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="title" id="title">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="description" id="description">
											</div>
										</div>				
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="keywords" id="keywords">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
											<div class="col-sm-9">
												<div id="single" class="btn btn-success" data-url="filtrs/add-image" data-name="single" data-razdel="filtrs">Выбрать файл</div>														
												<div class="single"></div>													
												<div class="overlay">
													<i class="fa fa-refresh fa-spin"></i>
												</div>
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
					<button type="submit" class="btn btn-success">Добавить</button>
				</div>
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