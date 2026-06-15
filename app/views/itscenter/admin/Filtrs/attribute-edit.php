<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Фильтры</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
                    <li class="breadcrumb-item"><a href="<?=ADMIN;?>/filtrs/attribute">Список фильтров</a></li>
                    <li class="breadcrumb-item active">Редактирование фильтра</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="row">
        <div class="col-12">

            <div class="menu_btn">
                <a target="_blank" href="/<?=$attrs?>/<?=h($attr->alias);?>" class="btn btn-success">
                    <i class="fad fa-eye"></i> Просмотр на сайте
                </a>
                <a href="<?=ADMIN;?>/filtrs/attribute" class="btn btn-primary">
                    <i class="fal fa-reply-all"></i>
                </a>
            </div>

            <form action="<?=ADMIN;?>/filtrs/attribute-edit" method="post" data-toggle="validator">
                <div class="card">
                    <div class="card-header d-flex p-0">
                        <h3 class="card-title p-3">Редактирование фильтра <?=h($attr->value);?></h3>

                        <ul class="nav nav-pills ml-auto p-2">
                            <li class="nav-item">
                                <a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#tab_3" data-toggle="tab">Перелинковка</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#tab_category_seo" data-toggle="tab">SEO по категориям</a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">
                        <div class="tab-content">

                            <div class="tab-pane active" id="tab_1">
                                <div class="box-body">

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="value">Наименование фильтра</label>
                                        <div class="col-sm-9">
                                            <input type="text"
                                                   name="value"
                                                   class="form-control"
                                                   id="value"
                                                   placeholder="Наименование фильтра"
                                                   required
                                                   value="<?=h($attr->value);?>">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="category_id">Группа</label>
                                        <div class="col-sm-9">
                                            <select name="attr_group_id" id="category_id" class="form-control">
                                                <?php foreach($attrs_group as $item): ?>
                                                    <option value="<?=$item->id;?>"<?php if($item->id == $attr->attr_group_id) echo ' selected'; ?>>
                                                        <?=$item->title;?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="alias">Ссылка фильтра</label>
                                        <div class="col-sm-9">
                                            <input type="text"
                                                   class="form-control mb-2"
                                                   value="<?=h($attr->alias);?>"
                                                   readonly>

                                            <input type="text"
                                                   class="form-control"
                                                   name="alias"
                                                   id="alias"
                                                   placeholder="Оставьте пустым, чтобы не менять URL. Чтобы изменить — введите новый alias">

                                            <small class="form-text text-muted">
                                                Текущий URL сохраняется, если поле пустое. Изменение alias меняет адрес страниц фильтра.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="hide">
                                            Статус активности <span class="text-danger">*</span>
                                        </label>
                                        <div class="col-sm-9">
                                            <select class="form-control" name="hide">
                                                <option value="">Выберите статус</option>
                                                <option value="show"<?php if($attr->hide == 'show') echo ' selected'; ?>>Да</option>
                                                <option value="hide"<?php if($attr->hide == 'hide') echo ' selected'; ?>>Нет</option>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="tab-pane" id="tab_3">
                                <div class="box-body">

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="related_sizes">Связанные типоразмеры</label>
                                        <div class="col-sm-9">
                                            <select name="related_sizes[]" id="related_sizes" class="form-control" multiple>
                                                <?php foreach($sizeValues as $v): ?>
                                                    <option value="<?=$v->id;?>" <?php if(in_array($v->id, $relatedSizeIds)) echo 'selected'; ?>>
                                                        <?=h($v->value);?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">
                                                Выберите типоразмеры, на которые нужно сослаться с этой страницы.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label" for="technic_links">Подходит для техники</label>
                                        <div class="col-sm-9">
                                            <select name="technic_ids[]" id="technic_links" class="form-control" multiple>
                                                <?php foreach($technics as $t): ?>
                                                    <option value="<?=$t->id;?>" <?php if(in_array($t->id, $technicIds)) echo 'selected'; ?>>
                                                        <?=h($t->name);?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">
                                                Ручной блок “Подходит для…”. Показывается на посадочных страницах фильтра.
                                            </small>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="tab-pane" id="tab_category_seo">
                                <div class="box-body">

                                    <div class="alert alert-info">
                                        Здесь задаётся уникальное SEO для страниц вида
                                        <strong>/category/категория/<?=h($attr->alias);?></strong>.
                                        Например, один и тот же размер может иметь разные тексты для шин, камер, дисков и ободных лент.
                                    </div>

                                    <?php
                                        $seoCategories = $seoCategories ?? [];
                                        $availableSeoCategories = $availableSeoCategories ?? [];
                                        $canonicalCategories = $canonicalCategories ?? [];

                                        $seoIndex = 0;

                                        function renderCategorySeoBlock($index, $row, $attr, $availableSeoCategories, $canonicalCategories, $isNew = false) {
                                            $catId = (int)($row['id'] ?? $row['category_id'] ?? 0);
                                            $catName = (string)($row['name'] ?? '');
                                            $catAlias = (string)($row['alias'] ?? '');

                                            $isActive = isset($row['is_active']) ? (int)$row['is_active'] : 1;
                                            $mode = $row['mode'] ?? 'landing';
                                            $source = $row['source'] ?? 'manual';
                                            $redirectCategoryId = (int)($row['redirect_category_id'] ?? 0);
                                            $ruleId = (int)($row['rule_id'] ?? 0);        
                                            $url = $catAlias !== '' ? '/category/' . $catAlias . '/' . $attr->alias : '';

                                            $faqItems = $row['faq'] ?? [];
                                            if (!is_array($faqItems)) {
                                                $faqItems = [];
                                            }
                                    ?>

                                        <div class="card mb-4 category-seo-row" data-rule-id="<?=$ruleId;?>">
                                            <input type="hidden" name="category_seo[<?=$index;?>][rule_id]" value="<?=$ruleId;?>">
                                            <div class="card-header">
                                                <div class="d-flex align-items-start" style="width:100%;">
                                                    <div style="flex:1 1 auto; min-width:0;">
                                                        <strong class="category-seo-title">
                                                            <?= $catName !== '' ? h($catName) : 'Новая SEO-категория'; ?>
                                                        </strong>

                                                        <?php if($source): ?>
                                                            <span class="badge badge-secondary ml-2">source: <?=h($source);?></span>
                                                        <?php endif; ?>

                                                        <?php if($url): ?>
                                                            <div class="text-muted mt-1">
                                                                <a href="<?=h($url);?>" target="_blank">
                                                                    <?=h($url);?>
                                                                    <i class="fas fa-external-link-alt ml-1"></i>
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div style="flex:0 0 auto; margin-left:15px;">
                                                        <button type="button" class="btn btn-outline-danger btn-sm categorySeoRemove">
                                                            Удалить SEO-страницу
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-body">

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Категория</label>
                                                    <div class="col-sm-9">
                                                        <select name="category_seo[<?=$index;?>][category_id]"
                                                                class="form-control category-seo-category-select"
                                                                <?=(!$isNew ? 'readonly disabled' : '');?>>
                                                            <option value="0">Выберите категорию</option>

                                                            <?php foreach($availableSeoCategories as $cat): ?>
                                                                <option value="<?=$cat['id'];?>"
                                                                        data-alias="<?=h($cat['alias'] ?? '');?>"
                                                                        data-name="<?=h($cat['name'] ?? '');?>"
                                                                        <?=((int)$cat['id'] === $catId ? 'selected' : '');?>>
                                                                    <?=h($cat['name']);?><?=!empty($cat['alias']) ? ' — /category/' . h($cat['alias']) : '';?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <?php if(!$isNew): ?>
                                                            <input type="hidden" name="category_seo[<?=$index;?>][category_id]" value="<?=$catId;?>">
                                                        <?php endif; ?>

                                                        <small class="form-text text-muted">
                                                            Для существующей SEO-страницы категорию менять нельзя. Если нужна другая — добавьте новую.
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Активность страницы</label>
                                                    <div class="col-sm-9">
                                                        <input type="hidden" name="category_seo[<?=$index;?>][is_active]" value="0">
                                                        <label>
                                                            <input type="checkbox"
                                                                   name="category_seo[<?=$index;?>][is_active]"
                                                                   value="1"
                                                                   <?=($isActive === 1 ? 'checked' : '');?>>
                                                            Страница активна
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Режим</label>
                                                    <div class="col-sm-9">
                                                        <select name="category_seo[<?=$index;?>][mode]" class="form-control">
                                                            <option value="landing" <?=($mode === 'landing' ? 'selected' : '');?>>Landing — показывать страницу</option>
                                                            <option value="redirect" <?=($mode === 'redirect' ? 'selected' : '');?>>Redirect — 301 на другую категорию</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Куда редиректить</label>
                                                    <div class="col-sm-9">
                                                        <select name="category_seo[<?=$index;?>][redirect_category_id]"
                                                                class="form-control category-redirect-select">
                                                            <option value="0">Не выбрано</option>

                                                            <?php foreach($canonicalCategories as $redirectCat): ?>
                                                                <option value="<?=$redirectCat->id;?>" <?=((int)$redirectCat->id === $redirectCategoryId ? 'selected' : '');?>>
                                                                    <?=h($redirectCat->name);?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>

                                                        <small class="form-text text-muted">
                                                            Используется только для режима Redirect.
                                                        </small>
                                                    </div>
                                                </div>

                                                <hr>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">SEO H1</label>
                                                    <div class="col-sm-9">
                                                        <input type="text"
                                                               name="category_seo[<?=$index;?>][seo_h1]"
                                                               class="form-control"
                                                               value="<?=h($row['seo_h1'] ?? '');?>"
                                                               placeholder="Например: Камеры 10.00-20 для спецтехники">
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Title</label>
                                                    <div class="col-sm-9">
                                                        <input type="text"
                                                               name="category_seo[<?=$index;?>][title]"
                                                               class="form-control"
                                                               value="<?=h($row['title'] ?? '');?>">
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Description</label>
                                                    <div class="col-sm-9">
                                                        <input type="text"
                                                               name="category_seo[<?=$index;?>][description]"
                                                               class="form-control"
                                                               value="<?=h($row['description'] ?? '');?>">
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Keywords</label>
                                                    <div class="col-sm-9">
                                                        <input type="text"
                                                               name="category_seo[<?=$index;?>][keywords]"
                                                               class="form-control"
                                                               value="<?=h($row['keywords'] ?? '');?>">
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Canonical</label>
                                                    <div class="col-sm-9">
                                                        <input type="text"
                                                               name="category_seo[<?=$index;?>][canonical_url]"
                                                               class="form-control"
                                                               value="<?=h($row['canonical_url'] ?? '');?>"
                                                               placeholder="<?=h($url);?>">
                                                        <small class="form-text text-muted">
                                                            Оставьте пустым, чтобы canonical формировался автоматически.
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Robots</label>
                                                    <div class="col-sm-9">
                                                        <?php $robotsValue = (string)($row['robots'] ?? ''); ?>
                                                        <select name="category_seo[<?=$index;?>][robots]" class="form-control">
                                                            <option value="" <?=($robotsValue === '' ? 'selected' : '');?>>Автоматически</option>
                                                            <option value="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" <?=($robotsValue === 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' ? 'selected' : '');?>>index, follow</option>
                                                            <option value="noindex, follow" <?=($robotsValue === 'noindex, follow' ? 'selected' : '');?>>noindex, follow</option>
                                                            <option value="noindex, nofollow" <?=($robotsValue === 'noindex, nofollow' ? 'selected' : '');?>>noindex, nofollow</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <?php if (!empty($row['created_at']) || !empty($row['updated_at'])): ?>
                                                    <div class="form-group row">
                                                        <label class="col-sm-3 col-form-label">Даты</label>
                                                        <div class="col-sm-9">
                                                            <div class="form-control-plaintext">
                                                                <?php if (!empty($row['created_at'])): ?>
                                                                    created_at: <?=h($row['created_at']);?>
                                                                <?php endif; ?>
                                                                <?php if (!empty($row['updated_at'])): ?>
                                                                    <?=!empty($row['created_at']) ? ' / ' : '';?>updated_at: <?=h($row['updated_at']);?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Текст сверху</label>
                                                    <div class="col-sm-9">
                                                        <textarea name="category_seo[<?=$index;?>][top_content]"
                                                                  id="editor2_<?=$index;?>"
                                                                  cols="80"
                                                                  rows="10"><?=h($row['top_content'] ?? '');?></textarea>
                                                    </div>
                                                </div>

                                                <div class="form-group row">
                                                    <label class="col-sm-3 col-form-label">Основной текст</label>
                                                    <div class="col-sm-9">
                                                        <textarea name="category_seo[<?=$index;?>][content]"
                                                                  id="editor1_<?=$index;?>"
                                                                  cols="80"
                                                                  rows="10"><?=h($row['content'] ?? '');?></textarea>
                                                    </div>
                                                </div>

                                                <hr>

                                                <h5>FAQ для этой категории</h5>

                                                <div class="category-faq-wrap">
                                                    <?php foreach($faqItems as $faq): ?>
                                                        <div class="card mb-3 category-faq-row">
                                                            <div class="card-body">

                                                                <div class="form-group">
                                                                    <label>Вопрос</label>
                                                                    <input type="text"
                                                                           name="category_seo[<?=$index;?>][faq][q][]"
                                                                           class="form-control"
                                                                           value="<?=h($faq['question'] ?? '');?>">
                                                                </div>

                                                                <div class="form-group">
                                                                    <label>Ответ</label>
                                                                    <textarea name="category_seo[<?=$index;?>][faq][a][]"
                                                                              class="form-control"
                                                                              rows="3"><?=h($faq['answer'] ?? '');?></textarea>
                                                                </div>

                                                                <div class="form-row">
                                                                    <div class="form-group col-md-3">
                                                                        <label>Порядок</label>
                                                                        <input type="number"
                                                                               name="category_seo[<?=$index;?>][faq][s][]"
                                                                               class="form-control"
                                                                               value="<?=h($faq['sort'] ?? 500);?>">
                                                                    </div>

                                                                    <div class="form-group col-md-3">
                                                                        <label>Показ</label>
                                                                        <select name="category_seo[<?=$index;?>][faq][h][]"
                                                                                class="form-control">
                                                                            <option value="show" <?= (($faq['hide'] ?? 'show') === 'show' ? 'selected' : ''); ?>>
                                                                                Показывать
                                                                            </option>
                                                                            <option value="hide" <?= (($faq['hide'] ?? 'show') === 'hide' ? 'selected' : ''); ?>>
                                                                                Скрыть
                                                                            </option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="form-group col-md-3 d-flex align-items-end">
                                                                        <button type="button"
                                                                                class="btn btn-outline-danger btn-sm categoryFaqRemove">
                                                                            Удалить вопрос
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <button type="button"
                                                        class="btn btn-sm btn-primary categoryFaqAdd">
                                                    Добавить вопрос
                                                </button>

                                                <hr>

                                                <div class="form-group row file-upload">
                                                    <label class="col-sm-3 col-form-label">Изображение</label>
                                                    <div class="col-sm-9">

                                                        <input type="hidden"
                                                               name="category_seo[<?=$index;?>][img]"
                                                               value="<?=h($row['img'] ?? '');?>"
                                                               class="category-seo-img-input">

                                                        <div class="category-seo-img-preview mb-2">
                                                            <?php if(!empty($row['img'])): ?>
                                                                <img src="/images/filtrs/baseimg/<?=h($row['img']);?>"
                                                                     alt=""
                                                                     style="max-height:150px; max-width:260px; border:1px solid #ddd; padding:3px; background:#fff;">
                                                            <?php else: ?>
                                                                <span class="text-muted">Изображение не выбрано</span>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="btn btn-success categorySeoImageBtn">
                                                            Выбрать файл
                                                        </div>

                                                        <small class="form-text text-muted">
                                                            Изображение используется только для этой SEO-страницы категории.
                                                        </small>

                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                    <?php
                                        }
                                    ?>
                                    <div id="categorySeoDeletedWrap"></div>
                                    <div id="categorySeoWrap">
                                        <?php foreach($seoCategories as $row): ?>
                                            <?php renderCategorySeoBlock($seoIndex, $row, $attr, $availableSeoCategories, $canonicalCategories, false); ?>
                                            <?php $seoIndex++; ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <button type="button"
                                            class="btn btn-primary"
                                            id="categorySeoAdd"
                                            data-next-index="<?=$seoIndex;?>">
                                        Добавить SEO-категорию
                                    </button>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="box-footer">
                    <input type="hidden" name="id" value="<?=$attr->id;?>">
                    <button type="submit" class="btn btn-success">Сохранить</button>
                </div>
            </form>

        </div>
    </div>
</section>

<script>
$(function () {
    $('#related_sizes').select2({ width: '100%' });
    $('#technic_links').select2({ width: '100%' });
    $('.category-redirect-select').select2({ width: '100%' });
    $('.category-seo-category-select').select2({ width: '100%' });

    initCategorySeoEditors(document);

    $('form').on('submit', function () {
        if (typeof CKEDITOR !== 'undefined') {
            for (var instanceName in CKEDITOR.instances) {
                if (CKEDITOR.instances.hasOwnProperty(instanceName)) {
                    CKEDITOR.instances[instanceName].updateElement();
                }
            }
        }

        var seenCategories = {};
        var hasDuplicateCategory = false;
        var hasInvalidRedirect = false;

        $('.category-seo-row').each(function () {
            var row = this;
            var categoryInput = row.querySelector('[name$="[category_id]"]');
            var modeInput = row.querySelector('[name$="[mode]"]');
            var redirectInput = row.querySelector('[name$="[redirect_category_id]"]');

            var categoryId = categoryInput ? parseInt(categoryInput.value || '0', 10) : 0;
            var mode = modeInput ? String(modeInput.value || '') : '';
            var redirectCategoryId = redirectInput ? parseInt(redirectInput.value || '0', 10) : 0;

            if (categoryId > 0) {
                if (seenCategories[categoryId]) {
                    hasDuplicateCategory = true;
                    return false;
                }

                seenCategories[categoryId] = true;
            }

            if (mode === 'redirect' && (!redirectCategoryId || redirectCategoryId === categoryId)) {
                hasInvalidRedirect = true;
                return false;
            }
        });

        if (hasDuplicateCategory) {
            alert('Для одной категории и одного фильтра может быть только одна SEO-запись.');
            return false;
        }

        if (hasInvalidRedirect) {
            alert('Для режима Redirect выберите другую категорию редиректа.');
            return false;
        }
    });
});

function initCategorySeoEditors(scope) {
    scope = scope || document;

    if (typeof CKEDITOR === 'undefined') {
        return;
    }

    $(scope).find('[id^="editor1_"], [id^="editor2_"]').each(function () {
        if (this.id && !CKEDITOR.instances[this.id]) {
            CKEDITOR.replace(this.id);
        }
    });
}

(function(){
    var filterAlias = <?=json_encode((string)$attr->alias, JSON_UNESCAPED_UNICODE);?>;
    var categories = <?=json_encode($availableSeoCategories ?? [], JSON_UNESCAPED_UNICODE);?>;
    var redirectCategories = <?=json_encode(array_values(array_map(function($cat){
        return [
            'id' => (int)$cat->id,
            'name' => (string)$cat->name
        ];
    }, $canonicalCategories ?? [])), JSON_UNESCAPED_UNICODE);?>;

    function esc(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function categoryOptions() {
        var html = '<option value="0">Выберите категорию</option>';

        categories.forEach(function(cat){
            var aliasText = cat.alias ? ' — /category/' + cat.alias : '';

            html += '<option value="' + esc(cat.id) + '" data-alias="' + esc(cat.alias || '') + '" data-name="' + esc(cat.name || '') + '">' +
                esc(cat.name || '') + esc(aliasText) +
            '</option>';
        });

        return html;
    }

    function redirectOptions() {
        var html = '<option value="0">Не выбрано</option>';

        redirectCategories.forEach(function(cat){
            html += '<option value="' + esc(cat.id) + '">' + esc(cat.name || '') + '</option>';
        });

        return html;
    }

    function faqTemplate(index) {
        return `
            <div class="card mb-3 category-faq-row">
                <div class="card-body">
                    <div class="form-group">
                        <label>Вопрос</label>
                        <input type="text"
                               name="category_seo[${index}][faq][q][]"
                               class="form-control"
                               value="">
                    </div>

                    <div class="form-group">
                        <label>Ответ</label>
                        <textarea name="category_seo[${index}][faq][a][]"
                                  class="form-control"
                                  rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Порядок</label>
                            <input type="number"
                                   name="category_seo[${index}][faq][s][]"
                                   class="form-control"
                                   value="500">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Показ</label>
                            <select name="category_seo[${index}][faq][h][]"
                                    class="form-control">
                                <option value="show" selected>Показывать</option>
                                <option value="hide">Скрыть</option>
                            </select>
                        </div>

                        <div class="form-group col-md-3 d-flex align-items-end">
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm categoryFaqRemove">
                                Удалить вопрос
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function imageBlockTemplate(index) {
        return `
            <div class="form-group row file-upload">
                <label class="col-sm-3 col-form-label">Изображение</label>
                <div class="col-sm-9">

                    <input type="hidden"
                           name="category_seo[${index}][img]"
                           value=""
                           class="category-seo-img-input">

                    <div class="category-seo-img-preview mb-2">
                        <span class="text-muted">Изображение не выбрано</span>
                    </div>

                    <div class="btn btn-success categorySeoImageBtn">
                        Выбрать файл
                    </div>

                    <small class="form-text text-muted">
                        Изображение используется только для этой SEO-страницы категории.
                    </small>

                </div>
            </div>
        `;
    }

    function blockTemplate(index) {
        return `
            <div class="card mb-4 category-seo-row">
                <div class="card-header">
                    <div class="d-flex align-items-start" style="width:100%;">
                        <div style="flex:1 1 auto; min-width:0;">
                            <strong class="category-seo-title">Новая SEO-категория</strong>
                            <span class="badge badge-secondary ml-2">source: manual</span>
                            <div class="text-muted mt-1 category-seo-url"></div>
                        </div>

                        <div style="flex:0 0 auto; margin-left:15px;">
                            <button type="button" class="btn btn-outline-danger btn-sm categorySeoRemove">
                                Удалить SEO-страницу
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Категория</label>
                        <div class="col-sm-9">
                            <select name="category_seo[${index}][category_id]" class="form-control category-seo-category-select">
                                ${categoryOptions()}
                            </select>
                            <small class="form-text text-muted">
                                Выберите категорию, для которой нужно создать отдельную SEO-страницу фильтра.
                            </small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Активность страницы</label>
                        <div class="col-sm-9">
                            <input type="hidden" name="category_seo[${index}][is_active]" value="0">
                            <label>
                                <input type="checkbox" name="category_seo[${index}][is_active]" value="1" checked>
                                Страница активна
                            </label>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Режим</label>
                        <div class="col-sm-9">
                            <select name="category_seo[${index}][mode]" class="form-control">
                                <option value="landing" selected>Landing — показывать страницу</option>
                                <option value="redirect">Redirect — 301 на другую категорию</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Куда редиректить</label>
                        <div class="col-sm-9">
                            <select name="category_seo[${index}][redirect_category_id]" class="form-control category-redirect-select">
                                ${redirectOptions()}
                            </select>
                            <small class="form-text text-muted">
                                Используется только для режима Redirect.
                            </small>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">SEO H1</label>
                        <div class="col-sm-9">
                            <input type="text" name="category_seo[${index}][seo_h1]" class="form-control" value="">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Title</label>
                        <div class="col-sm-9">
                            <input type="text" name="category_seo[${index}][title]" class="form-control" value="">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Description</label>
                        <div class="col-sm-9">
                            <input type="text" name="category_seo[${index}][description]" class="form-control" value="">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Keywords</label>
                        <div class="col-sm-9">
                            <input type="text" name="category_seo[${index}][keywords]" class="form-control" value="">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Canonical</label>
                        <div class="col-sm-9">
                            <input type="text" name="category_seo[${index}][canonical_url]" class="form-control" value="">
                            <small class="form-text text-muted">
                                Оставьте пустым, чтобы canonical формировался автоматически.
                            </small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Robots</label>
                        <div class="col-sm-9">
                            <select name="category_seo[${index}][robots]" class="form-control">
                                <option value="" selected>Автоматически</option>
                                <option value="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">index, follow</option>
                                <option value="noindex, follow">noindex, follow</option>
                                <option value="noindex, nofollow">noindex, nofollow</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Текст сверху</label>
                        <div class="col-sm-9">
                            <textarea name="category_seo[${index}][top_content]"
                                      id="editor2_${index}"
                                      cols="80"
                                      rows="10"></textarea>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Основной текст</label>
                        <div class="col-sm-9">
                            <textarea name="category_seo[${index}][content]"
                                      id="editor1_${index}"
                                      cols="80"
                                      rows="10"></textarea>
                        </div>
                    </div>

                    <hr>

                    <h5>FAQ для этой категории</h5>

                    <div class="category-faq-wrap"></div>

                    <button type="button"
                            class="btn btn-sm btn-primary categoryFaqAdd">
                        Добавить вопрос
                    </button>

                    <hr>

                    ${imageBlockTemplate(index)}

                </div>
            </div>
        `;
    }

    document.addEventListener('click', function(e){
        if (e.target && e.target.id === 'categorySeoAdd') {
            var btn = e.target;
            var index = parseInt(btn.getAttribute('data-next-index'), 10) || 0;
            var wrap = document.getElementById('categorySeoWrap');

            wrap.insertAdjacentHTML('beforeend', blockTemplate(index));
            btn.setAttribute('data-next-index', index + 1);

            var newRow = wrap.querySelector('.category-seo-row:last-child');

            $(newRow).find('.category-seo-category-select').select2({ width: '100%' });
            $(newRow).find('.category-redirect-select').select2({ width: '100%' });

            initCategorySeoEditors(newRow);
        }

        if (e.target && e.target.classList.contains('categorySeoRemove')) {
            var row = e.target.closest('.category-seo-row');

            if (row) {
                var ruleId = parseInt(row.getAttribute('data-rule-id') || '0', 10);
                var deletedWrap = document.getElementById('categorySeoDeletedWrap');

                if (ruleId > 0 && deletedWrap) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'delete_category_seo[]';
                    input.value = String(ruleId);
                    deletedWrap.appendChild(input);
                }

                row.remove();
            }
        }

        if (e.target && e.target.classList.contains('categoryFaqAdd')) {
            var card = e.target.closest('.category-seo-row');

            if (!card) {
                return;
            }

            var wrap = card.querySelector('.category-faq-wrap');

            if (!wrap) {
                return;
            }

            var firstInput = card.querySelector('[name^="category_seo["]');

            if (!firstInput) {
                return;
            }

            var match = firstInput.name.match(/^category_seo\[(\d+)\]/);

            if (!match) {
                return;
            }

            var index = match[1];

            wrap.insertAdjacentHTML('beforeend', faqTemplate(index));
        }

        if (e.target && e.target.classList.contains('categoryFaqRemove')) {
            var faqRow = e.target.closest('.category-faq-row');

            if (faqRow) {
                faqRow.remove();
            }
        }

        if (e.target && e.target.classList.contains('categorySeoImageBtn')) {
            var activeImageRow = e.target.closest('.file-upload');

            if (!activeImageRow) {
                return;
            }

            var input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';

            input.addEventListener('change', function(){
                if (!input.files || !input.files[0]) {
                    return;
                }

                var formData = new FormData();
                formData.append('category_seo_img', input.files[0]);

                fetch('<?=ADMIN;?>/filtrs/add-category-seo-image', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response){
                    return response.json();
                })
                .then(function(res){
                    if (res.error) {
                        alert(res.error);
                        return;
                    }

                    if (!res.file) {
                        alert('Файл не загружен');
                        return;
                    }

                    var hidden = activeImageRow.querySelector('.category-seo-img-input');
                    var preview = activeImageRow.querySelector('.category-seo-img-preview');

                    if (hidden) {
                        hidden.value = res.file;
                    }

                    if (preview) {
                        preview.innerHTML =
                            '<img src="/images/filtrs/baseimg/' + esc(res.file) + '" ' +
                            'style="max-height:150px; max-width:260px; border:1px solid #ddd; padding:3px; background:#fff;">';
                    }
                })
                .catch(function(){
                    alert('Ошибка загрузки изображения');
                });
            });

            input.click();
        }
    });

    document.addEventListener('change', function(e){
        if (e.target && e.target.classList.contains('category-seo-category-select')) {
            var selected = e.target.options[e.target.selectedIndex];
            var alias = selected.getAttribute('data-alias') || '';
            var name = selected.getAttribute('data-name') || '';
            var row = e.target.closest('.category-seo-row');

            if (!row) {
                return;
            }

            var title = row.querySelector('.category-seo-title');
            var urlBox = row.querySelector('.category-seo-url');

            if (title) {
                title.textContent = name || 'Новая SEO-категория';
            }

            if (urlBox && alias) {
                var url = '/category/' + alias + '/' + filterAlias;

                urlBox.innerHTML =
                    '<a href="' + esc(url) + '" target="_blank">' +
                    esc(url) +
                    ' <i class="fas fa-external-link-alt ml-1"></i></a>';
            } else if (urlBox) {
                urlBox.innerHTML = '';
            }
        }
    });
})();
</script>
