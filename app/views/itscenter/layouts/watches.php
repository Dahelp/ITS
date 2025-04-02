<?php new \app\widgets\botblockip\Botblockip(); //блокировка подозрительных IP ?>
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<base href="<?=PATH?>/">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />	
	<?php if($this->route["controller"] == "Product") {
	if(!empty($product->hide =="lock")) { ?>
	<meta name="robots" content="noindex, nofollow" />
	<?php }else{ ?>
	<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
	<?php } ?>
	<?php }else{ ?>
	<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
	<?php } ?>
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> 
	<link rel="icon" href="images/favicon.svg" type="image/svg" />
    <link rel="shortcut icon" href="images/favicon.svg" type="image/svg" />
    <?=$this->getMeta(); ?>	
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
	<meta name="yandex-verification" content="bd06556907d580a8" />
	<meta name="google-site-verification" content="YTzQMjO51p1Hu9bD8voK6ug0RLNL5sswqqgk55ECgV4" />
	<link rel="stylesheet" href="css/bootstrap.css" />
    <link rel="stylesheet" href="css/slider.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="css/flexslider.css" type="text/css" media="all" />
	<link rel="stylesheet" href="css/aiz-core.css" type="text/css" media="all" />
    <link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
	<link rel="stylesheet" href="public/adminlte/plugins/fontawesome-free/css/all.min.css" />
	
	<link rel="stylesheet" href="public/adminlte/plugins/select2/css/select2.min.css" />
	<link rel="stylesheet" href="public/adminlte/plugins/select2-bootstrap5-theme/select2-bootstrap-5-theme.min.css" />
	<link rel="stylesheet" href="css/swiper-bundle.min.css" />

	<script src="js/imask.min.js"></script><!-- telefon -->
	<meta name="geo.placename" content="ул. Комунальная, 26, стр. 2, Климовск, Московская область, Россия, 142184" />
	<meta name="geo.position" content="55.360413;37.562371" />
	<meta name="geo.region" content="RU-Московская область" />
	<meta name="ICBM" content="55.360413, 37.562371" />
	<?php $options = \R::getAll("SELECT znachenie, alt_name FROM options WHERE tip = 'Оформление'");
		foreach($options as $option){
			$znachenie = $option["znachenie"];
			$alt_name = $option["alt_name"];
			eval('$$alt_name = "$znachenie";');
		}

	?>
		<style type="text/css">	
			:root {
				--body: <?php if(!empty($body_background)) { echo "".$body_background.""; }else{ echo "#fff"; } ?>;
				--category: <?php if(!empty($font_background)) { echo "".$font_background.""; }else{ echo "#f2f3f8"; } ?>;
				--footer: <?php if(!empty($main_background)) { echo "".$main_background.""; }else{ echo "#2d2d39"; } ?>;
				--a: <?php if(!empty($a_background)) { echo "".$a_background.""; }else{ echo "#000"; } ?>;
				--blue: #007bff;
				--indigo: #6610f2;
				--purple: #6f42c1;
				--pink: #e83e8c;
				--red: #dc3545;
				--orange: #fd7e14;
				--yellow: #ffc107;
				--green: #28a745;
				--teal: #20c997;
				--cyan: #17a2b8;
				--white: #fff;
				--gray: #6c757d;
				--gray-dark: #343a40;
				--primary: #C0392B;
				--hov-primary: #C0392B;
				--soft-primary: rgba(230,46,4,0.15);
				--secondary: #8f97ab;
				--soft-secondary: rgba(143, 151, 171, 0.15);
				--success: #198754;
				--soft-success: rgba(10, 187, 117, 0.15);
				--info: #25bcf1;
				--soft-info: rgba(37, 188, 241, 0.15);
				--warning: #ffc519;
				--soft-warning: rgba(255, 197, 25, 0.15);
				--danger: <?php if(!empty($knp_background)) { echo "".$knp_background.""; }else{ echo "#ef486a"; } ?>;
				--soft-danger: rgba(239, 72, 106, 0.15);
				--light: #f2f3f8;
				--dark: #111723;
				--soft-dark: rgba(42, 50, 66, 0.15);
				--breakpoint-xs: 0;
				--breakpoint-sm: 576px;
				--breakpoint-md: 768px;
				--breakpoint-lg: 992px;
				--breakpoint-xl: 1200px;
				--font-family-sans-serif: -apple-system, BlinkMacSystemFont, "Segoe UI",
					Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif,
					"Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol",
					"Noto Color Emoji";
				--font-family-monospace: SFMono-Regular, Menlo, Monaco, Consolas,
					"Liberation Mono", "Courier New", monospace;
			}
		</style>
		
	<?php
		$yametrika = \ishop\App::options('yametrika');
		$gglmetrika = \ishop\App::options('gglmetrika');
	?>
	<!-- Yandex.Metrika counter -->
	<script type="text/javascript" >
	   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
	   m[i].l=1*new Date();
	   for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
	   k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
	   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

	   ym(<?=$yametrika?>, "init", {
			clickmap:true,
			trackLinks:true,
			accurateTrackBounce:true,
			webvisor:true,
			ecommerce:"dataLayer"
	   });
	</script>
	<noscript><div><img src="https://mc.yandex.ru/watch/<?=$yametrika?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
	<!-- /Yandex.Metrika counter -->
	
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?=$gglmetrika?>"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', '<?=$gglmetrika?>');
	</script>
	
</head>
<body>

<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
	<div class="mbl-menu">
		<div class="mbl-close">
			<div class="mbl-cl-icon"><button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation"><i class="fal fa-times"></i></button></div>
		</div>
		<div class="mbl-info">
			<ul>
				<li><a href="pages/kak-kupit" title="">Как купить</a></li>
				<li><a href="promo" title="">Акции</a></li>
				<li><a href="pages/about-us" title="">О компании</a></li>
				<li><a href="pages/sotrudnichestvo" title="">Сотрудничество</a></li>
				<li><a href="pages/contacts" title="">Адреса и контакты</a></li>
				
			</ul>
		</div>
	</div>
</div>
<!--top-header-->
<div class="top-header">
	<header id="masthead" class="site-header">
		<div class="container">	
			<div class="top-header-main">
				<div class="col-md-12 menu">                
					<div class="col-md-3 menu-left">
						<div class="drop">
						</div>
					</div><!-- menu1 -->
					<div class="col-md-6 menu-center">
						<div class="drop">
							<div class="menu-inner">
								<a href="pages/kak-kupit" title="">Как купить</a>
								<a href="promo" title="">Акции</a>
								<a href="pages/about-us" title="">О компании</a>
								<a href="pages/sotrudnichestvo" title="">Сотрудничество</a>
								<a href="pages/contacts" title="">Адреса и контакты</a>
							</div>
						</div>
					</div><!-- menu2 -->
					<div class="col-md-3 menu-right">
						<div class="drop">
							<div class="user-auth">
								<?php if(empty($_SESSION['user']['id'])) { ?>
									<a data-toggle="modal" data-target="#Modallogin" href="javascript:;"><span>Вход</span></a><a href="user/signup" title=""><span>Регистрация</span></a>
								<?php }else{ ?>
									<a href="user/cabinet"><span>Личный кабинет</span></a><a href="<?=PATH?>/user/logout" title=""><span>Выход</span></a>
								<?php } ?>
							</div>
						</div>
					</div><!-- menu3 -->                
				</div><!-- blk2 -->
				<div class="col-md-12 str_logo">
					<div class="col-md-3 ftr-blk-left">
						<div class="drop">
							<div id="logo">			
								<?php if($this->route["controller"] != "Main") { ?><a href="/"><?php }else {} ?>							
									<img src="../images/logo.png" title="ИТС-Центр" alt="ИТС-Центр" class="img-responsive">
								<?php if($this->route["controller"] != "Main") { ?></a><?php }else {} ?>
							</div>
							<div class="clearfix"></div>
						</div>
					</div><!-- blk1 -->
					<div class="col-md-6 ftr-blk-center">
						<!--<div class="text-center pt-1"><a href="https://its-center.ru/promo/chernaya-pyatnica-do-novogo-goda" title="Чёрная пятница"><img src="../images/black-friday.jpg" alt="" title=""></a></div>-->
					</div><!-- blk2 -->
					<div class="col-md-3 ftr-blk-right">
						<div class="drop">
							<?php $tell_zv = \ishop\App::options('option_telefon'); ?>
							<div class="tel-navbar">
								<div class="tel"><span class="tel-icon"><i class="fas fa-phone fa-flip-horizontal"></i></span> <span class="tel-inner"><?=$tell_zv?></span></div><div class="tel-priem">Приём звонков: 9:00 - 17:00</div>
							</div>
							<div class="m-tel">
								<a href="tel:<?php $telefon=str_replace("(","",$tell_zv); $telefon=str_replace(")","",$telefon); $telefon=str_replace(" ","",$telefon); $telefon=str_replace("-","",$telefon); echo "$telefon";	?>">
									<i class="fas fa-phone"></i>
								</a>
							</div>
							<div class="menu-inner-navbar">
								<nav class="navbar navbar-expand-lg navbar-light">
																		
										<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
											<span class="navbar-toggler-icon"></span>
										</button>
										
									
									
								</nav>							
							</div>
						</div>					
					</div><!-- blk3 -->
				</div>
				<div class="col-md-12 nz-header">
					<div class="col-md-3 byp-mmenu">
						<div class="drop">						
							<div class="list-group">
								<div class="nav-item dropdown">		
									<button class="navbar-nav nav-link dropdown-toggle ps-lg-0 menu_links-item"><i class="fas fa-bars"></i> <span>Каталог товаров</span></button>
									
									
									
									
								</div>									
							</div>
							
						</div>
					</div><!-- vpdmenu -->
					<div class="mmenu-close"></div>
					<div class="col-md-6 byp-msearch">
						<div class="drop">
							<div class="search">
								<div class="search_tb">
									<form action="search" method="get" autocomplete="off" aria-label="Ajax search form">
										<input type="text" aria-label="Search input" class="typeahead search_btn" id="typeahead" name="s" placeholder="Введите артикул, наименование или код запчасти">
										<button type="submit" class="search_botton" aria-label="Autocomplete input, do not use this"><i class="fas fa-search"></i></button>
									</form>
								</div>
							</div>
						</div>
					</div><!-- poisk -->
					<div class="col-md-3 cart-header">					
							<div class="col-md-6 zakladki">
								<i class="fas fa-tasks"></i> <span class="comparison_kol">
								<?php if($this->route["controller"] == "Comparison") { ?>
									<?php if($_SESSION['comparison_count']) { ?>Сравнение (<span id="comparison_kol"><?php echo $_SESSION['comparison_count']; ?></span>)<?php }else{ ?> Сравнение (<span id="comparison_kol">0</span>)<?php } ?>
								<?php }else{ ?>	
								<?php if($_SESSION['comparison_count']==1) { ?>Сравнение (<span id="comparison_kol">1</span>)</a><?php } ?>
								<?php if($_SESSION['comparison_count']>1) { ?><a href="comparison">Сравнение (<span id="comparison_kol"><?php echo $_SESSION['comparison_count']; ?></span>)</a><?php } ?>
								<?php if($_SESSION['comparison_count']==0) { ?>Сравнение (<span id="comparison_kol">0</span>)<?php } ?>
								<?php } ?>
								</span>
							</div>
							<div class="col-md-6 cartblk">
								<div id="cart" class="btn-group btn-block">
									<div class="btn-n0 btn-block">
										<i class="fas fa-shopping-cart"></i>
										<a href="cart/show" onclick="getCart(); return false;">
										<?php if(!empty($_SESSION['cart'])): ?>								
											Корзина (<span class="simpleCart_qty" id="cart-total"><?=$_SESSION['cart.qty']?></span>)							
										<?php else: ?>
											Корзина (<span class="simpleCart_qty" id="cart-total">0</span>)
										<?php endif; ?>
										</a>
									</div>
								</div>
							</div>
							<div class="clearfix"></div>					
					</div><!-- korz -->
					<div class="clearfix"></div>
				</div>
				<div class="dropdown mega-dropdown">									
					<div class="dropdown-menu clear">				
						<a class="dropdown-item submenu" href="category/industrialnye-shiny">Индустриальные шины</a>
						<div class="dropdown-menu-byp">
							<div class="mbl-1">
								<div class="menu-h2">Индустриальные шины</div>
								<a class="dropdown-item-byp" href="category/shiny-dlya-minipogruzchikov">Шины для минипогрузчиков</a>
								<a class="dropdown-item-byp" href="category/shiny-dlya-vilochnyh-pogruzchikov">Шины для вилочных погрузчиков</a>
								<a class="dropdown-item-byp" href="category/shiny-dlya-ekskavatorov-pogruzchikov">Шины для экскаваторов-погрузчиков</a>
								<a class="dropdown-item-byp" href="category/shiny-dlya-frontalnyh-pogruzchikov">Шины для фронтальных погрузчиков</a>
								<a class="dropdown-item-byp" href="category/shiny-dlya-kolesnyh-ekskavatorov">Шины для колесных экскаваторов</a>
								<a class="dropdown-item-byp" href="category/shiny-dlya-gruntovyh-katkov">Шины для грунтовых катков</a>
								<a class="dropdown-item-byp" href="category/shiny-dlya-greyderov">Шины для грейдеров</a>
								<a class="dropdown-item-byp" href="category/shiny-dlya-shahtnoy-tehniki">Шины для шахтной техники</a>
							</div>
							<div class="mbl-2">
								<div class="menu-h2">Популярные размеры шин</div>
								<div class="mbl-popul">
									<a class="dropdown-item-byp" href="size/4.00-8">4.00-8</a>
									<a class="dropdown-item-byp" href="size/10-16.5">10-16.5</a>
									<a class="dropdown-item-byp" href="size/5.00-8">5.00-8</a>
									<a class="dropdown-item-byp" href="size/12-16.5">12-16.5</a>
									<a class="dropdown-item-byp" href="size/6.00-9">6.00-9</a>
									<a class="dropdown-item-byp" href="size/12.5/80-18">12.5/80-18</a>
									<a class="dropdown-item-byp" href="size/18.4-26">18.4-26</a>						
									<a class="dropdown-item-byp" href="size/16.9-24">16.9-24</a>
									<a class="dropdown-item-byp" href="size/23.1-26">23.1-26</a>						
									<a class="dropdown-item-byp" href="size/16.9-28">16.9-28</a>
									<a class="dropdown-item-byp" href="size/28*9-15">28*9-15</a>						
									<a class="dropdown-item-byp" href="size/17.5-25">17.5-25</a>
									<a class="dropdown-item-byp" href="size/405/70-20">405/70-20</a>							
									<a class="dropdown-item-byp" href="size/20.5-25">20.5-25</a>
									<a class="dropdown-item-byp" href="size/405/70-24">405/70-24</a>
									<a class="dropdown-item-byp" href="size/23.5-25">23.5-25</a>						
								</div>
							</div>
							<div class="mbl-3">
								<div class="menu-h2">Популярные производители</div>						
									<a class="dropdown-item-byp" href="brand/ekka">EKKA</a>
									<a class="dropdown-item-byp" href="brand/huiton">HUITON</a>
									<a class="dropdown-item-byp" href="brand/ist">IST</a>
									<a class="dropdown-item-byp" href="brand/superguider">Superguider</a>
									<a class="dropdown-item-byp" href="brand/solid star">SOLID STAR</a>
									<a class="dropdown-item-byp" href="brand/xin feiya">Xin Feiya</a>
									<a class="dropdown-item-byp" href="brand/hengruida">Hengruida</a>
									<a class="dropdown-item-byp" href="brand/primex">Primex</a>							
							</div>
						</div>
						<a class="dropdown-item submenu" href="category/atv">Шины для квадроциклов АТВ</a>
						<div class="dropdown-menu-byp">					
							<div class="mbl-1">
								<div class="menu-h2">Шины на диск</div>
								<a class="dropdown-item-byp" href="category/atv?filter=1">4 дюйма</a>
								<a class="dropdown-item-byp" href="category/atv?filter=2">6 дюймов</a>
								<a class="dropdown-item-byp" href="category/atv?filter=3">7 дюймов</a>
								<a class="dropdown-item-byp" href="category/atv?filter=4">8 дюймов</a>
								<a class="dropdown-item-byp" href="category/atv?filter=5">9 дюймов</a>
								<a class="dropdown-item-byp" href="category/atv?filter=6">10 дюймов</a>
								<a class="dropdown-item-byp" href="category/atv?filter=7">12 дюймов</a>
								<a class="dropdown-item-byp" href="category/atv?filter=8">14 дюймов</a>
							</div>
							<div class="mbl-2">
								<div class="menu-h2">Популярные размеры шин</div>
								<div class="mbl-popul">
									<a class="dropdown-item-byp" href="size/13x5-6">13x5-6</a>
									<a class="dropdown-item-byp" href="size/15x6-6">15x6-6</a>
									<a class="dropdown-item-byp" href="size/145/70-6">145/70-6</a>
									<a class="dropdown-item-byp" href="size/16x6.50-8">16x6.50-8</a>
									<a class="dropdown-item-byp" href="size/18x8.50-8">18х8.50-8</a>
									<a class="dropdown-item-byp" href="size/19x9.50-8">19x9.50-8</a>
									<a class="dropdown-item-byp" href="size/20x10-9">20x10-9</a>						
									<a class="dropdown-item-byp" href="size/20x10-8">20x10-8</a>
									<a class="dropdown-item-byp" href="size/20x11-9">20х11-9</a>						
									<a class="dropdown-item-byp" href="size/21x7-10">21x7-10</a>
									<a class="dropdown-item-byp" href="size/22x7-10">22x7-10</a>						
									<a class="dropdown-item-byp" href="size/23x7-10">23x7-10</a>
									<a class="dropdown-item-byp" href="size/25x8-12">25x8-12</a>							
									<a class="dropdown-item-byp" href="size/25x10-12">25x10-12</a>
									<a class="dropdown-item-byp" href="size/26x9-12">26x9-12</a>
									<a class="dropdown-item-byp" href="size/26x11-12">26x11-12</a>						
								</div>
							</div>
							<div class="mbl-3">
								<div class="menu-h2">Комплект шин на квадроцикл</div>
								<div class="mbl-img">
									<img src="../images/komplect_kvadro.jpg" alt="Комплект шин на квадроцикл" title="Комплект шин на квадроцикл" />
								</div>							
							</div>
						</div>
						<a class="dropdown-item submenu" href="category/diski">Диски на технику</a>
						<div class="dropdown-menu-byp">					
							<div class="mbl-1">
								<div class="menu-h2">Диски для погрузчиков и грузовых машин</div>
								<a class="dropdown-item-byp" title="Диски для вилочных погрузчиков" href="category/diski-dlya-vilochnyh-pogruzchikov">Диски для вилочных погрузчиков</a>
								<a class="dropdown-item-byp" title="Диски для минипогрузчиков" href="category/diski-dlya-minipogruzchikov">Диски для минипогрузчиков</a>
								<a class="dropdown-item-byp" title="Диски для экскаваторов-погрузчиков" href="category/diski-dlya-ekskavatorov-pogruzchikov">Диски для экскаваторов-погрузчиков</a>
								<a class="dropdown-item-byp" title="Диски для грузовой техники" href="category/diski-dlya-gruzovoy-tehniki">Диски для грузовой техники</a>
								<a class="dropdown-item-byp" title="Диски для фронтальных погрузчиков" href="category/diski-dlya-frontalnyh-pogruzchikov">Диски для фронтальных погрузчиков</a>						
							</div>
							<div class="mbl-2">
								<div class="menu-h2">Диски для минипогрузчиков</div>
								<div class="mbl-img">
									<a href="category/diski-dlya-minipogruzchikov" title="Диски для вилочных погрузчиков">
										<img src="../images/disk-mini.jpg" alt="Диски для минипогрузчиков" title="Диски для минипогрузчиков" />
									</a>
								</div>
							</div>
							<div class="mbl-3">
								<div class="menu-h2">Диски для вилочных погрузчиков</div>
								<div class="mbl-img">
									<a href="category/diski-dlya-vilochnyh-pogruzchikov" title="Диски для вилочных погрузчиков">
										<img src="../images/disk-vil.jpg" alt="Диски для вилочных погрузчиков" title="Диски для вилочных погрузчиков" />
									</a>
								</div>							
							</div>
						</div>
						<a class="dropdown-item submenu" href="category/filtry">Фильтры для спецтехники</a>
						<div class="dropdown-menu-byp">
							<div class="mbl-1">
								<div class="menu-h2">Фильтры для спецтехники</div>
								<a class="dropdown-item-byp" href="category/vozdushnye-filtry">Воздушные фильтры</a>
								<a class="dropdown-item-byp" href="category/gidravlicheskie-filtry">Гидравлические фильтры</a>
								<a class="dropdown-item-byp" href="category/maslyanye-filtry">Масляные фильтры</a>
								<a class="dropdown-item-byp" href="category/toplivnye-filtry">Топливные фильтры</a>
								<a class="dropdown-item-byp" href="category/filtry-osushiteli">Фильтры салона (кабины)</a>
								<a class="dropdown-item-byp" href="category/filtry-osushiteli">Фильтры осушители</a>
								<a class="dropdown-item-byp" href="category/filtry-ohlazhdayuschey-zhidkosti">Фильтры охлаждающей жидкости</a>
								<a class="dropdown-item-byp" href="category/filtry-sapuna">Фильтры сапуна</a>
							</div>
							<div class="mbl-2">
								<div class="menu-h2">Комплект фильтров на технику JCB</div>
								<div class="mbl-img">
									<img src="../images/mini_filters_its_jcb.jpg" alt="Комплект фильтров на технику JCB" title="Комплект фильтров на технику JCB" />
								</div>
							</div>
							<div class="mbl-3">
								<div class="menu-h2">Комплект фильтров на технику BOBCAT</div>
								<div class="mbl-img">
									<img src="../images/mini_filters_its_bobcat.jpg" alt="Комплект фильтров на технику BOBCAT" title="Комплект фильтров на технику BOBCAT" />
								</div>							
							</div>
						</div>
						<a class="dropdown-item submenu" href="category/kamery-i-obodnye-lenty">Камеры и ободные ленты</a>
						<div class="dropdown-menu-byp">
							<div class="mbl-1">
								<div class="menu-h2">Камеры и ободные ленты</div>
								<a class="dropdown-item-byp" href="category/kamery">Камеры</a>
								<a class="dropdown-item-byp" href="category/obodnye-lenty">Ободные ленты</a>
								<a class="dropdown-item-byp" href="category/uplotnitelnye-kolca">Уплотнительные кольца (O-Ring)</a>
							</div>
							<div class="mbl-2">
								<div class="menu-h2">Популярные размеры камер</div>
								<div class="mbl-popul">
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=226">4.00-8</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=233">5.00-8</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=234">6.00-9</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=235">6.50-10</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=238">8.25-15</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=242">10.00-20</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=325">11.00-20</a>	
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=339">14.9-24</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=418">14.9-28</a>							
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=270">16.9-24</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=272">16.9-28</a>						
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=297">17.5-25</a>
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=299">20.5-25</a>						
									<a class="dropdown-item-byp" href="category/kamery-i-obodnye-lenty?filter=300">23.5-25</a>													
								</div>
							</div>
						</div>
						<a class="dropdown-item submenu" href="category/gruzovye-shiny">Грузовые шины</a>
						<div class="dropdown-menu-byp">
							<div class="mbl-1">
								<div class="menu-h2">Производители</div>						
									<a class="dropdown-item-byp" href="brand/kama">КАМА</a>
									<a class="dropdown-item-byp" href="brand/annaite">ANNAITE</a>
							</div>
							<div class="mbl-2">
								<div class="menu-h2">Популярные размеры шин</div>
								<div class="mbl-popul">
									<a class="dropdown-item-byp" href="size/12.00R20">12.00R20</a>
									<a class="dropdown-item-byp" href="size/275/70R22.5">275/70R22.5</a>																			
								</div>
							</div>
							<div class="mbl-3">
								<div class="menu-h2">Самый популярный товар</div>
								<div class="mbl-img">
									<img src="../images/product/baseimg/06bde34572e293f789e67c9b6a91807c.jpg" alt="315/80R22.5 F NF-202 КАМА Шина грузовая рулевая" title="315/80R22.5 F NF-202 КАМА Шина грузовая рулевая" />
								</div>							
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
	<!--top-header-->
	<div class="content">
		<div class="container">
			<div class="row">			
				<div class="col-md-12">
					<noindex>
					<?php if(isset($_SESSION['error'])): ?>
						<div class="alert alert-danger">
							<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
						</div>
					<?php endif; ?>
					<?php 
						$informations = \ishop\App::options('option_informations');				
						if($informations):
					?>
						<div class="alert alert-danger">
							<?=$informations?>
						</div>
					<?php endif; ?>
					<?php if(isset($_SESSION['success'])): ?>
						<div class="alert alert-success">
							<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
						</div>
					<?php endif; ?>
					</noindex>
				</div>
			</div>
		</div>

		<?php /*debug($main_title);*/ if(!empty($main_title[2])) { echo "".$main_title[2].""; } ?>
		<?php if(!empty($content)) { echo $content; } ?>
	</div>

<div class="decide">
	<div class="container">
		<div class="lines">
			<div class="lines__item"></div>
			<div class="lines__item"></div>
			<div class="lines__item"></div>
			<div class="lines__item"></div>
			<div class="lines__item"></div>
		</div>
		<div class="container-big decide-cont fix-title psr v1">
			<div class="decide-left">


				<div class="title rel fw4 mb tal fade_in"> <span class="fwb">Не можете<br>
определиться? </span>					<div class="fl-dot"></div>
				</div>

				<div class="text24 msm">
					Бесплатно проконсультируем<br>
и поможем подобрать товар по технике				</div>

				<div class="decide-left__item mb">
					<div class=" mr">
						<div class="kr-text t2">
							<div class="kr-text__cir mrm"></div>
                            <div class="tsm12">
                                <b class="fw7">Звоните Пн-Пт</b><br>с 9:00 до 17:00
							</div>
						</div>
					</div>

					<div class="">
													<div class="phone-block row-vcenter mbs">
								<div class="phone-block__ico col-center mrm">
									<i class="fas fa-phone fa-flip-horizontal"></i>
								</div>
								<a href="tel:<?php $telefon=str_replace("(","",$tell_zv); $telefon=str_replace(")","",$telefon); $telefon=str_replace(" ","",$telefon); $telefon=str_replace("-","",$telefon); echo "$telefon";	?>" class="phone-block__text black text-md link-hover fw7"><?=$tell_zv?></a>
							</div>
													<div class="phone-block row-vcenter mbs">
								<div class="phone-block__ico col-center mrm">
									<i class="fas fa-at"></i>
								</div>
								<a href="mailto:info@its-center.ru" class="phone-block__text black text-md link-hover fw7">info@its-center.ru</a>
							</div>
																		
						
					</div>
				</div>
				<div class="dline msm"></div>


				
			</div>

			<div class="decide-right">
				<div class="decide-right-bg">
					<img src="images/cir.svg" data-src="images/cir.svg" alt="" class="decide-right-cir ls-is-cached lazyloaded">
				</div>
				
				<div class="text24 msm">
					Вам перезвонит <span class="fw4">наш менеджер</span>				</div>

				<img src="images/w.png" data-src="images/w.png" alt="" class="decide-right-wm ls-is-cached lazyloaded">
				<div class="decide-right__block">
					<ul class="decide-right-ul tsm13 ">
												<li class="row-vcenter mbm">
							<span class="main__y-ico col-center mr">
								<img class=" ls-is-cached lazyloaded" src="images/ar-ico.png" data-src="images/ar-ico.png" alt="">
							</span>
							<span class="fw7 lhm">
								Поможем определиться <br>
<span class="fw4">с выбором, исходя из<br>
Вашего бюджета</span>								
							</span>
						</li>
											<li class="row-vcenter mbm">
							<span class="main__y-ico col-center mr">
								<img class=" ls-is-cached lazyloaded" src="images/ar-ico.png" data-src="images/ar-ico.png" alt="">
							</span>
							<span class="fw7 lhm">
								Расскажем, <span class="fw4"><br>
на чем<br>
можно сэкономить</span>								
							</span>
						</li>
											<li class="row-vcenter mbm">
							<span class="main__y-ico col-center mr">
								<img class=" ls-is-cached lazyloaded" src="images/ar-ico.png" data-src="images/ar-ico.png" alt="">
							</span>
							<span class="fw7 lhm">
								Рассчитаем <span class="fw4">стоимость комплекта</span>								
							</span>
						</li>
											<li class="row-vcenter mbm">
							<span class="main__y-ico col-center mr">
								<img class=" ls-is-cached lazyloaded" src="images/ar-ico.png" data-src="images/ar-ico.png" alt="">
							</span>
							<span class="fw7 lhm">
								Сориентируем <span class="fw4"> по доставке и сервису</span>								
							</span>
						</li>
																

						
					</ul>

					<form action="/callback" class="form decide-right-form" method="post" data-toggle="validator" novalidate="true">
						<input type="hidden" name="title" value="Заказать звонок">
						<div class="form__row">
						  <label class="input">
							<span class="input__title input__title_center">Введите номер Вашего телефона:*</span>
							<input type="text" id="phone-input1" name="phone">
						  </label>
						</div>
						<button type="submit" class="btn btn-danger max mbm btn-decide" id="btn-decide" disabled>							
							<span class="tsm13 white fw4">Заказать звонок</span>
						</button>
					</form>
				</div>
			</div>
		</div>
		</div>
	</div>

<!--footer-starts-->
<div class="footer-inner site-footer">
	<div class="container">
		<div class="row">		
			<div class="col-md-2 logo-icon">
				<?php if($this->route["controller"] != "Main") { ?><a href="/"><?php }else {} ?>			
					<img src="../images/logo-2.png" title="ИТС-Центр" alt="ИТС-Центр" class="img-responsive">
				<?php if($this->route["controller"] != "Main") { ?></a><?php }else {} ?>    
			</div>
			<div class="col-md-7 ftr-ul">
				<ul class="list-unstyled">
					<li><a href="category/industrialnye-shiny" title="Индустриальные шины">Индустриальные шины</a></li> 
					<li><a href="category/atv" title="Шины АТВ">Шины АТВ</a></li>
					<li><a href="category/diski" title="Диски">Диски</a></li>
					<li><a href="category/filtry" title="Фильтры">Фильтры</a></li>
					<li><a href="category/kamery-i-obodnye-lenty" title="Камеры и ободные ленты">Камеры и ободные ленты</a></li>
					<li><a href="promo" title="Акции и скидки на товары" class="text-danger">Акции</a></li>
				</ul>
				<ul class="list-unstyled">
					<li><a href="pages/kak-kupit" title="Как купить">Как купить</a></li>
					<li><a href="pages/sotrudnichestvo" title="Сотрудничество">Сотрудничество</a></li>
					<li><a href="services" title="Услуги">Услуги</a></li>
					<li><a href="pages/oplata" title="Оплата">Оплата</a></li>
					<li><a href="services/dostavka" title="Доставка">Доставка</a></li>
					<li><a href="pages/privacy" title="Политика конфиденциальности">Политика</a></li>

				</ul>
				<ul class="list-unstyled">
					<li><a href="pages/about-us" title="О компании">О компании</a></li>
					<li><a href="news" title="Новости">Новости</a></li>
					<li><a href="articles" title="Статьи">Статьи</a></li>
					<li><a href="pages/contacts" title="Контакты">Контакты</a></li>
					<li><a href="sitemap" title="Карта сайта">Карта сайта</a></li>
					<li><a href="articles/shinnyy-kalkulyator-na-sayte-its-centr" title="Шинный калькулятор">Шинный калькулятор</a></li>
				</ul>
				<ul class="list-unstyled">					
					<li class="text-danger">Подбор по:</li>
					<li><a href="technics" title="Подбор по технике">Подбор по технике</a></li>
					<li><a href="podbor/shiny" title="Подбор по размеру шины">Подбор по размеру шины</a></li>
					<li><a href="/" title="Подбор фильтров">Подбор фильтров</a></li>
					<li><a href="podbor/diski" title="Подбор дисков">Подбор дисков</a></li>
				</ul>
			</div>     
			<div class="col-md-3 foot-tell">
				<div class="drop">
					<div class="block-telefon">
						<div class="tel-2"><span class="tel-icon"><i class="fas fa-phone fa-flip-horizontal"></i></span> <span class="tel-inner2">+7 (495) 424-98-90</span></div>
						<div class="tel-priem2">Приём звонков: 9:00 - 17:00</div>
						<div class="tel-address">142115, МО, Подольск, ГО Климовск, ул. Коммунальная 26, стр. 2</div>
					<div class="block-social">						
							<ul class="social-inner">
								<li><a href="https://avito.ru/brands/i22305902" target="_blank" class="avito" title="Яндекс Дзен"><img src="images/avito.svg" alt="ИТС-Центр в Avito" style="border-radius: 10px;" /></a></li>
								<li><a href="https://dzen.ru/id/60c85a02ed79053dae0d1122" target="_blank" class="dzen" title="Яндекс Дзен"><img src="images/dzen.svg" alt="ИТС-Центр в Яндекс Дзен" /></a></li>
								<li><a href="https://vk.com/itscenterru" target="_blank" class="vkontakte" title="ВКонтакте"><i class="fab fa-vk"></i></a></li>
								<li><a href="https://ok.ru/group/54227545423872" target="_blank" class="odnoklassniki" title="Одноклассники"><i class="fab fa-odnoklassniki"></i></a></li>
								<li><a href="https://www.youtube.com/channel/UC3GcfXpBiS51zYyRXCbAaoA" target="_blank" class="youtube" title="Ютуб"><i class="fab fa-youtube"></i></a></li>
							</ul>						
					</div>
				</div>
			</div>
		</div>   
	</div>
</div>

<div class="fbg">
	<div class="container">
		<div class="row">
			<div class="col-lg-7">
				<div class="copyright">ИТС-Центр © 2007- <?php $year = date("Y"); echo $year; ?> <span>Шины Диски Фильтры</span></div>
			</div>
			<div class="col-lg-5 row copyright">
				<div class="col-lg-5">Мы принимаем к оплате</div>
				<div class="col-lg-7 card-accepted-payment">
					<div class="logos">
						<svg class="card icon-mir" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="enable-background:new 0 0 809 229" viewBox="0 0 809 220" y="0" x="0" version="1.1"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;fill:#10754e}</style><g><path d="M218 0v.1c-.1 0-31.6-.1-40 30-7.7 27.6-29.4 103.8-30 105.9h-6s-22.2-77.9-30-106C103.6-.1 72 0 72 0H0v229h72V93h6l42 136h50l42-135.9h6V229h72V0h-72z" class="st0"></path></g><g><path d="M481 0s-21.1 1.9-31 24l-51 112h-6V0h-72v229h68s22.1-2 32-24l50-112h6v136h72V0h-68z" class="st0"></path></g><g><path d="M581 104v125h72v-73h78c34 0 62.8-21.7 73.5-52H581z" class="st0"></path></g><g><linearGradient y2="47" x2="809" y1="47" x1="570.919" gradientUnits="userSpaceOnUse" id="gradient"><stop style="stop-color:#06aeff" offset=".3"></stop><stop style="stop-color:#205cd7" offset="1"></stop></linearGradient><path d="M731 0H570.9c8 43.6 40.7 78.6 83 90 9.6 2.6 19.7 4 30.1 4h123.4c1.1-5.2 1.6-10.5 1.6-16 0-43.1-34.9-78-78-78z" style="fill-rule:evenodd;clip-rule:evenodd;fill:url(#gradient)"></path></g></svg>
						<svg class="card icon-sbp" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 149.5 84.18" xml:space="preserve"><path d="M149.5 30.55v20.74h-7.4V36.74h-7.13v14.55h-7.4V30.55h21.93z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M112.35 52.03c6.63 0 11.55-4.06 11.55-10.23 0-5.96-3.63-9.84-9.7-9.84-2.8 0-5.11.99-6.85 2.69.42-3.52 3.39-6.08 6.66-6.08.76 0 6.44-.01 6.44-.01l3.22-6.16s-7.14.16-10.46.16c-7.59.13-12.71 7.03-12.71 15.4 0 9.77 5 14.07 11.85 14.07zm.04-14.61c2.46 0 4.17 1.62 4.17 4.38 0 2.49-1.52 4.54-4.17 4.54-2.54 0-4.24-1.9-4.24-4.5 0-2.76 1.7-4.42 4.24-4.42z"></path><path d="M94.47 44.35s-1.75 1.01-4.36 1.2c-3 .09-5.68-1.81-5.68-5.18 0-3.29 2.36-5.17 5.6-5.17 1.99 0 4.62 1.38 4.62 1.38s1.92-3.53 2.92-5.3c-1.83-1.38-4.26-2.14-7.08-2.14-7.14 0-12.66 4.65-12.66 11.19 0 6.62 5.19 11.16 12.66 11.03 2.09-.08 4.97-.81 6.72-1.94l-2.74-5.07z"></path><g><path fill="#5B57A2" d="m0 18.32 10.19 18.22v11.12L.01 65.85 0 18.32z"></path><path fill="#D90751" d="m39.14 29.92 9.55-5.85 19.55-.02-29.1 17.83V29.92z"></path><path fill="#FAB718" d="m39.09 18.22.05 24.13-10.22-6.28V0l10.17 18.22z"></path><path fill="#ED6F26" d="m68.25 24.04-19.55.02-9.61-5.84L28.92 0l39.33 24.04z"></path><path fill="#63B22F" d="M39.14 65.95V54.24l-10.22-6.16.01 36.1 10.21-18.23z"></path><path fill="#1487C9" d="m48.67 60.15-38.48-23.6L0 18.32l68.21 41.8-19.54.03z"></path><path fill="#017F36" d="m28.93 84.18 10.21-18.23 9.53-5.8 19.53-.02-39.27 24.05z"></path><path fill="#984995" d="m.01 65.85 29-17.76-9.75-5.98-9.07 5.56L.01 65.85z"></path></g></svg>
						<svg class="card icon-visa" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 324.684" overflow="visible" xml:space="preserve" style="overflow:visible"><path style="fill:#1434cb;fill-opacity:1;stroke:none" d="M651.185.5c-70.933 0-134.322 36.766-134.322 104.694 0 77.9 112.423 83.28 112.423 122.415 0 16.478-18.884 31.229-51.137 31.229-45.773 0-79.984-20.611-79.984-20.611l-14.638 68.547s39.41 17.41 91.734 17.41c77.552 0 138.576-38.572 138.576-107.66 0-82.316-112.89-87.537-112.89-123.86 0-12.91 15.501-27.053 47.662-27.053 36.286 0 65.892 14.99 65.892 14.99l14.326-66.204S696.614.5 651.185.5zM2.218 5.497.5 15.49s29.842 5.461 56.719 16.356c34.606 12.492 37.072 19.765 42.9 42.353l63.51 244.832h85.138L379.927 5.497h-84.942L210.707 218.67l-34.39-180.696c-3.154-20.68-19.13-32.477-38.685-32.477H2.218zm411.865 0L347.449 319.03h80.999l66.4-313.534h-80.765zm451.759 0c-19.532 0-29.88 10.457-37.474 28.73L709.699 319.03h84.942l16.434-47.468h103.483l9.994 47.468H999.5L934.115 5.497h-68.273zm11.047 84.707 25.178 117.653h-67.454L876.89 90.204z"></path></svg>
						<svg class="card icon-mastercard" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 116.5 72"><path fill="#FF5F00" d="M42.5 7.7H74v56.6H42.5z"></path><path fill="#EB001B" d="M44.5 36c0-11.5 5.4-21.7 13.7-28.3C52.1 2.9 44.4 0 36 0 16.1 0 0 16.1 0 36s16.1 36 36 36c8.4 0 16.1-2.9 22.2-7.7-8.3-6.6-13.7-16.8-13.7-28.3z"></path><path fill="#F79E1B" d="M116.5 36c0 19.9-16.1 36-36 36-8.4 0-16.1-2.9-22.2-7.7C66.6 57.7 72 47.5 72 36S66.6 14.3 58.2 7.7C64.4 2.9 72.1 0 80.5 0c19.9 0 36 16.1 36 36z"></path></svg>
					</div>
				</div>
			</div>	
		</div>
	</div>
</div>
</div>
             
<!--footer-end-->

<!-- panel bottom -->
<div class="aiz-mobile-bottom-nav d-xl-none fixed-bottom bg-white shadow-lg border-top rounded-top" style="box-shadow: 0px -1px 10px rgb(0 0 0 / 15%)!important; ">
    <div class="row align-items-center gutters-5">
        <div class="col">
            <a href="/" class="text-reset d-block text-center pb-2 pt-3">
                <i class="fas fa-home-alt fs-20 opacity-60 "></i>
                <span class="d-block fs-10 fw-600 opacity-60 ">Главная</span>
            </a>
        </div>
        <div class="col">
            <a href="category" class="text-reset d-block text-center pb-2 pt-3">
                <i class="fas fa-list fs-20 opacity-60 opacity-100 text-danger"></i>
                <span class="d-block fs-10 fw-600 opacity-60 opacity-100 fw-600">Категории</span>
            </a>
        </div>
        <div class="col-auto">
            <a href="cart/show" onclick="getCart(); return false;" class="text-reset d-block text-center pb-2 pt-3">
                <span class="align-items-center bg-danger border border-white border-width-4 d-flex justify-content-center position-relative rounded-circle size-50px" style="margin-top: -33px;box-shadow: 0px -5px 10px rgb(0 0 0 / 15%);border-color: #fff !important;">
                    <i class="fas fa-shopping-bag la-2x text-white"></i>
                </span>
                <span class="d-block mt-1 fs-10 fw-600 opacity-60 ">
                    <?php if(!empty($_SESSION['cart'])): ?>								
						Корзина (<span class="simpleCart_qty" id="cart-total"><?=$_SESSION['cart.qty']?></span>)						
					<?php else: ?>
						Корзина (<span class="simpleCart_qty" id="cart-total">0</span>)
					<?php endif; ?>
                </span>
            </a>
        </div>
        <div class="col">
            <a href="user/notifications" class="text-reset d-block text-center pb-2 pt-3">
                <span class="d-inline-block position-relative px-2">
                    <i class="fas fa-bell fs-20 opacity-60 "></i>
                                    </span>
                <span class="d-block fs-10 fw-600 opacity-60 ">Сообщения</span>
            </a>
        </div>
        <div class="col">
			<?php if(!empty($_SESSION['user']['id'])): ?>
				<a href="javascript:void(0)" class="text-reset d-block text-center pb-2 pt-3 mobile-side-nav-thumb" data-toggle="class-toggle" data-backdrop="static" data-target=".aiz-mobile-side-nav">
			<?php else: ?>
				<a data-toggle="modal" data-target="#Modallogin" href="javascript:;" class="text-reset d-block text-center pb-2 pt-3">		
			<?php endif; ?>
                <span class="d-block mx-auto">
                    <img src="images/avatar-place.png" class="rounded-circle size-20px">
                </span>
                <span class="d-block fs-10 fw-600 opacity-60">Кабинет</span>
            </a>
        </div>
    </div>
</div>
<!-- /panel bottom -->

<!-- Modal korzina -->
<div class="modal fade" id="exampleModalLive" tabindex="-1"  role="dialog" aria-labelledby="exampleModalLiveLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalLiveLabel">Корзина</h5>
				<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
			</div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Продолжить покупки</button>
                <a href="cart/view" type="button" class="btn btn-danger">Оформить заказ</a>
                <button type="button" class="btn btn-primary" onclick="clearCart()">Очистить корзину</button>
            </div>
        </div>
  </div>
</div>

<!-- Modal katalog -->
<div class="modal fade" id="exampleModalCatalog" tabindex="-1" role="dialog" aria-labelledby="exampleModalCatalogLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalCatalogLabel">Скачать каталог</h5>
				<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
			</div>
            <div class="modal-body" style="padding: 10px 0 !important;">
				<iframe width="100%" height="350" src="https://its-center.ru/crm/forms/wtl/503d2ca35f661be6a2275478b045b4f4" frameborder="0" sandbox="allow-top-navigation allow-scripts allow-forms allow-same-origin" allowfullscreen></iframe>
            </div>
            <div class="modal-footer">
                
            </div>
        </div>
  </div>
</div>

<!-- Modal zvonok callback-->
<form class="modal fade" id="exampleModalZvonok" tabindex="-1" role="dialog" aria-labelledby="exampleModalZvonokLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalZvonokLabel">Заказать обратный звонок</h5>
				<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
			</div>
            <div class="modal-body" style="min-height: 68vh;">
				<iframe width="100%" height="100%" src="https://its-center.ru/crm/forms/wtl/41a3cc3222a971ffda6f1cce491da5f9" frameborder="0" sandbox="allow-top-navigation allow-scripts allow-forms allow-same-origin" allowfullscreen></iframe>				
            </div>
            <div class="modal-footer">
                
            </div>
        </div>
  </div>
</form>

<!-- Modal login -->
<form action="/user/login" method="post" class="modal fade" id="Modallogin" tabindex="-1" role="dialog" aria-labelledby="ModalloginLabel" aria-hidden="true" data-toggle="validator">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalloginLabel">Вход</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
            <div class="modal-body">				
				<div class="fc-1">
					<div class="fc_name">E-Mail <span>*</span></div>
					<div class="fc_input"><input type="text" name="email" placeholder="Укажите ваш e-mail" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_name">Пароль <span>*</span></div>
					<div class="fc_input"><input type="password" name="password" required></div>
				</div>
				<div class="fc-1">
					<div class="fc_input"><a href="user/recover">Забыли пароль?</a></div>
				</div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="loginok" value="<?php echo md5(date('Y-m-d')); ?>" class="btn btn-danger btn-sm btn-block btn-vhod">Войти</button>
				<div class="reg_ili_inner"><div class="reg_ili">ИЛИ</div></div>
				
				<?php 
					$params = array(

						'client_id'     => 'ab48bcc31dcd4be3aac787f9f945c7c6',
						'redirect_uri'  => ''.PATH.'/user/signup',
						'response_type' => 'code',
						'state'         => 'ya_reg'
					);

					$url = 'https://oauth.yandex.ru/authorize?' . urldecode(http_build_query($params));
					echo '<a href="' . $url . '" class="btn btn-outline-secondary btn-sm btn-block btn-vhod">Яндекс</a>';
				?>		
				<?php
					$params_gg = array(
						'client_id'     => '952884474441-pjrml49bqmfos5g055qmkhdbrrlsp8sh.apps.googleusercontent.com',
						'redirect_uri'  => ''.PATH.'/user/signup',
						'response_type' => 'code',
						'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
						'state'         => 'gg_reg'
					);
					$url_gg = 'https://accounts.google.com/o/oauth2/auth?' . urldecode(http_build_query($params_gg));
					echo '<a href="' . $url_gg . '" class="btn btn-outline-primary btn-sm btn-block btn-vhod">Google</a>';
				?>
				<?php
					$url_vk = 'http://oauth.vk.com/authorize';
					$client_id = '8097823'; // ID приложения
					$client_secret = 'QUUb6UEcTpHCbEaeC31Y'; // Защищённый ключ
					$redirect_uri = 'https://its-center.ru/user/signup'; // Адрес сайта
					$params_vk = array(
						'client_id'     => $client_id,
						'redirect_uri'  => $redirect_uri,
						'response_type' => 'code',
						'scope'         => 'notify,email',
						'state'         => 'vk_reg',
						'v' 			=> '5.81'
					);
					echo '<a href="' . $url_vk . '?' . urldecode(http_build_query($params_vk)) . '" class="btn btn-outline-primary btn-sm btn-block btn-vhod">ВКонтакте</a>';
				?>
				</div>
				<div class="auth">
						<div class="btn_reg"><a href="user/signup" class="btn btn-outline-success">Регистрация</a></div><div class="reg_opis">Вам будет доступен личный кабинет, дисконтная программа, отслеживание заказов, персональные данные и много других полезных функций.</div>
				</div>			
        </div>
  </div>
</form>

<div class="preloader"><img src="images/ring.svg" alt=""></div>
<div class="right-menu tsm13">
  <a href="/technics" class="right-menu__item btn-price-js" data-technics-select-link="">
    <span class="right-menu__item-hover">Подобрать по технике</span>
    <img class=" ls-is-cached lazyloaded" src="images/calc.svg" data-src="images/calc.svg" alt="">
  </a>
  <a href="javascript:;" data-toggle="modal" data-target="#exampleModalZvonok" class="right-menu__item">
    <span class="right-menu__item-hover">Обратный звонок</span>
    <img class=" lazyloaded" src="images/ph.svg" data-src="images/ph.svg" alt="">
  </a>
  <a href="/promo" data-popup-link="" class="right-menu__item">
    <span class="right-menu__item-hover">Посмотреть акции</span>
    <img class=" ls-is-cached lazyloaded" src="images/gift.svg" data-src="images/gift.svg" alt="">
  </a>
  <a href="javascript:;" data-toggle="modal" data-target="#exampleModalCatalog" class="end-js right-menu__item">
    <span class="right-menu__item-hover">Скачать каталог</span>
    <img class=" ls-is-cached lazyloaded" src="images/dowvlad.svg" data-src="images/dowvlad.svg" alt="">
  </a>
</div>

<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
<script>
    var path = '<?=PATH;?>',
        course = <?=$curr['value'];?>,
        symboleLeft = '<?=$curr['symbol_left'];?>',
        symboleRight = '<?=$curr['symbol_right'];?>';
</script>
<script src="js/jquery-1.11.0.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="public/adminlte/plugins/select2/js/select2.full.min.js"></script>
<script src="js/validator.js"></script>
<script src="js/typeahead.bundle.js"></script>
<script src="js/imagezoom.js"></script>
<script src="js/slider.js"></script>
<script defer src="js/jquery.flexslider.js"></script>
<script type="text/javascript">

    $(window).load(function(){
      $('#carousel').flexslider({
        animation: "slide",
        controlNav: false,
        animationLoop: false,
        slideshow: false,
        itemWidth: 210,
        itemMargin: 5,
        asNavFor: '#slider'
      });

      $('#slider').flexslider({
		prevArrow: "<img src='https://svgshare.com/i/6Ei.svg' class='prev' alt='1'>",
		nextArrow: "<img src='https://svgshare.com/i/6Gf.svg' class='next' alt='2'>",
        animation: "slide",
        controlNav: false,
        animationLoop: false,
        slideshow: false,
        sync: "#carousel",
        start: function(slider){
          $('body').removeClass('loading');
        }
      });
    });
	

  </script>
<script src="js/jquery.easydropdown.js"></script>
<script type="text/javascript">
    $(function() {

        var menu_ul = $('.menu_drop > li > ul'),
            menu_a  = $('.menu_drop > li > a');

        menu_ul.hide();

        menu_a.click(function(e) {
            e.preventDefault();
            if(!$(this).hasClass('active')) {
                menu_a.removeClass('active');
                menu_ul.filter(':visible').slideUp('normal');
                $(this).addClass('active').next().stop(true,true).slideDown('normal');
            } else {
                $(this).removeClass('active');
                $(this).next().stop(true,true).slideUp('normal');
            }
        });

    });
</script>
<!-- Swiper JS -->
<!-- Swiper JS -->
<script>
$(function(){
    $(".dropdown .menu_links-item").on("click", function(){
        $(".dropdown-menu").toggleClass("show");
		$(".mmenu-close").toggleClass("show");
    });
	$(".mmenu-close").on("click", function(){
		$(".mmenu-close").removeClass("show");
		$(".dropdown-menu").removeClass("show");
	});
});
</script>
<script src="js/swiper-bundle.min.js"></script>
<script>
$(document).ready(function(){
     $(window).scroll(function () {
            if ($(this).scrollTop() > 50) {
                $('#back-to-top').fadeIn();
            } else {
                $('#back-to-top').fadeOut();
            }
        });
        // scroll body to 0px on click
        $('#back-to-top').click(function () {
           
            $('body,html').animate({
                scrollTop: 0
            }, 500);
            
        });
        
});
</script>

<!-- Initialize Swiper -->
<script>
      var swiper = new Swiper(".mySwiper", {
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesVisibility: true,
        watchSlidesProgress: true,
      });
      var swiper2 = new Swiper(".mySwiper2", {
        spaceBetween: 10,
        thumbs: {
          swiper: swiper,
        },
      });
</script>
<script>
	const pageWidth = window.screen.width;
	if(pageWidth > 1281) {
		
		var swiper1 = new Swiper('.swiper1', {
			slidesPerView: 5,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-1',
				prevEl: '.swiper-button-prev-1',
			},
		}); 
		var swiper2 = new Swiper('.swiper2', {
			slidesPerView: 5,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-2',
				prevEl: '.swiper-button-prev-2',
			},
		});		
	}
	if(pageWidth < 1281 && pageWidth > 1023) {
		
		var swiper1 = new Swiper('.swiper1', {
			slidesPerView: 4,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-1',
				prevEl: '.swiper-button-prev-1',
			},
		}); 
		var swiper2 = new Swiper('.swiper2', {
			slidesPerView: 4,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-2',
				prevEl: '.swiper-button-prev-2',
			},
		});
		
	}
	if(pageWidth < 1025 && pageWidth > 639) {
		
		var swiper1 = new Swiper('.swiper1', {
			slidesPerView: 3,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-1',
				prevEl: '.swiper-button-prev-1',
			},
		}); 
		var swiper2 = new Swiper('.swiper2', {
			slidesPerView: 3,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-2',
				prevEl: '.swiper-button-prev-2',
			},
		});
		
	}
	if(pageWidth < 641 && pageWidth > 480) {
		
		var swiper1 = new Swiper('.swiper1', {
			slidesPerView: 2,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-1',
				prevEl: '.swiper-button-prev-1',
			},
		}); 
		var swiper2 = new Swiper('.swiper2', {
			slidesPerView: 2,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-2',
				prevEl: '.swiper-button-prev-2',
			},
		});
		
	}
	if(pageWidth < 481 && pageWidth > 400) {
		
		var swiper1 = new Swiper('.swiper1', {
			slidesPerView: 2,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-1',
				prevEl: '.swiper-button-prev-1',
			},
		}); 
		var swiper2 = new Swiper('.swiper2', {
			slidesPerView: 2,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-2',
				prevEl: '.swiper-button-prev-2',
			},
		});
		
	}
	if(pageWidth < 400) {
		
		var swiper1 = new Swiper('.swiper1', {
			slidesPerView: 1,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-1',
				prevEl: '.swiper-button-prev-1',
			},
		}); 
		var swiper2 = new Swiper('.swiper2', {
			slidesPerView: 1,
			slidesPerGroup: 1,
			navigation: {
				nextEl: '.swiper-button-next-2',
				prevEl: '.swiper-button-prev-2',
			},
		});
		
	}
</script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
	<script>
      Fancybox.bind('[data-fancybox="gallery"]', {
        Thumbs : {
          type: "classic"
        }
      });    
    </script>	
<script src="js/main.js"></script>
<!--End-slider-script-->
<!-- upTop -->  
<div class="btn btn-danger back-to-top hidden-xs" id="back-to-top" role="button" data-toggle="tooltip" data-placement="left"><i class="fas fa-arrow-up"></i></div>

<!-- callback -->  
<script id="rendered-js" >
const phoneEl = document.getElementById('phone-input');
let phoneMask = IMask(phoneEl, {

  mask: '{+7} (#00) 000-00-00',

  definitions: {
    '#': /[012345679]/ },


  lazy: false,

  placeholderChar: ' ' });
//# sourceURL=pen.js
    </script>
<!-- callback -->
<script id="rendered-js" >
// Считываем поле ввода
const phoneEl1 = document.getElementById('phone-input1');
// Считываем кнопку
let btn = document.querySelector(".btn-decide");

// Создаем маску в инпуте
let phoneMask1 = IMask(phoneEl1, {
  mask: '{+7} (#00) 000-00-00',
  definitions: {'#': /[012345679]/ },
  lazy: false,
  placeholderChar: ' '
});

// Обработчик события для инпута
phoneEl1.addEventListener("input", phoneInputHandler);
// Обработчик события для кнопки
btn.addEventListener("click", btnHandler);

// Если ввели правлильно - кнопка активна
function phoneInputHandler() {
  if (phoneMask1.masked.isComplete) {
    btn.classList.add("active");
	document.getElementById("btn-decide").removeAttribute("disabled");
  } else {
    btn.classList.remove("active");
	btn.setAttribute("disabled", "");
  }
}
</script>
<!-- callback -->
<!-- callback -->  
<script id="rendered-js" >
const phoneEl2 = document.getElementById('phonenumber');
let phoneMask2 = IMask(phoneEl2, {

  mask: '{+7} (#00) 000-00-00',

  definitions: {
    '#': /[012345679]/ },


  lazy: false,

  placeholderChar: ' ' });
//# sourceURL=pen.js
    </script>
<!-- callback -->
<!-- podzakaz -->  
<script id="rendered-js" >
const phoneEl3 = document.getElementById('phone-input3');
let phoneMask3 = IMask(phoneEl3, {

  mask: '{+7} (#00) 000-00-00',

  definitions: {
    '#': /[012345679]/ },


  lazy: false,

  placeholderChar: ' ' });
//# sourceURL=pen.js
    </script>
<!-- podzakaz -->

</body>
</html>