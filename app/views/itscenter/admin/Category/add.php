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
              <li class="breadcrumb-item active">Добавить категорию</li>
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
			<form action="<?=ADMIN;?>/category/add" method="post" data-toggle="validator">
			<!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Добавить категорию</h3>
				<ul class="nav nav-pills ml-auto p-2">
                    <li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">SEO</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tab_3" data-toggle="tab">FAQ</a></li>
                </ul>
				</div><!-- /.card-header -->
                        <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane active" id="tab_1">
                    <div class="box-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name">Наименование категории</label>
								<div class="col-sm-9">
									<input type="text" name="name" class="form-control" id="name" placeholder="Наименование категории" required>                                
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
                        <label class="col-sm-3 col-form-label" for="top_content">Верхний текст категории</label>
                        <div class="col-sm-9">
                        <textarea
                            name="top_content"
                            id="editor2"
                            class="form-control" cols="80" rows="10"                       
                            placeholder="Текст выводится под H1, перед фильтрами и товарами"><?= isset($_SESSION['form_data']['top_content']) ? h($_SESSION['form_data']['top_content']) : '' ?></textarea>
                        </div>    
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="content">Основной текст</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?=isset($_SESSION['form_data']['content']) ? $_SESSION['form_data']['content'] : '';?></textarea>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="position">Позиция</label>
							<div class="col-sm-9">
								<input type="text" name="position" class="form-control" id="position" placeholder="0" value="0">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>
                    			<option value= "lock">Закрыт от индексации</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="type_id">Шаблон категории</label>
							<div class="col-sm-9">
							<select name="type_id" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите внешний вид шаблона</option>
								<option value= "1">Каталог</option>
                    			<option value= "2">Товары</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label">Зафиксировать цены РРЦ для категории (акция)</label>
							<div class="col-sm-9">												
								<div class="custom-control custom-checkbox">
									<input class="custom-control-input" type="checkbox" id="customCheckbox3" name="sale">
									<label style="font-weight:400" for="customCheckbox3" class="custom-control-label">Распродажа (акция)</label>
								</div>
							</div>
						</div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
							<div class="col-sm-9">
                                        <div id="single" class="btn btn-success" data-url="category/add-image" data-name="single" data-razdel="category">Выбрать файл</div>
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
                                <input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически" value="<?=isset($_SESSION['form_data']['alias']) ? h($_SESSION['form_data']['alias']) : '';?>">
							</div>
                        </div>
                        <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="h1">SEO H1</label>
								<div class="col-sm-9">
                                <input type="text" name="h1" class="form-control" id="h1" value="<?= isset($_SESSION['form_data']['h1']) ? h($_SESSION['form_data']['h1']) : '' ?>" placeholder="H1 на странице категории">
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

                            <div class="alert alert-info">
                            FAQ будет выводиться на странице категории и использоваться для JSON-LD микроразметки FAQPage.
                            </div>

                            <div id="category-faq-list">
                            <?php
                            $faqFormRows = $_SESSION['form_data']['faq'] ?? [];

                            if (empty($faqFormRows)) {
                                $faqFormRows = [
                                    [
                                        'question' => '',
                                        'answer' => '',
                                        'sort' => 1,
                                        'hide' => 'show',
                                    ],
                                ];
                            }

                            foreach ($faqFormRows as $i => $faq):
                                $question = $faq['question'] ?? '';
                                $answer = $faq['answer'] ?? '';
                                $sort = $faq['sort'] ?? ($i + 1);
                                $hide = $faq['hide'] ?? 'show';
                            ?>
                                <div class="card mb-3 category-faq-item">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <strong>Вопрос</strong>
                                    <button type="button" class="btn btn-danger btn-sm js-remove-category-faq ml-auto">Удалить</button>
                                </div>

                                <div class="card-body">
                                    <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Вопрос</label>
                                    <div class="col-sm-9">
                                        <input
                                        type="text"
                                        name="faq[<?=$i;?>][question]"
                                        class="form-control js-faq-question"
                                        value="<?=h($question);?>"
                                        placeholder="Например: Какие шины выбрать для вилочного погрузчика?"
                                        >
                                    </div>
                                    </div>

                                    <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Ответ</label>
                                    <div class="col-sm-9">
                                        <textarea
                                        name="faq[<?=$i;?>][answer]"
                                        class="form-control js-faq-answer"
                                        rows="4"
                                        placeholder="Краткий полезный ответ для пользователя"
                                        ><?=h($answer);?></textarea>
                                    </div>
                                    </div>

                                    <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Позиция</label>
                                    <div class="col-sm-3">
                                        <input
                                        type="number"
                                        name="faq[<?=$i;?>][sort]"
                                        class="form-control js-faq-sort"
                                        value="<?=h($sort);?>"
                                        >
                                    </div>

                                    <label class="col-sm-2 col-form-label">Статус</label>
                                    <div class="col-sm-4">
                                        <select name="faq[<?=$i;?>][hide]" class="form-control js-faq-hide">
                                        <option value="show" <?=$hide === 'show' ? 'selected' : '';?>>Показывать</option>
                                        <option value="hide" <?=$hide === 'hide' ? 'selected' : '';?>>Скрыть</option>
                                        </select>
                                    </div>
                                    </div>
                                </div>
                                </div>
                            <?php endforeach; ?>
                            </div>

                            <button type="button" class="btn btn-primary" id="add-category-faq">
                            Добавить вопрос
                            </button>

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
                    </form>
            <!-- ./card -->
			<?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->
		
</section>
<!-- /.content -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    var faqList = document.getElementById('category-faq-list');
    var addBtn = document.getElementById('add-category-faq');

    if (!faqList || !addBtn) {
        return;
    }

    function reindexFaqItems() {
        var items = faqList.querySelectorAll('.category-faq-item');

        items.forEach(function (item, index) {
            var question = item.querySelector('.js-faq-question');
            var answer = item.querySelector('.js-faq-answer');
            var sort = item.querySelector('.js-faq-sort');
            var hide = item.querySelector('.js-faq-hide');

            if (question) {
                question.name = 'faq[' + index + '][question]';
            }

            if (answer) {
                answer.name = 'faq[' + index + '][answer]';
            }

            if (sort) {
                sort.name = 'faq[' + index + '][sort]';

                if (!sort.value) {
                    sort.value = index + 1;
                }
            }

            if (hide) {
                hide.name = 'faq[' + index + '][hide]';
            }
        });
    }

    addBtn.addEventListener('click', function () {
        var index = faqList.querySelectorAll('.category-faq-item').length;

        var html = `
            <div class="card mb-3 category-faq-item">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Вопрос</strong>
                    <button type="button" class="btn btn-danger btn-sm js-remove-category-faq ml-auto">Удалить</button>
                </div>

                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Вопрос</label>
                        <div class="col-sm-9">
                            <input
                                type="text"
                                name="faq[${index}][question]"
                                class="form-control js-faq-question"
                                value=""
                                placeholder="Например: Какие шины выбрать для вилочного погрузчика?"
                            >
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Ответ</label>
                        <div class="col-sm-9">
                            <textarea
                                name="faq[${index}][answer]"
                                class="form-control js-faq-answer"
                                rows="4"
                                placeholder="Краткий полезный ответ для пользователя"
                            ></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Позиция</label>
                        <div class="col-sm-3">
                            <input
                                type="number"
                                name="faq[${index}][sort]"
                                class="form-control js-faq-sort"
                                value="${index + 1}"
                            >
                        </div>

                        <label class="col-sm-2 col-form-label">Статус</label>
                        <div class="col-sm-4">
                            <select name="faq[${index}][hide]" class="form-control js-faq-hide">
                                <option value="show" selected>Показывать</option>
                                <option value="hide">Скрыть</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;

        faqList.insertAdjacentHTML('beforeend', html);
        reindexFaqItems();
    });

    faqList.addEventListener('click', function (e) {
        if (!e.target.classList.contains('js-remove-category-faq')) {
            return;
        }

        var item = e.target.closest('.category-faq-item');

        if (item) {
            item.remove();
            reindexFaqItems();
        }
    });

    reindexFaqItems();
});
</script>