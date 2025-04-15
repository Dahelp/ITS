<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">CRON задания</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список CRON заданий</li>
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
                <a href="<?=ADMIN;?>/cron/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить задание</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список CRON заданий</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Название</th>
								<th>Системное имя</th>
								<th>URL-путь</th>                               
								<th>Статус</th>
								<th>Дата выполнения</th>
								<th>Действия</th>                                
                            </tr>
					    </thead>
                        <tbody>
                            <?php foreach($crons as $cron): ?>
                                <tr class="cont_td_znach">
                                    <td><?=$cron['name'];?></td>
									<td><?=$cron['url_params'];?></td>
									<td><?=$cron['alias'];?></td>
                                    <td>
									  <div class="cron-status" id="cron-status-<?=$cron['id'];?>">
										<div class="progress-container">
										  <div class="progress-bar" id="progress-bar-<?=$cron['id'];?>"></div>
										</div>
									  </div>
									</td>	
									<td><?=$cron['date_update'];?></td>
									<td>
										<a href="<?=ADMIN;?>/cron/edit?id=<?=$cron['id'];?>"><i class="fas fa-pencil-alt"></i></a>
										<a class="delete" href="<?=ADMIN;?>/cron/delete?id=<?=$cron['id'];?>"><i class="fas fa-times-circle text-danger"></i></a>
										<a href="#" class="run-cron" data-cron-url="<?=PATH?>/cron/<?=$cron['url_params'];?>?id=<?=$cron['id'];?>"><i class="far fa-rocket-launch"></i></a>
										<a target="_blank" href="/cron/<?=$cron['url_download'];?>"><i class='fas fa-eye'></i></a>
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.run-cron').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const url = btn.getAttribute('data-cron-url');
            const idMatch = url.match(/id=(\d+)/);
            const cronId = idMatch ? idMatch[1] : null;

            const statusWrapper = document.getElementById('cron-status-' + cronId);
            const progressBar = document.getElementById('progress-bar-' + cronId);

            if (!statusWrapper || !progressBar) return;

            statusWrapper.classList.remove('success', 'error');
            progressBar.style.width = '0%';

            // Эмуляция плавного роста прогресса (фейковая)
            let progress = 0;
            const interval = setInterval(() => {
                if (progress < 95) {
                    progress += Math.random() * 5;
                    progressBar.style.width = progress + '%';
                }
            }, 300);

            // Запуск самого cron
            fetch(url)
                .then(response => {
                    clearInterval(interval);
                    progressBar.style.width = '100%';

                    if (response.ok) {
                        statusWrapper.classList.add('success');
                        setTimeout(() => {
                            statusWrapper.innerHTML = '<span style="color:green;">✅ Успешно</span>';
                        }, 300);
                    } else {
                        statusWrapper.classList.add('error');
                        setTimeout(() => {
                            statusWrapper.innerHTML = '<span style="color:red;">❌ Ошибка (' + response.status + ')</span>';
                        }, 300);
                    }
                })
                .catch(error => {
                    clearInterval(interval);
                    progressBar.style.width = '100%';
                    statusWrapper.classList.add('error');
                    setTimeout(() => {
                        statusWrapper.innerHTML = '<span style="color:red;">❌ Ошибка соединения</span>';
                    }, 300);
                });
        });
    });
});
</script>


<?php }else{ ?>
<div class="alert alert-warning alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
		На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>
