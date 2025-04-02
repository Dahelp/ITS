<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Товары</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/product">Список товаров</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/product?category_id=<?=$product->category_id?>"><?php $cat = \R::findOne('category', 'id = ?', [$product->category_id]); echo "".$cat['name'].""; ?></a></li>
              <li class="breadcrumb-item active">Редактирование</li>
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
                <a target="_blank" href="/product/<?=$product["alias"]?>" class="btn btn-success"><i class="fad fa-eye"></i> Просмотр на сайте</a>
				<a href="<?=ADMIN;?>/product/category?id=<?=$product->category_id?>" class="btn btn-primary"><i class="fal fa-reply-all"></i></a>
				<a target="_blank" href="<?=ADMIN;?>/review/product?id=<?=$product["id"]?>" class="btn btn-secondary"><i class="fas fa-star-half-alt"></i> Всего отзывов: <?=$count_review?></a>
				<a target="_blank" href="<?=ADMIN;?>/order/stat_product?id=<?=$product["id"]?>" class="btn btn-purple"><i class="fad fa-cart-plus"></i> Покупок было: <?=$count_order?></a>
				<a target="_blank" href="<?=ADMIN;?>/bookmarks/product?id=<?=$product["id"]?>" class="btn btn-cyan"><i class="fad fa-bookmark"></i> В закладках: <?=$count_bookmarks?></a>
				<a target="_blank" href="<?=ADMIN;?>/product/copy?id=<?=$product["id"]?>" class="btn btn-success"><i class="fas fa-copy"></i> Скопировать</a>

				<?php 
					$nextprod = \R::findOne('product', 'id != ? AND id > ? AND category_id = ? ORDER BY id ASC LIMIT 1', [$product->id, $product->id, $product->category_id]);
					if($nextprod) {
				?>
				<a href="<?=ADMIN;?>/product/edit?id=<?=$nextprod->id?>" class="btn float-sm-right btn-primary">Следующий товар <i class="fad fa-angle-double-right"></i></a>
				<?php } ?>
				<?php 
					$backprod = \R::findOne('product', 'id != ? AND id < ? AND category_id = ? ORDER BY id DESC LIMIT 1', [$product->id, $product->id, $product->category_id]);
					if($backprod) {
				?>
				<a href="<?=ADMIN;?>/product/edit?id=<?=$backprod->id?>" class="btn float-sm-right mr-2 btn-primary"><i class="fad fa-angle-double-left"></i> Предыдущий товар</a>
				<?php } ?>
				
            </div>			
            <form action="<?=ADMIN;?>/product/edit" method="post" data-toggle="validator">
            <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0" <?php if($product->hide == "hide") { ?>style="background-color:#fed8d8"<?php } ?><?php if($product->hide == "lock") { ?>style="background-color:#d7d6d6"<?php } ?>>
                <h3 class="card-title p-3">Редактирование товара <?=$product->name;?></h3>
                <ul class="nav nav-pills ml-auto p-2">
					<li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>
					<li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">Атрибуты</a></li>
					<li class="nav-item"><a class="nav-link" href="#tab_3" data-toggle="tab">SEO</a></li>
					<li class="nav-item"><a class="nav-link" href="#tab_4" data-toggle="tab">Фильтры</a></li>
					<li class="nav-item"><a class="nav-link" href="#tab_5" data-toggle="tab">Модификации</a></li>
                    <li class="nav-item"><a class="nav-link" href="#tab_6" data-toggle="tab">Доп. параметры</a></li>				  
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane active" id="tab_1">
                    <div class="box-body">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="name">Наименование товара</label>
								<div class="col-sm-9">
									<input type="text" name="name" class="form-control" id="name" placeholder="Наименование товара" value="<?=h($product->name);?>" required>
								</div>                                        
							</div>
							<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="category_id">Родительская категория</label>
                            <div class="col-sm-9">
                            <?php new \app\widgets\menu\Menu([
                                'tpl' => WWW . '/menu/select.php',
                                'container' => 'select',
                                'cache' => 0,
                                'cacheKey' => 'admin_select',
                                'class' => 'form-control category_id',
                                'attrs' => [
                                    'name' => 'category_id',
                                    'id' => 'category_id',
									'onchange' => 'document.form1.parametr = !this.selectedIndex',
                                ],
                            ]) ?>
                        </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="article">Артикул (SKU)</label>
							<div class="col-sm-9">
								<input type="text" name="article" class="form-control" id="article" placeholder="Артикул товара" value="<?=h($product->article);?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="price">Цена</label>
							<div class="col-sm-9">
								<input type="text" name="price" class="form-control price" id="price" placeholder="Цена" pattern="^[0-9.]{1,}$" value="<?=$product->price;?>" required data-error="Допускаются цифры и десятичная точка">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="price_rrs">Цена РРЦ</label>
							<div class="col-sm-9">
								<input type="text" name="price_rrs" class="form-control price_rrs" id="price_rrs" placeholder="Цена РРЦ" pattern="^[0-9.]{1,}$" value="<?=$product->price_rrs;?>" data-error="Допускаются цифры и десятичная точка">
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="old_price">Цена по акции</label>
							<div class="col-sm-9">
								<input type="text" name="old_price" class="form-control" id="old_price" placeholder="Цена по акции" pattern="^[0-9.]{1,}$" value="<?=$product->old_price;?>" data-error="Допускаются цифры и десятичная точка" disabled>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="opt_price">Цена оптовая</label>
							<div class="col-sm-9">
								<input type="text" name="opt_price" class="form-control" id="opt_price" placeholder="Цена оптовая" pattern="^[0-9.]{1,}$" value="<?=$product->opt_price;?>" data-error="Допускаются цифры и десятичная точка">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="model">Модель</label>
							<div class="col-sm-9">
								<input type="text" name="model" class="form-control" id="model" placeholder="Модель товара" value="<?=$product->model;?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="brand_id">Производитель</label>
							<div class="col-sm-9">								
								<select name="brand_id" class="form-control" style="width: 100%;">
									<?php $b = \R::findOne('brand', 'id = ?', [$product->brand_id]); ?>
									<option value= "<?=$b->id?>" selected="selected"><?=$b->name?></option>
									<?php $brands = \R::getAll('SELECT id, name FROM brand WHERE id != ?', [$b->id]);
									$i=1;	foreach($brands as $brand_item => $item): ?>									
									<option value= "<?=$item["id"]?>"><?=$item["name"]?></option>
								<?php $i++; endforeach; ?>
								</select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="content">Подробное описание</label>
							<div class="col-sm-9">                        
								<textarea name="content" id="editor1" cols="80" rows="10"><?=$product->content;?></textarea>
							</div>
						</div>                        
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label">Отметить как</label>
							<div class="col-sm-9">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="customCheckbox1" name="new_product" <?=$product->new_product ? ' checked' : null;?>>
                                    <label style="font-weight:400" for="customCheckbox1" class="custom-control-label">Новинка</label>
                                </div>
								<div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="customCheckbox2" name="hit" <?=$product->hit ? ' checked' : null;?>>
                                    <label style="font-weight:400" for="customCheckbox2" class="custom-control-label">Лидер продаж</label>
                                </div>
								<div class="custom-control custom-checkbox">
                                    <input class="custom-control-input rrs-click" type="checkbox" id="customCheckbox3" name="sale" <?=$product->sale ? ' checked' : null;?>>
                                    <label style="font-weight:400" for="customCheckbox3" class="custom-control-label">Распродажа (акция)</label>
                                </div>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">								
								<option value="show" <?php if($product->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value="hide" <?php if($product->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
                    			<option value="lock" <?php if($product->hide == "lock") { echo "selected=\"selected\""; } ?>>Закрыт от индексации</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label">Статус наличия</label>
							<div class="col-sm-9">
							<select name="stock_status_id" class="form-control" style="width: 100%;">
								<option value="1" <?php if($product->stock_status_id == 1) { echo "selected=\"selected\""; } ?>>В наличии</option>
                    			<option value="0" <?php if($product->stock_status_id == 0) { echo "selected=\"selected\""; } ?>>Нет в наличии</option>
                    			<option value="2" <?php if($product->stock_status_id == 2) { echo "selected=\"selected\""; } ?>>Под заказ</option>
								<option value="3" <?php if($product->stock_status_id == 3) { echo "selected=\"selected\""; } ?>>Ожидается поступление</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="quantity">Количество</label>
							<div class="col-sm-9">
								<input type="text" name="quantity" class="form-control" id="quantity" placeholder="Количество" value="<?=$product->quantity;?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="unit">Единица измерения</label>
							<div class="col-sm-9">
							<select name="unit" class="form-control" style="width: 100%;">
								<option value= "шт" <?php if($product->unit == "шт") { echo "selected=\"selected\""; } ?>>Штука</option>
								<option value= "упак" <?php if($product->unit == "упак") { echo "selected=\"selected\""; } ?>>Упаковка</option>
                    			<option value= "компл" <?php if($product->unit == "компл") { echo "selected=\"selected\""; } ?>>Комплект</option>
                    			<option value= "кг" <?php if($product->unit == "кг") { echo "selected=\"selected\""; } ?>>Килограмм</option>
								<option value= "л" <?php if($product->unit == "л") { echo "selected=\"selected\""; } ?>>Литр</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="weight">Вес, кг</label>
							<div class="col-sm-9">
								<input type="text" name="weight" class="form-control" id="weight" placeholder="Вес товара" value="<?=$product->weight;?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="volume">Объём, м3</label>
							<div class="col-sm-9">
								<input type="text" name="volume" class="form-control" id="volume" placeholder="Объём товара" value="<?=$product->volume;?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
							<div class="col-sm-9">
                                        <div id="single" class="btn btn-success" data-url="product/add-image" data-name="single" data-razdel="product">Выбрать файл</div>
                                        <p><small>Рекомендуемые размеры: 600х450</small></p>
                                        <div class="single">
                                            <img src="/images/product/baseimg/<?=$product->img;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$product->id;?>" data-src="<?=$product->img;?>" data-razdel="product" class="del-base">
                                        </div>
                                    
                                    <div class="overlay">
                                        <i class="fa fa-refresh fa-spin"></i>
                                    </div>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img_gallery">Картинки галереи</label>
							<div class="col-sm-9">
                                        <div id="multi" class="btn btn-success" data-url="product/add-image" data-name="multi" data-razdel="product">Выбрать файл</div>
                                        <p><small>Рекомендуемые размеры: 1000х750</small></p>
                                        <div class="multi">
                                            <?php if(!empty($gallery)): ?>
                                                <?php foreach($gallery as $item): ?>
                                                    <img src="/images/product/gallery/<?=$item;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$product->id;?>" data-src="<?=$item;?>" data-razdel="product" class="del-item">
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    
                                    <div class="overlay">
                                        <i class="fa fa-refresh fa-spin"></i>
                                    </div>
                                </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img_unload">Изображение для выгрузки</label>
							<div class="col-sm-9">
                                        <div id="unload" class="btn btn-success" data-url="product/add-image" data-name="unload" data-razdel="product">Выбрать файл</div>
                                        <p><small>Рекомендуемые размеры: 600х600</small></p>
                                        <div class="unload">
                                            <img src="/images/product/unload/<?=$product->unload_img;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$product->id;?>" data-src="<?=$product->unload_img;?>" data-razdel="product" class="del-unload">
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
						<table id="table_container">
							<tr>
								<td style="width:40%;padding: 0 0 0 10px;"><strong>Атрибут</strong></td><td style="width:60%;padding: 0 0 0 10px;"><strong>Текст</strong></td><td></td>
							</tr>
							<?php $k = 1; foreach($att_product as $att_item): ?>
								
							<tr id="tr_image_<?=$k?>" style="line-height: 20px;">			
								<td id="td_name_<?=$k?>" style="padding: 5px 10px; width: 30%;">
									<select style="padding: 5px 10px; width: 80%;" name="product_attribute[<?=$k?>][attribute_id]" class="form-control">
										<option value="<?=$att_item["attribute_id"]?>" /><?=$att_item["attribute_name"]?></option>
									</select>
								</td>
								<td id="td_text_<?=$k?>" style="padding: 5px 10px; width: 40%;">
									<input style="padding: 5px 10px; width: 80%;" id="attribute_text_<?=$k?>" name="product_attribute[<?=$k?>][text]" class="form-control" value="<?=$att_item["attribute_text"]?>">									
								</td>
								<td style="padding: 5px 10px; width: 15%;">
									<span id="progress_<?=$k?>"><a href="javascript:void(0)" onclick="$('#tr_image_<?=$k?>').remove();" class="btn btn-default float-right">Удалить</a></span>
								</td>
							</tr>
							<?php $k++; endforeach; ?>
						</table>
						<br/>
						<div style="float:right;padding:0 30px 0 0"><input type="button" value="Добавить атрибут" class="btn btn-success" onclick="return add_new_attribute();"></div>
					</div>
                  </div>
                  <!-- /.tab-pane -->
                  <div class="tab-pane" id="tab_3">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="alias">Ссылка страницы</label>
							<div class="col-sm-9">
                                <input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически" value="<?=$product->alias;?>">
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
                            <div class="col-sm-9">
								<input type="text" name="title" class="form-control" id="title" placeholder="Если пусто, то используется Название" value="<?=$product->title;?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
                            <div class="col-sm-9">
								<input type="text" name="description" class="form-control" id="description" placeholder="SEO описание (130-150 символов)" value="<?=$product->description;?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
                            <div class="col-sm-9">
								<input type="text" name="keywords" class="form-control" id="keywords" placeholder="Заполняются через запятую (4-6 фраз)" value="<?=$product->keywords;?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name_tag">Теги товара</label>
                            <div class="col-sm-9">
								<input type="text" name="name_tag" class="form-control" id="name_tag" placeholder="Заполняются через запятую (1-5 фраз)" value="<?php foreach($tags_product as $tag_item):
            					    $name_tg .="".$tag_item["name"].", ";									
								 endforeach; echo $name_tg = rtrim($name_tg, ', ');?>">
							</div>
                        </div>
					</div>
                  </div>
                  <!-- /.tab-pane -->
				  <div class="tab-pane" id="tab_4">
                    <div class="box-body">
						<div class="box-body filters">
							<?php $i = 1; foreach($groups as $group_id => $group_item): ?>
						<div class="form-group row" id="filter">
							<label class="col-sm-3 col-form-label"><?= $group_item ?></label>
							<div class="col-sm-9">
							<?php if(!empty($attrs[$group_id])): ?>
							<select class="form-control" name="attrs[<?=$group_id ?>]">            
									<option value=""> Выбрать фильтр</option>
									
										
									<?php foreach($attrs[$group_id] as $attr_id => $value): ?>
										<?php if(!empty($filter) && in_array($attr_id, $filter)){ $selected = 'selected="selected"'; }else{ $selected = null; } ?>					

										<option value="<?= $attr_id ?>" <?=$selected?>> <?= $value ?></option>
									<?php endforeach; ?>                
									
								</select>
							<?php endif; ?>
								</div>
						</div>
						<?php $i++; endforeach; ?>
						</div>							
					</div>
                  </div>
                  <!-- /.tab-pane -->
				  <div class="tab-pane" id="tab_5">
                    <div class="box-body">
						<table id="table_container_mods" style="width: 100%;">
							<tr>
								<td style="width:50%;padding: 0 0 0 10px;"><strong>Модификация</strong></td><td style="width:15%;padding: 0 0 0 10px;"><strong>Артикул</strong></td><td style="width:15%;padding: 0 0 0 10px;"><strong>Цена</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Количество</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Ед.из.</strong></td><td style="width:10%;padding: 0 0 0 10px;"></td>
							</tr>
							<?php $j = 0; foreach($mods as $mod): ?>
								
							<tr id="tr_mods_<?=$j?>" style="line-height: 20px;">			
								<td id="td_name_modification_<?=$j?>" style="padding: 5px 10px; width: 50%;">									
									<input style="padding: 5px 10px;" id="name_modification_<?=$j?>" name="product_mods[<?=$j?>][name_modification]" class="form-control" value="<?=$mod["name_modification"]?>">
								</td>
								<td id="td_article_<?=$j?>" style="padding: 5px 10px; width: 15%;">
									<input style="padding: 5px 10px;" id="article_<?=$j?>" name="product_mods[<?=$j?>][article]" class="form-control" value="<?=$mod["article"]?>">									
								</td>
								<td id="td_price_<?=$j?>" style="padding: 5px 10px; width: 15%;">
									<input style="padding: 5px 10px;" id="price_<?=$j?>" name="product_mods[<?=$j?>][price]" class="form-control" value="<?=$mod["price"]?>">									
								</td>
								<td id="td_quantity_<?=$j?>" style="padding: 5px 10px; width: 10%;">
									<input style="padding: 5px 10px;" id="quantity_<?=$j?>" name="product_mods[<?=$j?>][quantity]" class="form-control" value="<?=$mod["quantity"]?>">									
								</td>
								<td id="td_unit_<?=$j?>" style="padding: 5px 10px; width: 10%;">
									<select style="padding: 5px 10px;" id="unit_<?=$j?>" name="product_mods[<?=$j?>][unit]" class="form-control">
										<option value= "шт" <?php if($mod["unit"] == "шт") { echo "selected=\"selected\""; } ?>>Штука</option>
										<option value= "упак" <?php if($mod["unit"] == "упак") { echo "selected=\"selected\""; } ?>>Упаковка</option>
										<option value= "компл" <?php if($mod["unit"] == "компл") { echo "selected=\"selected\""; } ?>>Комплект</option>
										<option value= "кг" <?php if($mod["unit"] == "кг") { echo "selected=\"selected\""; } ?>>Килограмм</option>
										<option value= "л" <?php if($mod["unit"] == "л") { echo "selected=\"selected\""; } ?>>Литр</option>
									</select>
								</td>
								<td style="padding: 5px 10px; width: 10%;">
									<span id="progress_mods_<?=$j?>"><a href="/admin/product/edit?id=<?=$product->id;?>#" onclick="$('#tr_mods_<?=$j?>').remove();" class="btn btn-default float-right">Удалить</a></span>
								</td>
							</tr>
							<?php $j++; endforeach; ?>
						</table>
						<br/>
						<div style="float:right;padding:0 30px 0 0"><input type="button" value="Добавить модификацию" class="btn btn-success" onclick="return add_new_modification();"></div>
					</div>
                  </div>
                  <!-- /.tab-pane -->
				  <div class="tab-pane" id="tab_6">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="related">Связанные товары</label>
                            <div class="col-sm-9">
								<select name="related[]" class="form-control select2" id="related" multiple>
									<?php if(!empty($related_product)): ?>
										<?php foreach($related_product as $item): ?>
											<option value="<?=$item['related_id'];?>" selected><?=$item['name'];?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="similar">Похожие товары</label>
                            <div class="col-sm-9">
								<select name="similar[]" class="form-control select2" id="similar" multiple>
									<?php if(!empty($similar_product)): ?>
										<?php foreach($similar_product as $sitem): ?>
											<option value="<?=$sitem['similar_id'];?>" selected><?=$sitem['name'];?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="service">Услуги</label>
                            <div class="col-sm-9">
								<select name="service[]" class="form-control select2" id="service" multiple>
									<?php if(!empty($service_product)): ?>
										<?php foreach($service_product as $srcp): ?>
											<option value="<?=$srcp['service_id'];?>" selected><?=$srcp['name'];?></option>
										<?php endforeach; ?>									
									<?php endif; ?>
								</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url_video">Ссылка на видео</label>
                            <div class="col-sm-9">
								<input type="text" name="url_video" class="form-control" id="url_video" placeholder="Укажите ссылку на видео" value="<?=$product->url_video;?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="note">Примечание к товару</label>
                            <div class="col-sm-9">
								<input type="text" name="note" class="form-control" id="note" placeholder="Замечания по товару" value="<?=$product->note;?>">
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
                    <input type="hidden" name="id" value="<?=$product->id;?>">
                    <button type="submit" class="btn btn-success">Сохранить</button>
            </div>
            <!-- ./card -->
			</form>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->		
</section>
<!-- /.content -->

<script type="text/javascript">
var total = <?=count($att_product)?>;
function add_new_attribute(){
    total++;
    $('<tr>')
    .attr('id','tr_image_'+total)
    .css({lineHeight:'20px'})
    .append (
        $('<td>')
        .attr('id','td_name_'+total)
        .css({padding:'5px 10px',width:'30%'})
        .append(
            $('<select>')
            .css({padding:'5px 10px',width:'80%'})
            .attr('name','product_attribute['+total+'][attribute_id]')
		    .attr('class','form-control')
		   
		.append(
		    $('<option>')               
			.attr('value','')
			.append(
			    document.createTextNode("Выберите атрибут")
			)
        )  
<?php $attributes = \R::getAll('SELECT id, attribute_name FROM attribute WHERE attribute_group_id = ?', [0]);
	  
	$i = 0; foreach($attributes as $attribut):
	  
	    $attribute_name = $attribut["attribute_name"];
	    $attribute_id = $attribut["id"];
?>
		   .append(
		       $('<option>')               
			   .attr('value','<?=$attribute_id?>')			   
			   .css({color:'green'})
			   .attr('disabled','')
			   .append(
			      document.createTextNode("<?=$attribute_name?>")
				)
           )
			<?php $attributes_parent = \R::getAll('SELECT id, attribute_name FROM attribute WHERE attribute_group_id = ?', [$attribute_id]);
	  
			$j = 0; foreach($attributes_parent as $attr_pr):
	  
			$attribute_name2 = $attr_pr["attribute_name"];
			$attribute_id2 = $attr_pr["id"];
?>
			.append(
		       $('<option>')               
			   .attr('value','<?=$attribute_id2?>')
			   .append(
			      document.createTextNode("-- <?=$attribute_name2?>")
				)
           )	
		   <?php $j++; endforeach; ?>
<?php $i++; endforeach; ?>
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_text_'+total)
       .css({padding:'5px 10px',width:'40%'})
       .append(
           $('<input>')
           .css({padding:'5px 10px',width:'80%'})
           .attr('id','attribute_text_'+total)
           .attr('name','product_attribute['+total+'][text]')
		   .attr('class','form-control')
       )                             
                              
    )
    .append(
        $('<td>')
        .css({padding:'5px 10px',width:'15%'})
        .append(
           $('<span id="progress_'+total+'"><a href="javascript:void(0)" onclick="$(\'#tr_image_'+total+'\').remove();" class="btn btn-default float-right">Удалить</a></span>')
         )
     )
     .appendTo('#table_container');                
}
$(document).ready(function() {
    add_new_attribute();
});
</script>
<script type="text/javascript">
var mtotal = <?=count($mods)?>;
function add_new_modification(){
    mtotal++;
    $('<tr>')
    .attr('id','tr_mods_'+mtotal)
    .css({lineHeight:'20px'})
    .append (
        $('<td>')
       .attr('id','td_name_modification_'+mtotal)
       .css({padding:'5px 10px',width:'50%'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
           .attr('id','name_modification_'+mtotal)
           .attr('name','product_mods['+mtotal+'][name_modification]')
		   .attr('class','form-control')
       )                            
                              
    )
	.append (
       $('<td>')
       .attr('id','td_article_'+mtotal)
       .css({padding:'5px 10px',width:'15%'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
           .attr('id','article_'+mtotal)
           .attr('name','product_mods['+mtotal+'][article]')
		   .attr('class','form-control')
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_price_'+mtotal)
       .css({padding:'5px 10px',width:'15%'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
           .attr('id','price_'+mtotal)
           .attr('name','product_mods['+mtotal+'][price]')
		   .attr('class','form-control')
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_quantity_'+mtotal)
       .css({padding:'5px 10px',width:'10%'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
           .attr('id','quantity_'+mtotal)
           .attr('name','product_mods['+mtotal+'][quantity]')
		   .attr('class','form-control')
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_unit_'+mtotal)
       .css({padding:'5px 10px',width:'10%'})
       .append(
            $('<select>')
            .css({padding:'5px 10px',width:'80%'})
            .attr('name','product_mods['+mtotal+'][unit]')
		    .attr('class','form-control')
		   
			.append(
				$('<option>')               
				.attr('value','шт')
				.append(
					document.createTextNode("Штука")
				)
			)
			.append(
				$('<option>')               
				.attr('value','упак')
				.append(
					document.createTextNode("Упаковка")
				)
			)
			.append(
				$('<option>')               
				.attr('value','компл')
				.append(
					document.createTextNode("Комплект")
				)
			)
			.append(
				$('<option>')               
				.attr('value','кг')
				.append(
					document.createTextNode("Килограмм")
				)
			)
			.append(
				$('<option>')               
				.attr('value','л')
				.append(
					document.createTextNode("Литр")
				)
			)
		)                             
                              
    )
    .append(
        $('<td>')
        .css({padding:'5px 10px',width:'10%'})
        .append(
           $('<span id="progress_mods_'+mtotal+'"><a href="javascript:void(0)" onclick="$(\'#tr_mods_'+mtotal+'\').remove();" class="btn btn-default float-right">Удалить</a></span>')
         )
     )
     .appendTo('#table_container_mods');                
}
$(document).ready(function() {
    add_new_modification();
});
</script>