<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Компоненты</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список установленных дополнений</li>
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
                    <h3 class="card-title">Список установленных дополнений</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Название</th>
								<th>Системный URL</th>
                                <th>Версия</th>
								<th>Автор</th>
								<th>Статус</th>                                
                            </tr>
					    </thead>
                        <tbody>
                            <?php foreach($plagins as $plagin): ?>
                                <tr class="cont_td_znach">
                                    <td><a href="<?=ADMIN;?>/plagins/<?=$plagin['alias'];?>"><?=$plagin['name'];?></a></td>
									<td><?=$plagin['alias'];?></td>
                                    <td><?=$plagin['version'];?></td>
									<td><?=$plagin['author'];?></td>
									<td><?php
									if($plagin['hide'] == 'show') { $hide = "Активный"; }
									if($plagin['hide'] == 'hide') { $hide = "Неактивный"; }
									echo "".$hide."";?></td>                                    
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
<?php }else{ ?>
<div class="alert alert-warning alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
		На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>