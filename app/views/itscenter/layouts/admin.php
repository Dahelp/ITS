<?php 
	$online = \ishop\App::on_line(); 
	$shop_name = \ishop\App::$app->getProperty('shop_name');
	$today = date("Y-m-d");
	$NewMails = \R::count('mails_imap', "is_seen != '1'");
	$NewOrders = \R::count('order', "status = '1'");
	$Availability = \R::count('mail_availability', "status_nalichiya = '0'");
?>
<?php
    header("Content-Type: text/html; charset=utf-8");
    mb_internal_encoding('UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?=$this->getMeta();?>  
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <base href="/adminlte/">
	<link rel="icon" href="<?=PATH;?>/adminlte/dist/img/AdminLTELogo.png" type="image/svg" /> 
    <link rel="shortcut icon" href="<?=PATH;?>/adminlte/dist/img/AdminLTELogo.png" type="image/svg" />
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Bootstrap Color Picker -->
  <link rel="stylesheet" href="plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayscrollbars/css/overlayscrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
  <!-- Datatables CSS-->
  <link rel="stylesheet" href="plugins/datatables/css/datatables.bootstrap5.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap5.min.css">

  <!-- bypstyle-->
  <link rel="stylesheet" href="my.css">
  <!-- jQuery 3 -->
  <script src="plugins/jquery/jquery.min.js"></script>
  
  <!-- Datatables JS -->  
	<script src="plugins/datatables/js/jquery.datatables.min.js"></script>
	<script src="plugins/datatables/js/datatables.bootstrap5.min.js"></script>
	<script src="plugins/datatables-responsive/js/datatables.responsive.min.js"></script>
	<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
	<script src="plugins/datatables-buttons/js/datatables.buttons.min.js"></script>
	<script src="plugins/datatables-buttons/js/buttons.bootstrap5.min.js"></script>
	<script src="plugins/jszip/jszip.min.js"></script>
	<script src="plugins/pdfmake/pdfmake.min.js"></script>
	<script src="plugins/pdfmake/vfs_fonts.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.print.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.colVis.min.js"></script>
	<script src="../js/typeahead.bundle.js"></script>

</head>
<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="logo_round.png" alt="<?=$shop_name?>" height="60" width="60">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" aria-expanded="false" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?= ADMIN ?>/" class="nav-link">Главная</a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a target="_blank" href="https://bypcms.ru" class="nav-link">Помощь</a>
      </li>
	  <li class="nav-item d-none d-sm-inline-block">
        <a target="_blank" href="<?=PATH;?>" class="nav-link">Просмотр сайта</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar typeahead" type="search" id="typeahead" placeholder="Поиск" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>

      <!-- Messages Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-comments"></i>
          <span class="badge badge-danger navbar-badge">3</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Гость #1
                  <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">Интересует наличие...</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 часа назад</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Гость #2
                  <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">Сколько стоят...</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 часа назад</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Гость #3
                  <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">Есть у вас доставка?</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 часа назад</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">Все сообщения</a>
        </div>
      </li>
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">15</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">15 новых уведомлений</span>
          <div class="dropdown-divider"></div>
		  <?php if($NewMails) { ?>
          <a href="<?=ADMIN?>/mailbox" class="dropdown-item">
            <i class="fas fa-file mr-2"></i> Входящие письма: <?=$NewMails;?>
            <span class="float-right text-muted text-sm">
				<?php 
					$mails_date = \R::findOne('mails_imap', 'is_seen = ? ORDER BY date_dispatch LIMIT 1', [0]);
					list($mails_pastdate, $mails_pastoclock) = explode(' ', $mails_date->date_dispatch);
					echo \ishop\App::getPeriod($mails_pastdate,$today);					
				?></span>
          </a>
          <div class="dropdown-divider"></div>
		  <?php } ?>
          <a href="<?=ADMIN?>/callback" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> Обратный звонок: 4
            <span class="float-right text-muted text-sm">3 минуты</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-users mr-2"></i> Пользователей: 8
            <span class="float-right text-muted text-sm">12 часов</span>
          </a>
          <div class="dropdown-divider"></div>
		  <?php if($NewOrders) { ?>
          <a href="<?=ADMIN?>/order" class="dropdown-item">
            <i class="fas fa-file mr-2"></i> Заказов: <?=$NewOrders;?>
            <span class="float-right text-muted text-sm">
				<?php
					$order_date = \R::findOne('order', 'status = ? ORDER BY date DESC LIMIT 1', [1]);					
					list($pastdate, $pastoclock) = explode(' ', $order_date->date);										
					echo \ishop\App::getPeriod($pastdate,$today);					
				?></span>
          </a>
		  <div class="dropdown-divider"></div>
		  <?php } ?>
		  <?php if($Availability) { ?>
          <a href="<?=ADMIN?>/availability" class="dropdown-item">
            <i class="fas fa-file mr-2"></i> Заявка о поступлении: <?=$Availability;?>
            <span class="float-right text-muted text-sm">
				<?php
					$availability_date = \R::findOne('mail_availability', 'status_nalichiya = ? ORDER BY data_create DESC LIMIT 1', [0]);					
					list($pastdate, $pastoclock) = explode(' ', $availability_date->date);										
					echo \ishop\App::getPeriod($pastdate,$today);					
				?></span>
          </a>
          <div class="dropdown-divider"></div>
		  <?php } ?>
          <a href="<?= ADMIN ?>/notifications" class="dropdown-item dropdown-footer">Просмотр всех уведомлений</a>
        </div>
      </li>
      
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?= ADMIN ?>/" class="brand-link">
      <img src="logo_round.png" alt="<?=$shop_name?>" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light"><strong><?=$shop_name?></strong></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="<?= ADMIN ?>/user/edit?id=<?=$_SESSION['user']['id'];?>" class="d-block"><?=$_SESSION['user']['name'];?></a>
        </div>
		<div class="out_user">
			<a href="/user/logout" title="Выход из панели управления"><i class="fas fa-sign-out-alt"></i></a>
		</div>		
      </div>
		
      <!-- Sidebar Menu -->
      <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Optionally, you can add icons to the links -->
                <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/"><i class="nav-icon fas fa-home"></i> <p>Главная</p></a></li>
				<li class="nav-item">
                    <a class="nav-link" href="#">
						<i class="nav-icon  far fa-file-alt"></i>
						<p>Контент
							<i class="fas fa-angle-left right"></i>
						</p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/contents/pages"><i class="far fa-circle nav-icon"></i><p>Список контента</p></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/contents/type-content"><i class="far fa-circle nav-icon"></i><p>Типы контента</p></a></li>
                    </ul>
                </li>
				<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/leads"><i class="nav-icon fa fa-tty menu-icon"></i> <p>Лиды</p></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/order"><i class="nav-icon fas fa-shopping-cart"></i> <p>Заказы</p></a></li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
						<i class="nav-icon fas fa-cubes"></i>
						<p>Товары
							<i class="fas fa-angle-left right"></i>
						</p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/product"><i class="far fa-circle nav-icon"></i><p>Список товаров</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/category"><i class="far fa-circle nav-icon"></i><p>Категории</p></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/attribute"><i class="far fa-circle nav-icon"></i><p>Атрибуты</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/brand"><i class="far fa-circle nav-icon"></i><p>Производители</p></a></li>
						<li class="nav-item"><a class="nav-link" href="#">
								<i class="far fa-circle nav-icon"></i>
								<p>Фильтры
									<i class="fas fa-angle-left right"></i>
								</p>
							</a>
							<ul class="nav nav-treeview">
								<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/filtrs/attribute-group"><i class="far fa-dot-circle nav-icon"></i><p>Группы фильтров</p></a></li>
								<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/filtrs/attribute"><i class="far fa-dot-circle nav-icon"></i><p>Фильтры</p></a></li>
							</ul>
						</li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/action"><i class="far fa-circle nav-icon"></i><p>Акции</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/import"><i class="far fa-circle nav-icon"></i><p>Импорт</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/export"><i class="far fa-circle nav-icon"></i><p>Экспорт</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/review"><i class="far fa-circle nav-icon"></i><p>Отзывы</p></a></li>
                    </ul>
                </li>
				
				<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/plagins"><i class="nav-icon fad fa-th"></i> <p>Компоненты</p></a></li>
				
                <li class="nav-item">
                    <a class="nav-link" href="#">
						<i class="nav-icon fas fa-users"></i>
						<p>Пользователи
							<i class="fas fa-angle-left right"></i>
						</p>
                    </a>
                    <ul class="nav nav-treeview">
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/company"><i class="far fa-circle nav-icon"></i><p>Компании</p></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/user"><i class="far fa-circle nav-icon"></i><p>Клиенты</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/user/customers"><i class="far fa-circle nav-icon"></i><p>Пользователи</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/user/groups"><i class="far fa-circle nav-icon"></i><p>Группы</p></a></li>                        
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/user/roles"><i class="far fa-circle nav-icon"></i><p>Роли</p></a></li>
						
                    </ul>
                </li>
				
                <li class="nav-item">
                    <a class="nav-link" href="#">
						<i class="nav-icon far fa-envelope"></i>
						<p>Почта
							<i class="fas fa-angle-left right"></i>
						</p>
                    </a>
                    <ul class="nav nav-treeview">
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/mailbox"><i class="far fa-circle nav-icon"></i><p>Входящие</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/mailbox/compose"><i class="far fa-circle nav-icon"></i><p>Написать</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/newsletter"><i class="far fa-circle nav-icon"></i><p>Рассылки</p></a></li>
						
                    </ul>
                </li>
								
				<li class="nav-item">
                    <a class="nav-link" href="#">
						<i class="nav-icon fas fa-cog"></i>
						<p>Настройки
							<i class="fas fa-angle-left right"></i>
						</p>
                    </a>
                    <ul class="nav nav-treeview">
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/options"><i class="far fa-circle nav-icon"></i><p>Основные настройки</p></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/cache"><i class="far fa-circle nav-icon"></i><p>Кэширование</p></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/currency"><i class="far fa-circle nav-icon"></i><p>Валюты</p></a></li>
						<li class="nav-item"><a class="nav-link" href="<?= ADMIN ?>/cron"><i class="far fa-circle nav-icon"></i><p>CRON задания</p></a></li>
                    </ul>
                </li>
				
            </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>
		
  <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?=$content;?>
    </div>
    <!-- /.content-wrapper -->
  <footer class="main-footer">
    <strong>Copyright &copy; 2010-2021 <a href="https://bypcms.ru">BYP.CMS</a>.</strong>
    <div class="float-right d-none d-sm-inline-block">
      <b>Версия</b> 3.0.0
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<script>
    var path = '<?=PATH;?>',
        adminpath = '<?=ADMIN;?>';
</script>


<!-- jQuery UI 1.11.4 -->
<script src="plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<script src="/js/ajaxupload.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Select2 -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/select2/js/i18n/ru.js"></script>

<script src="/js/validator.js"></script>
<!-- ChartJS -->
<script src="plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- InputMask -->
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<!-- date-range-picker -->
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<!-- bootstrap color picker -->
<script src="plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- telefon -->
<script src="maskedinput.min.js" ></script> <!-- telefon -->
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="dist/js/demo.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="dist/js/pages/dashboard.js"></script>
<!-- Ckeditor -->
<script src="bower_components/ckeditor/ckeditor.js"></script>
<script src="bower_components/ckeditor/adapters/jquery.js"></script>
<!-- Myscripts js -->
<script src="my.js"></script>
<script>
  $(function () {
	//Maska
	$(".phonez").mask("+7 (999) 999-99-99");
    <?php $scrnastr = \R::getAll("SELECT*FROM options WHERE tip = 'Оформление'");
		foreach($scrnastr as $scr) { ?>
			//color picker with addon
			$('.my-colorpicker<?=$scr["option_id"]?>').colorpicker()
			$('.my-colorpicker<?=$scr["option_id"]?>').on('colorpickerChange', function(event) {
				$('.my-colorpicker<?=$scr["option_id"]?> .fa-square').css('color', event.color.toString());
			})
	<?php } ?>	
  })
</script>
<script>
	//Date and time picker
    $('#reservationdatetime').datetimepicker({
		icons: { time: 'far fa-clock' },
		format: 'YYYY-MM-DD HH:mm:ss'	
	});
	$('#reservationdatetime2').datetimepicker({
		icons: { time: 'far fa-clock' },
		format: 'YYYY-MM-DD HH:mm:ss'	
	});
</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.4/pdfmake.js" integrity="sha512-yfb1lLOhiYYJh7C3dsBE4XGCnDCEe4dJ/jdVgoinVdKwVuDP2SJqrEngf0Q+m6gaU8vOjCaJ0EaeakGzXXfWIA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.4/vfs_fonts.js" integrity="sha512-cktKDgjEiIkPVHYbn8bh/FEyYxmt4JDJJjOCu5/FQAkW4bc911XtKYValiyzBiJigjVEvrIAyQFEbRJZyDA1wQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>


</body>
</html>