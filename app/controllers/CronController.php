<?php

namespace app\controllers;

use app\models\admin\Cron;
use app\models\AppModel;
use ishop\App;
use GuzzleHttp\Client;

class CronController extends AppController {

    public function emailsImapAction() { return "Задание выполнено!"; }
    public function refreshTovarsAction() { return "Задание выполнено!"; }
    public function refreshKameryAction() { return "Задание выполнено!"; }
    public function refreshCompleteTovarsAction() { return "Задание выполнено!"; }
    public function sitemapAction() { return "Задание выполнено!"; }
    public function ymlfidAction() { return "Задание выполнено!"; }
    public function ymlfidSpecshinyAction() { return "Задание выполнено!"; }
    public function ymlfidKvadroAction() { return "Задание выполнено!"; }
    public function ymlfidFiltryAction() { return "Задание выполнено!"; }
    public function crossymlAction() { return "Задание выполнено!"; }
    public function rssContentAction() { return "Задание выполнено!"; }
    public function exportExcelAction() { return "Задание выполнено!"; }
    public function exportCsvAction() { return "Задание выполнено!"; }
    public function exportYmlAction() { return "Задание выполнено!"; }
    public function exportExcelVseshinyAction() { return "Задание выполнено!"; }
    public function exportCsvVseshinyAction() { return "Задание выполнено!"; }
    public function exportYmlVseshinyAction() { return "Задание выполнено!"; }
    public function exportExcelKvadroshinyAction() { return "Задание выполнено!"; }
    public function exportCsvKvadroshinyAction() { return "Задание выполнено!"; }
    public function exportYmlKvadroshinyAction() { return "Задание выполнено!"; }
    public function exportYmlKvadroshinyNewAction() { return "Задание выполнено!"; }
    public function exportExcelDiskiAction() { return "Задание выполнено!"; }
    public function exportCsvDiskiAction() { return "Задание выполнено!"; }
    public function exportYmlDiskiAction() { return "Задание выполнено!"; }
    public function exportExcelFiltryAction() { return "Задание выполнено!"; }
    public function exportCsvFiltryAction() { return "Задание выполнено!"; }
    public function exportYmlFiltryAction() { return "Задание выполнено!"; }
    public function exportExcelKameryAction() { return "Задание выполнено!"; }
    public function exportExcelTyreoptAction() { return "Задание выполнено!"; }
    public function exportCsvKameryAction() { return "Задание выполнено!"; }
    public function exportYmlKameryAction() { return "Задание выполнено!"; }
    public function ymlfidCompleteAction() { return "Задание выполнено!"; }
    public function exportYmlBbSpectyreAction() { return "Задание выполнено!"; }
    public function ymlfidDirectAction() { return "Задание выполнено!"; }
    public function refreshDiskiServerAction() { return "Задание выполнено!"; }
    public function refreshAtvServerAction() { return "Задание выполнено!"; }
    public function mailAvailabilityAction() { return "Задание выполнено!"; }

	public function refreshTovarsServerAction()
    {
        http_response_code(410);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Use CLI: public/cron/run_task_cli.php --id=CRON_ID'], JSON_UNESCAPED_UNICODE);
        exit;

        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        $offset = (int)($_GET['offset'] ?? 0);
        $categories = $_GET['categories'] ?? '';
        $limit = 50;

        if (!$id || !$categories) {
            echo json_encode(['error' => 'Не переданы ID или категории']);
            exit;
        }

        \app\models\admin\Cron::writeLog("▶️ API: старт партии offset={$offset}", $id);

        $date_price = date('Y-m-d');
        $date_update = date('Y-m-d H:i:s');

        $cat_arr = array_filter(array_map('intval', explode(',', urldecode($categories))));
        if (empty($cat_arr)) {
            echo json_encode(['error' => 'Категории невалидны']);
            exit;
        }
        $cat_sql = 'AND category_id IN (' . implode(',', $cat_arr) . ')';

        try {
            $config = require ROOT . '/config/api.php';
            $client = new \GuzzleHttp\Client([
                'auth' => $config['auth'],
                'timeout' => 10,
            ]);

            $total_all = \R::getCell("SELECT COUNT(*) FROM product WHERE 1 {$cat_sql}");
            $products = \R::getAll("SELECT * FROM product WHERE 1 {$cat_sql} ORDER BY id LIMIT {$limit} OFFSET {$offset}");

            if (empty($products)) {
                \app\models\admin\Cron::writeLog("✅ API: завершено, товаров нет (offset={$offset})", $id);

                \app\models\admin\Cron::finalizeCronUpdate($id, $res['date_update'], $res['date_price']);

                echo json_encode([
                    'done' => true,
                    'offset' => $offset,
                    'total' => 0,
                    'total_all' => $total_all,
                ]);
                exit;
            }

            $updated = 0;
            foreach ($products as $product) {
                $article = ltrim(trim($product['article']), '0');

                try {
                    $res = $client->get($config['host'] . '?code=' . $article);
                    $body = $res->getBody()->getContents();
                    $data = json_decode($body, true);

                    if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
                        \app\models\admin\Cron::updateProduct($product, $data[0], $date_price, $date_update);
                        \app\models\admin\Cron::writeLog("✅ {$article} обновлён", $id);
                        $updated++;
                    } else {
                        \app\models\admin\Cron::writeLog("⚠️ {$article} — пустой ответ", $id);
                    }
                } catch (\Throwable $e) {
                    \app\models\admin\Cron::writeLog("❌ {$article} — ошибка API: " . $e->getMessage(), $id);
                }
            }

            $product_ids = array_column($products, 'id');
            $mods = \R::findAll('modification', 'product_id IN (' . implode(',', $product_ids) . ')');

            foreach ($mods as $mod) {
                $mod_article = ltrim($mod['article'], '0');

                try {
                    $res = $client->get($config['host'] . '?code=' . $mod_article);
                    $body = $res->getBody()->getContents();
                    $data = json_decode($body, true);

                    if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
                        \app\models\admin\Cron::updateModification(
                            $mod['article'],
                            $data[0]['rest'] + $data[0]['reserve'],
                            $data[0]['price_rozn'] ?? 0,
                            $data[0]['price_spec'] ?? 0,
                            $data[0]['price_opt'] ?? 0,
                            $date_price
                        );
                        \app\models\admin\Cron::writeLog("🔧 Модификация {$mod_article} обновлена", $id);
                    } else {
                        \app\models\admin\Cron::writeLog("⚠️ Модификация {$mod_article} — пустой ответ", $id);
                    }
                } catch (\Throwable $e) {
                    \app\models\admin\Cron::writeLog("❌ Модификация {$mod_article} — ошибка API: " . $e->getMessage(), $id);
                }
            }

            $endOfList = ($offset + $limit >= $total_all);

            if ($endOfList || $updated === 0) {
                \app\models\admin\Cron::writeLog("✅ API: обновление завершено (offset={$offset})", $id);

                \app\models\admin\Cron::finalizeCronUpdate($id, $res['date_update'], $res['date_price']);

                echo json_encode([
                    'done' => true,
                    'offset' => $offset + $limit,
                    'total' => $updated,
                    'total_all' => $total_all,
                ]);
                exit;
            }

            \app\models\admin\Cron::writeLog("🟢 API: партия завершена (offset={$offset}, обновлено={$updated})", $id);

            echo json_encode([
                'next' => true,
                'offset' => $offset + $limit,
                'total' => $updated,
                'total_all' => $total_all,
            ]);
            exit;
        } catch (\Throwable $e) {
            \app\models\admin\Cron::writeLog("⛔ Ошибка API, переключение на файл: " . $e->getMessage(), $id);
            echo json_encode([
                'redirect' => "/cron/refresh-tovars-from-file?id={$id}&offset={$offset}&categories=" . urlencode($categories),
            ]);
            exit;
        }
    }
	
	public function downloadTovarsFileAction()
	{
		http_response_code(410);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['error' => 'Use CLI: public/cron/run_task_cli.php --id=CRON_ID'], JSON_UNESCAPED_UNICODE);
		exit;

		header('Content-Type: application/json');
	
		$id = (int)($_GET['id'] ?? 0);
		if (!$id) {
			echo json_encode(['success' => false, 'error' => 'Не передан ID']); exit;
		}
	
		$cron = \R::findOne('cron', 'id = ?', [$id]);
		if (!$cron || empty($cron->alias)) {
			Cron::writeLog("❌ [DOWNLOAD] Не указан alias", $id);
			echo json_encode(['success' => false, 'error' => 'Не указан alias']); exit;
		}
	
		$url = trim($cron->alias);
		$file_path = WWW . '/cron/cache_file.csv';
		Cron::writeLog("📥 [DOWNLOAD] Старт загрузки: {$url}", $id);
	
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_FOLLOWLOCATION => true,
		]);
	
		$data = curl_exec($curl);
		$err = curl_error($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
	
		if (!$data || $code != 200) {
			Cron::writeLog("❌ [DOWNLOAD] Ошибка: HTTP {$code}, CURL: {$err}", $id);
			echo json_encode(['success' => false, 'error' => 'Ошибка загрузки файла']); exit;
		}
	
		if (!file_put_contents($file_path, $data)) {
			Cron::writeLog("❌ [DOWNLOAD] Не удалось сохранить файл", $id);
			echo json_encode(['success' => false, 'error' => 'Ошибка сохранения']); exit;
		}
	
		Cron::writeLog("✅ [DOWNLOAD] Файл сохранён: {$file_path}", $id);
		echo json_encode(['success' => true]); exit;
	}
	
	public function refreshTovarsFromFileAction()
    {
        http_response_code(410);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Use CLI: public/cron/run_task_cli.php --id=CRON_ID'], JSON_UNESCAPED_UNICODE);
        exit;

        header('Content-Type: application/json; charset=utf-8');

        $id         = (int)($_GET['id'] ?? 0);
        $offset     = (int)($_GET['offset'] ?? 0);
        $categories = (string)($_GET['categories'] ?? '');
        $limit      = 50;

        $res = \app\models\admin\Cron::processFileBatch($id, $categories, $offset, $limit);

        if (!empty($res['done']) && !empty($res['date_update']) && !empty($res['date_price'])) {
            // финализация в том же месте, где была раньше
            \app\models\admin\Cron::finalizeCronUpdate($id, $res['date_update'], $res['date_price']);
            unset($res['date_update'], $res['date_price']);
        }

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }
	
	public function pingApiAction()
	{
		header('Content-Type: application/json');
		$id = (int)($_GET['id'] ?? 0);
		$categories = $_GET['categories'] ?? '';

		if (!$id || !$categories) {
			echo json_encode(['success' => false, 'error' => 'Нет ID или категорий']); exit;
		}

		try {
			$cfg = require ROOT . '/config/api.php';
			$client = new \GuzzleHttp\Client([
				'auth' => $cfg['auth'],
				'timeout' => 5,
				'connect_timeout' => 3,
			]);

			// простой запрос на один код (можно любой код товара из БД)
			$response = $client->request('GET', $cfg['host'] . '?code=test', ['http_errors' => false]);
			if ($response->getStatusCode() == 200) {
				echo json_encode(['success' => true]); exit;
			} else {
				echo json_encode(['success' => false, 'error' => 'Код ' . $response->getStatusCode()]); exit;
			}
		} catch (\Throwable $e) {
			echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit;
		}
	}

}
