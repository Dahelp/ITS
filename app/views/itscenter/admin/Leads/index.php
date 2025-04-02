<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Лиды</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Лиды</li>
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
			<div class="menu_btn">
                <a href="<?=ADMIN;?>/leads/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Новый лид</a>
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title">Список лидов</h3>
                </div>
                <!-- /.card-header -->               
				<div class="card-body p-4">
					<div class="table-responsive">
						<table id="example" class="table display" width="100%">
							<thead>
								<tr>
									<th>#</th>
									<th>Имя</th>
									<th>Компания</th>
									<th>Эл. почта</th>
									<th>Телефон</th>
									<th>Значение лида</th>
									<th>Теги</th>
									<th></th>
									<th>Прикреплён к</th>
									<th>Статус</th>
									<th>Источник</th>
									<th>Последний контакт</th>
									<th>Добавлен</th>
									<th>Действия</th>
								</tr>
							</thead>
						</table>  
					</div>
				</div>

				<div class="card-footer p-0">
					<div class="mailbox-controls">
						
					</div>
				</div>                
            </div>
        </div>
    </div>
</section>
<!-- /.content -->


<script>
	$(document).ready(function () {
		var dt = $('#example').DataTable({		
			"processing": true,
			"serverSide": true,			
			"lengthChange": true,
			"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
			"aoColumns": [
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				{ "visible": false },
				null,
				null,
				null,
				null,
				null,
				{ 'bSortable': false },
			],
			"aaSorting": [[ 0, "desc" ]],
			"ajax": {
				url: adminpath + '/leads/server-processing<?php if($_GET["status"]) { echo "?status=".$_GET["status"].""; } ?>',		
			},			
		});
	})
</script>