<?php require (defined('APP')?APP:dirname(__DIR__,4)).'/views/itscenter/admin/partials/catalog-workflow-nav.php'; ?>
<div class="content-header"><h1>Единый каталог товаров</h1></div>
<section class="content"><p><a class="btn btn-outline-primary" href="<?=ADMIN?>/catalog-names">Названия каталога, H1, Title и YML</a></p>
<p><a href="<?=ADMIN?>/catalog-properties/migrations">Журнал переноса</a></p>
 <p><a class="btn btn-primary" href="<?=ADMIN?>/catalog-properties/quality">Заполнение характеристик</a> <a class="btn btn-outline-primary" href="<?=ADMIN?>/catalog-properties/reviews">Замечания и отложенные проверки</a> <a class="btn btn-outline-secondary" href="<?=ADMIN?>/catalog-properties/dictionaries">Справочники и правила</a></p>
 <div class="alert alert-info">Подтверждённые характеристики с источником подключены к карточкам и YML. Для исторических и отложенных сведений сохраняются прежние значения. YML получает изменения при формировании задания; товары без остатка также доступны для заполнения. <a href="<?=ADMIN?>/cron">Задания обновления YML</a></div>
 <?php if(!$installed): ?><div class="alert alert-warning">Пилотная схема ещё не установлена. Установку выполняет разработчик отдельной командой; открытие страницы ничего не создаёт.</div><?php endif ?>
 <form method="get"><label for="catalog-property-search">Артикул или название</label><div class="input-group mb-3"><input class="form-control" id="catalog-property-search" name="q" maxlength="100" value="<?=h($search)?>"><div class="input-group-append"><button class="btn btn-primary">Найти</button></div></div></form>
 <div class="card"><div class="card-body table-responsive"><table class="table table-bordered"><thead><tr><th>Артикул</th><th>Товар</th><th>Видимость</th><th>Действие</th></tr></thead><tbody>
 <?php foreach($products as$p): ?><tr><td><?=h($p['sku'])?></td><td><?=h($p['name'])?></td><td><?=h($p['hide'])?></td><td><?php if($installed): ?><a href="<?=ADMIN?>/catalog-workflow/product?id=<?=(int)$p['id']?>">Рабочая карточка</a> · <a href="<?=ADMIN?>/catalog-properties/edit?id=<?=(int)$p['id']?>">Характеристики и размеры</a><?php endif ?></td></tr><?php endforeach ?>
 </tbody></table><p class="text-muted">Не более 50 результатов. Для остальных товаров уточните артикул или название.</p></div></div>
</section>
