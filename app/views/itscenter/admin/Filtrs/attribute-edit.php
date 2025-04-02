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
              <li class="breadcrumb-item active">Редактирование фильтра</li>
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
                <a target="_blank" href="/<?=$attrs?>/<?=h($attr->alias);?>" class="btn btn-success"><i class="fad fa-eye"></i> Просмотр на сайте</a>
				<a href="<?=ADMIN;?>/filtrs/attribute" class="btn btn-primary"><i class="fal fa-reply-all"></i></a>
            </div>
                <form action="<?=ADMIN;?>/filtrs/attribute-edit" method="post" data-toggle="validator">
                    <div class="card">
						<div class="card-header d-flex p-0">
							<h3 class="card-title p-3">Редактирование фильтра <?=h($attr->value);?></h3>
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
												<input type="text" name="value" class="form-control" id="value" placeholder="Наименование фильтра" required value="<?=h($attr->value);?>">
												
											</div>
										</div>										
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="category_id">Группа</label>
											<div class="col-sm-9">
												<select name="attr_group_id" id="category_id" class="form-control">
													<?php foreach($attrs_group as $item): ?>
													<option value="<?=$item->id;?>"<?php if($item->id == $attr->attr_group_id) echo ' selected'; ?>><?=$item->title;?></option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide">Статус активности <span class="text-danger">*</span></label>
											<div class="col-sm-9">
												<select class="form-control" name="hide">
													<option value="" />Выберите статус</option>
													<option value="show"<?php if($attr->hide == 'show') echo ' selected'; ?> />Да</option>
													<option value="hide"<?php if($attr->hide == 'hide') echo ' selected'; ?> />Нет</option>					
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
												<textarea name="content" id="editor1" cols="80" rows="10"><?=h($attr->content);?></textarea>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="alias">Ссылка страницы</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="alias" id="alias" placeholder="Если пусто, создается автоматически" value="<?=h($attr->alias);?>">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="title" id="title" value="<?=h($attr->title);?>">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="description" id="description" value="<?=h($attr->description);?>">
											</div>
										</div>				
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="keywords" id="keywords" value="<?=h($attr->keywords);?>">
											</div>
										</div>
										<div class="form-group row file-upload">
											<label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
											<div class="col-sm-9">
                                       			<div id="single" class="btn btn-success" data-url="filtrs/add-image" data-name="single" data-razdel="filtrs">Выбрать файл</div>												
												<div class="single">
													<img src="/images/filtrs/baseimg/<?=$attr->img;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$attr->id;?>" data-src="<?=$attr->img;?>" data-razdel="filtrs" class="del-base">
												</div>                                    
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
					<input type="hidden" name="id" value="<?=$attr->id;?>">
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