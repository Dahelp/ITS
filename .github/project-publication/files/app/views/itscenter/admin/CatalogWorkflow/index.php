<?php require APP.'/views/itscenter/admin/partials/catalog-workflow-nav.php';$s=$overview['summary']; ?>
<div class="content-header"><h1>Рабочий центр каталога</h1><p>Обновлено: <?=h($overview['generated_at'])?></p></div>
<section class="content">
<div class="card"><div class="card-header"><h2 class="card-title">Найти товар и продолжить работу</h2></div><div class="card-body"><form method="get"><label for="workflow-search">Артикул или название</label><div class="input-group"><input class="form-control" id="workflow-search" name="q" value="<?=h($q)?>" maxlength="100"><div class="input-group-append"><button class="btn btn-primary">Найти</button></div></div></form>
<?php if($q!==''):?><p class="mt-2">Найдено: <?=$matched?>. Показано: <?=count($products)?>.</p><ul><?php foreach($products as$row):?><li><a href="<?=ADMIN?>/catalog-workflow/product?id=<?=$row['id']?>"><?=h($row['sku']?:'#'.$row['id'])?> — <?=h($row['name'])?></a></li><?php endforeach ?></ul><?php endif ?></div></div>
<p>Сводка включает скрытые товары и товары без остатка. Группы характеристик отражают основное замечание товара; SEO-замечания и отложенные проверки могут пересекаться.</p>
<div class="row">
<?php foreach([
 ['Всего товаров',$s['total'],'/catalog-properties/quality'],
 ['Профиль сохранён, включая черновики',$s['saved'],'/catalog-properties/quality?source=saved'],
 ['Ещё в прежнем формате',$s['legacy_proposal'],'/catalog-properties/quality?source=legacy_proposal'],
 ['Противоречия и ошибки',$s['statuses']['conflict']??0,'/catalog-properties/quality?status=conflict'],
 ['Обязательные пропуски',$s['statuses']['missing']??0,'/catalog-properties/quality?status=missing'],
 ['Уточнить тип изделия',$s['statuses']['classification']??0,'/catalog-properties/quality?status=classification'],
 ['SEO-поля с замечаниями',$s['effective_seo_gaps'],'/catalog-properties/quality?seo=effective'],
 ['Отложенные проверки',$overview['reviews']['deferred']??0,'/catalog-properties/reviews?status=deferred'],
 ['Посадочные без подходящих товаров',count($overview['landings']['issues']),'/catalog-workflow/landings'],
]as[$label,$count,$url]):?><div class="col-xl-4 col-md-6"><div class="card"><div class="card-body"><strong class="h3 d-block"><?=$count?></strong><?=h($label)?><br><a href="<?=ADMIN.h($url)?>">Открыть список</a></div></div></div><?php endforeach ?>
</div>
<div class="card"><div class="card-header"><h2 class="card-title">Порядок работы с товаром</h2></div><div class="card-body"><ol><li>Найдите товар и откройте рабочую карточку.</li><li>Проверьте основные поля, фото и SEO; затем характеристики, тип изделия и размеры.</li><li>Сверьте источники. Сохранённый черновик не означает подтверждение технических данных.</li><li>Проверьте итоговые названия и товар на сайте, затем предложение в предпросмотре YML.</li><li>Перепроверьте качество. Результат проверки спорных данных сохраните в «Замечаниях» с источником.</li></ol><p class="mb-0">Изменения выполняются в профильных редакторах с их проверками доступа, версий форм и историей. Этот центр не подтверждает данные и не запускает выгрузку автоматически. Индексация и позиция в поиске не гарантируются заполнением полей.</p></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Опубликованные YML-файлы</h2></div><div class="card-body table-responsive"><p><a href="<?=ADMIN?>/plagins/yandex-tovars">Предпросмотр и причины исключения</a> · <a href="<?=ADMIN?>/catalog-workflow/updates">Контроль обновлений и запусков</a> · <a href="<?=ADMIN?>/cron">Задания выгрузки</a></p><table class="table table-bordered"><thead><tr><th>Файл</th><th>Локальное состояние</th><th>Предложений</th><th>Изменён</th></tr></thead><tbody><?php foreach($feeds as$f):?><tr><td><?=h($f['filename'])?></td><td><?=h($f['status'])?></td><td><?=h($f['offers']??'—')?></td><td><?=h($f['date']??'—')?></td></tr><?php endforeach ?></tbody></table><small>Это состояние локального XML, не статус приёма Яндексом.</small></div></div>
</section>
