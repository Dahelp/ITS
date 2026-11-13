<?php require (defined('APP')?APP:dirname(__DIR__,4)).'/views/itscenter/admin/partials/catalog-workflow-nav.php'; ?>
<div class="content-header"><div class="container-fluid"><h1>Вывод каталога</h1></div></div>
<section class="content"><div class="container-fluid">
  <?php if($error!==''): ?><div class="alert alert-danger" role="alert"><?=h($error)?></div><?php endif; ?>
  <div class="card"><div class="card-body">
    <form method="get" action="<?=ADMIN?>/catalog-display"><label for="display-category">Настройки для</label>
      <div class="d-flex mb-3"><select id="display-category" name="id" class="form-control mr-2"><option value="0" <?=$categoryId===0?'selected':''?>>Весь каталог — общая сетка</option>
        <?php foreach($categories as$row): ?><option value="<?=(int)$row['id']?>" <?=(int)$row['id']===$categoryId?'selected':''?>><?=h($row['name'])?> · ID <?=(int)$row['id']?></option><?php endforeach; ?>
      </select><button class="btn btn-secondary" type="submit">Открыть</button></div>
    </form>
    <form method="post" action="<?=ADMIN?>/catalog-display?id=<?=$categoryId?>">
      <input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="category_id" value="<?=$categoryId?>"><input type="hidden" name="version" value="<?=$settings['version']?>">
      <div class="form-group"><label for="display-columns">Карточек в ряд на широком экране</label><select id="display-columns" name="columns" class="form-control" style="max-width:400px">
        <?php if($categoryId): ?><option value="0" <?=$settings['own_columns']===0?'selected':''?>>Общая настройка каталога</option><?php endif; ?>
        <?php foreach([3,4]as$count): ?><option value="<?=$count?>" <?=($categoryId?$settings['own_columns']:$settings['columns'])===$count?'selected':''?>><?=$count?> карточки</option><?php endforeach; ?>
      </select><small class="form-text text-muted">На планшетах и телефонах число колонок уменьшается автоматически. Настройка относится к списку товаров категории, включая подгрузку и страницы пагинации.</small></div>
      <?php if($categoryId): ?>
        <div class="form-group"><label for="display-mode">Группы фильтров в этой категории</label><select id="display-mode" name="group_mode" class="form-control" style="max-width:500px"><option value="auto" <?=$settings['groups']===null?'selected':''?>>Автоматически — текущие связи групп с категориями</option><option value="custom" <?=$settings['groups']!==null?'selected':''?>>Свой список и порядок ниже</option></select></div>
        <p>Отметьте нужные группы и задайте порядок: меньшее число выводится выше. Настройка действует только для выбранной категории. Группа появится на сайте, если у товаров есть её значения.</p>
        <p class="text-muted">Пустой свой список скрывает группы характеристик. Фильтр наличия остаётся доступным. Характеристики товаров и SEO-страницы сохраняются.</p>
        <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Показывать</th><th>Группа</th><th>Порядок</th></tr></thead><tbody>
        <?php foreach($groups as$i=>$group): $gid=(int)$group['id'];$position=array_search($gid,$settings['groups']??$automaticGroups,true); ?>
          <tr><td><input type="checkbox" name="groups[]" value="<?=$gid?>" aria-label="Показывать <?=h($group['title'])?>" <?=$position!==false?'checked':''?>></td><td><?=h($group['title'])?></td><td><input type="number" name="positions[<?=$gid?>]" value="<?=$position===false?($i+1)*10:($position+1)*10?>" min="0" max="99999" class="form-control" aria-label="Порядок <?=h($group['title'])?>" style="max-width:120px"></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php else: ?><input type="hidden" name="group_mode" value="auto"><p>Общая настройка применяется к категориям, где не задано своё количество карточек. Состав и порядок фильтров настраиваются после выбора категории.</p><?php endif; ?>
      <button class="btn btn-primary" type="submit">Сохранить настройки</button>
      <?php if($categoryId): ?><a href="<?=ADMIN?>/category/edit?id=<?=$categoryId?>" class="btn btn-default">Редактировать категорию</a><?php endif; ?>
    </form>
  </div></div>
</div></section>
