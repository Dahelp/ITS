<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">IndexNow</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
              <li class="breadcrumb-item active">IndexNow</li>
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
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список поисковыx систем</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">						
					<div class="table-responsive">
						<table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Поисковая система</th>
                                <th>Код верификации</th>
								<th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
							<?php foreach($indexnow as $item): ?>
								<tr class="cont_td_znach">
									<td><?=$item['search_engine'];?></td>
									<td><?=$item['verification'];?></td>
									<td>
										<?php
											if($item['hide'] == "show"){ echo "Активный"; }
											if($item['hide'] == "hide"){ echo "Не активный"; }
										?>
									</td>
									<td><a href="<?=ADMIN;?>/plagins/indexnow-edit?id=<?=$item['id'];?>"><i class="fas fa-pencil-alt"></i></a></td>
								</tr>
							<?php endforeach; ?>                            
                        </tbody>
                    </table>
					</div>						
				</div>
			</div>
		</div>
	</div>
</section>