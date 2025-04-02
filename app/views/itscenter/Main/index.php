<!-- podbor -->
<div class="category-inner">
	<div class="container">
		<h1>Интернет-магазин резины, дисков и фильтров для спецтехники</h1>
		<div class="container-grid">
			<div class="item item-0">
				<a class="category category-0" href="category/gruzovye-shiny" title="Грузовые шины">
					<img src="images/cat-7.jpg" alt="Грузовые шины" title="Грузовые шины" />
					<div class="cat-i"><i class="far fa-tire-rugged"></i></div>
					<div class="cat-name"><h2>Грузовые шины</h2></div>
					<div class="cat-span"><?php $count_gruzlo = \R::getAll("SELECT * FROM product WHERE category_id = '36'"); echo count($count_gruzlo);?> товаров</div>
				</a>
			</div>
			<div class="item item-1">
				<a class="category category-1" href="category/industrialnye-shiny" title="Индустриальные шины">
					<img src="images/cat-1.jpg" alt="Индустриальные шины" title="Индустриальные шины" />
					<div class="cat-i"><i class="fad fa-tire-rugged"></i></div>
					<div class="cat-name"><h2>Индустриальные шины</h2></div>
					<div class="cat-span"><?php $count_inds = \R::getAll("SELECT * FROM product WHERE category_id IN ('9', '18', '19', '20', '21', '22', '23', '24')"); echo count($count_inds);?> товаров</div>
				</a>
			</div>
			<div class="item item-2">
				<a class="category category-2" href="category/atv" title="Шины для квадроциклов АТВ">
					<img src="images/cat-2.jpg" alt="Шины АТВ" title="Шины АТВ" />
					<div class="cat-i"><i class="far fa-tire-rugged"></i></div>
					<div class="cat-name"><h2>Шины АТВ</h2></div>
					<div class="cat-span"><?php $count_atv = \R::getAll("SELECT * FROM product WHERE category_id = '2'"); echo count($count_atv);?> товаров</div>
				</a>
			</div>
			<div class="item item-3">
				<a class="category category-3" href="category/kamery-i-obodnye-lenty" title="Камеры и ободные ленты">
					<img src="images/cat-5.jpg" alt="Камеры и ободные ленты" title="Камеры и ободные ленты" />
					<div class="cat-i"><img src="images/camera.png" alt="Камеры и ободные ленты" title="Камеры и ободные ленты" /></div>
					<div class="cat-name"><h2>Камеры и ободные ленты</h2></div>
					<div class="cat-span"><?php $count_atv = \R::getAll("SELECT * FROM product WHERE category_id IN ('31', '32', '33')"); echo count($count_atv);?> товаров</div>
				</a>
			</div>
			<div class="item item-4">
				<a class="category category-4" href="category/diski" title="Диски для спецтехники">
					<img src="images/cat-3.jpg" alt="Диски" title="Диски" />
					<div class="cat-i"><i class="fal fa-tire"></i></div>
					<div class="cat-name"><h2>Диски</h2></div>
					<div class="cat-span"><?php $count_inds = \R::getAll("SELECT * FROM product WHERE category_id IN ('26', '27', '28', '29', '30')"); echo count($count_inds);?> товаров</div>
				</a>
			</div>
			<div class="item item-5">
				<a class="category category-5" href="category/filtry" title="Фильтры для спецтехники">
					<img src="images/cat-4.jpg" alt="Фильтры" title="Фильтры" />
					<div class="cat-i"><img src="images/air-filter.png" alt="Фильтры" title="Фильтры" /></div>
					<div class="cat-name"><h2>Фильтры</h2></div>
					<div class="cat-span"><?php $count_inds = \R::getAll("SELECT * FROM product WHERE category_id IN ('10', '11', '12', '13', '14', '15', '16', '17')"); echo count($count_inds);?> товаров</div>
				</a>
			</div>
			<div class="item item-6">
				<a class="category category-6" href="complete" title="Подобрать комплект">
					<div class="cat-i filter"><i class="fal fa-calendar-alt"></i></div>
					<div class="cat-name"><h2>Подобрать комплект</h2></div>
					<div class="cat-span"></div>					
				</a>
			</div>
			<div class="item item-7">
				<a class="category category-7" href="podbor/shiny" title="Подбор шин по типоразмеру">
					<div class="cat-i filter"><i class="far fa-filter"></i></div>
					<div class="cat-name"><h2>Подбор шин по типоразмеру</h2></div>
					<div class="cat-span">109 типоразмеров</div>					
				</a>
			</div>
			<div class="item item-8">
				<a class="category category-8" href="/technics" title="Подбор шин по марке техники">
					<div class="cat-i"><i class="far fa-filter"></i></div>
					<div class="cat-name"><h2>Подбор шин по марке техники</h2></div>
					<div class="cat-span">650 марок техники</div>					
				</a>
			</div>
			<div class="item item-9">
				<a class="category category-9" href="podbor/kamery" title="Подбор камер по размеру шин">
					<div class="cat-i"><i class="far fa-filter"></i></div>
					<div class="cat-name"><h2>Подбор камер по размеру шин</h2></div>
					<div class="cat-span">40 размеров</div>					
				</a>
			</div>
			<div class="item item-10">
				<a class="category category-10" href="podbor/diski" title="Подбор дисков по параметрам">
					<div class="cat-i"><i class="far fa-filter"></i></div>
					<div class="cat-name"><h2>Подбор дисков по параметрам</h2></div>
					<div class="cat-span">20 параметров</div>					
				</a>
			</div>
			<div class="item item-11">
				<a class="category category-11" href="/" title="Подбор фильтров по номерам">
					<div class="cat-i"><i class="far fa-filter"></i></div>
					<div class="cat-name"><h2>Подбор фильтров по кросс-номерам</h2></div>
					<div class="cat-span">6574 кросс-номеров</div>					
				</a>
			</div>
		</div>
	</div>
</div>
<div class="clearfix"></div>
<div class="uslugi-inner">
	<div class="container">
		<div class="row">
			<div class="uslugi-name"><h3>Услуги</h3></div>
			<div class="uslugi-block">
				<div class="dostavka">					
					<div class="uls-nm">Доставка товаров по РФ</div>
					<a class="btn" href="services/dostavka">Подробнее</a>					
				</div>
				<div class="shinamontazh">
					<div class="uls-nm">Шиномонтаж шин для спецтехники</div>
					<a class="btn" href="services/shinomontazh-shin-dlya-spectehniki">Подробнее</a>
				</div>
				<div class="oshipovka">
					<div class="uls-nm">Ошиповка и нарезка протектора шин для спецтехники</div>
					<a class="btn" href="services/oshipovka-i-narezka-protektora-shin-dlya-spectehniki">Подробнее</a>					
				</div>
			</div>
		</div>
	</div>
</div>
<div class="text-inner">
	<div class="container">
		<h3>Официальный сайт компании ИТС-ЦЕНТР</h3>
		<div class="ocomp_blk">
			<div class="col-md-9 ocomp_info">
				<p>Наш интернет-магазин рад видеть и приветствовать вас! На наших страницах вы найдёте шины, диски, фильтры для спецтехники, а также резину для квадроциклов, садовой техники, гольфкаров и мототехники.</p>
				<p>ИТС-ЦЕНТР – надежный, проверенный временем поставщик шин российских и зарубежных производителей, являющихся признанными лидерами. Почти 90% товаров мы продаем на эксклюзивных условиях, так как являемся официальными дилерами заводов-изготовителей. Их удостоверения и сертификаты – признание нашей экспертности и свидетельство доверия брендов международного уровня.</p>
				<p>Сотрудники компании знают о предлагаемых шинах и прочих товарах всё. Поэтому предоставят вам подробную информацию о любом интересующем товаре – от масляного фильтра и диска до шин на садовую тачку и цельнолитых шин на фронтальный погрузчик весом в полторы тонны. Вы купите то, что подходит на вашу технику.</p>
			</div>
			<div class="col-md-3 ocomp_img">
				<img src="images/companys.jpg" alt="Официальный сайт компании ИТС-ЦЕНТР" title="Официальный сайт компании ИТС-ЦЕНТР">
			</div>
		</div>
	</div>
</div>
<div class="clearfix"></div>
<div class="advantages-inner">
	<div class="container">
		<div class="pch_name"><h3>Почему клиенты выбирают ИТС-ЦЕНТР?</h3></div>
		<div class="pch_m">
			<div class="col-md-4 pch_img">
				<img src="images/advantages.jpg" alt="Почему клиенты выбирают ИТС-ЦЕНТР?" title="Почему клиенты выбирают ИТС-ЦЕНТР?">
			</div>
			<div class="pch_b col-md-8">				
				<div class="pch_info">				
					<div class="pch_info_1 col-md-12">
						<div class="pch_td_1 col-md-6">							
							<div class="pch_td_2"><h4>Сотрудничаем с компаниями</h4><p>Работаем на рынке более 15 лет. Более 25000 довольных клиентов. Заключаем договора. Предзаказ на любые объемы продукции.</p></div>
						</div>
						<div class="pch_td_1 col-md-6">							
							<div class="pch_td_2"><h4>Акции и скидки</h4><p>Мы поставщики, по-этому у нас низкие цены. Скидки постоянным и оптовым клиентам. Большой ассортимент товаров по акционной цене.</p></div>
						</div>
					</div>
					<div class="pch_info_1 col-md-12">
						<div class="pch_td_1 col-md-6">							
							<div class="pch_td_2"><h4>Доставка по России</h4><p>Ежедневные отправки транспортными компаниями. Бесплатная доставка до ТК. Самовывоз: Климовск (Московская область).</p></div>
						</div>
						<div class="pch_td_1 col-md-6">						
							<div class="pch_td_2"><h4>Оплата</h4><p>Вы можете оплатить выбранный вами товар наличными в пункте самовывоза. От организаций принимаем безналичные переводы.</p></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!--product-starts-->
<?php if($hits): ?>
<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
<div class="hit-inner">
    <div class="container">
        <div class="related_prod">
          <section class="pb-5 mb-2 mb-xl-4 recomend-1">
			<div class="product-name"><h3>Хиты продаж</h3></div>
			<div class="review-wrap">

			<div class="wrap-container">
			<div class="inner-container">				

			<div class="swiper-container swiper1">
				<div class="swiper-wrapper">
            <?php foreach($hits as $product): ?>
				<div class="swiper-slide">
					
					<?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>	
					
				</div>
            <?php endforeach; ?>
			</div>
						
				</div>
					
				</div>
					
				</div>
				
			</div>
			<div class="swiper-button-inner">
				<div class="swiper-button-next swiper-button-next-1"></div>
				<div class="swiper-button-prev swiper-button-prev-1"></div>
			</div>
            <div class="clearfix"></div>            
        </div>
	</div>
</div>
<?php endif; ?>
<div class="clearfix"></div>
<div class="main-articles">
	<div class="container">
		<h3>Статьи</h3>
		<div class="cont-blok">
			<?php foreach($articles as $articl): ?>
			<div class="col-md-3 cont-one">
				<div class="cont_ht border border-grey">
					<div class="cont_blok_img">
						<img src="images/contents/baseimg/<?=$articl["img"]?>" alt="<?=$articl["name"]?>" title="<?=$articl["name"]?>">
					</div>
					<div class="cont_info">
						<div class="cont_info_data"><?php echo \ishop\App::contdate($articl["date_post"]); ?></div>
						<div class="cont_info_name">
							<a href="articles/<?=$articl["alias"]?>"><?=$articl["name"]?></a>
						</div>
						<div class="cont_info_anons">
							<p><?=$articl["anons"]?></p>
						</div>
					</div>
				</div>
			</div>
			<?php endforeach; ?>			
		</div>	
	</div>
</div>
<div class="clearfix"></div>