
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Заказы</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/order">Список заказов</a></li>
              <li class="breadcrumb-item active">Создать заказ</li>
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
		    <form action="<?=ADMIN;?>/order/add" method="post" data-toggle="validator">
            <!-- Custom Tabs -->
            <div class="card">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Создать заказ</h3>
				</div><!-- /.card-header -->
				<div class="card-body">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="client">Клиент</label>							
							<div class="col-sm-9">
							<select name="client" class="form-control client" onclick="tipClient(this)">
								<option value = "" selected="selected">Выберите тип клиента</option>
								<option value = "1">Новый</option>
                    			<option value = "2">Зарегистрированный</option>								
                 			</select>
							</div>
                        </div>						
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="vid">Вид</label>							
							<div class="col-sm-9">
							<select name="vid" id="vid" class="form-control vid" onchange="change_price()" onclick="vidUrlface(this)">
								<option value = "" selected="selected">Выберите вид клиента</option>
								<option value = "3">Физическое лицо</option>
                    			<option value = "4">Юридическое лицо</option>								
                 			</select>
							</div>
                        </div>
						<div id="user_contact" style="display:none">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="uname">Контакт</label>
								<div class="col-sm-9">
									<div id="tip_user_reg" style="display:none">
										<select name="user_id" class="form-control usercontact" id="user_id" data-placeholder="Выберите ФИО клиента"></select>
									</div>
									<div id="tip_user_new" style="display:none">
										<input name="user_name" class="form-control" placeholder="ФИО клиента" />
									</div>
								</div>                                        
							</div>
						</div>
						<script>
							function tipClient(el) {
								var u = el.options[el.selectedIndex].value;
								document.getElementById("user_contact").style.display = (u>0)? "block":"none";
								document.getElementById("tip_user_reg").style.display = (u==2)? "block":"none";
								document.getElementById("tip_user_new").style.display = (u==1)? "block":"none";
								document.getElementById("tip_comp_reg").style.display = (u==1)? "none":"block";
								document.getElementById("tip_comp_new").style.display = (u==1)? "block":"none";
							}
						</script>
						<div id="vid_urlface" style="display:none">							
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="comp_id">Компания</label>							
								<div class="col-sm-9">
								<div id="tip_comp_reg" style="display:none">
									<select name="comp_id" class="form-control companys" id="comp_id" data-placeholder="Выберите компанию по ИНН или названию"></select>
								</div>
								<div id="tip_comp_new" style="display:none">
									<input name="comp_name" id="comp_name" class="form-control" placeholder="Название компании" />
								</div>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="tip">Тип взаимодействия <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<select class="form-control" id="tip" name="tip">
										<option value="">Выберите тип</option>
										<option value="1">Розничная торговля</option>
										<option value="2">Оптовая торговля</option>
									</select>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="url_address">Юр. адрес</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="url_address" id="url_address" value="<?= isset($_SESSION['form_data']['url_address']) ? $_SESSION['form_data']['url_address'] : '' ?>">
								</div>
							</div>				
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="postal_address">Почтовый адрес</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="postal_address" id="postal_address" value="<?= isset($_SESSION['form_data']['postal_address']) ? $_SESSION['form_data']['postal_address'] : '' ?>">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="ogrn">ОГРН, ОГРНИП <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="ogrn" id="ogrn" value="<?= isset($_SESSION['form_data']['ogrn']) ? $_SESSION['form_data']['ogrn'] : '' ?>" placeholder="ОГРН 13 цифр, ОГРНИП 15 цифр" maxlength="15">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="inn">ИНН <span class="text-danger">*</span></label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="inn" id="inn" value="<?= isset($_SESSION['form_data']['inn']) ? $_SESSION['form_data']['inn'] : '' ?>" placeholder="Юр.лицо 10 цифр, ИП 12 цифр" maxlength="12">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="kpp">КПП</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="kpp" id="kpp" value="<?= isset($_SESSION['form_data']['kpp']) ? $_SESSION['form_data']['kpp'] : '' ?>" placeholder="9 цифр" maxlength="9" data-error="КПП состоит из 9 цифр" data-minlength="9">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="bik">БИК</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="bik" id="bik" value="<?= isset($_SESSION['form_data']['bik']) ? $_SESSION['form_data']['bik'] : '' ?>" placeholder="9 цифр" maxlength="9" data-error="БИК состоит из 9 цифр" data-minlength="9">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="raschet">Расч. счёт</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="raschet" id="raschet" value="<?= isset($_SESSION['form_data']['raschet']) ? $_SESSION['form_data']['raschet'] : '' ?>">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="korschet">Кор. счёт</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="korschet" id="korschet" value="<?= isset($_SESSION['form_data']['korschet']) ? $_SESSION['form_data']['korschet'] : '' ?>">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="bank">Наименование банка</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="bank" id="bank" value="<?= isset($_SESSION['form_data']['bank']) ? $_SESSION['form_data']['bank'] : '' ?>">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="dir_name">Генеральный директор</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="dir_name" id="dir_name" value="<?= isset($_SESSION['form_data']['dir_name']) ? $_SESSION['form_data']['dir_name'] : '' ?>">
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="nds">Система налогообложения</label>
								<div class="col-sm-9">
									<select class="form-control" id="cnds" onchange="change_price()" name="nds">
										<option value="">Выберите статус</option>
										<option value = "1">с НДС</option>
										<option value = "2">без НДС</option>
									</select>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="dogovor">Условия поставки</label>
								<div class="col-sm-9">
									<select class="form-control" id="dogovor" name="dogovor">
										<option value="">Выберите статус</option>
										<option value = "1">Договор</option>
										<option value = "2">Счёт-договор</option>
									</select>
								</div>
							</div>
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="hide">Статус</label>
								<div class="col-sm-9">
									<select class="form-control" id="hide" name="hide">
										<option value="">Выберите статус</option>
										<option value="show">Активный</option>
										<option value="hide">Не активный</option>									
									</select>
								</div>
							</div>
						</div>
						<script>
							function vidUrlface(el) {
								var u = el.options[el.selectedIndex].value;    
								document.getElementById("vid_urlface").style.display = (u>3)? "block":"none";																
							}
						</script>    
						
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="telefon">Телефон</label>
							<div class="col-sm-9">
								<input type="text" name="telefon" class="form-control phonez" id="utelefon" placeholder="Телефон для связи" value="<?php isset($_SESSION['form_data']['telefon']) ? h($_SESSION['form_data']['telefon']) : null; ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="email">Email</label>
							<div class="col-sm-9">
								<input type="text" name="email" class="form-control" id="uemail" placeholder="Email клиента" value="<?php isset($_SESSION['form_data']['email']) ? h($_SESSION['form_data']['email']) : null; ?>">
                            </div>
                        </div>                        
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="dostavka_id">Способ получения</label>
							<div class="col-sm-9">
							<select name="dostavka_id" class="form-control" onclick="anotherCity(this)">
								<option value= "" selected="selected">Выберите способ получения</option>
								<?php $dostavka = \R::getAll("SELECT * FROM dostavka WHERE hide='show'");
									foreach($dostavka as $ds){ ?>
										<option value="<?=$ds["id"]?>"><?=$ds["name"]?></option>
								<?php } ?>
                 			</select>
							</div>
                        </div>
						<div id="another_transport" style="display:none">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="transport_id">Транспортная компания</label>
								<div class="col-sm-9">
								<select name="transport_id" class="form-control">
									<option value= "" selected="selected">Выберите транспортную компанию</option>
									<?php $transport = \R::getAll("SELECT * FROM transport_company WHERE hide='show'");
										foreach($transport as $ts){ ?>
											<option value="<?=$ts["id"]?>"><?=$ts["name"]?></option>
									<?php } ?>
								</select>
								</div>
							</div>
						</div>
						<div id="another_sklad" style="display:none">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="branch_id">Самовывоз</label>
								<div class="col-sm-9">
								<select name="branch_id" class="form-control">
									<option value= "" selected="selected">Выберите место самовывоза</option>
									<?php $branch = \R::getAll("SELECT * FROM branch_office WHERE hide='show'");
										foreach($branch as $br){ ?>
											<option value="<?=$br["branch_id"]?>"><?=$br["branch_name"]?></option>
										<?php } ?>
								</select>
								</div>
							</div>
						</div>
						<div id="another_city" style="display:none">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="city_id">Город доставки</label>
								<div class="col-sm-9">
								<select name="city_id" class="form-control">
									<option value= "" selected="selected">Выберите город доставки</option>
									<?php $cities = \R::getAll("SELECT * FROM cities ORDER BY city_name");
										foreach($cities as $st){ ?>
											<option value="<?=$st["city_id"]?>"><?=$st["city_name"]?></option>
									<?php } ?>
								</select>
								</div>
							</div>
						</div>
						<div id="another_adress" style="display:none">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="address">Адрес доставки</label>
								<div class="col-sm-9">
									<input type="text" name="address" class="form-control" id="address" placeholder="Адрес доставки товаров" value="<?php isset($_SESSION['form_data']['address']) ? h($_SESSION['form_data']['address']) : null; ?>">
								</div>
							</div>
						</div>
						<script>
							function anotherCity(el) {
								var v = el.options[el.selectedIndex].value;    
								document.getElementById("another_city").style.display = (v>1)? "block":"none";
								document.getElementById("another_sklad").style.display = (v==1)? "block":"none";
								document.getElementById("another_transport").style.display = (v==2)? "block":"none";
								document.getElementById("another_adress").style.display = (v==3)? "block":"none";								
							}
						</script>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="note">Комментарий к заказу</label>
							<div class="col-sm-9">
								<textarea class="form-control" name="note"></textarea>
                            </div>
                        </div>												          
                </div>
                <!-- /.box-body -->
				
              </div><!-- /.card-body -->
			  
            </div>
			
			<div class="card">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Товары</h3>
				</div><!-- /.card-header -->
				<div class="card-body">
                    <div class="box-body table-responsive">
						<table id="table_container" class="table_order">
							<tr>
								<td style="width:2%;"><strong>#</strong></td><td style="width:26%;padding: 0 0 0 10px;"><strong>Наименование</strong></td><td style="width:5%;padding: 0 0 0 10px;"><strong>Артикул</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Цена</strong></td><td style="width:6%;padding: 0 0 0 10px;"><strong>Количество</strong></td><td style="width:6%;padding: 0 0 0 10px;"><strong>Наличие</strong></td><td style="width:6%;padding: 0 0 0 10px;"><strong>Резерв</strong></td><td style="width:5%;padding: 0 0 0 10px;"><strong>Значение</strong></td><td style="width:5%;padding: 0 0 0 10px;"></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Скидка</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Цена со скидкой</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Сумма скидки</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Сумма</strong></td><td style="width:6%;"></td>
							</tr>
						</table>
						<br/>
						<div style="float:right;padding:10px 10px 0 0"><input type="button" value="Добавить позицию" class="btn btn-success" id="add_prod"></div>
                        <div class="order-content">
							<div class="order-container">
								<table class="order-table">
									<tbody>
										<tr class="table-row">
											<td>Сумма без скидки и налогов:</td>
											<td class="itogs-cont">
												<span data-total="totalWithoutDiscount" class="sum_beznalog_skidki">0</span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<tr class="table-row">
											<td>Сумма доставки:</td>
											<td class="itogs-cont">
												<span data-total="totalDelivery">0</span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<tr class="table-row">
											<td>
												Сумма скидки:
											</td>
											<td class="itogs-cont">
												<span data-total="totalDiscount" id="amount" class="amount">0</span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<tr class="table-row">
											<td>Сумма без налога:</td>
											<td class="itogs-cont">
												<span data-total="totalWithoutTax" class="sum_beznalog_itogo">0</span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<tr class="table-row">
											<td class="border-bottom pb-3">
												Сумма налога:
											</td>
											<td class="itogs-cont border-bottom pb-3">
												<span data-total="totalTax" class="sum_nalog_itogo">0</span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
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
							<div class="order-container-info">
								<table class="order-table">
									<tbody>
										<tr class="table-row">
											<td>Вес, кг:</td>
											<td class="itogs-weight">
												<span class="sum_weight">0</span>
											</td>
										</tr>																				
										<tr class="table-row">
											<td>Объём, м3:</td>
											<td class="itogs-volume">
												<span class="sum_volume">0</span>												
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>						          
					</div><!-- /.box-body -->
				
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
<?php header('Content-type: text/html; charset=windows-1251'); ?>
<script type="text/javascript">
$( "select.companys" ).change(function () {
    var id = $( ".companys" ).val();

		$.ajax({
			url: adminpath + "/order/compinfo",
			data: {id: id},
			type: 'GET',
			dataType: 'json',
			success: function(res){				
				$("#user_id").html("<option value = \""+res.uid+"\" data-id=\""+res.uid+"\" selected=\"selected\">"+res.uname+"</option>");
				document.getElementById("utelefon").value = res.utelefon;
				document.getElementById("uemail").value = res.uemail;
				if(res.cnds == 1){
					$("#cnds").html("<option value = \"1\" data-id=\"20\" selected=\"selected\">с НДС</option>");					
				}
				if(res.cnds == 2){
					$("#cnds").html("<option value = \"2\" data-id=\"0\" selected=\"selected\">без НДС</option>");
				}
				if(res.tip == 1){
					$("#tip").html("<option value = \"1\" data-id=\"1\" selected=\"selected\">Розничная торговля</option>");
				}
				if(res.tip == 2) {
					$("#tip").html("<option value = \"2\" data-id=\"2\" selected=\"selected\">Оптовая торговля</option>");
				}
				document.getElementById("url_address").value = res.url_address;
				document.getElementById("postal_address").value = res.postal_address;
				document.getElementById("ogrn").value = res.ogrn;
				document.getElementById("inn").value = res.inn;
				document.getElementById("kpp").value = res.kpp;
				document.getElementById("bik").value = res.bik;
				document.getElementById("raschet").value = res.raschet;
				document.getElementById("korschet").value = res.korschet;
				document.getElementById("bank").value = res.bank;
				document.getElementById("dir_name").value = res.dir_name;
				document.getElementById("dogovor").value = res.dogovor;
				if(res.hide == "show"){
					$("#hide").html("<option value = \"show\" data-id=\"1\" selected=\"selected\">Активный</option>");
				}
				if(res.hide == "hide") {
					$("#hide").html("<option value = \"hide\" data-id=\"2\" selected=\"selected\">Не активный</option>");
				}
			},
			error: function(){
				alert('Ошибка');
			}
		});
	})
	
	$( "select.usercontact" ).change(function () {
    var id = $( ".usercontact" ).val();

		$.ajax({
			url: adminpath + "/order/usercontact",
			data: {id: id},
			type: 'GET',
			dataType: 'json',
			success: function(res){
				
				document.getElementById("utelefon").value = res.utelefon;
				document.getElementById("uemail").value = res.uemail;
				document.getElementById("vid_urlface").style.cssText = "display: block;";
				document.getElementById("tip_comp_reg").style.cssText = "display: block;";
				$("#comp_id").html("<option value = \""+res.comp_id+"\" data-id=\""+res.comp_id+"\" selected=\"selected\">"+res.comp_name+"</option>");
				if(res.cnds == 1){
					$("#cnds").html("<option value = \"1\" data-id=\"20\" selected=\"selected\">с НДС</option>");					
				}
				if(res.cnds == 2){
					$("#cnds").html("<option value = \"2\" data-id=\"0\" selected=\"selected\">без НДС</option>");
				}
				if(res.tip == 1){
					$("#tip").html("<option value = \"1\" data-id=\"1\" selected=\"selected\">Розничная торговля</option>");
				}
				if(res.tip == 2) {
					$("#tip").html("<option value = \"2\" data-id=\"2\" selected=\"selected\">Оптовая торговля</option>");
				}
				document.getElementById("url_address").value = res.url_address;
				document.getElementById("postal_address").value = res.postal_address;
				document.getElementById("ogrn").value = res.ogrn;
				document.getElementById("inn").value = res.inn;
				document.getElementById("kpp").value = res.kpp;
				document.getElementById("bik").value = res.bik;
				document.getElementById("raschet").value = res.raschet;
				document.getElementById("korschet").value = res.korschet;
				document.getElementById("bank").value = res.bank;
				document.getElementById("dir_name").value = res.dir_name;
				document.getElementById("dogovor").value = res.dogovor;
				if(res.hide == "show"){
					$("#hide").html("<option value = \"show\" data-id=\"1\" selected=\"selected\">Активный</option>");
				}
				if(res.hide == "hide") {
					$("#hide").html("<option value = \"hide\" data-id=\"2\" selected=\"selected\">Не активный</option>");
				}
			},
			error: function(){
				alert('Ошибка - ппц');
			}
		});
	})
</script>
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
            .attr('name','order_zakaz['+total+'][product_id]')
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
           .attr('name','order_zakaz['+total+'][article]')
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
           .attr('name','order_zakaz['+total+'][price]')
		   .attr('class','form-control')
		   .attr('oninput', 'change_price('+total+')')
        )
		.append(
           $('<input>')
		   .attr('type', 'hidden')
           .attr('id','price_nalog_text_'+total)
           .attr('name','order_zakaz['+total+'][price_nalog]')		
        )
		.append(
           $('<input>')
		   .attr('type', 'hidden')
           .attr('id','sum_nalog_text_'+total)
           .attr('name','order_zakaz['+total+'][sum_nalog]')	   
        )
		.append(
			$('<input>')
			.attr('type', 'hidden')
			.attr('id','itogsum_nalog_text_'+total)
			.attr('name','order_zakaz['+total+'][itogsum_nalog]')
			.attr('class','form-control itogsum_nalog')
        )
		.append(
           $('<input>')
		   .attr('type', 'hidden')
           .attr('id','sum_beznalog_text_'+total)
           .attr('name','order_zakaz['+total+'][sum_beznalog]')
		   .attr('class','form-control sum_beznalog')		   
        )
		.append(
           $('<input>')
		   .attr('type', 'hidden')
           .attr('id','sum_beznalog_skidki_text_'+total)
           .attr('name','order_zakaz['+total+'][sum_beznalog_skidki]')
		   .attr('class','form-control beznalog_skidki')		   
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
           .attr('name','order_zakaz['+total+'][quantity]')
		   .attr('class','form-control orderquantity_'+total+'')
		   .attr('value', '1')
		   .attr('oninput', 'change_price('+total+')')
       ) 
		.append(
           $('<input>')
		   .attr('type', 'hidden')
           .attr('id','itog_quantity_text_'+total)
           .attr('name','order_zakaz['+total+'][itogquantity]')
		   .attr('class','form-control itogquantity_'+total+'')
       )
                              
    )
	.append (
       $('<td>')
       .attr('id','td_nalichie_'+total)
       .css({padding:'5px 10px'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
		   .attr('id','nalichie_text_'+total)
           .attr('name','order_zakaz['+total+'][order_nalichie]')
		   .attr('class','form-control')
		   .attr('readonly')
       )                             
    )
	.append (
       $('<td>')
       .attr('id','td_rezerv_'+total)
       .css({padding:'5px 10px'})
       .append(
           $('<input>')
            .css({padding:'5px 10px'})
			.attr('id','rezerv_text_'+total)
            .attr('name','order_zakaz['+total+'][order_rezerv]')
		    .attr('class','form-control')
			.attr('readonly')
       )                             
    )
	.append (
       $('<td>')
       .attr('id','td_discount_value_'+total)
       .css({padding:'5px 10px'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
		   .attr('type', 'number')
           .attr('id','discount_text_value_'+total)
           .attr('name','order_zakaz['+total+'][discount_value]')
		   .attr('class','form-control orderdiscount_value_'+total+'')
		   .attr('value', '0')
		   .attr('oninput', 'change_price('+total+')')
       )                             
                              
    )
	.append(
        $('<td>')
		.attr('id','td_type_discount_'+total)
        .css({padding:'5px 10px'})
        .append(
           $('<select>')
		   .attr('id','type_discount_text_'+total)
		   .attr('name','order_zakaz['+total+'][type_discount]')
		   .attr('class','form-control ordertypediscount_'+total+'')
		   .attr('onChange', 'change_price('+total+')')
				.append(
					$('<option>', {
						value: 1,
						text: '₽'
					})			
				 )
				.append(
					$('<option>', {
						value: 2,
						text: '%'
					})			
				 )
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
           .attr('name','order_zakaz['+total+'][discount]')
		   .attr('class','form-control orderdiscount_'+total+'')
		   .attr('value', '0')
		   .attr('oninput', 'change_price('+total+')')
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_price_discount_'+total)
       .css({padding:'5px 10px'})
       .append(
           $('<input>')
           .css({padding:'5px 10px'})
		   .attr('type', 'number')
           .attr('id','price_discount_text_'+total)
           .attr('name','order_zakaz['+total+'][price_discount]')
		   .attr('class','form-control orderpricediscount_'+total+'')
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
           .attr('name','order_zakaz['+total+'][discount_amount]')
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
           .attr('name','order_zakaz['+total+'][itog]')
		   .attr('class','form-control itog_price_'+total+' td_itog')
		   .attr('disabled', 'disabled')
       )
		.append(
           $('<input>')
		   .attr('type', 'hidden')
           .css({padding:'5px 10px'})
           .attr('id','sumweight_text_'+total)
           .attr('name','order_zakaz['+total+'][sumweight]')
		   .attr('class','form-control sumweight_'+total+' td_sumweight')
		   .attr('disabled', 'disabled')
       )
	   .append(
           $('<input>')
		   .attr('type', 'hidden')
           .css({padding:'5px 10px'})
           .attr('id','sumvolume_text_'+total)
           .attr('name','order_zakaz['+total+'][sumvolume]')
		   .attr('class','form-control sumvolume_'+total+' td_sumvolume')
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
	var comp_id = $( ".companys" ).val();
	var nalog = $('select[name=nds]').val();
	var skidka = 0;
	
		$.ajax({
			url: adminpath + "/order/productprice",
			data: {id: id, comp_id: comp_id},
			type: 'GET',
			dataType: 'json',
			success: function(res){
			    
				if(res.result7){
					var price_comp = Math.round(res.result2 - (res.result2/1.2), 0) * 6;
					var price_opt = price_comp - ((price_comp/100) * res.result7);
					var opt = Math.round(price_opt / 6) * 6;
					
					var skidka = res.result2 - opt;
					var price_discount = res.result2 - skidka;
					if(nalog == 1) { nalog = 20; var sumnalog = (opt * 0.2 / 1.2 ) * 1; var sumnalog = sumnalog.toFixed(2); var sumbeznalog = (opt / 1.2 ) * 1; var sumbeznalog = sumbeznalog.toFixed(2);}
					if(nalog == 2) { nalog = 0; }

					$("#td_nalichie_"+total).html("<input name=\"order_zakaz["+total+"][order_nalichie]\" value=\""+res.result3+" шт.\" class=\"form-control\" readonly />");
					$("#td_rezerv_"+total).html("<input name=\"order_zakaz["+total+"][order_rezerv]\" value=\""+res.result4+" шт.\" class=\"form-control\" readonly />");
					$("#td_article_"+total).html("<input name=\"order_zakaz["+total+"][article]\" type=\"text\" value=\""+res.result1+"\" class=\"form-control\" placeholder=\"артикул товара\" readonly />");
					$("#td_price_"+total).html("<input name=\"order_zakaz["+total+"][price]\"id=\"price_text_"+total+"\" type=\"number\" value=\""+res.result2+"\" class=\"form-control orderprice_"+total+"\" placeholder=\"0\" oninput=\"change_price("+total+")\" /><input type=\"hidden\" id=\"price_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][price_nalog]\" class=\"form-control prod_nalog\" value=\""+nalog+"\"><input type=\"hidden\" id=\"sum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_nalog]\" class=\"form-control sum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"itogsum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][itogsum_nalog]\" class=\"form-control itogsum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"sum_beznalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog]\" class=\"form-control sum_beznalog\" value=\""+sumbeznalog+"\"><input type=\"hidden\" id=\"sum_beznalog_skidki_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog_skidki]\" class=\"form-control beznalog_skidki\" value=\""+res.result2+"\">");				
					$("#td_quantity_"+total).html("<input name=\"order_zakaz["+total+"][quantity]\"id=\"quantity_text_"+total+"\" type=\"number\" value=\"1\" class=\"form-control orderquantity_"+total+"\" oninput=\"change_price("+total+")\" /><input type=\"hidden\" id=\"itog_quantity_text_"+total+"\" name=\"order_zakaz["+total+"][itogquantity]\" class=\"form-control itogquantity_"+total+"\">");				
					$("#td_discount_value_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_value]\" type=\"number\" value=\""+res.result7+"\" class=\"form-control orderdiscount_value_"+total+"\" id=\"discount_text_value_"+total+"\" oninput=\"change_price("+total+")\" />");
					$("#td_discount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount]\" type=\"number\" value=\""+skidka+"\" class=\"form-control orderdiscount_"+total+"\" id=\"discount_text_"+total+"\" oninput=\"change_price("+total+")\" />");
					$("#td_price_discount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][price_discount]\" type=\"number\" value=\""+price_discount+"\" class=\"form-control orderpricediscount_"+total+"\" id=\"price_discount_text_"+total+"\" oninput=\"change_price("+total+")\" />");					
					$("#td_discount_amount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_amount]\" type=\"hidden\" value=\""+skidka+"\" class=\"form-control orderdiscount_amount_"+total+"\" id=\"discount_amount_text_"+total+"\" oninput=\"change_price("+total+")\" /><input style=\"padding: 5px 10px;\" type=\"number\" id=\"itog_discount_text_"+total+"\" value=\""+skidka+"\" name=\"order_zakaz["+total+"][itogdiscount]\" class=\"form-control td_amount itogdiscount_"+total+"\">");					
					$("#td_type_discount_"+total).html("<select id=\"type_discount_text_"+total+"\" name=\"order_zakaz["+total+"][type_discount]\" class=\"form-control ordertypediscount_"+total+"\" onchange=\"change_price("+total+")\"><option value=\"2\">%</option><option value=\"1\">₽</option></select>");
					$("#td_itog_"+total).html("<input style=\"padding: 5px 10px;\" id=\"itog_text_"+total+"\" name=\"order_zakaz["+total+"][itog]\" class=\"form-control itog_price_"+total+" td_itog\" value=\""+opt+"\" readonly><input style=\"padding: 5px 10px;\" id=\"sumweight_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][sumweight]\" value=\""+res.result5+"\" class=\"form-control sumweight_"+total+" td_sumweight\" readonly><input style=\"padding: 5px 10px;\" id=\"itog_sumweight_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][itog_sumweight]\" value=\""+res.result5+"\" class=\"form-control itog_sumweight_"+total+" td_itog_sumweight\" readonly><input style=\"padding: 5px 10px;\" id=\"sumvolume_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][sumvolume]\" value=\""+res.result6+"\" class=\"form-control sumvolume_"+total+" td_sumvolume\" readonly><input style=\"padding: 5px 10px;\" id=\"itog_sumvolume_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][itog_sumvolume]\" value=\""+res.result6+"\" class=\"form-control itog_sumvolume_"+total+" td_itog_sumvolume\" readonly>");
					
				}else{
					if(nalog == 1) { nalog = 20; var sumnalog = (res.result2 * 0.2 / 1.2 ) * 1; var sumnalog = sumnalog.toFixed(2); var sumbeznalog = (res.result2 / 1.2 ) * 1; var sumbeznalog = sumbeznalog.toFixed(2);}
					if(nalog == 2) { nalog = 0; }
					if(nalog == "") { nalog = 0; }
					$("#td_nalichie_"+total).html("<input name=\"order_zakaz["+total+"][order_nalichie]\" value=\""+res.result3+" шт.\" class=\"form-control\" readonly />");
					$("#td_rezerv_"+total).html("<input name=\"order_zakaz["+total+"][order_rezerv]\" value=\""+res.result4+" шт.\" class=\"form-control\" readonly />");
					$("#td_article_"+total).html("<input name=\"order_zakaz["+total+"][article]\" type=\"text\" value=\""+res.result1+"\" class=\"form-control\" placeholder=\"артикул товара\" readonly />");
					$("#td_price_"+total).html("<input name=\"order_zakaz["+total+"][price]\"id=\"price_text_"+total+"\" type=\"number\" value=\""+res.result2+"\" class=\"form-control orderprice_"+total+"\" placeholder=\"0\" oninput=\"change_price("+total+")\" /><input type=\"hidden\" id=\"price_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][price_nalog]\" class=\"form-control prod_nalog\" value=\""+nalog+"\"><input type=\"hidden\" id=\"sum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_nalog]\" class=\"form-control sum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"itogsum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][itogsum_nalog]\" class=\"form-control itogsum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"sum_beznalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog]\" class=\"form-control sum_beznalog\" value=\""+sumbeznalog+"\"><input type=\"hidden\" id=\"sum_beznalog_skidki_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog_skidki]\" class=\"form-control beznalog_skidki\" value=\""+res.result2+"\">");				
					
					$("#td_quantity_"+total).html("<input name=\"order_zakaz["+total+"][quantity]\"id=\"quantity_text_"+total+"\" type=\"number\" value=\"1\" class=\"form-control orderquantity_"+total+"\" oninput=\"change_price("+total+")\" /><input type=\"hidden\" id=\"itog_quantity_text_"+total+"\" name=\"order_zakaz["+total+"][itogquantity]\" class=\"form-control itogquantity_"+total+"\">");				
					$("#td_discount_value_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_value]\" type=\"number\" value=\"0\" class=\"form-control orderdiscount_value_"+total+"\" id=\"discount_text_value_"+total+"\" oninput=\"change_price("+total+")\" />");
					$("#td_discount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount]\" type=\"number\" value=\"0\" class=\"form-control orderdiscount_"+total+"\" id=\"discount_text_"+total+"\" oninput=\"change_price("+total+")\" />");
					$("#td_price_discount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][price_discount]\" type=\"number\" value=\""+res.result2+"\" class=\"form-control orderpricediscount_"+total+"\" id=\"price_discount_text_"+total+"\" oninput=\"change_price("+total+")\" />");					
					$("#td_discount_amount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_amount]\" type=\"hidden\" value=\"0\" class=\"form-control orderdiscount_amount_"+total+"\" id=\"discount_amount_text_"+total+"\" oninput=\"change_price("+total+")\" /><input style=\"padding: 5px 10px;\" type=\"number\" id=\"itog_discount_text_"+total+"\" value=\"0\" name=\"order_zakaz["+total+"][itogdiscount]\" class=\"form-control td_amount itogdiscount_"+total+"\">");					
					$("#td_type_discount_"+total).html("<select id=\"type_discount_text_"+total+"\" name=\"order_zakaz["+total+"][type_discount]\" class=\"form-control ordertypediscount_"+total+"\" onchange=\"change_price("+total+")\"><option value=\"2\">%</option><option value=\"1\">₽</option></select>");
					
					
					$("#td_itog_"+total).html("<input style=\"padding: 5px 10px;\" id=\"itog_text_"+total+"\" name=\"order_zakaz["+total+"][itog]\" class=\"form-control itog_price_"+total+" td_itog\" value=\""+res.result2+"\" readonly><input style=\"padding: 5px 10px;\" id=\"sumweight_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][sumweight]\" value=\""+res.result5+"\" class=\"form-control sumweight_"+total+" td_sumweight\" readonly><input style=\"padding: 5px 10px;\" id=\"itog_sumweight_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][itog_sumweight]\" value=\""+res.result5+"\" class=\"form-control itog_sumweight_"+total+" td_itog_sumweight\" readonly><input style=\"padding: 5px 10px;\" id=\"sumvolume_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][sumvolume]\" value=\""+res.result6+"\" class=\"form-control sumvolume_"+total+" td_sumvolume\" readonly><input style=\"padding: 5px 10px;\" id=\"itog_sumvolume_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][itog_sumvolume]\" value=\""+res.result6+"\" class=\"form-control itog_sumvolume_"+total+" td_itog_sumvolume\" readonly>");
				}
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
	var type_discount = document.getElementById("type_discount_text_"+total+"").value;	
	var discount_value = document.getElementById("discount_text_value_"+total+"").value;
	var vid = document.getElementById("vid").value;
	var cnds = document.getElementById("cnds").value; // НДС 1-20%, 2-0, ""-0

	
	if(type_discount == 1) { //₽
		if(vid == ""){
			var itogprice = price - discount_value;
			var nalog = 0;
		}
		if(vid == 3){ //Физ. лицо
			var itogprice = price - discount_value;
			var nalog = 0;
		}
		if(vid == 4){ //Юр. лицо
			if(cnds == "") {
				var itogprice = price - discount_value;
				var nalog = 0;
			}
			if(cnds == 1) {
				var price_nds = Math.round(price - (price/1.2), 0) * 6;
				var discountprice = price_nds - discount_value;
				var itogprice = Math.round(discountprice / 6) * 6;
				var nalog = 20;
			}
			if(cnds == 2) {
				var itogprice = price - discount_value;
				var nalog = 0;
			}
		}		
		
	}
	if(type_discount == 2) { //%
		if(vid == ""){
			var itogprice = price - ((price/100) * discount_value);
			var nalog = 0;
		}
		if(vid == 3){ //Физ. лицо
			var itogprice = price - ((price/100) * discount_value);
			var nalog = 0;
		}
		if(vid == 4){ //Юр. лицо
			if(cnds == "") {
				var itogprice = price - ((price/100) * discount_value);
				var nalog = 0;
			}
			if(cnds == 1) {
				var price_nds = Math.round(price - (price/1.2), 0) * 6;
				var discountprice = price_nds - ((price_nds/100) * discount_value);
				var itogprice = Math.round(discountprice / 6) * 6;
				var nalog = 20;
			}
			if(cnds == 2) {
				var itogprice = price - ((price/100) * discount_value);
				var nalog = 0;
			}
		}		
		
	}
	
	var itogsumma = itogprice * kolvo;
	
	// Значение
	$("#td_discount_value_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_value]\" type=\"number\" value=\""+discount_value+"\" class=\"form-control orderdiscount_value_"+total+"\" id=\"discount_text_value_"+total+"\" oninput=\"change_price("+total+")\" />");
	
	// Скидка
	var skidka = price - itogprice;
	document.getElementById("discount_text_"+total+"").value = skidka;
	$("#td_discount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount]\" type=\"number\" value=\""+skidka+"\" class=\"form-control orderdiscount_"+total+"\" id=\"discount_text_"+total+"\" oninput=\"change_price("+total+")\" />");
		
	
	// Цена со скидкой
	document.getElementById("itog_text_"+total+"").value = itogprice;
	$("#td_price_discount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][price_discount]\" type=\"number\" value=\""+itogprice+"\" class=\"form-control orderpricediscount_"+total+"\" id=\"price_discount_text_"+total+"\" oninput=\"change_price("+total+")\" />");					
		
	
	// Сумма скидки
	var summaskidka = (price - itogprice) * kolvo;
	document.getElementById("itog_discount_text_"+total+"").value = summaskidka;
	$("#td_discount_amount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_amount]\" type=\"hidden\" value=\""+skidka+"\" class=\"form-control orderdiscount_amount_"+total+"\" id=\"discount_amount_text_"+total+"\" oninput=\"change_price("+total+")\" /><input style=\"padding: 5px 10px;\" type=\"number\" id=\"itog_discount_text_"+total+"\" value=\""+summaskidka+"\" name=\"order_zakaz["+total+"][itogdiscount]\" class=\"form-control td_amount itogdiscount_"+total+"\">");
		
	
	// Сумма
	document.getElementById("itog_text_"+total+"").value = itogprice * kolvo;
	$("#td_itog_"+total).html("<input style=\"padding: 5px 10px;\" id=\"itog_text_"+total+"\" name=\"order_zakaz["+total+"][itog]\" class=\"form-control itog_price_"+total+" td_itog\" value=\""+itogsumma+"\"><input style=\"padding: 5px 10px;\" id=\"sumweight_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][sumweight]\" value=\""+res.result5+"\" class=\"form-control sumweight_"+total+" td_sumweight\" readonly><input style=\"padding: 5px 10px;\" id=\"itog_sumweight_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][itog_sumweight]\" value=\""+res.result5+"\" class=\"form-control itog_sumweight_"+total+" td_itog_sumweight\" readonly><input style=\"padding: 5px 10px;\" id=\"sumvolume_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][sumvolume]\" value=\""+res.result6+"\" class=\"form-control sumvolume_"+total+" td_sumvolume\" readonly><input style=\"padding: 5px 10px;\" id=\"itog_sumvolume_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][itog_sumvolume]\" value=\""+res.result6+"\" class=\"form-control itog_sumvolume_"+total+" td_itog_sumvolume\" readonly>");
		
	// Цена
	if(nalog == 20) { var sumnalog = (itogprice * 0.2 / 1.2 ) * 1; var sumnalog = sumnalog.toFixed(2); var sumbeznalog = (itogprice / 1.2 ) * 1; var sumbeznalog = sumbeznalog.toFixed(2); }
	if(nalog == 0) { var sumnalog = 0; var sumbeznalog = 0; }
	document.getElementById("price_text_"+total+"").value = itogprice;
	
	var itogsumnalog = kolvo * sumnalog;
	var itogsumnalog = itogsumnalog.toFixed(2);		
	document.getElementById("itogsum_nalog_text_"+total+"").value = itogsumnalog;
	
	var sumbeznalog = (itogprice - sumnalog) * kolvo;
	var sumbeznalog = sumbeznalog.toFixed(2);
	document.getElementById("sum_beznalog_text_"+total+"").value = sumbeznalog;
	
	$("#td_price_"+total).html("<input name=\"order_zakaz["+total+"][price]\"id=\"price_text_"+total+"\" type=\"number\" value=\""+itogprice+"\" class=\"form-control orderprice_"+total+"\" placeholder=\"0\" oninput=\"change_price("+total+")\" /><input type=\"hidden\" id=\"price_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][price_nalog]\" class=\"form-control prod_nalog\" value=\""+nalog+"\"><input type=\"hidden\" id=\"sum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_nalog]\" class=\"form-control sum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"itogsum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][itogsum_nalog]\" class=\"form-control itogsum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"sum_beznalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog]\" class=\"form-control sum_beznalog\" value=\""+sumbeznalog+"\"><input type=\"hidden\" id=\"sum_beznalog_skidki_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog_skidki]\" class=\"form-control beznalog_skidki\" value=\""+price+"\">");				
	
	var amount = 0;
	$('.td_amount').each(function(){
		amount += parseFloat($(this).val());
	})
	$(".amount").html(""+amount+"");
	
	var itogweight = weight * kolvo;
	document.getElementById("itog_sumweight_text_"+total+"").value = itogweight;
	var itogvolume = volume * kolvo;
	document.getElementById("itog_sumvolume_text_"+total+"").value = itogvolume;
	
	var sum = 0;
	$('.td_itog').each(function(){
		sum += parseFloat($(this).val());
	})
	var sum = sum.toFixed(2);
	$(".sum").html(""+sum+"");
	
	//	
	var nalogitogo = 0;
	$('.itogsum_nalog').each(function(){
		nalogitogo += parseFloat($(this).val());
	})
	var nalogitogo = nalogitogo.toFixed(2);
	$(".sum_nalog_itogo").html(""+nalogitogo+"");
	
	//
	var beznalog_skidki = 0;
	$('.beznalog_skidki').each(function(){
		beznalog_skidki += parseFloat($(this).val());
		console.log(typeof $(this).val());
	})
	var beznalog = sum - nalogitogo;
	var beznalog = beznalog.toFixed(2);
	var beznalog_skidki = beznalog_skidki.toFixed(2);
	$(".sum_beznalog_skidki").html(""+beznalog_skidki+"");
	$(".sum_beznalog_itogo").html(""+beznalog+"");
	
	//	
	var itogqty = 0;
	$('.itog_qty').each(function(){
		itogqty += parseFloat($(this).val());
	})
	$(".itogqty").html(""+itogqty+"");
	
	var itogsweight = 0;
	$('.td_itog_sumweight').each(function(){
		itogsweight += parseFloat($(this).val());
	})
	var itogsweight = itogsweight.toFixed(2);
	$(".sum_weight").html(""+itogsweight+"");

	var itogsvolume = 0;
	$('.td_itog_sumvolume').each(function(){
		itogsvolume += parseFloat($(this).val());
	})
	var itogsvolume = itogsvolume.toFixed(2);
	$(".sum_volume").html(""+itogsvolume+"");
		
	console.log(summaskidka);	
}
</script>