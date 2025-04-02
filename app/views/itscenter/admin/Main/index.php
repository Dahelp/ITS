<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Панель управления</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">Главная</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <div class="row">
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
              <div class="inner">
                    <h3><?=$countNewOrders;?></h3>
                    <p>Новые заказы</p>
                </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <a href="/admin/order" class="small-box-footer">Все заказы <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
				<div class="small-box-2">
					<div class="col-6 inner">
						<h3><?=$countProducts?></h3>
						<p>Товарные позиции</p>
					</div>
					<div class="col-6 inner">
						<h3><?=$countInStock?></h3>
						<p>В наличии товаров</p>
					</div>
				</div>
				<div class="icon">
					<i class="ion ion-stats-bars"></i>
				</div>
				<a href="/admin/product" class="small-box-footer">Все товары <i class="fas fa-arrow-circle-right"></i></a>
				  
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
              <div class="inner">
                    <h3><?=$countUsers;?></h3>
                    <p>Клиенты</p>
                </div>
              <div class="icon">
                <i class="ion ion-person-add"></i>
              </div>
              <a href="/admin/user" class="small-box-footer">Все клиенты <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
              <div class="inner">
                    <h3><?=$countCategories;?></h3>
                    <p>Категории</p>
                </div>
              <div class="icon">
                <i class="ion ion-pie-graph"></i>
              </div>
              <a href="/admin/category" class="small-box-footer">Все категории <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
        </div>
        <!-- /.row -->
		<div class="row">
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
              <div class="inner">
                    <h3><?=$countOneClick;?></h3>
                    <p>Новые заказы в 1 клик</p>
                </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <a href="/admin/oneclick" class="small-box-footer">Все заказы в 1 клик <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->        
        </div>
        <!-- /.row -->
		<div class="row">
			<div class="col-md-6">
                <!-- USERS LIST -->
                <div class="card">
                  <div class="card-header">
                    <h3 class="card-title">Пользователи</h3>

                    <div class="card-tools">
                      <span class="badge badge-danger">Онлайн <?php $count_online_users = \R::exec("SELECT * FROM user, user_online WHERE user_online.user_id = user.id AND user.role !='user'"); echo "".$count_online_users.""; ?></span>
                      <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                      </button>
                      <button type="button" class="btn btn-tool" data-card-widget="remove">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </div>
                  <!-- /.card-header -->
                  <div class="card-body p-0">
                    <ul class="users-list clearfix">
						<?php foreach($usersonline as $ouser) { ?>
							<li>
								<img src="dist/img/user1-128x128.jpg" alt="User Image">
								<a class="users-list-name" href="#"><?=$ouser["name"]?></a>
								<span class="users-list-date">Онлайн</span>
							</li>
						<?php } ?>                      
                    </ul>
                    <!-- /.users-list -->
                  </div>
                  <!-- /.card-body -->
                  <div class="card-footer text-center">
                    <a href="">Все пользователи онлайн</a>
                  </div>
                  <!-- /.card-footer -->
                </div>
                <!--/.card -->
				
				
				<div class="card collapsed-card">
				  <div class="card-header">
					<h3 class="card-title">Последние действия (24часа)</h3>

					<div class="card-tools">
					    <span class="badge badge-primary">
							<?php
								$data_yestoday = date("Y-m-d H:i:s", strtotime('-24 hours', time()));
								$count_data = \R::exec("SELECT id_last FROM admin_last_history WHERE date_modified > '".$data_yestoday."'");
								echo $count_data;
							?>
						</span>
					  <button type="button" class="btn btn-tool" data-card-widget="collapse">
						<i class="fas fa-plus"></i>
					  </button>                  
					  <button type="button" class="btn btn-tool" data-card-widget="remove">
						<i class="fas fa-times"></i>
					  </button>
					</div>
				  </div>
				  <!-- /.card-header -->
				  <div class="card-body m-4 admin-main" style="display: none">
						<div class="timeline">
						<?php 
							$last_data = \R::getAll("SELECT date_modified, DATE_FORMAT(`date_modified`,'%d.%m.%Y') AS group_date FROM admin_last_history WHERE date_modified >= '".$data_yestoday."' GROUP BY group_date ORDER BY date_modified DESC");
							
							foreach($last_data as $last) {
						?>
						  <!-- timeline time label -->
						  <div class="time-label">
							<span class="bg-red"><?php echo \ishop\App::contdate($last["date_modified"]); ?></span>
						  </div>
						  <!-- /.timeline-label -->					  
						<?php
							list($currentDate, $currentOclock) = explode(' ', $last["date_modified"]);
							$last_history = \R::getAll("SELECT * FROM admin_last_history, admin_group_history, admin_action_history WHERE admin_last_history.gh_id = admin_group_history.id_gh AND admin_last_history.ah_id = admin_action_history.id_ah AND admin_last_history.date_modified >= '".$currentDate." 00:00:00' AND admin_last_history.date_modified <= '".$currentDate." 23:59:59' ORDER BY admin_last_history.date_modified DESC");
							
							foreach($last_history as $history) {
								
						?>		
							<!-- timeline item -->
						  <div>
							<?php 
								$action_history = \R::findOne('admin_action_history', 'name_ah = ?', [$history["name_ah"]]);
								
								if($action_history["status"] == "warning") { echo "<i class=\"fas fa-comments bg-yellow\"></i>"; }
								if($action_history["status"] == "success") { echo "<i class=\"fas fa-user bg-green\"></i>"; }
								if($action_history["status"] == "danger") { echo "<i class=\"fas fa-user bg-red\"></i>"; }
								if($action_history["status"] == "primary") { echo "<i class=\"fas fa-envelope bg-blue\"></i>"; }
							?>
							
							<div class="timeline-item">
							  <span class="time"><i class="fas fa-clock"></i> <?php echo $vremya = substr($history["date_modified"], 11, -3); ?></span>
							  <h3 class="timeline-header"><a href="#"><?=$history["name_gh"]?></a><?php if($history["customer_id"] !="") { $nuser = \R::findOne('user', 'id = ?', [$history["customer_id"]]); echo " <strong>".$nuser["name"]."</strong>"; } ?> <?=$history["name_ah"]?></h3>
							  <div class="timeline-body">
									<?php 
										$type = \R::findOne(''.$history["name_tbl"].'', 'id = ?', [$history["id_tbl"]]); 
										echo $type["name"];									
									?>
							  </div>
							  <?php if($action_history->controller !="") { ?>
							  <div class="timeline-footer">
								<a href="/admin/<?=$action_history->controller?><?php if($action_history->status !="warning") { ?>?id=<?=$history["id_tbl"]?><?php } ?>" class="btn btn-<?=$action_history->status?> btn-sm">Подробнее</a>
							  </div>
							  <?php } ?>
							</div>
						  </div>
						  <!-- END timeline item -->
						<?php } } ?>  
						  <div>
							<i class="fas fa-clock bg-gray"></i>
						  </div>
						</div>
						<div class="card-footer ft-main" style="display: block;">
							<a href="">Все уведомления</a>	
						</div>
				  <!-- /.card-footer-->
				  </div>
				  <!-- /.card-body -->
				  
				</div>			
            </div>
			<div class="col-md-3">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Топ менеджеров по заказам</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-card-widget="collapse">
								<i class="fas fa-minus"></i>
							</button>
							<button type="button" class="btn btn-tool" data-card-widget="remove">
								<i class="fas fa-times"></i>
							</button>
						</div>
					</div>

					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<ul class="nav nav-pills flex-column">
									<?php $menager_top_prodazh = \R::getAll("SELECT `user`.`name`, `order`.`admin_id` FROM `order`, `user` WHERE `order`.`admin_id` = `user`.`id` AND `order`.`admin_id` != '0' GROUP BY `order`.`admin_id`");
										foreach($menager_top_prodazh as $menag){
											
											$summ_menag = \R::getRow("SELECT ROUND(SUM(`order_product`.`price` * `order_product`.`qty`), 2) AS `it_price` FROM `order_product`, `order` WHERE `order_product`.`order_id` = `order`.`id` AND `order`.`admin_id` = '".$menag["admin_id"]."' AND `order`.`status` IN (4,5,6)");
											if($summ_menag["it_price"] >0){
									?>
										<li class="nav-item">
											<a href="#" class="nav-link"><?=$menag["name"]?>
												<span class="float-right"><?=$summ_menag["it_price"]?> <?=$curr['symbol_right']?></span>
											</a>								
										</li>
									<?php } } ?>									
								</ul>
							</div>
						</div>
					</div>
					<div class="card-footer p-0">
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6">
				<div class="card">
					<div class="card-header border-0">
						<div class="d-flex justify-content-between">
							<h3 class="card-title">Наличие товаров</h3>
							<a href="javascript:void(0);">View Report</a>
						</div>
					</div>
					<div class="card-body">
						<div class="d-flex">
							<p class="d-flex flex-column">
							<span class="text-bold text-lg">820</span>
							<span>Visitors Over Time</span>
							</p>
							<p class="ml-auto d-flex flex-column text-right">
							<span class="text-success">
							<i class="fas fa-arrow-up"></i> 12.5%
							</span>
							<span class="text-muted">Since last week</span>
							</p>
						</div>
						<div class="position-relative mb-4"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
							<canvas id="visitors-chart" height="200" style="display: block; width: 764px; height: 200px;" width="764" class="chartjs-render-monitor"></canvas>
						</div>
						<div class="d-flex flex-row justify-content-end">
							<span class="mr-2">
								<i class="fas fa-square text-primary"></i> This Week
							</span>
							<span>
								<i class="fas fa-square text-gray"></i> Last Week
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>

</section>
<!-- /.content -->
<?php

	foreach($qtytotals as $qt){
		$nal_qt .= "".$qt["qty_total"].",";
		
	}
	$nal_qt = rtrim($nal_qt, ',');
?>
<script>
	$(function(){'use strict'
var ticksStyle={fontColor:'#495057',fontStyle:'bold'}
var mode='index'
var intersect=true
var $salesChart=$('#sales-chart')
var salesChart=new Chart($salesChart,{type:'bar',data:{labels:['JUN','JUL','AUG','SEP','OCT','NOV','DEC'],datasets:[{backgroundColor:'#007bff',borderColor:'#007bff',data:[1000,2000,3000,2500,2700,2500,3000]},{backgroundColor:'#ced4da',borderColor:'#ced4da',data:[700,1700,2700,2000,1800,1500,2000]}]},options:{maintainAspectRatio:false,tooltips:{mode:mode,intersect:intersect},hover:{mode:mode,intersect:intersect},legend:{display:false},scales:{yAxes:[{gridLines:{display:true,lineWidth:'4px',color:'rgba(0, 0, 0, .2)',zeroLineColor:'transparent'},ticks:$.extend({beginAtZero:true,callback:function(value){if(value>=1000){value/=1000
value+='k'}
return '$'+value}},ticksStyle)}],xAxes:[{display:true,gridLines:{display:false},ticks:ticksStyle}]}}})
var $visitorsChart=$('#visitors-chart')
var visitorsChart=new Chart($visitorsChart,{data:{labels:['18th','20th','22nd','24th','26th','28th','30th'],datasets:[{type:'line',data:[<?=$nal_qt?>],backgroundColor:'transparent',borderColor:'#007bff',pointBorderColor:'#007bff',pointBackgroundColor:'#007bff',fill:false},{type:'line',data:[60,80,70,67,80,77,100],backgroundColor:'tansparent',borderColor:'#ced4da',pointBorderColor:'#ced4da',pointBackgroundColor:'#ced4da',fill:false}]},options:{maintainAspectRatio:false,tooltips:{mode:mode,intersect:intersect},hover:{mode:mode,intersect:intersect},legend:{display:false},scales:{yAxes:[{gridLines:{display:true,lineWidth:'4px',color:'rgba(0, 0, 0, .2)',zeroLineColor:'transparent'},ticks:$.extend({beginAtZero:true,suggestedMax:200},ticksStyle)}],xAxes:[{display:true,gridLines:{display:false},ticks:ticksStyle}]}}})
});
</script>