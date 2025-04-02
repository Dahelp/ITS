<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Группы фильтров</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/filtrs/attribute-group">Список группы фильтров</a></li>
              <li class="breadcrumb-item active">Добавить группу</li>
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
                <form action="<?=ADMIN;?>/filtrs/group-add" method="post" data-toggle="validator">
                    <div class="card">
						<div class="card-header d-flex p-0">
							<h3 class="card-title p-3">Добавить группу</h3>
							<ul class="nav nav-pills ml-auto p-2">
								<li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>                  
								<li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">SEO</a></li>
								<li class="nav-item"><a class="nav-link" href="#tab_3" data-toggle="tab">Шаблон</a></li>								
							</ul>
						</div><!-- /.card-header -->
						<div class="card-body">
							<div class="tab-content">
									<div class="tab-pane active" id="tab_1">
										<div class="box-body">
											<div class="form-group row">
												<label class="col-sm-3 col-form-label" for="title">Наименование группы</label>
												<div class="col-sm-9">
													<input type="text" name="title" class="form-control" id="title" placeholder="Наименование группы" required>
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
									</div>
								<!-- /.tab-pane -->
								<div class="tab-pane" id="tab_2">
									<div class="box-body">
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="seo_content">Описание</label>
											<div class="col-sm-9">
												<textarea name="seo_content" id="editor1" cols="80" rows="10"></textarea>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="url_params">Системное имя</label>
											<div class="col-sm-9">
												<input type="text" name="url_params" class="form-control" id="url_params" placeholder="Системное имя">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="page_name">Название для страницы (H1)</label>
												<div class="col-sm-9">
												<input type="text" name="page_name" class="form-control" id="page_name" placeholder="Название которое будет отображаться в поисковиках">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="seo_title">Заголовок (Title)</label>
												<div class="col-sm-9">
												<input type="text" name="seo_title" class="form-control" id="seo_title" placeholder="Название которое будет отображаться в поисковиках">
											</div>
										</div>						
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="seo_description">Ключевое описание (Description)</label>
											<div class="col-sm-9">	
												<input type="text" name="seo_description" class="form-control" id="seo_description" placeholder="Описание">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="seo_keywords">Ключевые слова (Keywords)</label>
											<div class="col-sm-9">
												<input type="text" name="seo_keywords" class="form-control" id="seo_keywords" placeholder="Ключевые слова">
											</div>
										</div>									
									</div>
                  				</div>
								<div class="tab-pane" id="tab_3">
									<div class="box-body">
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="template">Шаблон страницы</label>
											<div class="col-sm-9">
												<select name="template" id="template" class="form-control">
													<option>Выберите шаблон</option>														
													<option value="0">Автоматический</option>
													<option value="1">Ручной</option>
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="notproduct">Текст при отсутствии товаров</label>
											<div class="col-sm-9">
												<input type="text" name="notproduct" class="form-control" id="notproduct" placeholder="Товары отсутствуют">
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