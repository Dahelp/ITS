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
              <li class="breadcrumb-item active">Добавить товар</li>
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
		    <form action="<?=ADMIN;?>/product/add" method="post" data-toggle="validator" id="add">
            <!-- Custom Tabs -->
            <div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Добавить товар</h3>
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
											<input type="text" name="name" class="form-control" id="name" placeholder="Наименование товара" value="<?php isset($_SESSION['form_data']['name']) ? h($_SESSION['form_data']['name']) : null; ?>" required>											
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
                                'prepend' => '<option>Выберите категорию</option>',
                            ]) ?>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="article">Артикул (SKU)</label>
							<div class="col-sm-9">
								<input type="text" name="article" class="form-control" id="article" placeholder="Артикул товара" value="<?php isset($_SESSION['form_data']['article']) ? h($_SESSION['form_data']['article']) : null; ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="price">Цена</label>
							<div class="col-sm-9">
								<input type="text" name="price" class="form-control" id="price" placeholder="Цена" pattern="^[0-9.]{1,}$" value="<?php isset($_SESSION['form_data']['price']) ? h($_SESSION['form_data']['price']) : null; ?>" required data-error="Допускаются цифры и десятичная точка">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="old_price">Старая цена</label>
							<div class="col-sm-9">
								<input type="text" name="old_price" class="form-control" id="old_price" placeholder="Старая цена" pattern="^[0-9.]{1,}$" value="<?php isset($_SESSION['form_data']['old_price']) ? h($_SESSION['form_data']['old_price']) : null; ?>" data-error="Допускаются цифры и десятичная точка">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="opt_price">Цена оптовая</label>
							<div class="col-sm-9">
								<input type="text" name="opt_price" class="form-control" id="opt_price" placeholder="Цена оптовая" pattern="^[0-9.]{1,}$" value="<?php isset($_SESSION['form_data']['opt_price']) ? h($_SESSION['form_data']['opt_price']) : null; ?>" data-error="Допускаются цифры и десятичная точка">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="model">Модель</label>
							<div class="col-sm-9">
								<input type="text" name="model" class="form-control" id="model" placeholder="Модель товара" value="<?php isset($_SESSION['form_data']['model']) ? h($_SESSION['form_data']['model']) : null; ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="brand_id">Производитель</label>
							<div class="col-sm-9">
								<select name="brand_id" class="form-control" style="width: 100%;">
									<option value= "" selected="selected">Выберите производителя</option>
									<?php $brands = \R::getAll('SELECT id, name FROM brand');
									$i=1;	foreach($brands as $brand_item => $item): ?>									
									<option value= "<?=$item["id"]?>"><?=$item["name"]?></option>
								<?php $i++; endforeach; ?>
								</select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="content">Подробное описание</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?php isset($_SESSION['form_data']['content']) ? $_SESSION['form_data']['content'] : null; ?></textarea>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label">Отметить как</label>
							<div class="col-sm-9">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="customCheckbox1" name="new_product">
                                    <label style="font-weight:400" for="customCheckbox1" class="custom-control-label">Новинка</label>
                                </div>
								<div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="customCheckbox2" name="hit">
                                    <label style="font-weight:400" for="customCheckbox2" class="custom-control-label">Лидер продаж</label>
                                </div>
								<div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="customCheckbox3" name="sale">
                                    <label style="font-weight:400" for="customCheckbox3" class="custom-control-label">Распродажа (акция)</label>
                                </div>
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value="" selected="selected">Выберите статус активности</option>
								<option value="show">Активный</option>
                    			<option value="hide">Не активный</option>
                    			<option value="lock">Закрыт от индексации</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="stock_status_id">Статус наличия</label>
							<div class="col-sm-9">
							<select name="stock_status_id" class="form-control" style="width: 100%;">
								<option value="" selected="selected">Выберите статус наличия</option>
								<option value="1">В наличии</option>
                    			<option value="0">Нет в наличии</option>
                    			<option value="2">Под заказ</option>
								<option value="3">Ожидается поступление</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="quantity">Количество</label>
							<div class="col-sm-9">
								<input type="text" name="quantity" class="form-control" id="quantity" placeholder="Количество" value="<?php isset($_SESSION['form_data']['quantity']) ? h($_SESSION['form_data']['quantity']) : null; ?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="unit">Единица измерения</label>
							<div class="col-sm-9">
							<select name="unit" class="form-control" style="width: 100%;">
								<option value="шт" selected="selected">Штука</option>
								<option value="упак">Упаковка</option>
                    			<option value="компл">Комплект</option>
                    			<option value="кг">Килограмм</option>
								<option value="л">Литр</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="weight">Вес, кг</label>
							<div class="col-sm-9">
								<input type="text" name="weight" class="form-control" id="weight" placeholder="Вес товара" value="<?php isset($_SESSION['form_data']['weight']) ? h($_SESSION['form_data']['weight']) : null; ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="volume">Объём, м3</label>
							<div class="col-sm-9">
								<input type="text" name="volume" class="form-control" id="volume" placeholder="Объём товара" value="<?php isset($_SESSION['form_data']['volume']) ? h($_SESSION['form_data']['volume']) : null; ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
							<div class="col-sm-9">
                                        <div id="single" class="btn btn-success" data-url="product/add-image" data-name="single" data-razdel="product">Выбрать файл</div>
                                        <p><small>Рекомендуемые размеры: 600х450</small></p>
                                        <div class="single"></div>
                                    
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
								<div class="multi"></div>								
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
                                <input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически" value="<?php isset($_SESSION['form_data']['alias']) ? h($_SESSION['form_data']['alias']) : null; ?>">
							</div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
                            <div class="col-sm-9">
								<input type="text" name="title" class="form-control" id="title" placeholder="Если пусто, то используется Название" value="<?php isset($_SESSION['form_data']['title']) ? h($_SESSION['form_data']['title']) : null; ?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
                            <div class="col-sm-9">
								<input type="text" name="description" class="form-control" id="description" placeholder="SEO описание (130-150 символов)" value="<?php isset($_SESSION['form_data']['description']) ? h($_SESSION['form_data']['description']) : null; ?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
                            <div class="col-sm-9">
								<input type="text" name="keywords" class="form-control" id="keywords" placeholder="Заполняются через запятую (4-6 фраз)" value="<?php isset($_SESSION['form_data']['keywords']) ? h($_SESSION['form_data']['keywords']) : null; ?>">
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name_tag">Теги товара</label>
                            <div class="col-sm-9">
								<input type="text" name="name_tag" class="form-control" id="name_tag" placeholder="Заполняются через запятую (1-5 фраз)" value="<?php isset($_SESSION['form_data']['name_tag']) ? h($_SESSION['form_data']['name_tag']) : null; ?>">								
							</div>
                        </div>
					</div>
                  </div>
                  <!-- /.tab-pane -->
				  <div class="tab-pane" id="tab_4">
					<!-- filters -->
                    <div class="box-body filters"></div>
					<!-- /filters -->
                  </div>
                  <!-- /.tab-pane -->
				  <div class="tab-pane" id="tab_5">
                    <div class="box-body">
						<table id="table_container_mods" style="width: 100%;">
							<tr>
								<td style="width:50%;padding: 0 0 0 10px;"><strong>Модификация</strong></td><td style="width:15%;padding: 0 0 0 10px;"><strong>Артикул</strong></td><td style="width:15%;padding: 0 0 0 10px;"><strong>Цена</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Количество</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Ед.из.</strong></td><td style="width:10%;padding: 0 0 0 10px;"></td>
							</tr>							
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
								<select name="related[]" class="form-control select2" id="related" multiple="multiple" data-placeholder="Выберите товары"></select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="similar">Похожие товары</label>
                            <div class="col-sm-9">
								<select name="similar[]" class="form-control select2" id="similar" multiple="multiple" data-placeholder="Выберите товары"></select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="service">Услуги</label>
                            <div class="col-sm-9">
								<select name="service[]" class="form-control select2" id="service" data-placeholder="Выберите услуги"></select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url_video">Ссылка на видео</label>
                            <div class="col-sm-9">
								<input type="text" name="url_video" class="form-control" id="url_video" placeholder="Укажите ссылку на видео" value="<?php isset($_SESSION['form_data']['url_video']) ? h($_SESSION['form_data']['url_video']) : null; ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="note">Примечание к товару</label>
                            <div class="col-sm-9">
								<input type="text" name="note" class="form-control" id="note" placeholder="Замечания по товару" value="<?php isset($_SESSION['form_data']['note']) ? h($_SESSION['form_data']['note']) : null; ?>">
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
                        <button type="submit" class="btn btn-primary btn_save">Добавить</button>
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
var total = 0;
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