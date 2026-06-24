<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a><span class="visually-hidden">Главная</span></li>				
                <li class="breadcrumb-item active">Карта сайта</li>
            </ol>
		</nav>
    </div>
</div>
<div class="contents">
    <div class="container">
		<div class="row">		
			<div class="col-md-12">
				<div class="bg-light rounded-3 p-lg-5">
					<div class="register-top heading">
						<h1>Карта сайта</h1>
					</div>
					<div class="cont-inner">
						<div class="cont-desc">
							<ul>
								<li><a href="pages/kak-kupit" title="Как купить">Как купить</a></li>
								<li><a href="pages/sotrudnichestvo" title="Сотрудничество">Сотрудничество</a></li>
								<li><a href="services" title="Услуги">Услуги</a></li>
								<li><a href="services/dostavka" title="Доставка">Доставка</a></li>

							</ul>
							<ul>
								<li><a href="pages/about-us" title="О компании">О компании</a></li>
								<li><a href="news" title="Новости">Новости</a></li>
								<li><a href="pages/privacy" title="Политика конфиденциальности">Политика конфиденциальности</a></li>
								<li><a href="pages/personal-data-consent" title="Согласие на обработку персональных данных">Согласие на обработку персональных данных</a></li>
								<li><a href="pages/cookie-policy" title="Политика использования cookies">Политика использования cookies</a></li>
								<li><a href="pages/terms" title="Пользовательское соглашение">Пользовательское соглашение</a></li>
								<li><a href="pages/contacts" title="Контакты">Контакты</a></li>								
							</ul>
							<?php if($sm_category){ ?>
								<ul>
							<?php foreach($sm_category as $smc) { ?>
									<li>
										<a href="<?= PATH ?>/category/<?= h($smc['alias']) ?>"><?= h($smc['name']) ?></a>
										<ul>
											<?php foreach (($sm_category_children[(int)$smc['id']] ?? []) as $par): ?>
												<li><a href="<?= PATH ?>/category/<?= h($par['alias']) ?>" title="<?= h($par['name']) ?>"><?= h($par['name']) ?></a></li>
											<?php endforeach; ?>
										</ul>
									</li>
							<?php } ?> 
								</ul>
							<?php }	?>
							<?php if($sm_category){ ?>
								
							<?php }	?>	
							<?php if (!empty($sm_filter_landings) && is_array($sm_filter_landings)): ?>
								<ul>
									<?php foreach ($sm_filter_landings as $landing): ?>
										<?php
										$categoryAlias = trim((string)($landing['category_alias'] ?? ''), '/');
										$valueAlias = trim((string)($landing['value_alias'] ?? ''), '/');

										if ($categoryAlias === '' || $valueAlias === '') {
											continue;
										}

										$url = \app\services\filters\FilterUrlHelper::buildCategoryFilterUrl($categoryAlias, $valueAlias);
										$title = trim((string)($landing['category_name'] ?? '') . ' - ' . (string)($landing['value_name'] ?? ''));
										?>
										<li>
											<a href="<?= h($url) ?>" title="<?= h($title) ?>">
												<?= h($title) ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					</div>
				</div>					
			</div>	
		</div>
	</div>	
</div>
