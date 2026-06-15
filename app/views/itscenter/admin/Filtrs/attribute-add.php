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
								<li class="nav-item"><a class="nav-link" href="#tab_3" data-toggle="tab">Перелинковка</a></li>
								<li class="nav-item"><a class="nav-link" href="#tab_faq" data-toggle="tab">FAQ</a></li>
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
													<option value="">Выберите группу</option>
													<?php foreach($group as $item): ?>
													<option value="<?=$item->id;?>"><?=$item->title;?></option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="canonical_category_id">Каноническая категория для SEO</label>
											<div class="col-sm-9">
												<select name="canonical_category_id" id="canonical_category_id" class="form-control">
													<option value="0">Не выбрано</option>
													<?php foreach($canonicalCategories as $cat): ?>
														<option value="<?=$cat->id;?>"><?=h($cat->name);?></option>
													<?php endforeach; ?>
												</select>
												<small class="form-text text-muted">
													Используется для 301-редиректа и определения основной category-страницы фильтра.
													Для типоразмеров выбирается одна каноническая категория, даже если фильтр применяется в нескольких категориях.
												</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="hide">Статус активности <span class="text-danger">*</span></label>
											<div class="col-sm-9">
												<select class="form-control" name="hide">
													<option value="">Выберите статус</option>
													<option value="show">Да</option>
													<option value="hide">Нет</option>					
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
										<label class="col-sm-3 col-form-label" for="top_content">Текст сверху (над товарами)</label>
											<div class="col-sm-9">
												<textarea name="top_content" id="editor2" cols="80" rows="10"></textarea>
												<small class="form-text text-muted">
												Короткий вступительный текст для индексации и ориентации пользователя. Показывается под H1 и над товарами.
												</small>
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="alias">Ссылка страницы</label>
											<div class="col-sm-9">
												<input type="text" class="form-control" name="alias" id="alias" placeholder="Если пусто, создается автоматически">
											</div>
										</div>
										<div class="form-group row">
											<label class="col-sm-3 col-form-label" for="seo_h1">
												SEO H1
											</label>
											<div class="col-sm-9">
												<input type="text"
													class="form-control"
													name="seo_h1"
													id="seo_h1"
													placeholder="Ручной H1 для страницы фильтра">
												<small class="form-text text-muted">
													Если заполнено — используется как H1 на странице фильтра.  
													Имеет приоритет над InSEO. Если пусто — используется InSEO или значение фильтра.
												</small>
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
								 <div class="tab-pane" id="tab_3">
									<div class="box-body">

										<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="related_sizes">Связанные типоразмеры</label>
										<div class="col-sm-9">
											<select name="related_sizes[]" id="related_sizes" class="form-control" multiple>
											<?php foreach($sizeValues as $v): ?>
												<option value="<?=$v->id;?>"><?=h($v->value);?></option>
											<?php endforeach; ?>
											</select>
											<small class="form-text text-muted">
											Выберите типоразмеры, на которые нужно сослаться с этой страницы (ручная перелинковка).
											</small>
										</div>
										</div>

										<div class="form-group row">
										<label class="col-sm-3 col-form-label" for="technic_links">Подходит для техники</label>
										<div class="col-sm-9">
											<select name="technic_ids[]" id="technic_links" class="form-control" multiple>
											<?php foreach($technics as $t): ?>
												<option value="<?=$t->id;?>"><?=h($t->name);?></option>
											<?php endforeach; ?>
											</select>
											<small class="form-text text-muted">
											Ручной блок “Подходит для…”. Показывается под верхним текстом (или сразу под H1, если текста нет).
											</small>
										</div>
										</div>

									</div>
								</div>
								<!-- /.tab-pane -->
								<div class="tab-pane" id="tab_faq">
									<div class="box-body">

										<div class="mb-3">
										<button type="button" class="btn btn-sm btn-primary" id="faqAddRow">Добавить вопрос</button>
										<small class="form-text text-muted mt-2">
											2–6 вопросов на страницу достаточно. Вопрос и ответ обязательны. Порядок — сортировка (меньше = выше).
										</small>
										</div>

										<div id="faqWrap"></div>

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

<script>
  $(function () {
    $('#related_sizes').select2({ width: '100%' });
    $('#technic_links').select2({ width: '100%' });
    $('#canonical_category_id').select2({ width: '100%' });
  });
</script>

<script>
(function(){
  function faqRowTemplate(){
    return `
      <div class="card mb-3 faq-row">
        <div class="card-body">
          <div class="form-group">
            <label>Вопрос</label>
            <input type="text" name="faq[q][]" class="form-control" value="">
          </div>

          <div class="form-group">
            <label>Ответ</label>
            <textarea name="faq[a][]" class="form-control" rows="3"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group col-md-3">
              <label>Порядок</label>
              <input type="number" name="faq[s][]" class="form-control" value="500">
            </div>

            <div class="form-group col-md-3">
              <label>Показ</label>
              <select name="faq[h][]" class="form-control">
                <option value="show" selected>Показывать</option>
                <option value="hide">Скрыть</option>
              </select>
            </div>

            <div class="form-group col-md-3 d-flex align-items-end">
              <button type="button" class="btn btn-outline-danger btn-sm faqRemoveRow">Удалить</button>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  document.addEventListener('click', function(e){
    if (e.target && e.target.id === 'faqAddRow') {
      var wrap = document.getElementById('faqWrap');
      wrap.insertAdjacentHTML('beforeend', faqRowTemplate());
    }
    if (e.target && e.target.classList.contains('faqRemoveRow')) {
      var row = e.target.closest('.faq-row');
      if (row) row.remove();
    }
  });
})();
</script>
