<?php

namespace app\controllers;

use app\models\Breadcrumbs;
use ishop\App;

class CatalogController extends AppController {

	public function indexAction()
	{
		$alias = $this->route['alias'] ?? '';
		$cats = null;

		// Условие активной категории:
		// show — активная
		// '' или NULL — старые категории без заполненного статуса
		$activeWhere = "(hide = 'show' OR hide = '' OR hide IS NULL)";

		if ($alias) {
			$cats = \R::findOne(
				'category',
				"alias = ? AND {$activeWhere}",
				[$alias]
			);

			if (!$cats) {
				throw new \Exception('Страница не найдена', 404);
			}

			$category = \R::find(
				'category',
				"parent_id = ? AND {$activeWhere} ORDER BY position, id",
				[$cats->id]
			);

			$breadcrumbs = Breadcrumbs::getBreadcrumbs(
				$cats->id,
				null,
				$alias,
				mb_strtolower($this->route["controller"])
			);

			// H1 для вложенной страницы каталога
			if (!empty($cats->h1)) {
				$h1 = $cats->h1;
			} elseif (!empty($cats->name)) {
				$h1 = $cats->name;
			} elseif (!empty($cats->title)) {
				$h1 = $cats->title;
			} else {
				$h1 = 'Каталог';
			}

			/* SEO для категории каталога */
			$inseo = \R::findOne(
				'plagins_inseo',
				"tip = ? AND category_id = ? AND hide = 'show'",
				['category', $cats->id]
			);

			if (!empty($inseo->title)) {
				$title = App::seoreplace($inseo->title, $cats->id);
			} else {
				$title = !empty($cats->title)
					? $cats->title
					: "Каталог " . App::downFirstLetter($cats->name) . " в интернет-магазине ИТС-Центр";
			}

			if (!empty($inseo->description)) {
				$description = App::seoreplace($inseo->description, $cats->id);
			} else {
				$description = !empty($cats->description)
					? $cats->description
					: "В каталоге " . App::downFirstLetter($cats->name) . " в интернет магазине ИТС-Центр можно подобрать и купить товары с доставкой до транспортной компании.";
			}

			if (!empty($inseo->keywords)) {
				$keywords = App::seoreplace($inseo->keywords, $cats->id);
			} else {
				$keywords = !empty($cats->keywords)
					? $cats->keywords
					: App::downFirstLetter($cats->name) . " для спецтехники, " . App::downFirstLetter($cats->name) . " для погрузчиков";
			}

		} else {
			// Главная страница каталога: /catalog
			$category = \R::find(
				'category',
				"parent_id = ? AND {$activeWhere} ORDER BY position, id",
				[0]
			);

			$breadcrumbs = [
				[
					'title' => 'Главная',
					'link'  => PATH,
				],
				[
					'title' => 'Каталог',
					'link'  => '',
				],
			];

			$h1 = 'Каталог';

			$title = 'Каталог товаров ИТС-Центр';
			$description = 'Каталог товаров для спецтехники в интернет-магазине ИТС-Центр.';
			$keywords = '';
		}

		if (!$category) {
			throw new \Exception('Страница не найдена', 404);
		}

		$path_controller = !empty($this->route["controller"])
			? "/" . mb_strtolower($this->route["controller"])
			: "";

		$path_alias = !empty($this->route["alias"])
			? "/" . $this->route["alias"]
			: "";

		$this->setMeta(
			$title,
			$description,
			$keywords,
			App::$app->getProperty('shop_name'),
			PATH . '/images/' . App::$app->getProperty('og_logo'),
			PATH . $path_controller . $path_alias
		);

		$this->set(compact('category', 'breadcrumbs', 'cats', 'h1'));
	}
}
