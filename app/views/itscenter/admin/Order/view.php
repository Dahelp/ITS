<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Заказ № <?=$order['inv']; ?></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>/order">Список заказов</a></li>
			  <li class="breadcrumb-item active">Заказ № <?=$order['inv'];?></li>
			</ol>	
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
			<div class="col-md-12 menu_btn">				
				<div class="order_status">
				<span class="glyphicon form-control-feedback" aria-hidden="true">Статус заказа</span>
					<form action="<?=ADMIN;?>/order/change?id=<?=$order['id'];?>" method="post" data-toggle="validator">
						<div class="select_status">							
							<select class="form-control" name="status">
								<?php $status = \R::findAll('order_status'); 
								foreach($status as $item) { ?>
									<option value="<?=$item->id?>" <?php if($order['status'] == $item->id) { ?>selected<?php } ?>><?=$item->status_name?></option>
								<?php } ?>
							</select>
						</div>
						<div class="select_btn">
							<button type="submit" class="btn btn-primary">Выполнить</button>
						</div>
					</form>
				</div>
				<div class="order_manageg">
					<span class="glyphicon form-control-feedback" aria-hidden="true">Менеджер</span>
					<form action="<?=ADMIN;?>/order/manager?id=<?=$order['id'];?>" method="post" data-toggle="validator">
						<div class="select_status">
							<select class="form-control" name="admin_id">
								<?php $managers = \R::findAll('user', 'groups = ?', [2]); 
								if($managers) { ?><option value="0">Присвоить менеджера</option><?php }
								foreach($managers as $manager) { ?>
									<option value="<?=$manager->id?>" <?php if($order['admin_id'] == $manager->id) { ?>selected<?php } ?>><?=$manager->name?></option>
								<?php } ?>
							</select>
						</div>
						<div class="select_btn">
							<button type="submit" class="btn btn-primary">Выполнить</button>
						</div>
					</form>
				</div>
				<div class="order_seller">
					<span class="glyphicon form-control-feedback" aria-hidden="true">Продавец</span>
					<form action="<?=ADMIN;?>/order/seller?id=<?=$order['id'];?>" method="post" data-toggle="validator">
						<div class="select_status">
							<select class="form-control" name="seller">
								<?php $comps = \R::findAll('company', 'tip = ?', [0]); 
								if($comps) { ?><option value="0">Присвоить продавца</option><?php }
								foreach($comps as $comp) { ?>
									<option value="<?=$comp->id?>" <?php if($order['seller'] == $comp->id) { ?>selected<?php } ?>><?=$comp->comp_short_name?></option>
								<?php } ?>
							</select>
						</div>
						<div class="select_btn">
							<button type="submit" class="btn btn-primary">Выполнить</button>
						</div>
					</form>
				</div>
				<?php if($order['seller']) { ?>
				<div class="order_schet">
					<span class="glyphicon form-control-feedback" aria-hidden="true">Счёт</span>
					<a class="btn btn-danger" href="<?=ADMIN?>/order/pdfscore?id=<?=$order["id"]?>"><i class="fas fa-file-pdf"></i></a>
				</div>
				<?php } ?>
			</div>
			<?php if($order['status'] < 3) { ?>
				<form action="<?=ADMIN;?>/order/view?id=<?=$order['id'];?>" method="post" data-toggle="validator">
			<?php } ?>
			<div class="row">
				<div class="col-md-8">
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">История заказа № <?=$order['inv'];?> от <?=$order['date'];?></h3>
						</div>
						<!-- /.card-header -->
						<div class="card-body">					
							<table class="table table-bordered">
									<tbody>										
										<tr>
											<td>Дата изменения</td>
											<td><?=$order['update_at'];?></td>
										</tr>
										<tr>
											<td>Кол-во позиций в заказе</td>
											<td><?=count($order_products);?></td>
										</tr>
										<tr>
											<td>Сумма заказа</td>
											<td><?=$curr['symbol_left'];?> <?=$order['sum'];?> <?=$curr['symbol_right'];?></td>
										</tr>																		
										<tr>
											<td>Статус</td>
											<td><?=$order['status_name'];?></td>
										</tr>
										<tr>
											<td>Комментарий</td>
											<td><?=$order['note'];?></td>
										</tr>
									</tbody>
							</table>
						</div>
					</div>
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">Доставка</h3>
						</div>
						<!-- /.card-header -->
						<div class="card-body">
							<?php 
								$transportcompany = \R::findOne('transport_company', 'id = ?', [$order['transport_id']]);
								$branchoffice = \R::findOne('branch_office', 'branch_id = ?', [$order['branch_id']]);
								$cities = \R::findOne('cities', 'city_id = ?', [$order['city_id']]);
								$city = \R::getAll("SELECT * FROM cities WHERE city_id != '".$cities['city_id']."' ORDER BY city_name");
							?>
							<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="dostavka_id">Способ получения</label>
							<div class="col-sm-9">
								<select name="dostavka_id" class="form-control" onclick="anotherCity(this)">
									<option value= "<?=$order['dostavka_id'];?>" selected="selected"><?=$order['dostavkaname'];?></option>
									<?php $dostavka = \R::getAll("SELECT * FROM dostavka WHERE hide='show' AND id != '".$order['dostavka_id']."'");
										foreach($dostavka as $ds){ ?>
											<option value="<?=$ds["id"]?>"><?=$ds["name"]?></option>
									<?php } ?>
								</select>
							</div>
                        </div>
						<div id="another_transport" style="display:<?php if($order['dostavka_id'] == 2) { ?>block<?php }else{?>none<?php } ?>">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="transport_id">Транспортная компания</label>
								<div class="col-sm-9">
								<select name="transport_id" class="form-control">
									<option value= "<?=$transportcompany['id'];?>" selected="selected"><?=$transportcompany['name'];?></option>
									<?php $transport = \R::getAll("SELECT * FROM transport_company WHERE hide='show'");
										foreach($transport as $ts){ ?>
											<option value="<?=$ts["id"]?>"><?=$ts["name"]?></option>
									<?php } ?>
								</select>
								</div>
							</div>
						</div>
						<div id="another_sklad" style="display:<?php if($order['dostavka_id'] == 1) { ?>block<?php }else{?>none<?php } ?>">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="branch_id">Самовывоз</label>
								<div class="col-sm-9">
								<select name="branch_id" class="form-control">
									<option value= "<?=$branchoffice['branch_id'];?>" selected="selected"><?=$branchoffice['branch_name'];?></option>
									<?php $branch = \R::getAll("SELECT * FROM branch_office WHERE hide='show' AND branch_id !='".$branchoffice['branch_id']."'");
										foreach($branch as $br){ ?>
											<option value="<?=$br["branch_id"]?>"><?=$br["branch_name"]?></option>
										<?php } ?>
								</select>
								</div>
							</div>
						</div>
						<div id="another_city" style="display:<?php if($order['dostavka_id'] == 2 OR $order['dostavka_id'] == 3) { ?>block<?php }else{?>none<?php } ?>">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="city_id">Город доставки</label>
								<div class="col-sm-9">
								<select name="city_id" class="form-control">
									<option value= "<?=$cities['city_id'];?>" selected="selected"><?=$cities['city_name'];?></option>
									<?php foreach($city as $st){ ?>
										<option value="<?=$st["city_id"]?>"><?=$st["city_name"]?></option>
									<?php } ?>
								</select>
								</div>
							</div>
						</div>
						<div id="another_adress" style="display:<?php if($order['dostavka_id'] == 3) { ?>block<?php }else{?>none<?php } ?>">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="address">Адрес доставки</label>
								<div class="col-sm-9">
									<textarea name="address" class="form-control"><?=$order['address'];?></textarea>
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
						
							
						</div>
					</div>
				</div>
				
				
				
				<div class="col-md-4">					
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">Клиент</h3>
						</div>
						<!-- /.card-header -->
						<div class="card-body">
							<table class="table table-bordered">
								<tbody>
									<tr>
										<td>Вид клиента</td>
										<td>
											<?php if($order["groups"] == "3") { echo "Физическое лицо"; } ?>
											<?php if($order["groups"] == "4") { echo "Юридическое лицо"; } ?>
										</td>
									</tr>
									<?php if($order["groups"] == "4") { ?>									
									<?php $comporder = \R::getAll("SELECT company.id, company.comp_short_name FROM company, user WHERE user.comp_id = company.id AND user.id = '".$order["user_id"]."'"); ?>
									<?php $compinf = \R::findOne('company', 'user_id = ?', [$order["user_id"]]); ?>
										<tr>
											<td>Компания</td>											
											<td>
												<?php if($comporder) { ?>
													<select name="comp_id" class="form-control">
														<option value="0">Не выбрана</option>
														<?php foreach($comporder as $comp_order) { ?>
															<option value="<?=$comp_order["id"];?>" <?php if($comp_order["id"] == $order["comp_id"]) { ?>selected="selected"<?php } ?>><?=$comp_order["comp_short_name"];?></option>
														<?php } ?>
													</select>
														<input type="hidden" class="compid_text" value="<?=$order["comp_id"]?>" />
													<?php }else{ ?>Не добавлена (<a target="_blank" href="<?=ADMIN?>/company/add">Добавить</a>)
														
													<?php } ?>
											</td>
										</tr>
										<tr>
											<td>Система налогообложения</td>											
											<td>
												<?php if($compinf->nds==1) { ?>с НДС<?php } ?>
												<?php if($compinf->nds==2) { ?>без НДС<?php } ?>
											</td>
										</tr>
										<tr>
											<td>Условия поставки</td>											
											<td>
												<?php if($compinf->dogovor==1) { $dogovor = "Договор"; } ?>
												<?php if($compinf->dogovor==2) { $dogovor = "Счёт-договор"; } ?>
												<?=$dogovor?>
											</td>
										</tr>
									<?php }else { ?>
										<input type="hidden" class="compid_text" value="<?=$order["comp_id"]?>" />
									<?php } ?>
									<tr>
										<td>Имя заказчика</td>
										<td><a target="_blank" href="<?=ADMIN?>/user/edit?id=<?=$order["user_id"];?>"><?=$order['name'];?></a></td>
									</tr>
									<tr>
										<td>Телефон</td>
										<td><?=$order['telefon'];?></td>
									</tr>
									<tr>
										<td>E-mail</td>
										<td><a href="<?=ADMIN?>/mailbox/answer?email=<?=$order['email'];?>&subject=Сделан заказ <?=$order['inv']?> на сайте <?=$namecomp?>"><?=$order['email'];?></a></td>
									</tr>														
								</tbody>
							</table>
						</div>
					</div>
				</div>		
			</div>
			<div class="card">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Товары</h3>
				</div><!-- /.card-header -->
				<div class="card-body">
                    <div class="box-body table-responsive">
						<table id="table_container">							
								<tr>
									<td style="width:2%;"><strong>#</strong></td><td style="width:26%;padding: 0 0 0 10px;"><strong>Наименование</strong></td><td style="width:5%;padding: 0 0 0 10px;"><strong>Артикул</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Цена</strong></td><td style="width:6%;padding: 0 0 0 10px;"><strong>Количество</strong></td><td style="width:6%;padding: 0 0 0 10px;"><strong>Наличие</strong></td><td style="width:6%;padding: 0 0 0 10px;"><strong>Резерв</strong></td><td style="width:5%;padding: 0 0 0 10px;"><strong>Значение</strong></td><td style="width:5%;padding: 0 0 0 10px;"></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Скидка</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Цена со скидкой</strong></td><td style="width:8%;padding: 0 0 0 10px;"><strong>Сумма скидки</strong></td><td style="width:10%;padding: 0 0 0 10px;"><strong>Сумма</strong></td>
									<?php if($order['status'] < 3) { ?>
										<td style="width:6%;"></td>
									<?php } ?>	
								</tr>							
							<?php $k = 1; foreach($order_products as $product):	?>
							<?php 
								$item = \R::findOne('product', 'id = ?', [$product->product_id]);
								$rezerv = \R::findOne('in_stock', 'product_id = ? AND branch_id = ?', [$product->product_id, 9]);
								$qty += $product->qty;
							?>
							<?php $itog_price += ($product->price * $product->qty) - ( $product->discount * $product->qty ); ?>
							<?php $itog_summa = ($product->price * $product->qty) - ( $product->discount * $product->qty ); ?>
							<?php $sumweight += $item->weight * $product->qty; ?>
							<?php $sumvolume += $item->volume * $product->qty; ?>
							<?php $sumnalog = (($product->price * 0.2 / 1.2 ) * $product->qty) - ( $product->discount * $product->qty ); $sumnalog = round($sumnalog, 2); ?>
							<?php $itog_sumnalog += (($product->price * 0.2 / 1.2 ) * $product->qty) - ( $product->discount * $product->qty ); $itog_sumnalog = round($itog_sumnalog, 2); ?>
							<?php $nalog = (($product->price * 0.2 / 1.2 ) * $product->qty); $nalog = round($nalog, 2); ?>
							<?php $sumbeznalog = $itog_price - $itog_sumnalog; $sumbeznalog = round($sumbeznalog, 2); ?>
							<?php $itogonalog = (($product->price * $product->qty) - $nalog) - ( $product->discount * $product->qty ); $itogonalog = round($itogonalog, 2); ?>
							<?php $itogosummanalog += (($product->price * $product->qty) - $nalog) - ( $product->discount * $product->qty ); $itogosummanalog = round($itogosummanalog, 2); ?>
							<?php $itog_amount += ($product->discount * $product->qty); ?>
							
							<tr id="tr_order_<?=$k?>" style="line-height: 20px;">			
								<td><?=$k?></td>								
								<td id="td_product_<?=$k?>" style="padding: 5px 10px;">
									<select class="form-control select_product searchproduct_<?=$k?>" name="order_zakaz[<?=$k?>][product_id]" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
										<option value="<?=$product->product_id;?>" /><?=$product->name;?></option>
									</select>
								</td>
								<td id="td_article_<?=$k?>" style="padding: 5px 10px;">
									<input name="order_zakaz[<?=$k?>][article]" type="text" value="<?=$product->article;?>" class="form-control" placeholder="артикул товара" readonly>
								</td>
								<td id="td_price_<?=$k?>" style="padding: 5px 10px;">
									<input name="order_zakaz[<?=$k?>][price]" id="price_text_<?=$k?>" type="number" value="<?=$product->price;?>" class="form-control orderprice_<?=$k?>" placeholder="0" oninput="change_price(<?=$k?>)" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
									<input style="padding: 5px 10px;" type="hidden" id="price_nalog_text_<?=$k?>" name="order_zakaz[<?=$k?>][price_nalog]" class="form-control prod_nalog" value="" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
									<input style="padding: 5px 10px;" type="hidden" id="sum_nalog_text_<?=$k?>" name="order_zakaz[<?=$k?>][sum_nalog]" class="form-control sum_nalog" value="<?=$sumnalog?>" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
									<input style="padding: 5px 10px;" type="hidden" id="sum_beznalog_text_<?=$k?>" name="order_zakaz[<?=$k?>][sum_beznalog]" class="form-control sum_beznalog" value="<?=$itogonalog?>" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
									<input name="order_zakaz[<?=$k?>][weight]" type="hidden" id="weight_text_<?=$k?>" value="<?=$item->weight;?>" class="form-control weight_<?=$k?>" placeholder="0" oninput="change_price(<?=$k?>)" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
									<input name="order_zakaz[<?=$k?>][volume]" type="hidden" id="volume_text_<?=$k?>" value="<?=$item->volume;?>" class="form-control volume_<?=$k?>" placeholder="0" oninput="change_price(<?=$k?>)" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
								</td>
								<td id="td_quantity_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="number" id="quantity_text_<?=$k?>" name="order_zakaz[<?=$k?>][quantity]" class="form-control itog_qty orderquantity_<?=$k?>" value="<?=$product->qty?>" oninput="change_price(<?=$k?>)" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
									<input type="hidden" id="itog_quantity_text_<?=$k?>" name="order_zakaz[<?=$k?>][itogquantity]" class="form-control itogquantity_<?=$k?>">
								</td>
								
								<td id="td_nalichie_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="text" name="order_nalichie" class="form-control" value="<?=$item->quantity;?> шт." readonly>
								</td>
								<td id="td_rezerv_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="text" name="order_rezerv" class="form-control" value="<?=$rezerv["quantity"];?> шт." readonly>
								</td>
								
								<td id="td_discount_value_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="number" id="discount_text_value_<?=$k?>" name="order_zakaz[<?=$k?>][discount_value]" class="form-control orderdiscount_value_<?=$k?>" value="<?=$product->discount_value;?>" oninput="change_price(<?=$k?>)" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
								</td>
								<td id="td_type_discount_<?=$k?>" style="padding: 5px 10px;">
									<select id="type_discount_text_<?=$k?>" name="order_zakaz[<?=$k?>][type_discount]" class="form-control ordertypediscount_<?=$k?>" onchange="change_price(<?=$k?>)">
										<option value="2">%</option>
										<option value="1">₽</option>
									</select>
								</td>
								<td id="td_discount_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="number" id="discount_text_<?=$k?>" name="order_zakaz[<?=$k?>][discount]" class="form-control orderdiscount_<?=$k?>" value="<?=$product->discount;?>" oninput="change_price(<?=$k?>)" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
								</td>
								<td id="td_price_discount_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="number" id="price_discount_text_<?=$k?>" name="order_zakaz[<?=$k?>][price_discount]" class="form-control orderpricediscount_<?=$k?>" value="<?=$product->price_discount;?>" oninput="change_price(<?=$k?>)" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
								</td>
								<td id="td_discount_amount_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="text" id="discount_amount_text_<?=$k?>" name="order_zakaz[<?=$k?>][discount_amount]" class="form-control td_amount orderdiscount_amount_<?=$k?>" value="<?=$product->discount_amount;?>" oninput="change_price(<?=$k?>)" readonly>
								</td>								
								<td id="td_itog_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" id="itog_text_<?=$k?>" name="order_zakaz[<?=$k?>][itog]" value="<?=$itog_summa;?>" class="form-control itog_price_<?=$k?> td_itog" readonly>
									<input style="padding: 5px 10px;" id="sumweight_text_<?=$k?>" type="hidden" name="order_zakaz[<?=$k?>][sumweight]" value="<?=$sumweight;?>" class="form-control sumweight_<?=$k?> td_sumweight" readonly>
									<input style="padding: 5px 10px;" id="sumvolume_text_<?=$k?>" type="hidden" name="order_zakaz[<?=$k?>][sumvolume]" value="<?=$sumvolume;?>" class="form-control sumvolume_<?=$k?> td_sumvolume" readonly>
								</td>
								<?php if($order['status'] < 3) { ?>
									<td style="padding: 5px 10px;">
										<span id="progress_<?=$k?>"><a href="javascript:void(0)" onclick="$('#tr_order_<?=$k?>').remove(); recalc();"  class="btn btn-default float-right">Удалить</a></span>
									</td>
								<?php } ?>
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
										<td class="border-top pt-3"></td>
										<td class="border-top pt-3"></td>
										<td class="border-top pt-3"></td>
										<td class="border-top pt-3"></td>
										<?php if($order['status'] < 3) { ?><td class="border-top pt-3"></td><?php } ?>
								</tr>
							</tfoot>
						</table>
						
						<?php if($order['status'] < 3) { ?>
							<br/>
							<div style="float:right;padding:10px 10px 0 0"><input type="button" value="Добавить позицию" class="btn btn-success" id="add_prod"></div>
						<?php } ?>
                        <div class="order-content">
							<div class="order-container">
								<table class="order-table">
									<tbody>
										<?php 
											if($order["groups"] == 4) { 
												if($comp["nds"]== 1) { 
										?>
										<tr class="table-row">
											<td>Сумма без скидки и налогов:</td>
											<td class="itogs-cont">
												<span data-total="totalWithoutDiscount" class="sum_beznalog_skidki"><?=$sumbeznalog?></span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<?php } } ?>										
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
												<span data-total="totalDiscount" class="amount"><?=$itog_amount;?></span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<?php 
											if($order["groups"] == 4) { 
												if($comp["nds"]== 1) {
										?>
										<tr class="table-row">
											<td>Сумма без налога:</td>
											<td class="itogs-cont">
												<span data-total="totalWithoutTax" class="sum_beznalog_itogo"><?=$itogosummanalog?></span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<tr class="table-row">
											<td class="border-bottom pb-3">
												Сумма налога:
											</td>
											<td class="itogs-cont border-bottom pb-3">
												<span data-total="totalTax" class="sum_nalog_itogo"><?=$itog_sumnalog?></span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<?php } } ?>
										<tr class="table-row">
											<td class="pt-3 total-itogs-cont">
												Общая сумма:
											</td>
											<td class="pt-3 itogs-cont total-itogs-cont">
												<span data-total="totalCost" class="sum"><?=$itog_price?></span>
												<span data-role="currency-wrapper" class="item-currency-symbol"><?=$curr['symbol_right'];?></span>
											</td>
										</tr>
										<tr class="table-row">
											<td class="pt-3 total-itogs-cont">												
											</td>
											<td class="pt-3 itogs-cont total-itogs-cont">
												<?php if($comp["nds"]==1) { ?>с НДС<?php } ?>
												<?php if($comp["nds"]==2) { ?>без НДС<?php } ?>
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
												<span class="sum_weight"><?=$sumweight?></span>
											</td>
										</tr>																				
										<tr class="table-row">
											<td>Объём, м3:</td>
											<td class="itogs-volume">
												<span class="sum_volume"><?=$sumvolume?></span>												
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>						          
					</div><!-- /.box-body -->
				
				</div><!-- /.card-body -->
			  
            </div>
			<?php if($order['status'] < 3) { ?>
			<div class="box-footer">
				<input type="hidden" name="id" value="<?=$order->id;?>">
                <button type="submit" class="btn btn-success">Сохранить</button>
                
             </div>			 
			</form>			
			<?php } ?>
        </div>
    </div>
</section>
<!-- /.content -->
<script type="text/javascript">
$( "select.companys" ).change(function () {
    var id = $( ".companys" ).val();

		$.ajax({
			url: adminpath + "/order/compinfo",
			data: {id: id},
			type: 'GET',
			dataType: 'json',
			success: function(res){
				document.getElementById("uname").value = res.uname;
				document.getElementById("utelefon").value = res.utelefon;
				document.getElementById("uemail").value = res.uemail;
				if(res.cnds == 1){
					$("#cnds").html("<option value = \"1\" data-id=\"20\" selected=\"selected\">с НДС</option>");					
				}
				if(res.cnds == 2){
					$("#cnds").html("<option value = \"2\" data-id=\"0\" selected=\"selected\">без НДС</option>");
				}
			},
			error: function(){
				alert('Ошибка');
			}
		});
	})
</script>
<!-- /.content -->
<script type="text/javascript">
var total = <?=count($order_products)?>;

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
					if(nalog == 1) { nalog = 20; var sumnalog = (opt * 0.2 / 1.2 ) * 1; var sumnalog = sumnalog.toFixed(2); var sumbeznalog = (opt / 1.2 ) * 1; var sumbeznalog = sumbeznalog.toFixed(2);}
					if(nalog == 2) { nalog = 0; }

					$("#td_nalichie_"+total).html("<input name=\"order_zakaz["+total+"][order_nalichie]\" value=\""+res.result3+" шт.\" class=\"form-control\" readonly />");
					$("#td_rezerv_"+total).html("<input name=\"order_zakaz["+total+"][order_rezerv]\" value=\""+res.result4+" шт.\" class=\"form-control\" readonly />");
					$("#td_article_"+total).html("<input name=\"order_zakaz["+total+"][article]\" type=\"text\" value=\""+res.result1+"\" class=\"form-control\" placeholder=\"артикул товара\" readonly />");
					$("#td_price_"+total).html("<input name=\"order_zakaz["+total+"][price]\"id=\"price_text_"+total+"\" type=\"number\" value=\""+res.result2+"\" class=\"form-control orderprice_"+total+"\" placeholder=\"0\" oninput=\"change_price("+total+")\" /><input type=\"hidden\" id=\"price_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][price_nalog]\" class=\"form-control prod_nalog\" value=\""+nalog+"\"><input type=\"hidden\" id=\"sum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_nalog]\" class=\"form-control sum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"itogsum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][itogsum_nalog]\" class=\"form-control itogsum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"sum_beznalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog]\" class=\"form-control sum_beznalog\" value=\""+sumbeznalog+"\"><input type=\"hidden\" id=\"sum_beznalog_skidki_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog_skidki]\" class=\"form-control beznalog_skidki\" value=\""+res.result2+"\">");				
					$("#td_quantity_"+total).html("<input name=\"order_zakaz["+total+"][quantity]\"id=\"quantity_text_"+total+"\" type=\"number\" value=\"1\" class=\"form-control orderquantity_"+total+"\" oninput=\"change_price("+total+")\" /><input type=\"hidden\" id=\"itog_quantity_text_1\" name=\"order_zakaz[1][itogquantity]\" class=\"form-control itogquantity_"+total+"\">");				
					$("#td_discount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount]\" type=\"number\" value=\""+res.result7+"\" class=\"form-control orderdiscount_"+total+"\" id=\"discount_text_"+total+"\" oninput=\"change_price("+total+")\" />");
					$("#td_discount_amount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_amount]\" type=\"hidden\" value=\""+skidka+"\" class=\"form-control orderdiscount_amount_"+total+"\" id=\"discount_amount_text_"+total+"\" oninput=\"change_price("+total+")\" /><input style=\"padding: 5px 10px;\" type=\"number\" id=\"itog_discount_text_"+total+"\" value=\""+skidka+"\" name=\"order_zakaz["+total+"][itogdiscount]\" class=\"form-control td_amount itogdiscount_"+total+"\">");					
					$("#td_type_discount_"+total).html("<select id=\"type_discount_text_"+total+"\" name=\"order_zakaz["+total+"][type_discount]\" class=\"form-control ordertypediscount_"+total+"\" onchange=\"change_price("+total+")\"><option value=\"2\">%</option><option value=\"1\">₽</option></select>");
					$("#td_itog_"+total).html("<input style=\"padding: 5px 10px;\" id=\"itog_text_"+total+"\" name=\"order_zakaz["+total+"][itog]\" class=\"form-control itog_price_"+total+" td_itog\" value=\""+opt+"\" readonly><input style=\"padding: 5px 10px;\" id=\"sumweight_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][sumweight]\" value=\""+res.result5+"\" class=\"form-control sumweight_"+total+" td_sumweight\" readonly><input style=\"padding: 5px 10px;\" id=\"itog_sumweight_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][itog_sumweight]\" value=\""+res.result5+"\" class=\"form-control itog_sumweight_"+total+" td_itog_sumweight\" readonly><input style=\"padding: 5px 10px;\" id=\"sumvolume_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][sumvolume]\" value=\""+res.result6+"\" class=\"form-control sumvolume_"+total+" td_sumvolume\" readonly><input style=\"padding: 5px 10px;\" id=\"itog_sumvolume_text_"+total+"\" type=\"hidden\" name=\"order_zakaz["+total+"][itog_sumvolume]\" value=\""+res.result6+"\" class=\"form-control itog_sumvolume_"+total+" td_itog_sumvolume\" readonly>");
					
				}else{
					if(nalog == 1) { nalog = 20; var sumnalog = (res.result2 * 0.2 / 1.2 ) * 1; var sumnalog = sumnalog.toFixed(2); var sumbeznalog = (res.result2 / 1.2 ) * 1; var sumbeznalog = sumbeznalog.toFixed(2);}
					if(nalog == 2) { nalog = 0; }
					
					$("#td_nalichie_"+total).html("<input name=\"order_zakaz["+total+"][order_nalichie]\" value=\""+res.result3+" шт.\" class=\"form-control\" readonly />");
					$("#td_rezerv_"+total).html("<input name=\"order_zakaz["+total+"][order_rezerv]\" value=\""+res.result4+" шт.\" class=\"form-control\" readonly />");
					$("#td_article_"+total).html("<input name=\"order_zakaz["+total+"][article]\" type=\"text\" value=\""+res.result1+"\" class=\"form-control\" placeholder=\"артикул товара\" readonly />");
					$("#td_price_"+total).html("<input name=\"order_zakaz["+total+"][price]\"id=\"price_text_"+total+"\" type=\"number\" value=\""+res.result2+"\" class=\"form-control orderprice_"+total+"\" placeholder=\"0\" oninput=\"change_price("+total+")\" /><input type=\"hidden\" id=\"price_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][price_nalog]\" class=\"form-control prod_nalog\" value=\""+nalog+"\"><input type=\"hidden\" id=\"sum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_nalog]\" class=\"form-control sum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"itogsum_nalog_text_"+total+"\" name=\"order_zakaz["+total+"][itogsum_nalog]\" class=\"form-control itogsum_nalog\" value=\""+sumnalog+"\"><input type=\"hidden\" id=\"sum_beznalog_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog]\" class=\"form-control sum_beznalog\" value=\""+sumbeznalog+"\"><input type=\"hidden\" id=\"sum_beznalog_skidki_text_"+total+"\" name=\"order_zakaz["+total+"][sum_beznalog_skidki]\" class=\"form-control beznalog_skidki\" value=\""+res.result2+"\">");				
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
	var discount = document.getElementById("discount_text_"+total+"").value;
	var type_discount = document.getElementById("type_discount_text_"+total+"").value;
	var nalog = document.getElementById("price_nalog_text_"+total+"").value; //20%
	var skidka = document.getElementById("discount_amount_text_"+total+"").value;
	var itogskidka = document.getElementById("itog_discount_text_"+total+"").value;
	var sum_nalog = document.getElementById("sum_nalog_text_"+total+"").value;
	var weight = document.getElementById("sumweight_text_"+total+"").value;
	var volume = document.getElementById("sumvolume_text_"+total+"").value;
	
	
	if(type_discount == 1) {
		document.getElementById("itog_text_"+total+"").value = (price - discount) * kolvo;
		var itogskidka = discount * kolvo;
		$("#td_discount_amount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_amount]\" type=\"hidden\" value=\""+discount+"\" class=\"form-control orderdiscount_amount_"+total+"\" id=\"discount_amount_text_"+total+"\" oninput=\"change_price("+total+")\" /><input style=\"padding: 5px 10px;\" type=\"number\" id=\"itog_discount_text_"+total+"\" value=\""+itogskidka+"\" name=\"order_zakaz["+total+"][itogdiscount]\" class=\"form-control td_amount itogdiscount_"+total+"\">");
		
		if(nalog == 20) { var sumnalog = ((price - discount) * 0.2 / 1.2 ) * 1; var sumnalog = sumnalog.toFixed(2);}
		if(nalog == 0) { }
		
		var itogsumnalog = kolvo * sumnalog;
		var itogsumnalog = itogsumnalog.toFixed(2);		
		document.getElementById("itogsum_nalog_text_"+total+"").value = itogsumnalog;
		
		var sumbeznalog = ((price - discount) - sumnalog) * kolvo;
		var sumbeznalog = sumbeznalog.toFixed(2);
		document.getElementById("sum_beznalog_text_"+total+"").value = sumbeznalog;
	}
	if(type_discount == 2) {
		var price_comp = Math.round(price - (price/1.2), 0) * 6;
		var price_opt = price_comp - ((price_comp/100) * discount);
		var opt = Math.round(price_opt / 6) * 6;
		var skidka = price - opt;
		document.getElementById("itog_text_"+total+"").value = (price - skidka) * kolvo;
		var itogskidka = (price - opt) * kolvo;
		$("#td_discount_amount_"+total).html("<input style=\"padding: 5px 10px;\" name=\"order_zakaz["+total+"][discount_amount]\" type=\"hidden\" value=\""+skidka+"\" class=\"form-control orderdiscount_amount_"+total+"\" id=\"discount_amount_text_"+total+"\" oninput=\"change_price("+total+")\" /><input style=\"padding: 5px 10px;\" type=\"number\" id=\"itog_discount_text_"+total+"\" value=\""+itogskidka+"\" name=\"order_zakaz["+total+"][itogdiscount]\" class=\"form-control td_amount itogdiscount_"+total+"\">");					
		
		if(nalog == 20) { var sumnalog = (opt * 0.2 / 1.2 ) * 1; var sumnalog = sumnalog.toFixed(2); var sumbeznalog = (opt / 1.2 ) * 1; var sumbeznalog = sumbeznalog.toFixed(2); }
		if(nalog == 0) { }
		
		document.getElementById("sum_beznalog_skidki_text_"+total+"").value = price * kolvo;
		
		var itogsumnalog = kolvo * sum_nalog;
		var itogsumnalog = itogsumnalog.toFixed(2);		
		document.getElementById("itogsum_nalog_text_"+total+"").value = itogsumnalog;
		
		var sumbeznalog = (price - sum_nalog) * kolvo;
		var sumbeznalog = sumbeznalog.toFixed(2);
		document.getElementById("sum_beznalog_text_"+total+"").value = sumbeznalog;
		
	}
	
	var itogweight = weight * kolvo;
	document.getElementById("itog_sumweight_text_"+total+"").value = itogweight;
	var itogvolume = volume * kolvo;
	document.getElementById("itog_sumvolume_text_"+total+"").value = itogvolume;
	
	var amount = 0;
	$('.td_amount').each(function(){
		amount += parseFloat($(this).val());
	})
	$(".amount").html(""+amount+"");
	
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
	
}
</script>