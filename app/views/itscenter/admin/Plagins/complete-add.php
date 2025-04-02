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
              <li class="breadcrumb-item active">Добавить комплект</li>
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
                <form method="post" action="<?=ADMIN;?>/plagins/complete-add" role="form" data-toggle="validator">
				
					<div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Добавить комплект</h3>
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
								<input type="text" class="form-control" name="name" id="name" value="<?= isset($_SESSION['form_data']['name']) ? $_SESSION['form_data']['name'] : '' ?>" required>
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
                            <label class="col-sm-3 col-form-label" for="content">Описание</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="content" id="editor1" cols="80" rows="10"><?php isset($_SESSION['form_data']['content']) ? $_SESSION['form_data']['content'] : null; ?></textarea>
							</div>
                        </div>				
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>
								<option value="lock">Закрыт от индексации</option>
                 			</select>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="img">Базовое изображение</label>
							<div class="col-sm-9">
								<div id="single" class="btn btn-success" data-url="plagins/complete-add-image" data-name="single" data-razdel="complete">Выбрать файл</div>
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
								<div id="multi" class="btn btn-success" data-url="plagins/complete-add-image" data-name="multi" data-razdel="complete">Выбрать файл</div>
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
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="alias">Ссылка страницы</label>
							<div class="col-sm-9">
                                <input type="text" name="alias" class="form-control" id="alias" placeholder="Если пусто, создается автоматически" value="<?php isset($_SESSION['form_data']['alias']) ? h($_SESSION['form_data']['alias']) : null; ?>">
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

				<div class="card-body">
                    <div class="box-body table-responsive">
						<table id="table_container">
							<tr>
								<td style="width:2%;"><strong>#</strong></td><td style="width:30%;padding: 0 0 0 10px;"><strong>Наименование</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Артикул</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Цена</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Количество</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Скидка</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Сумма скидки</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Сумма</strong></td><td style="width:6%;"></td>
							</tr>
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
												<span data-total="totalCost" class="sum">0</span>
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
                        <button type="submit" class="btn btn-primary">Добавить</button>
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
var total = 0;


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
		   .attr('class','form-control orderquantity_'+total+'')
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
		   .attr('type', 'number')
           .attr('id','discount_amount_text_'+total)
           .attr('name','complete['+total+'][discount_amount]')
		   .attr('class','form-control td_amount orderdiscount_amount_'+total+'')
		   .attr('value', '0')
		   .attr('oninput', 'change_price('+total+')')
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
           $('<span id="progress_'+total+'"><a href="javascript:void(0)" onclick="$(\'#tr_order_'+total+'\').remove(change_price(total));" class="btn btn-default float-right">Удалить</a></span>')
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
        url: adminpath + "/order/searchproduct",
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
	var nalog = $('select[name=nds]').val();
	
		$.ajax({
			url: adminpath + "/order/productprice",
			data: {id: id},
			type: 'GET',
			dataType: 'json',
			success: function(res){	
				if(nalog == 1) { nalog = 20; var sumnalog = (res.result2 * 0.2 / 1.2 ) * 1; var sumnalog = sumnalog.toFixed(2); var sumbeznalog = (res.result2 / 1.2 ) * 1; var sumbeznalog = sumbeznalog.toFixed(2);}
				if(nalog == 2) { nalog = 0; }

			    $("#td_article_"+total).html("<input name=\"complete["+total+"][article]\" type=\"text\" value=\""+res.result1+"\" class=\"form-control\" placeholder=\"артикул товара\" disabled />");
			    $("#td_price_"+total).html("<input name=\"complete["+total+"][price]\"id=\"price_text_"+total+"\" type=\"number\" value=\""+res.result2+"\" class=\"form-control orderprice_"+total+"\" placeholder=\"0\" oninput=\"change_price("+total+")\" /><input style=\"padding: 5px 10px;\" type=\"hidden\" id=\"price_nalog_text_"+total+"\" name=\"complete["+total+"][price_nalog]\" class=\"form-control prod_nalog\" value=\""+nalog+"\"><input style=\"padding: 5px 10px;\" type=\"hidden\" id=\"sum_nalog_text_"+total+"\" name=\"complete["+total+"][sum_nalog]\" class=\"form-control sum_nalog\" value=\""+sumnalog+"\"><input style=\"padding: 5px 10px;\" type=\"hidden\" id=\"sum_beznalog_text_"+total+"\" name=\"complete["+total+"][sum_beznalog]\" class=\"form-control sum_beznalog\" value=\""+sumbeznalog+"\">");
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
	var nalog = document.getElementById("price_nalog_text_"+total+"").value;

	document.getElementById("itog_text_"+total+"").value = ( price * kolvo ) - ( discount * kolvo );
	
	document.getElementById("discount_amount_text_"+total+"").value = discount * kolvo;	
	
	var sumnalog = ((price * 0.2 / 1.2 ) * kolvo) - ( discount * kolvo );
	var sumnalog = sumnalog.toFixed(2);
	
	document.getElementById("sum_nalog_text_"+total+"").value = sumnalog;
	
	var sumbeznalog = ((price / 1.2 ) * kolvo) - ( discount * kolvo );
	var sumbeznalog = sumbeznalog.toFixed(2);
	document.getElementById("sum_beznalog_text_"+total+"").value = sumbeznalog;
	
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