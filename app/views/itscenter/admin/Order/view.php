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
								$cities = \R::findOne('cities', 'id = ?', [$order['city_id']]);
								$city = \R::getAll("SELECT * FROM cities WHERE id != '".$cities['id']."' ORDER BY city_name");
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
											<?php if($order["groups"] == "5") { echo "Юридическое лицо"; } ?>
										</td>
									</tr>
									<?php if($order["groups"] == "4" OR $order["groups"] == "5") { ?>									
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
												<?php if (($compinf->nds ?? null) == 1) { ?>с НДС<?php } ?>
												<?php if (($compinf->nds ?? null) == 2) { ?>без НДС<?php } ?>
											</td>
										</tr>
										<tr>
											<td>Условия поставки</td>											
											<td>
												<?php
												$dogovor = '';
												if (($compinf->dogovor ?? null) == 1) {
													$dogovor = 'Договор';
												} elseif (($compinf->dogovor ?? null) == 2) {
													$dogovor = 'Счёт-договор';
												}
												?>
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

							<?php
								$k = 1;
								$qty = 0;
								$itog_price = 0.0;
								$itog_summa = 0.0;
								$sumweight = 0.0;
								$sumvolume = 0.0;
								$sumnalog = 0.0;
								$itog_sumnalog = 0.0;
								$nalog = 0.0;
								$sumbeznalog = 0.0;
								$itogonalog = 0.0;
								$itogosummanalog = 0.0;
								$itog_amount = 0.0;
							?>
							<?php foreach($order_products as $product): ?>
							<?php
								$item = \R::findOne('product', 'id = ?', [(int)$product->product_id]);
								$rezerv = \R::findOne('in_stock', 'product_id = ? AND branch_id = ?', [(int)$product->product_id, 9]);

								$productQty = is_numeric($product->qty ?? null) ? (float)$product->qty : 0.0;
								$productPrice = is_numeric($product->price ?? null) ? (float)$product->price : 0.0;
								$productDiscount = is_numeric($product->discount ?? null) ? (float)$product->discount : 0.0;

								$itemWeight = ($item && is_numeric($item->weight ?? null)) ? (float)$item->weight : 0.0;
								$itemVolume = ($item && is_numeric($item->volume ?? null)) ? (float)$item->volume : 0.0;
								$itemQuantity = ($item && is_numeric($item->quantity ?? null)) ? (float)$item->quantity : 0.0;

								$rezervQty = ($rezerv && is_numeric($rezerv['quantity'] ?? null)) ? (float)$rezerv['quantity'] : 0.0;

								$qty += $productQty;

								$lineBaseSum = $productPrice * $productQty;
								$lineDiscountSum = $productDiscount * $productQty;
								$itog_summa = $lineBaseSum - $lineDiscountSum;
								$itog_price += $itog_summa;

								$lineWeight = $itemWeight * $productQty;
								$lineVolume = $itemVolume * $productQty;

								$sumweight += $lineWeight;
								$sumvolume += $lineVolume;

								$nalog = round(($productPrice * 0.2 / 1.2) * $productQty, 2);
								$sumnalog = round((($productPrice * 0.2 / 1.2) * $productQty) - $lineDiscountSum, 2);
								$itog_sumnalog = round($itog_sumnalog + $sumnalog, 2);

								$sumbeznalog = round($itog_price - $itog_sumnalog, 2);

								$itogonalog = round(($lineBaseSum - $nalog) - $lineDiscountSum, 2);
								$itogosummanalog = round($itogosummanalog + $itogonalog, 2);

								$itog_amount += $lineDiscountSum;
							?>
							
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
									<input style="padding: 5px 10px;" type="text" name="order_nalichie" class="form-control" value="<?=$itemQuantity;?> шт." readonly>
								</td>
								<td id="td_rezerv_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="text" name="order_rezerv" class="form-control" value="<?=$rezervQty;?> шт." readonly>
								</td>
								
								<td id="td_discount_value_<?=$k?>" style="padding: 5px 10px;">
									<input style="padding: 5px 10px;" type="number" id="discount_text_value_<?=$k?>" name="order_zakaz[<?=$k?>][discount_value]" class="form-control orderdiscount_value_<?=$k?>" value="<?=$product->discount;?>" oninput="change_price(<?=$k?>)" <?php if($order['status'] > 2) { ?>readonly<?php } ?>>
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
									<input style="padding: 5px 10px;" id="sumweight_text_<?=$k?>" type="hidden" name="order_zakaz[<?=$k?>][sumweight]" value="<?=$itemWeight;?>" class="form-control sumweight_<?=$k?> td_sumweight" readonly>
									<input style="padding: 5px 10px;" id="sumvolume_text_<?=$k?>" type="hidden" name="order_zakaz[<?=$k?>][sumvolume]" value="<?=$itemVolume;?>" class="form-control sumvolume_<?=$k?> td_sumvolume" readonly>

									<input style="padding: 5px 10px;" id="itog_sumweight_text_<?=$k?>" type="hidden" name="order_zakaz[<?=$k?>][itog_sumweight]" value="<?=$lineWeight;?>" class="form-control itog_sumweight_<?=$k?> td_itog_sumweight" readonly>
									<input style="padding: 5px 10px;" id="itog_sumvolume_text_<?=$k?>" type="hidden" name="order_zakaz[<?=$k?>][itog_sumvolume]" value="<?=$lineVolume;?>" class="form-control itog_sumvolume_<?=$k?> td_itog_sumvolume" readonly>
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
											if ((int)($order["groups"] ?? 0) === 4) {
												if ((int)($comp['nds'] ?? 0) === 1) { 
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
												<?php if(($comp['nds'] ?? 0) == 1) { ?>с НДС<?php } ?>
												<?php if(($comp['nds'] ?? 0) == 2) { ?>без НДС<?php } ?>
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
				<input type="hidden" name="id" value="<?=$order['id'];?>">
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

function add_new_orders() {
    total++;

    let row = '';
    row += '<tr id="tr_order_' + total + '" style="line-height:20px;">';
    row += '   <td>' + total + '</td>';

    row += '   <td id="td_product_' + total + '" style="padding: 5px 10px;">';
    row += '       <select class="form-control select_product searchproduct_' + total + '" name="order_zakaz[' + total + '][product_id]">';
    row += '           <option value=""></option>';
    row += '       </select>';
    row += '   </td>';

    row += '   <td id="td_article_' + total + '" style="padding: 5px 10px;">';
    row += '       <input id="article_text_' + total + '" name="order_zakaz[' + total + '][article]" type="text" value="" class="form-control" placeholder="артикул товара" readonly>';
    row += '   </td>';

    row += '   <td id="td_price_' + total + '" style="padding: 5px 10px;">';
    row += '       <input id="price_text_' + total + '" name="order_zakaz[' + total + '][price]" type="number" value="0" class="form-control orderprice_' + total + '" placeholder="0" oninput="change_price(' + total + ')">';
    row += '       <input type="hidden" id="price_nalog_text_' + total + '" name="order_zakaz[' + total + '][price_nalog]" class="form-control prod_nalog" value="20">';
    row += '       <input type="hidden" id="sum_nalog_text_' + total + '" name="order_zakaz[' + total + '][sum_nalog]" class="form-control sum_nalog" value="0">';
    row += '       <input type="hidden" id="itogsum_nalog_text_' + total + '" name="order_zakaz[' + total + '][itogsum_nalog]" class="form-control itogsum_nalog" value="0">';
    row += '       <input type="hidden" id="sum_beznalog_text_' + total + '" name="order_zakaz[' + total + '][sum_beznalog]" class="form-control sum_beznalog" value="0">';
    row += '       <input type="hidden" id="sum_beznalog_skidki_text_' + total + '" name="order_zakaz[' + total + '][sum_beznalog_skidki]" class="form-control beznalog_skidki" value="0">';
    row += '       <input type="hidden" id="weight_text_' + total + '" name="order_zakaz[' + total + '][weight]" value="0" class="form-control weight_' + total + '">';
    row += '       <input type="hidden" id="volume_text_' + total + '" name="order_zakaz[' + total + '][volume]" value="0" class="form-control volume_' + total + '">';
    row += '   </td>';

    row += '   <td id="td_quantity_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="number" id="quantity_text_' + total + '" name="order_zakaz[' + total + '][quantity]" class="form-control itog_qty orderquantity_' + total + '" value="1" oninput="change_price(' + total + ')">';
    row += '       <input type="hidden" id="itog_quantity_text_' + total + '" name="order_zakaz[' + total + '][itogquantity]" class="form-control itogquantity_' + total + '" value="1">';
    row += '   </td>';

    row += '   <td id="td_nalichie_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="text" id="nalichie_text_' + total + '" name="order_zakaz[' + total + '][order_nalichie]" value="0 шт." class="form-control" readonly>';
    row += '   </td>';

    row += '   <td id="td_rezerv_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="text" id="rezerv_text_' + total + '" name="order_zakaz[' + total + '][order_rezerv]" value="0 шт." class="form-control" readonly>';
    row += '   </td>';

    row += '   <td id="td_discount_value_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="number" id="discount_text_value_' + total + '" name="order_zakaz[' + total + '][discount_value]" class="form-control orderdiscount_value_' + total + '" value="0" oninput="change_price(' + total + ')">';
    row += '   </td>';

    row += '   <td id="td_type_discount_' + total + '" style="padding: 5px 10px;">';
    row += '       <select id="type_discount_text_' + total + '" name="order_zakaz[' + total + '][type_discount]" class="form-control ordertypediscount_' + total + '" onchange="change_price(' + total + ')">';
    row += '           <option value="2">%</option>';
    row += '           <option value="1">₽</option>';
    row += '       </select>';
    row += '   </td>';

    row += '   <td id="td_discount_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="number" id="discount_text_' + total + '" name="order_zakaz[' + total + '][discount]" class="form-control orderdiscount_' + total + '" value="0" oninput="change_price(' + total + ')">';
    row += '   </td>';

    row += '   <td id="td_price_discount_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="number" id="price_discount_text_' + total + '" name="order_zakaz[' + total + '][price_discount]" class="form-control orderpricediscount_' + total + '" value="0" readonly>';
    row += '   </td>';

    row += '   <td id="td_discount_amount_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="text" id="discount_amount_text_' + total + '" name="order_zakaz[' + total + '][discount_amount]" class="form-control td_amount orderdiscount_amount_' + total + '" value="0" readonly>';
    row += '   </td>';

    row += '   <td id="td_itog_' + total + '" style="padding: 5px 10px;">';
    row += '       <input id="itog_text_' + total + '" name="order_zakaz[' + total + '][itog]" value="0" class="form-control itog_price_' + total + ' td_itog" readonly>';
    row += '       <input id="sumweight_text_' + total + '" type="hidden" name="order_zakaz[' + total + '][sumweight]" value="0" class="form-control sumweight_' + total + ' td_sumweight" readonly>';
    row += '       <input id="itog_sumweight_text_' + total + '" type="hidden" name="order_zakaz[' + total + '][itog_sumweight]" value="0" class="form-control itog_sumweight_' + total + ' td_itog_sumweight" readonly>';
    row += '       <input id="sumvolume_text_' + total + '" type="hidden" name="order_zakaz[' + total + '][sumvolume]" value="0" class="form-control sumvolume_' + total + ' td_sumvolume" readonly>';
    row += '       <input id="itog_sumvolume_text_' + total + '" type="hidden" name="order_zakaz[' + total + '][itog_sumvolume]" value="0" class="form-control itog_sumvolume_' + total + ' td_itog_sumvolume" readonly>';
    row += '   </td>';

    row += '   <td style="padding: 5px 10px;">';
    row += '       <span id="progress_' + total + '"><a href="javascript:void(0)" onclick="$(\'#tr_order_' + total + '\').remove(); recalcAll();" class="btn btn-default float-right">Удалить</a></span>';
    row += '   </td>';

    row += '</tr>';

    $('#table_container').append(row);

    initailizeSelect2(String(total));
    change_price(total);
}
// Initialize select2
function initailizeSelect2(total) {
    $(".searchproduct_" + total).select2({
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
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        }
    });

    $(".searchproduct_" + total).on("change", function () {
        var id = $(this).val();
        var comp_id = $(".compid_text").val() || 0;
        var nalog = $('select[name=nds]').val() || 1;

        $.ajax({
            url: adminpath + "/order/productprice",
            data: {id: id, comp_id: comp_id},
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                var price = parseFloat(res.result2 || 0);
                var qty = 1;
                var discountPercent = parseFloat(res.result7 || 0);
                var weight = parseFloat(res.result5 || 0);
                var volume = parseFloat(res.result6 || 0);

                var discountRub = 0;
                var priceDiscount = price;

                if (discountPercent > 0) {
                    discountRub = price * discountPercent / 100;
                    priceDiscount = price - discountRub;
                }

                var priceNalog = (parseInt(nalog, 10) === 1) ? 20 : 0;
                var sumNalog = (priceNalog === 20) ? ((priceDiscount * 0.2 / 1.2) * qty) : 0;
                var sumBezNalog = (priceDiscount * qty) - sumNalog;

                $("#article_text_" + total).val(res.result1 || '');
                $("#price_text_" + total).val(price.toFixed(2));
                $("#price_nalog_text_" + total).val(priceNalog);
                $("#sum_nalog_text_" + total).val(sumNalog.toFixed(2));
                $("#itogsum_nalog_text_" + total).val(sumNalog.toFixed(2));
                $("#sum_beznalog_text_" + total).val(sumBezNalog.toFixed(2));
                $("#sum_beznalog_skidki_text_" + total).val((price * qty).toFixed(2));

                $("#quantity_text_" + total).val(1);
                $("#itog_quantity_text_" + total).val(1);

                $("#nalichie_text_" + total).val((res.result3 || 0) + " шт.");
                $("#rezerv_text_" + total).val((res.result4 || 0) + " шт.");

                $("#discount_text_value_" + total).val(discountPercent);
                $("#type_discount_text_" + total).val(discountPercent > 0 ? '2' : '1');
                $("#discount_text_" + total).val(discountRub.toFixed(2));
                $("#price_discount_text_" + total).val(priceDiscount.toFixed(2));
                $("#discount_amount_text_" + total).val(discountRub.toFixed(2));

                $("#weight_text_" + total).val(weight);
                $("#volume_text_" + total).val(volume);
                $("#sumweight_text_" + total).val(weight);
                $("#sumvolume_text_" + total).val(volume);
                $("#itog_sumweight_text_" + total).val((weight * qty).toFixed(2));
                $("#itog_sumvolume_text_" + total).val((volume * qty).toFixed(2));

                $("#itog_text_" + total).val((priceDiscount * qty).toFixed(2));

                change_price(total);
            },
            error: function () {
                alert('Ошибка');
            }
        });
    });
}

function toNum(val) {
    var n = parseFloat(val);
    return isNaN(n) ? 0 : n;
}

function recalcAll() {
    var amount = 0;
    $('.td_amount').each(function () {
        amount += toNum($(this).val());
    });
    $(".amount").html(amount.toFixed(2));

    var sum = 0;
    $('.td_itog').each(function () {
        sum += toNum($(this).val());
    });
    $(".sum").html(sum.toFixed(2));

    var nalogitogo = 0;
    $('.itogsum_nalog').each(function () {
        nalogitogo += toNum($(this).val());
    });
    $(".sum_nalog_itogo").html(nalogitogo.toFixed(2));

    var beznalog_skidki = 0;
    $('.beznalog_skidki').each(function () {
        beznalog_skidki += toNum($(this).val());
    });
    $(".sum_beznalog_skidki").html(beznalog_skidki.toFixed(2));

    var beznalog = sum - nalogitogo;
    $(".sum_beznalog_itogo").html(beznalog.toFixed(2));

    var itogqty = 0;
    $('.itog_qty').each(function () {
        itogqty += toNum($(this).val());
    });
    $(".itogqty").html(itogqty);

    var itogsweight = 0;
    $('.td_itog_sumweight').each(function () {
        itogsweight += toNum($(this).val());
    });
    $(".sum_weight").html(itogsweight.toFixed(2));

    var itogsvolume = 0;
    $('.td_itog_sumvolume').each(function () {
        itogsvolume += toNum($(this).val());
    });
    $(".sum_volume").html(itogsvolume.toFixed(2));
}

function change_price(total) {
    var price = toNum($("#price_text_" + total).val());
    var qty = toNum($("#quantity_text_" + total).val());
    var discountValue = toNum($("#discount_text_value_" + total).val());
    var typeDiscount = $("#type_discount_text_" + total).val();
    var nalog = toNum($("#price_nalog_text_" + total).val());
    var weight = toNum($("#weight_text_" + total).val());
    var volume = toNum($("#volume_text_" + total).val());

    var discountRub = 0;
    var priceDiscount = price;

    if (typeDiscount == '1') {
        discountRub = discountValue;
        if (discountRub > price) {
            discountRub = price;
        }
        priceDiscount = price - discountRub;
    } else {
        discountRub = price * discountValue / 100;
        if (discountRub > price) {
            discountRub = price;
        }
        priceDiscount = price - discountRub;
    }

    var discountAmount = discountRub * qty;
    var itog = priceDiscount * qty;

    var sumNalog = 0;
    var itogSumNalog = 0;
    var sumBezNalog = 0;

    if (nalog == 20) {
        sumNalog = priceDiscount * 0.2 / 1.2;
        itogSumNalog = sumNalog * qty;
        sumBezNalog = itog - itogSumNalog;
    } else {
        sumNalog = 0;
        itogSumNalog = 0;
        sumBezNalog = itog;
    }

    var itogWeight = weight * qty;
    var itogVolume = volume * qty;

    $("#discount_text_" + total).val(discountRub.toFixed(2));
    $("#price_discount_text_" + total).val(priceDiscount.toFixed(2));
    $("#discount_amount_text_" + total).val(discountAmount.toFixed(2));
    $("#itog_text_" + total).val(itog.toFixed(2));

    $("#sum_nalog_text_" + total).val(sumNalog.toFixed(2));
    $("#itogsum_nalog_text_" + total).val(itogSumNalog.toFixed(2));
    $("#sum_beznalog_text_" + total).val(sumBezNalog.toFixed(2));
    $("#sum_beznalog_skidki_text_" + total).val((price * qty).toFixed(2));

    $("#itog_quantity_text_" + total).val(qty);

    $("#sumweight_text_" + total).val(weight.toFixed(2));
    $("#itog_sumweight_text_" + total).val(itogWeight.toFixed(2));

    $("#sumvolume_text_" + total).val(volume.toFixed(2));
    $("#itog_sumvolume_text_" + total).val(itogVolume.toFixed(2));

    recalcAll();
}

$(function () {
    $("#add_prod").on("click", function () {
        add_new_orders();
    });

    recalcAll();
});
</script>