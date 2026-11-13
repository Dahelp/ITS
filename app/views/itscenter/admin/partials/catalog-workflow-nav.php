<?php if(($_SESSION['user']['role']??'')==='admin'): ?>
<nav class="px-3 pt-3 pb-2" aria-label="Работа с каталогом">
<?php foreach(['/catalog-workflow'=>'Рабочий центр','/catalog-properties'=>'Характеристики товаров','/catalog-properties/quality'=>'Качество','/catalog-properties/reviews'=>'Замечания','/catalog-properties/dictionaries'=>'Справочники','/catalog-facet-bindings'=>'Связи фильтров','/catalog-display'=>'Вывод категорий','/catalog-names'=>'Названия','/plagins/yandex-tovars'=>'Яндекс Товары','/catalog-workflow/landings'=>'SEO-посадочные','/catalog-workflow/updates'=>'Обновления и YML','/catalog-workflow/history'=>'История']as$workflowRoute=>$workflowLabel):?>
<a class="btn btn-sm btn-outline-secondary mb-2 mr-1" href="<?=ADMIN.h($workflowRoute)?>" <?=(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)===parse_url(ADMIN.$workflowRoute,PHP_URL_PATH))?'aria-current="page"':''?>><?=h($workflowLabel)?></a>
<?php endforeach ?>
<?php $workflowProductId=is_array($product??null)?(int)($product['id']??0):(is_object($product??null)?(int)($product->id??0):0);if($workflowProductId>0):?><a class="btn btn-sm btn-primary mb-2" href="<?=ADMIN?>/catalog-workflow/product?id=<?=$workflowProductId?>">Рабочая карточка этого товара</a><?php endif ?>
</nav>
<?php endif ?>
