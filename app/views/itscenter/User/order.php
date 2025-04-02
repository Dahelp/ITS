<?php $order_prefix = \ishop\App::options('order_prefix'); ?>
<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active"><a href="<?= PATH ?>/user/orders">История заказов</a></li>
				<li class="breadcrumb-item active">Заказ №<?=$order_prefix?><?=$_GET["id"]?></li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">				
				<?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
			</div>
			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Заказ №<?=$order_prefix?><?=$_GET["id"]?> (<?=$status["status_name"]?>)</h5>
					</div>
					<div class="card-body">
						<?php if($order): ?>
							<div class="table-responsive">
								<table class="table aiz-table mb-0 footable footable-1 breakpoint-xl">
									<thead>
									<tr>
										<th style="width: 8%">Фото</th>
										<th style="width: 52%">Наименование</th>									
										<th style="width: 20%">Количество</th>
										<th style="width: 20%">Цена</th>									
									</tr>
									</thead>
									<tbody>
									<?php foreach($order as $item): ?>									
										<tr>
											<td><img src="images/product/mini/<?=$item["img"]?>" /></td>
											<td><a href="product/<?=$item["alias"]?>"><?=$item["name"]?></a></td>
											<td><?=$item["qty"]?></td>
											<td><?=$curr['symbol_left'];?> <?=$item["price"]?> <?=$curr['symbol_right'];?><?php $summa_sum += $item["price"]*$item["qty"]?></td>
										</tr>
									<?php endforeach; ?>
									</tbody>
									<?php 
										$summa = \R::getAll("SELECT SUM(price) as sum, SUM(qty) as qty FROM `order_product` WHERE order_id = '".$item["id"]."'");									
									?>
									<tr style="background:#fff">
											<td colspan="2">Итого:</td>
											<td><?=$summa[0][qty]?> шт.</td>
											<td><?=$curr['symbol_left'];?> <?=$summa_sum?> <?=$curr['symbol_right'];?></td>
									</tr>							
								</table>
							</div>
							<h4>Дополнительная информация по заказу:</h4>
							<p>
								Дата создания: <?=$order_info["date"]?><br />
								Дата изменения: <?=$order_info["date"]?><br />
								Статус заказа: <?=$status['status_name']?><br />
								<?php 
									$dostavka = \R::getAll("SELECT * FROM dostavka WHERE id = ?", [$order_info["dostavka_id"]]);
									if($dostavka) { echo "Способ доставки: ".$dostavka[0]["name"]."<br />"; }
								?>
								<?php 
									$transport = \R::getAll("SELECT * FROM transport_company WHERE id = ?", [$order_info["transport_id"]]);
									if($transport) { echo "Транспортная компания: ".$transport[0]["name"]."<br />"; }
								?>
								<?php 
									$branch = \R::getAll("SELECT * FROM branch_office WHERE branch_id = ?", [$order_info["branch_id"]]);
									if($branch) { echo "Адрес самовывоза: ".$branch[0]["name"]."<br />"; }
								?>
								<?php 
									$cities = \R::getAll("SELECT * FROM cities WHERE city_id = ?", [$order_info["city_id"]]);
									if($cities) { echo "Город: ".$cities[0]["city_name"]."<br />"; }
								?>
								<?php if($order_info["address"]) { echo "Адрес: ".$order_info["address"]."<br />"; } ?>
								<?php if($order_info["note"]) { echo "Комментарий: ".$order_info["note"]."<br />"; } ?>
							</p>
						<?php else: ?>
							<p class="text-danger">Возможно заказ удалён или не существует.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->