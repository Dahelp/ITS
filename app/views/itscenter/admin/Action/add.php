<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Акции</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/action">Список акции</a></li>
              <li class="breadcrumb-item active">Добавить товар в акцию</li>
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
			<form action="<?=ADMIN;?>/action/add" method="post" data-toggle="validator">
			<!-- Custom Tabs -->
            <div class="card">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Добавить товар в акцию</h3>
				</div><!-- /.card-header -->
                <div class="card-body">
                    <div class="box-body">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="product_id">Наименование</label>
							<div class="col-sm-9">
								<select name="product_id" class="form-control select2" id="related" data-placeholder="Выберите товары"></select>                              
							</div>                                        
                        </div>			
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="type_id">Тип скидки</label>
							<div class="col-sm-9">
								<select name="type_id" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите тип скидки</option>
								<?php foreach($types as $type) { ?>
									<option value= "<?=$type["id"]?>"><?=$type["type"]?></option>
                    			<?php } ?>
                 			</select>                                
							</div>                                        
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="znachenie">Значение скидки</label>
							<div class="col-sm-9">
								<input type="text" name="znachenie" class="form-control" id="znachenie" placeholder="20" required>                                
							</div>                                        
                        </div>						
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="date_start">Дата начала акции</label>
							<div class="col-sm-9">
								<div class="input-group">									
									<div class="input-group date" id="reservationdatetime" data-target-input="nearest">										
										<div class="input-group-append" data-target="#reservationdatetime" data-toggle="datetimepicker">
											<div class="input-group-text"><i class="far fa-calendar-alt"></i></div>
										</div>
										<input type="text" name="date_start" class="form-control form-right datetimepicker-input" data-target="#reservationdatetime">
									</div>
								</div>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="date_end">Дата окончания акции</label>
							<div class="col-sm-9">
								<div class="input-group">									
									<div class="input-group date" id="reservationdatetime2" data-target-input="nearest">										
										<div class="input-group-append" data-target="#reservationdatetime2" data-toggle="datetimepicker">
											<div class="input-group-text"><i class="far fa-calendar-alt"></i></div>
										</div>
										<input type="text" name="date_end" class="form-control form-right datetimepicker-input" data-target="#reservationdatetime2">
									</div>
								</div>
							</div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус активности</label>
							<div class="col-sm-9">
							<select name="hide" class="form-control" style="width: 100%;">
								<option value= "" selected="selected">Выберите статус активности</option>
								<option value= "show">Активный</option>
                    			<option value= "hide">Не активный</option>
                 			</select>
							</div>
                        </div>
					</div>
                <!-- /.tab-content -->				
				</div><!-- /.card-body -->			  
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-success">Добавить</button>
            </div>
            </form>
            <!-- ./card -->
			
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->
		
</section>
<!-- /.content -->
<div class="daterangepicker ltr show-calendar opensright" style="top: 1698.93px; left: 1145px; right: auto; display: block;"><div class="ranges"></div><div class="drp-calendar left"><div class="calendar-table"><table class="table-condensed"><thead><tr><th class="prev available"><span></span></th><th colspan="5" class="month">Dec 2021</th><th></th></tr><tr><th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th></tr></thead><tbody><tr><td class="weekend off ends available" data-title="r0c0">28</td><td class="off ends available" data-title="r0c1">29</td><td class="off ends available" data-title="r0c2">30</td><td class="available" data-title="r0c3">1</td><td class="available" data-title="r0c4">2</td><td class="available" data-title="r0c5">3</td><td class="weekend available" data-title="r0c6">4</td></tr><tr><td class="weekend available" data-title="r1c0">5</td><td class="available" data-title="r1c1">6</td><td class="available" data-title="r1c2">7</td><td class="available" data-title="r1c3">8</td><td class="available" data-title="r1c4">9</td><td class="available" data-title="r1c5">10</td><td class="weekend available" data-title="r1c6">11</td></tr><tr><td class="weekend available" data-title="r2c0">12</td><td class="available" data-title="r2c1">13</td><td class="available" data-title="r2c2">14</td><td class="available" data-title="r2c3">15</td><td class="available" data-title="r2c4">16</td><td class="available" data-title="r2c5">17</td><td class="weekend available" data-title="r2c6">18</td></tr><tr><td class="weekend available" data-title="r3c0">19</td><td class="available" data-title="r3c1">20</td><td class="available" data-title="r3c2">21</td><td class="available" data-title="r3c3">22</td><td class="available" data-title="r3c4">23</td><td class="today active start-date active end-date available" data-title="r3c5">24</td><td class="weekend available" data-title="r3c6">25</td></tr><tr><td class="weekend available" data-title="r4c0">26</td><td class="available" data-title="r4c1">27</td><td class="available" data-title="r4c2">28</td><td class="available" data-title="r4c3">29</td><td class="available" data-title="r4c4">30</td><td class="available" data-title="r4c5">31</td><td class="weekend off ends available" data-title="r4c6">1</td></tr><tr><td class="weekend off ends available" data-title="r5c0">2</td><td class="off ends available" data-title="r5c1">3</td><td class="off ends available" data-title="r5c2">4</td><td class="off ends available" data-title="r5c3">5</td><td class="off ends available" data-title="r5c4">6</td><td class="off ends available" data-title="r5c5">7</td><td class="weekend off ends available" data-title="r5c6">8</td></tr></tbody></table></div><div class="calendar-time"><select class="hourselect"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12" selected="selected">12</option></select> : <select class="minuteselect"><option value="0" selected="selected">00</option><option value="30">30</option></select> <select class="ampmselect"><option value="AM" selected="selected">AM</option><option value="PM">PM</option></select></div></div><div class="drp-calendar right"><div class="calendar-table"><table class="table-condensed"><thead><tr><th></th><th colspan="5" class="month">Jan 2022</th><th class="next available"><span></span></th></tr><tr><th>Su</th><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th></tr></thead><tbody><tr><td class="weekend off ends available" data-title="r0c0">26</td><td class="off ends available" data-title="r0c1">27</td><td class="off ends available" data-title="r0c2">28</td><td class="off ends available" data-title="r0c3">29</td><td class="off ends available" data-title="r0c4">30</td><td class="off ends available" data-title="r0c5">31</td><td class="weekend available" data-title="r0c6">1</td></tr><tr><td class="weekend available" data-title="r1c0">2</td><td class="available" data-title="r1c1">3</td><td class="available" data-title="r1c2">4</td><td class="available" data-title="r1c3">5</td><td class="available" data-title="r1c4">6</td><td class="available" data-title="r1c5">7</td><td class="weekend available" data-title="r1c6">8</td></tr><tr><td class="weekend available" data-title="r2c0">9</td><td class="available" data-title="r2c1">10</td><td class="available" data-title="r2c2">11</td><td class="available" data-title="r2c3">12</td><td class="available" data-title="r2c4">13</td><td class="available" data-title="r2c5">14</td><td class="weekend available" data-title="r2c6">15</td></tr><tr><td class="weekend available" data-title="r3c0">16</td><td class="available" data-title="r3c1">17</td><td class="available" data-title="r3c2">18</td><td class="available" data-title="r3c3">19</td><td class="available" data-title="r3c4">20</td><td class="available" data-title="r3c5">21</td><td class="weekend available" data-title="r3c6">22</td></tr><tr><td class="weekend available" data-title="r4c0">23</td><td class="available" data-title="r4c1">24</td><td class="available" data-title="r4c2">25</td><td class="available" data-title="r4c3">26</td><td class="available" data-title="r4c4">27</td><td class="available" data-title="r4c5">28</td><td class="weekend available" data-title="r4c6">29</td></tr><tr><td class="weekend available" data-title="r5c0">30</td><td class="available" data-title="r5c1">31</td><td class="off ends available" data-title="r5c2">1</td><td class="off ends available" data-title="r5c3">2</td><td class="off ends available" data-title="r5c4">3</td><td class="off ends available" data-title="r5c5">4</td><td class="weekend off ends available" data-title="r5c6">5</td></tr></tbody></table></div><div class="calendar-time"><select class="hourselect"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11" selected="selected">11</option><option value="12">12</option></select> : <select class="minuteselect"><option value="0">00</option><option value="30">30</option></select> <select class="ampmselect"><option value="AM">AM</option><option value="PM" selected="selected">PM</option></select></div></div><div class="drp-buttons"><span class="drp-selected">12/24/2021 12:00 AM - 12/24/2021 11:59 PM</span><button class="cancelBtn btn btn-sm btn-default" type="button">Cancel</button><button class="applyBtn btn btn-sm btn-primary" type="button">Apply</button> </div></div>