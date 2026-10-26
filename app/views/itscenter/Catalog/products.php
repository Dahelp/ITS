<div class="prdt"><div class="container">
 <nav aria-label="Навигация"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/">Главная</a></li><li class="breadcrumb-item"><a href="/catalog">Каталог</a></li><li class="breadcrumb-item active" aria-current="page">Все товары</li></ol></nav>
 <h1>Все товары</h1>
 <form method="get" class="catalog-category-picker">
   <label for="listing-category">Категория</label><select id="listing-category" name="category" class="form-control" style="width:auto"><option value="">Все категории</option><?php foreach($categories as$item):?><option value="<?=(int)$item['id']?>" <?=(int)($_GET['category']??0)===(int)$item['id']?'selected':''?>><?=h($item['name'])?></option><?php endforeach;?></select>
   <?php foreach(['status','sort','perpage','in_stock']as$key):if(isset($_GET[$key])&&is_scalar($_GET[$key])):?><input type="hidden" name="<?=$key?>" value="<?=h((string)$_GET[$key])?>"><?php endif;endforeach;?><button class="btn btn-primary">Выбрать</button>
 </form>
 <?php require APP.'/views/itscenter/partials/catalog-controls.php';?>
 <?php $curr=\ishop\App::$app->getProperty('currency');?>
 <div class="row gx-3 gy-3 product-one">
 <?php foreach($products as$product):?><div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 mb-3"><?php new \app\widgets\product\Product($product,$curr,'product_tpl.php',$productWidgetContext);?></div><?php endforeach;?>
 </div>
 <?php if(!$products):?><p class="alert alert-info">По выбранным условиям товаров нет. Измените категорию или отбор.</p><?php endif;?>
 <div class="d-flex justify-content-center mb-4"><?=$pagination?></div>
</div></div>
