<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Комплекты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins/complete">Список комплектов</a></li>
              <li class="breadcrumb-item active"Редактирование комплекта</li>
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
                <form method="post" action="<?=ADMIN;?>/plagins/complete-edit" role="form" data-toggle="validator">
				
					<div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактирование комплекта <?=$complete->name;?></h3>
				<ul class="nav nav-pills ml-auto p-2">
					<li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>                  
					<li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">SEO</a></li>
					<li class="nav-item"><a class="nav-link" href="#tab_3" data-toggle="tab">Товарные позиции</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
				<div class="tab-content">
                  <div class="tab-pane active" id="tab_1">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="name">Название комплекта <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="name" id="name" value="<?=h($complete->name);?>" required>
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
                            <label class="col-sm-3 col-form-label" for="content">Описание</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?=$complete->content;?></textarea>
							</div>
                        </div>				
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value="show" <?php if($complete->hide == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
                    			<option value="hide" <?php if($complete->hide == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
                    			<option value="lock" <?php if($complete->hide == "lock") { echo "selected=\"selected\""; } ?>>Закрыт от индексации</option>                   			
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
							<div class="col-sm-9">
								<div id="single" class="btn btn-success" data-url="plagins/complete-add-image" data-name="single" data-razdel="complete">Выбрать файл</div>
								<p><small>Рекомендуемые размеры: 600х450</small></p>
								<div class="single">
									<img src="/images/complete/baseimg/<?=$complete->img;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$complete->id;?>" data-src="<?=$complete->img;?>" data-razdel="plagins" data-plagins="complete" class="del-base">
								</div>							
								<div class="overlay">
									<i class="fa fa-refresh fa-spin"></i>
								</div>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img_gallery">Картинки галереи</label>
							<div class="col-sm-9">
								<div id="multi" class="btn btn-success" data-url="plagins/complete-add-image" data-name="multi" data-razdel="complete">Выбрать файл</div>
								<p><small>Рекомендуемые размеры: 1000х750</small></p>
								<div class="multi">
									<?php if(!empty($gallery)): ?>
                                                <?php foreach($gallery as $item): ?>
                                                    <img src="/images/complete/gallery/<?=$item;?>" alt="" style="max-height: 150px; cursor: pointer;" data-id="<?=$complete->id;?>" data-src="<?=$item;?>" data-razdel="plagins" data-plagins="complete" class="del-item">
                                                <?php endforeach; ?>
                                            <?php endif; ?>
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
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="alias">Ссылка страницы</label>
							<div class="col-sm-9">
                                <input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически" value="<?=$complete->alias;?>">
							</div>
                        </div>
						<div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="title">Заголовок (Title)</label>
								<div class="col-sm-9">
                                <input type="text" name="title" class="form-control" id="title" placeholder="Название которое будет отображаться в поисковиках" value="<?=$complete->title;?>">
                            </div>
                        </div>						
                        <div class="form-group row">
							<label class="col-sm-3 col-form-label" for="description">Ключевое описание (Description)</label>
							<div class="col-sm-9">	
                                <input type="text" name="description" class="form-control" id="description" placeholder="Описание" value="<?=$complete->description;?>">
                            </div>
                        </div>
						<div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="keywords">Ключевые слова (Keywords)</label>
								<div class="col-sm-9">
                                <input type="text" name="keywords" class="form-control" id="keywords" placeholder="Ключевые слова" value="<?=$complete->keywords;?>">
								</div>
                        </div>						
					</div>
					</div>
                  <!-- /.tab-pane -->
                  <div class="tab-pane" id="tab_3">
                    <div class="card-body">
                    <div class="box-body table-responsive">
						<table id="table_container">							
								<tr>
									<td style="width:2%;"><strong>#</strong></td><td style="width:30%;padding: 0 0 0 10px;"><strong>Наименование</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Артикул</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Цена</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Количество</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Скидка</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Сумма скидки</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Сумма</strong></td>
									
									<td style="width:6%;"></td>
										
								</tr>							
							<?php $k = 1; foreach($complete_product as $product):	?>
							<?php 
								$qty += $product["qty"];
							?>
							<?php $itog_price += ($product["price"] * $product["qty"]) - ( $product["discount"] * $product["qty"] ); ?>
							<?php $itog_summa = ($product["price"] * $product["qty"]) - ( $product["discount"] * $product["qty"] ); ?>
							<?php $itog_amount += ($product["discount"] * $product["qty"]); ?>
							
							<tr id="tr_order_<?=$k?>" style="line-height: 20px;">			
								<td><?=$k?></td>								
								<td id="td_product_<?=$k?>" style="padding: 5px 10px;">
									<select class="form-control select_product searchproduct_<?=$k?>" name="complete[<?=$k?>][product_id]">
										<option value="<?=$product["product_id"]?>" /><?=$product["name"]?></option>
									</select>
								</td>
								<td id="td_article_<?=$k?>" style="padding: 5px 10px;">
									<input name="complete[<?=$k?>][article]" type="text" value="<?=$product["article"]?>" class="form-control" placeholder="артикул товара" readonly>
								</td>
								<td id="td_price_<?=$k?>" style="padding: 5px 10px;">
									<input name="complete[<?=$k?>][price]" id="price_text_<?=$k?>" type="number" value="<?=$product["price"]?>" class="form-control orderprice_<?=$k?>" placeholder="0" oninput="change_price(<?=$k?>)">
								</td>
								<td id="td_quantity_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="number" id="quantity_text_<?=$k?>" name="complete[<?=$k?>][quantity]" class="form-control itog_qty orderquantity_<?=$k?>" value="<?=$product["qty"]?>" oninput="change_price(<?=$k?>)">
									<input type="hidden" id="itog_quantity_text_<?=$k?>" name="complete[<?=$k?>][itogquantity]" class="form-control itogquantity_<?=$k?>">
								</td>								
								<td id="td_discount_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="number" id="discount_text_<?=$k?>" name="complete[<?=$k?>][discount]" class="form-control orderdiscount_<?=$k?>" value="<?=$product["discount"]?>" oninput="change_price(<?=$k?>)">
								</td>
								<td id="td_discount_amount_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="text" id="discount_amount_text_<?=$k?>" name="complete[<?=$k?>][discount_amount]" class="form-control td_amount orderdiscount_amount_<?=$k?>" value="<?=$product["discount_amount"]?>" oninput="change_price(<?=$k?>)" readonly>
								</td>								
								<td id="td_itog_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" id="itog_text_<?=$k?>" name="complete[<?=$k?>][itog]" value="<?=$itog_summa;?>" class="form-control itog_price_<?=$k?> td_itog" readonly>
								</td>								
								<td style="padding: 5px 10px;">
									<span id="progress_<?=$k?>"><a href="javascript:void(0)" onclick="$('#tr_order_<?=$k?>').remove(); recalc();"  class="btn btn-default float-right">Удалить</a></span>
								</td>								
							</tr>							
							<?php $k++; endforeach; ?>
							<tfoot>							
								<tr class="active">
										<td class="border-top pt-3" colspan="4">
											Итого:
										</td>
										<td class="border-top pt-3 pl-3 align-middle itogqty"><?=$qty;?></td>
										<td class="border-top pt-3"></td>
										<td class="border-top pt-3"></td>
										<td class="border-top pt-3"></td>
										<td class="border-top pt-3"></td>
								</tr>
							</tfoot>
						</table>					
							<br/>
							<div style="float:right;padding:10px 10px 0 0"><input type="button" value="Добавить позицию" class="btn btn-success" id="add_prod"></div>
						
                        <div class="order-content">
							<div class="order-container">
								<table class="order-table">
									<tbody>										
										<tr class="table-row">
											<td class="pt-3 total-itogs-cont">
												Общая сумма:
											</td>
											<td class="pt-3 itogs-cont total-itogs-cont">
												<span data-total="totalCost" class="sum"><?=$itog_price?></span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
									</tbody>
								</table>
							</div>

						</div>						          
					</div><!-- /.box-body -->
				
				</div><!-- /.card-body -->
					</div>
                  <!-- /.tab-pane -->                  
                </div>
                <!-- /.tab-content -->
				
              </div><!-- /.card-body -->
			  
            </div>                   

                    <div class="box-footer">
                        <input type="hidden" name="id" value="<?=$complete->id;?>">
                    <button type="submit" class="btn btn-success">Сохранить</button>
                    </div>
                </form>
                <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
            </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->
		
</section>
<!-- /.content -->

<script type="text/javascript">
var total = <?=count($complete_product)?>;

function add_new_orders(){
    total++;
    $('<tr>')
    .attr('id','tr_order_'+total)
    .css({lineHeight:'20px'})
	.append (
        $('<td>')
		.append (''+total+'')        
	)
    .append (
        $('<td>')
        .attr('id','td_product_'+total)
        .css({padding:'5px 10px'})
        .append(
            $('<select>')
            .css({padding:'5px 10px',width:'100%'})
            .attr('name','complete['+total+'][product_id]')
		    .attr('class','form-control select_product searchproduct_'+total+'')
		)
	)
	.append (
       $('<td>')
       .attr('id','td_article_'+total)
       .css({padding:'5px 10px'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
           .attr('id','article_text_'+total)
           .attr('name','complete['+total+'][article]')
		   .attr('class','form-control')
		   .attr('readonly')
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_price_'+total)
       .css({padding:'5px 10px'})
        .append(
           $('<input>')
           .css({padding:'5px 10px'})
		   .attr('type', 'number')
           .attr('id','price_text_'+total)
           .attr('name','complete['+total+'][price]')
		   .attr('class','form-control')
		   .attr('oninput', 'change_price('+total+')')
        )		                    
    )
	.append (
       $('<td>')
       .attr('id','td_quantity_'+total)
       .css({padding:'5px 10px'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
		   .attr('type', 'number')
           .attr('id','quantity_text_'+total)
           .attr('name','complete['+total+'][quantity]')
		   .attr('class','form-control itog_qty orderquantity_'+total+'')
		   .attr('value', '1')
		   .attr('oninput', 'change_price('+total+')')
       ) 
		.append(
           $('<input>')
		   .attr('type', 'hidden')
           .attr('id','itog_quantity_text_'+total)
           .attr('name','complete['+total+'][itogquantity]')
		   .attr('class','form-control itogquantity_'+total+'')
       )
                              
    )
	.append (
       $('<td>')
       .attr('id','td_discount_'+total)
       .css({padding:'5px 10px'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
		   .attr('type', 'number')
           .attr('id','discount_text_'+total)
           .attr('name','complete['+total+'][discount]')
		   .attr('class','form-control orderdiscount_'+total+'')
		   .attr('value', '0')
		   .attr('oninput', 'change_price('+total+')')
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_discount_amount_'+total)
       .css({padding:'5px 10px',})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
		   .attr('type', 'text')
           .attr('id','discount_amount_text_'+total)
           .attr('name','complete['+total+'][discount_amount]')
		   .attr('class','form-control td_amount orderdiscount_amount_'+total+'')
		   .attr('value', '0')
		   .attr('oninput', 'change_price('+total+')')
		   .attr('disabled', 'disabled')
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_itog_'+total)
       .css({padding:'5px 10px'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
           .attr('id','itog_text_'+total)
           .attr('name','complete['+total+'][itog]')
		   .attr('class','form-control itog_price_'+total+' td_itog')
		   .attr('disabled', 'disabled')
       )                          
    )
	.append(
        $('<td>')
        .css({padding:'5px 10px'})
        .append(
           $('<span id="progress_'+total+'"><a href="javascript:void(0)" onclick="$(\'#tr_order_'+total+'\').remove(); recalc();"  oninput=\"change_price("+total+")\" class="btn btn-default float-right">Удалить</a></span>')
         )
     )
    .appendTo('#table_container');
	initailizeSelect2(total = ""+total+"");
}
// Initialize select2
function initailizeSelect2(total){

   $(".searchproduct_"+total).select2({
    placeholder: "Начните вводить название или артикул товара",
    minimumInputLength: 1,
    cache: true,
    ajax: {
        url: adminpath + "/plagins/searchproduct",
        delay: 250,
        dataType: 'json',
        data: function (params) {
            return {
                q: params.term,
                page: params.page
            };
        },
        processResults: function (data, params) {
            return {
                results: data.items								
            };			
        }		
     }
   });

   
   $( "select.select_product" ).change(function () {
    var id = $( ".searchproduct_"+total ).val();
	
		$.ajax({
			url: adminpath + "/plagins/productprice",
			data: {id: id},
			type: 'GET',
			dataType: 'json',
			success: function(res){	
			    $("#td_article_"+total).html("<input name=\"complete["+total+"][article]\" type=\"text\" value=\""+res.result1+"\" class=\"form-control\" placeholder=\"артикул товара\" readonly />");
			    $("#td_price_"+total).html("<input name=\"complete["+total+"][price]\"id=\"price_text_"+total+"\" type=\"number\" value=\""+res.result2+"\" class=\"form-control orderprice_"+total+"\" placeholder=\"0\" oninput=\"change_price("+total+")\" />");
				change_price(total = ""+total+"");
			},
			error: function(){
				alert('Ошибка');
			}
		});
	})
	
}

$(function(){
    $("#add_prod").on("click", function(){
        add_new_orders();
    });
});

function change_price(total) {
	var price = document.getElementById("price_text_"+total+"").value;
	var kolvo = document.getElementById("quantity_text_"+total+"").value;
	var discount = document.getElementById("discount_text_"+total+"").value;	
	
	document.getElementById("itog_text_"+total+"").value = ( price * kolvo ) - ( discount * kolvo );
	document.getElementById("discount_amount_text_"+total+"").value = discount * kolvo;	
	
	var amount = 0;
	$('.td_amount').each(function(){
		amount += parseFloat($(this).val());
	})
	$(".amount").html(""+amount+"");
	var sum = 0;
	$('.td_itog').each(function(){
		sum += parseFloat($(this).val());
	})
	$(".sum").html(""+sum+"");

	var itogqty = 0;
	$('.itog_qty').each(function(){
		itogqty += parseFloat($(this).val());
	})
	$(".itogqty").html(""+itogqty+"");	
}

function recalc() {
	var amount = 0;
	$('.td_amount').each(function(){
		amount += parseFloat($(this).val());
	})
	$(".amount").html(""+amount+"");
	var sum = 0;
	$('.td_itog').each(function(){
		sum += parseFloat($(this).val());
	})
	$(".sum").html(""+sum+"");
		
	var itogqty = 0;
	$('.itog_qty').each(function(){
		itogqty += parseFloat($(this).val());
	})
	$(".itogqty").html(""+itogqty+"");	
}
</script>