<?php

namespace app\controllers;

class ComparisonController extends AppController {

	public function indexAction()
	{
		$cat_id = (int)($_GET['cat_id'] ?? 0);
		$this->setMeta('Сравнение товаров');
		$this->set(compact('cat_id'));
	}
	
	public function addcomparisonAction()
	{
		$product_id = (int)($_GET['product_id'] ?? 0);
		$category_id = (int)($_GET['category_id'] ?? 0);

		if ($product_id <= 0) {
			http_response_code(400);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['ok' => false, 'error' => 'bad_product_id']);
			exit;
		}

		if (!isset($_SESSION['comparison']) || !is_array($_SESSION['comparison'])) {
			$_SESSION['comparison'] = [];
		}

		$_SESSION['comparison'][$product_id] = $category_id;
		$_SESSION['comparison_count'] = count($_SESSION['comparison']);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'ok' => true,
			'result' => (int)$_SESSION['comparison_count'],
			'product_id' => $product_id,
			'category_id' => $category_id,
		]);
		exit;
	}
	
	public function deletecomparisonAction()
	{
		$product_id = (int)($_GET['product_id'] ?? 0);
		$category_id = (int)($_GET['category_id'] ?? 0);

		if ($product_id <= 0) {
			http_response_code(400);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['ok' => false, 'error' => 'bad_product_id']);
			exit;
		}

		if (!isset($_SESSION['comparison']) || !is_array($_SESSION['comparison'])) {
			$_SESSION['comparison'] = [];
		}

		if (isset($_SESSION['comparison'][$product_id])) {
			unset($_SESSION['comparison'][$product_id]);
		}

		$_SESSION['comparison_count'] = count($_SESSION['comparison']);

		$result2 = 0;
		if ($category_id > 0) {
			foreach ($_SESSION['comparison'] as $pid => $catId) {
				if ((int)$catId === $category_id) {
					$result2++;
				}
			}
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'ok' => true,
			'result' => (int)$_SESSION['comparison_count'],
			'result2' => (int)$result2,
			'product_id' => $product_id,
			'category_id' => $category_id,
		]);
		exit;
	}
	
	public function deletevseAction(){		
		unset($_SESSION['comparison']);
		unset($_SESSION['comparison_count']);
		redirect();		
	}

}