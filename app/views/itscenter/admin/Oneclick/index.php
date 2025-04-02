<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Заказы в 1 клик</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список заказов в 1 клик</li>
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
                    <h3 class="card-title">Список заказов в 1 клик</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10px">ID</th>
								<th>Статус</th>
								<th>Наименование</th>
                                <th>Имя</th>
								<th style="width: 150px">Телефон</th>
								<th style="width: 150px">E-mail</th>
								<th style="width: 160px">Примечание</th>
								<th style="width: 60px">Дата</th>
                                <th style="width: 170px">Действия</th>
                            </tr>
					    </thead>
                        <tbody>
                            <?php foreach($clicks as $click): ?>
                                <tr class="cont_td_znach">
                                    <td><?=$click['id'];?></td>
									<td><?php
										if($click['hide']=="0") { echo "<span class=\"text-danger\">Новый</span>"; }
										if($click['hide']=="1") { echo "В обработке"; }
										if($click['hide']=="2") { echo "Обработан"; }
										?></td>
									<td><?=$click['name'];?></td>
									<td><?=$click['fio_click'];?></td>
									<td><?=$click['tell_click'];?></td>
									<td><?=$click['email_click'];?></td>
									<td><?=$click['prim_click'];?></td>                                   
                                    <td><?=$click['data_create'];?></td>
                                    <td>
										<a type="button" href="/admin/mailbox/answer?email=<?=$click['email_click'];?>&subject=Заказ товара в 1 клик на сайте <?=$namecomp?>" class="btn btn-primary btn-block btn-xs"><i class="fas fa-envelope" style="padding: 0 5px 0 0;"></i> Написать письмо</a>
										<a type="button" href="tel:<?php $tell_zv=str_replace("(","",$click['tell_click']); $tell_zv=str_replace(")","",$tell_zv); $tell_zv=str_replace(" ","",$tell_zv); $tell_zv=str_replace("-","",$tell_zv); echo "$tell_zv";	?>" class="btn btn-success btn-block btn-xs"><i class="fas fa-envelope" style="padding: 0 5px 0 0;"></i> Сделать звонок</a>
									</td>
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
<!-- /.content -->