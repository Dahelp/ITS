<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Производители техники</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/technics-manufacturer">Список производителей техники</a></li>
              <li class="breadcrumb-item active">Редактирование производителя техники <?=$manufacturer->name;?></li>
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
            <form action="<?=ADMIN;?>/plagins/technics-edit-manufacturer" method="post" data-toggle="validator">
                <!-- Custom Tabs -->
				<div class="card">
					<div class="card-header d-flex p-0">
						<h3 class="card-title p-3">Редактирование производителя <?=$manufacturer->name;?></h3>
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
											<label class="col-sm-3 col-form-label" for="name">Название производителя</label>
											<div class="col-sm-9">
												<input type="text" name="name" class="form-control" id="name" placeholder="Наименование производителя" value="<?=h($manufacturer->name);?>" required>
											</div>                                        
										</div>										
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="content">Описание</label>
											<div class="col-sm-9">
												<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?=h($manufacturer->content);?></textarea>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
											<div class="col-sm-9">
											<select name="hide" class="form-control" style="width: 100%;">												
												<option value="show" <?php if($manufacturer->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
												<option value="hide" <?php if($manufacturer->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
											</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
											<div class="col-sm-9">
                                       			<div id="single" class="btn btn-success" data-url="plagins/technics-manufacturer-add-image" data-name="single" data-razdel="technics_manufacturer">Выбрать файл</div>
												<p><small>Рекомендуемые размеры: 600х450</small></p>
												<div class="single">
													<img src="/images/technics_manufacturer/baseimg/<?=$manufacturer->img;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$manufacturer->id;?>" data-src="<?=$manufacturer->img;?>" data-razdel="plagins" data-plagins="technics_manufacturer" class="del-base">
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
												<input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически"  value="<?=$manufacturer->alias;?>">
											</div>
                        				</div>
										<div class="form-group row">
                               				<label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
												<div class="col-sm-9">
                                				<input type="text" name="title" class="form-control" id="title" placeholder="Ключевые слова" value="<?=h($manufacturer->title);?>">
                            				</div>
                        				</div>						
                        				<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
											<div class="col-sm-9">	
                                				<input type="text" name="description" class="form-control" id="description" placeholder="Описание" value="<?=h($manufacturer->description);?>">
                            				</div>
                        				</div>
										<div class="form-group row">
                               				<label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
											<div class="col-sm-9">
                                				<input type="text" name="keywords" class="form-control" id="keywords" placeholder="Ключевые слова" value="<?=h($manufacturer->keywords);?>">
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
                    <input type="hidden" name="id" value="<?=$manufacturer->id;?>">
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