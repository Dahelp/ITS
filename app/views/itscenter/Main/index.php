<?php require __DIR__ . '/catalog.php'; ?>

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
				<p>В каталоге представлены сотни моделей шин, дисков и фильтров для спецтехники.</p>
				<p>ИТС-ЦЕНТР – надежный, проверенный временем поставщик шин российских и зарубежных производителей, являющихся признанными лидерами. Почти 90% товаров мы продаем на эксклюзивных условиях, так как являемся официальными дилерами заводов-изготовителей. Их удостоверения и сертификаты – признание нашей экспертности и свидетельство доверия брендов международного уровня.</p>
				<p>Сотрудники компании знают о предлагаемых шинах и прочих товарах всё. Поэтому предоставят вам подробную информацию о любом интересующем товаре – от масляного фильтра и диска до шин на садовую тачку и цельнолитых шин на фронтальный погрузчик весом в полторы тонны. Вы купите то, что подходит на вашу технику.</p>
			</div>
			<div class="col-md-3 ocomp_img">
				<img src="images/companys.webp" width="189" height="400" alt="Официальный сайт компании ИТС-ЦЕНТР" title="Официальный сайт компании ИТС-ЦЕНТР">
			</div>
		</div>
	</div>
</div>
<div class="advantages-inner">
	<div class="container">
		<div class="pch_name"><h3>Почему клиенты выбирают ИТС-ЦЕНТР?</h3></div>
		<div class="pch_m">
			<div class="col-md-4 pch_img">
				<img src="images/advantages.webp" width="487" height="534" alt="Почему клиенты выбирают ИТС-ЦЕНТР?" title="Почему клиенты выбирают ИТС-ЦЕНТР?">
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
            <section class="mb-2 recomend-1">
                <div class="product-name"><h3>Хиты продаж</h3></div>

                <div class="swiper-container swiper1 hits-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach($hits as $product): ?>
                            <div class="swiper-slide">
                                <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php', $hitsWidgetContext ?? []); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="swiper-button-inner">
                    <div class="swiper-button-next swiper-button-next-1"></div>
                    <div class="swiper-button-prev swiper-button-prev-1"></div>
                </div>
            </section>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="main-articles">
	<div class="container">
		<h3>Статьи</h3>

		<div class="cont-blok">
			<?php foreach($articles as $articl): ?>
			<div class="cont-one">
				<div class="cont_ht">
					<a class="cont_blok_img" href="articles/<?=$articl["alias"]?>">
						<span class="cont_badge">Статья</span>
						<img src="images/contents/baseimg/<?=$articl["img"]?>" alt="<?=$articl["name"]?>" title="<?=$articl["name"]?>" width="818" height="460">
					</a>

					<div class="cont_info">
						<div class="cont_info_data"><?php echo \ishop\App::contdate($articl["date_post"]); ?></div>

						<div class="cont_info_name">
							<a href="articles/<?=$articl["alias"]?>"><?=$articl["name"]?></a>
						</div>

						<div class="cont_info_anons">
							<?=$articl["anons"]?>
						</div>

						<div class="cont_info_bottom">
							<a class="cont_read_btn" href="articles/<?=$articl["alias"]?>">Читать статью</a>
						</div>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
