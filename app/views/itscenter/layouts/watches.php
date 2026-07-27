<?php $banners = \ishop\App::$app->getProperty('banners_front') ?? []; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<base href="<?= PATH ?>/">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<?php
	$controller = $this->route['controller'] ?? '';
	$action     = $this->route['action'] ?? '';

	$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
	$isPaginationPage = $page > 1;

	$robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

	$isLegacyFilterGroupController = false;
	if (!empty($controller) && !in_array($controller, ['Category', 'Product', 'Catalog'], true)) {
		try {
			$filterGroupUrls = \R::getCol("SELECT url_params FROM attribute_group WHERE url_params <> ''");
			foreach ($filterGroupUrls as $filterGroupUrl) {
				$parts = preg_split('#[^a-z0-9]+#i', (string)$filterGroupUrl);
				$parts = array_values(array_filter($parts));
				$filterController = '';
				foreach ($parts as $part) {
					$filterController .= ucfirst($part);
				}

				if (strcasecmp($filterController, (string)$controller) === 0) {
					$isLegacyFilterGroupController = true;
					break;
				}
			}
		} catch (\Throwable $e) {
			$isLegacyFilterGroupController = false;
		}
	}

	if ($controller === 'Error') {
		$robots = 'noindex, nofollow';
	} elseif ($controller === 'Cart' && $action === 'view') {
		$robots = 'noindex, follow';
	} elseif ($controller === 'Product' && !empty($product) && ($product->hide ?? '') === 'lock') {
		$robots = 'noindex, nofollow';
	} elseif ($isLegacyFilterGroupController) {
		$robots = 'noindex, follow';
	} elseif ($isPaginationPage) {
		/**
		 * Все страницы пагинации page>1 закрываем от индексации.
		 * Например:
		 * /category/shiny-dlya-vilochnyh-pogruzchikov?page=2
		 */
		$robots = 'noindex, follow';
	} elseif ($controller === 'Category') {
		$hasExtraFilter = !empty($_GET['filter']);
		foreach (array_keys($_GET) as $queryKey) {
			if (strpos((string)$queryKey, 'filter_') === 0 && $_GET[$queryKey] !== '') {
				$hasExtraFilter = true;
				break;
			}
		}
		$hasSort = !empty($_GET['sort']);

		if ($hasExtraFilter || $hasSort) {
			$robots = 'noindex, follow';
		}
	}

	$manualRobots = trim((string)($metaRobots ?? ''));
	$allowedRobots = [
		'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
		'noindex, follow',
		'noindex, nofollow',
	];

	if (
		$manualRobots !== ''
		&& in_array($manualRobots, $allowedRobots, true)
		&& !$isPaginationPage
		&& empty($_GET['filter'])
		&& empty($_GET['sort'])
	) {
		$robots = $manualRobots;
	}
	?>

	<meta name="robots" content="<?= htmlspecialchars($robots, ENT_QUOTES, 'UTF-8'); ?>" />
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

	<link rel="icon" href="/images/favicon.svg" type="image/svg+xml" />
	<link rel="shortcut icon" href="/images/favicon.svg" type="image/svg+xml" />

	<?= $this->getMeta(); ?>

	<link rel="preconnect" href="https://mc.yandex.ru" crossorigin>
	<meta name="yandex-verification" content="881bb639ff32cffe" />
	<meta name="google-site-verification" content="YTzQMjO51p1Hu9bD8voK6ug0RLNL5sswqqgk55ECgV4" />

	<style>
		/* Критические anti-FOUC стили до загрузки основных CSS */
		.collapse:not(.show) {
			display: none !important;
		}

		.modal:not(.show) {
			display: none !important;
		}

		.modal.show {
			display: block !important;
		}

		.dropdown-menu {
			display: none;
		}

		img {
			max-width: 100%;
			height: auto;
		}
	</style>

	<link rel="stylesheet" href="/css/bootstrap.css">
	<link rel="stylesheet" href="/css/flexslider.css" type="text/css" media="all" />
	<link rel="stylesheet" href="/css/aiz-core.css">
	<link rel="stylesheet" href="<?= PATH ?>/css/style.css" type="text/css" media="all" />
	<link rel="stylesheet" href="/public/adminlte/plugins/fontawesome-free/css/all.min.css" />
	<link rel="stylesheet" href="/public/adminlte/plugins/select2/css/select2.min.css" />
	<link rel="stylesheet" href="/public/adminlte/plugins/select2-bootstrap5-theme/select2-bootstrap-5-theme.min.css" />
	<link rel="stylesheet" href="/css/swiper-bundle.min.css" />
	<link rel="stylesheet" href="/css/fonts-override.css">
	<?php if (!empty($needFancybox)): ?>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css"/>
	<?php endif; ?>
	<meta name="geo.placename" content="ул. Комунальная, 26, стр. 2, Климовск, Московская область, Россия, 142184" />
	<meta name="geo.position" content="55.360413;37.562371" />
	<meta name="geo.region" content="RU-Московская область" />
	<meta name="ICBM" content="55.360413, 37.562371" />

	<?php
	$ui = [];
	$options = \R::getAll("SELECT znachenie, alt_name FROM options WHERE tip='Оформление'");
	foreach ($options as $o) {
		$ui[$o['alt_name']] = $o['znachenie'];
	}
	?>

	<style type="text/css">
		:root {
			--body: <?= !empty($ui['body_background']) ? $ui['body_background'] : '#f2f3f8' ?>;
			--category: <?= !empty($ui['font_background']) ? $ui['font_background'] : '#f2f3f8' ?>;
			--footer: <?= !empty($ui['main_backgroun']) ? $ui['main_backgroun'] : '#2d2d39' ?>;
			--a: <?= !empty($ui['a_background']) ? $ui['a_background'] : '#000' ?>;
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
			--soft-primary: rgba(230, 46, 4, 0.15);
			--secondary: #8f97ab;
			--soft-secondary: rgba(143, 151, 171, 0.15);
			--success: #198754;
			--soft-success: rgba(10, 187, 117, 0.15);
			--info: #25bcf1;
			--soft-info: rgba(37, 188, 241, 0.15);
			--warning: #ffc519;
			--soft-warning: rgba(255, 197, 25, 0.15);
			--danger: <?= !empty($ui['knp_background']) ? $ui['knp_background'] : '#ef486a' ?>;
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

	<?= ($jsonLdProduct ?? '') . "\n" ?>
	<?= ($jsonLdBreadcrumbs ?? '') . "\n" ?>
	<?= ($jsonLdCollection ?? '') . "\n" ?>
	<?= ($jsonLdFaq ?? '') . "\n" ?>

	<?php
	use app\helpers\SchemaHelper;

	$orgCfg = \ishop\App::$app->getProperty('org') ?? [];

	$base = \ishop\App::$app->getProperty('base_url') ?: (defined('PATH') ? PATH : '');
	if ($base) {
		$orgCfg['url'] = rtrim($base, '/');
	}

	$ctrl = isset($this->route['controller']) ? mb_strtolower($this->route['controller']) : '';
	$alias = isset($this->route['alias']) ? mb_strtolower($this->route['alias']) : '';
	$isContacts = ($ctrl === 'pages') && preg_match('/contact|kontak|контакт/u', $alias);

	$orgOut = $orgCfg;
	$orgOut['type'] = $isContacts ? 'LocalBusiness' : 'Organization';

	if (!$isContacts) {
		unset($orgOut['openingHours']);
	}

	if (!empty($orgOut)) {
		echo SchemaHelper::renderOrganizationJsonLd($orgOut), "\n";
	}
	?>
</head>
<body>	

<?php require __DIR__ . '/../partials/header.php'; ?>

<div class="content">
    <?php require __DIR__ . '/../partials/b2b-block.php'; ?>
	<?php
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	?>

	<?php if (!empty($_SESSION['success']) || !empty($_SESSION['error']) || !empty($_SESSION['warning']) || !empty($_SESSION['info'])): ?>
		<div class="container">
			<div class="site-flashes">

				<?php if (!empty($_SESSION['success'])): ?>
					<div class="site-flash site-flash--success" role="alert">
						<div class="site-flash__icon"><i class="fas fa-check-circle"></i></div>
						<div class="site-flash__text"><?= htmlspecialchars((string)$_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></div>
					</div>
					<?php unset($_SESSION['success']); ?>
				<?php endif; ?>

				<?php if (!empty($_SESSION['error'])): ?>
					<div class="site-flash site-flash--error" role="alert">
						<div class="site-flash__icon"><i class="fas fa-exclamation-circle"></i></div>
						<div class="site-flash__text"><?= htmlspecialchars((string)$_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></div>
					</div>
					<?php unset($_SESSION['error']); ?>
				<?php endif; ?>

				<?php if (!empty($_SESSION['warning'])): ?>
					<div class="site-flash site-flash--warning" role="alert">
						<div class="site-flash__icon"><i class="fas fa-exclamation-triangle"></i></div>
						<div class="site-flash__text"><?= htmlspecialchars((string)$_SESSION['warning'], ENT_QUOTES, 'UTF-8') ?></div>
					</div>
					<?php unset($_SESSION['warning']); ?>
				<?php endif; ?>

				<?php if (!empty($_SESSION['info'])): ?>
					<div class="site-flash site-flash--info" role="alert">
						<div class="site-flash__icon"><i class="fas fa-info-circle"></i></div>
						<div class="site-flash__text"><?= htmlspecialchars((string)$_SESSION['info'], ENT_QUOTES, 'UTF-8') ?></div>
					</div>
					<?php unset($_SESSION['info']); ?>
				<?php endif; ?>

			</div>
		</div>
	<?php endif; ?>
    <?php if(!empty($main_title[2])) { echo "".$main_title[2].""; } ?>
    <?php if(!empty($content)) { echo $content; } ?>
</div>

<?php require __DIR__ . '/../partials/decide-block.php'; ?>
<?php require __DIR__ . '/../partials/footer.php'; ?>
<?php require __DIR__ . '/../partials/modals.php'; ?>
<?php require __DIR__ . '/../partials/floating-ui.php'; ?>

<noscript>
  <div>
    <img src="https://mc.yandex.ru/watch/<?= (int)\ishop\App::options('yametrika'); ?>" style="position:absolute;left:-9999px" alt="Счётчик Яндекс Метрики">
  </div>
</noscript>

<?php $curr = \ishop\App::$app->getProperty('currency'); ?>

<script src="/js/jquery-1.11.0.min.js"></script>
<script src="/js/bootstrap.bundle.min.js"></script>
<script src="/public/adminlte/plugins/select2/js/select2.full.min.js"></script>
<script src="/js/typeahead.bundle.js"></script>
<script defer src="/js/jquery.flexslider.js"></script>
<script src="/js/swiper-bundle.min.js"></script>
<?php if (!empty($needFancybox)): ?>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<?php endif; ?>
<script>
window.APP_CONFIG = {
    path: '<?= PATH ?>',
    course: <?= (float)$curr['value'] ?>,
    symboleLeft: '<?= addslashes($curr['symbol_left']) ?>',
    symboleRight: '<?= addslashes($curr['symbol_right']) ?>',
    ymId: <?= (int)\ishop\App::options('yametrika'); ?>
};
window.APP_PATH = window.APP_CONFIG.path;
</script>

<?php require __DIR__ . '/../partials/catalog-menu.php'; ?>
<script src="/js/main.js"></script>

</body>
</html>
