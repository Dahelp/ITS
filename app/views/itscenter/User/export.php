<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
				<li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active">Экспорт товаров</li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">				
				<?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
			</div>
			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Выгрузка товаров на ваш сайт</h5>
					</div>
					<div class="card-body">			
						<div class="blk-alert blk-alert-danger">Необходима помощь в выгрузке товаров на вашем сайте? Пишите на почту <a href="mailto:oooitscenter@yandex.ru">oooitscenter@yandex.ru</a> (доп.услуга).</div>
						<div class="price_excel">
							<?php
								$cron_export_excel = \R::findOne('cron', 'url_params = ?', ['export-excel']);
								$cron_export_csv = \R::findOne('cron', 'url_params = ?', ['export-csv']);
								$cron_export_xml = \R::findOne('cron', 'url_params = ?', ['export-yml']);
								
								$cron_export_excel_vseshiny = \R::findOne('cron', 'url_params = ?', ['export-excel-vseshiny']);
								$cron_export_csv_vseshiny = \R::findOne('cron', 'url_params = ?', ['export-csv-vseshiny']);
								$cron_export_xml_vseshiny = \R::findOne('cron', 'url_params = ?', ['export-yml-vseshiny']);
								
								$cron_export_excel_kvadroshiny = \R::findOne('cron', 'url_params = ?', ['export-excel-kvadroshiny']);
								$cron_export_csv_kvadroshiny = \R::findOne('cron', 'url_params = ?', ['export-csv-kvadroshiny']);
								$cron_export_xml_kvadroshiny = \R::findOne('cron', 'url_params = ?', ['export-yml-kvadroshiny']);
								
								$cron_export_excel_diski = \R::findOne('cron', 'url_params = ?', ['export-excel-diski']);
								$cron_export_csv_diski = \R::findOne('cron', 'url_params = ?', ['export-csv-diski']);
								$cron_export_xml_diski = \R::findOne('cron', 'url_params = ?', ['export-yml-diski']);
								
								$cron_export_excel_filtry = \R::findOne('cron', 'url_params = ?', ['export-excel-filtry']);
								$cron_export_csv_filtry = \R::findOne('cron', 'url_params = ?', ['export-csv-filtry']);
								$cron_export_xml_filtry = \R::findOne('cron', 'url_params = ?', ['export-yml-filtry']);
								
								$cron_export_excel_kamery = \R::findOne('cron', 'url_params = ?', ['export-excel-kamery']);
								$cron_export_csv_kamery = \R::findOne('cron', 'url_params = ?', ['export-csv-kamery']);
								$cron_export_xml_kamery = \R::findOne('cron', 'url_params = ?', ['export-yml-kamery']);
							?>
							<div class="price_ex1">
								<h4>Все товары</h4>
								<ul>
									<li><strong>Прайс Excel(.xlsx) от <?=$cron_export_excel["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_excel["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_excel["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс CSV(.csv) от <?=$cron_export_csv["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_csv["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_csv["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс YML(.xml) от <?=$cron_export_xml["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_xml["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_xml["url_download"]?></li>
										</ol>										
									</li>
								</ul>
							</div>
							<div class="price_ex1">
								<h4>Индустриальные шины</h4>
								<ul>
									<li><strong>Прайс Excel(.xlsx) от <?=$cron_export_excel_vseshiny["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_excel_vseshiny["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_excel_vseshiny["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс CSV(.csv) от <?=$cron_export_csv_vseshiny["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_csv_vseshiny["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_csv_vseshiny["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс YML(.xml) от <?=$cron_export_xml_vseshiny["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_xml_vseshiny["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_xml_vseshiny["url_download"]?></li>
										</ol>										
									</li>
								</ul>
							</div>
							<div class="price_ex1">
								<h4>Шины для квадроциклов</h4>
								<ul>
									<li><strong>Прайс Excel(.xlsx) от <?=$cron_export_excel_kvadroshiny["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_excel_kvadroshiny["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_excel_kvadroshiny["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс CSV(.csv) от <?=$cron_export_csv_kvadroshiny["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_csv_kvadroshiny["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_csv_kvadroshiny["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс YML(.xml) от <?=$cron_export_xml_kvadroshiny["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_xml_kvadroshiny["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_xml_kvadroshiny["url_download"]?></li>
										</ol>										
									</li>
								</ul>
							</div>
							<div class="price_ex1">
								<h4>Диски на технику</h4>
								<ul>
									<li><strong>Прайс Excel(.xlsx) от <?=$cron_export_excel_diski["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_excel_diski["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_excel_diski["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс CSV(.csv) от <?=$cron_export_csv_diski["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_csv_diski["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_csv_diski["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс YML(.xml) от <?=$cron_export_xml_diski["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_xml_diski["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_xml_diski["url_download"]?></li>
										</ol>										
									</li>
								</ul>
							</div>
							<div class="price_ex1">
								<h4>Фильтры для спецтехники</h4>
								<ul>
									<li><strong>Прайс Excel(.xlsx) от <?=$cron_export_excel_filtry["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_excel_filtry["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_excel_filtry["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс CSV(.csv) от <?=$cron_export_csv_filtry["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_csv_filtry["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_csv_filtry["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс YML(.xml) от <?=$cron_export_xml_filtry["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_xml_filtry["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_xml_filtry["url_download"]?></li>
										</ol>										
									</li>
								</ul>
							</div>
							<div class="price_ex1">
								<h4>Камеры и ободные ленты</h4>
								<ul>
									<li><strong>Прайс Excel(.xlsx) от <?=$cron_export_excel_kamery["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_excel_kamery["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_excel_kamery["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс CSV(.csv) от <?=$cron_export_csv_kamery["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_csv_kamery["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_csv_kamery["url_download"]?></li>
										</ol>										
									</li>
									<li><strong>Прайс YML(.xml) от <?=$cron_export_xml_kamery["date_update"]?></strong>
										<ol>
											<li><a href="<?=PATH?>/cron/<?=$cron_export_xml_kamery["url_download"]?>">Скачать</a></li>
											<li>URL: <?=PATH?>/cron/<?=$cron_export_xml_kamery["url_download"]?></li>
										</ol>										
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>				
	</div>
</section>

<!--product-end-->
