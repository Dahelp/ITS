<?php

namespace app\controllers;

use app\services\search\SearchCandidateProvider;
use app\services\search\SearchChipsBuilder;
use app\services\search\SearchQueryParser;
use app\services\search\SearchRanker;
use ishop\App;
use ishop\libs\Pagination;

class SearchController extends AppController
{
    private SearchQueryParser $parser;
    private SearchCandidateProvider $candidateProvider;
    private SearchRanker $ranker;
    private SearchChipsBuilder $chipsBuilder;

    public function __construct($route)
    {
        parent::__construct($route);

        $this->parser = new SearchQueryParser();
        $this->candidateProvider = new SearchCandidateProvider();
        $this->ranker = new SearchRanker();
        $this->chipsBuilder = new SearchChipsBuilder();
    }

    public function typeaheadAction()
    {
        $this->layout = false;
        $this->view = null;

        header('Content-Type: application/json; charset=UTF-8');

        try {
            $query = isset($_GET['query']) ? trim((string)$_GET['query']) : '';

            if ($query === '') {
                echo json_encode([
                    'keywords' => [],
                    'products' => [],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $parsed = $this->parser->parse($query);

			// Для dropdown берём немного больше кандидатов, потом режем
			$candidates = $this->candidateProvider->findCandidates($parsed, 200);

			if (empty($candidates)) {
				echo json_encode([
					'keywords' => [],
					'products' => [],
				], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				exit;
			}

			$ranked = $this->ranker->rankAll($parsed, $candidates);
            $chips = $this->chipsBuilder->build($parsed, $ranked, 8);

            $keywords = array_values(array_filter(array_map(static function (array $chip): string {
                return trim((string)($chip['label'] ?? ''));
            }, $chips)));

            $topProducts = array_slice($ranked, 0, 8);

            $payload = [
                'keywords' => $keywords,
                'products' => array_map(static function (array $row): array {
                    return [
                        'id'        => (int)($row['id'] ?? 0),
                        'name'      => (string)($row['name'] ?? ''),
                        'img'       => (string)($row['img'] ?? ''),
                        'price'     => (string)($row['price'] ?? ''),
                        'opt_price' => (string)($row['opt_price'] ?? ''),
                        'alias'     => (string)($row['alias'] ?? ''),
                        'category'  => (string)($row['category_name'] ?? ''),
                    ];
                }, $topProducts),
            ];

            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (\Throwable $e) {
            error_log('[SearchController::typeaheadAction] ' . $e->getMessage());

            echo json_encode([
                'keywords' => [],
                'products' => [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    public function indexAction()
	{
		$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
		$perpage = App::$app->getProperty('pagination') ?: 20;

		if (empty($_GET['s'])) {
			throw new \Exception('Страница не найдена', 404);
		}

		$query = trim((string)$_GET['s']);
		$raw = $query;

		if ($query === '') {
			throw new \Exception('Страница не найдена', 404);
		}

		try {
			$parsed = $this->parser->parse($query);

			// Для страницы поиска допустим больший пул кандидатов
			$candidateLimit = 500;
			$candidates = $this->candidateProvider->findCandidates($parsed, $candidateLimit);

			if (empty($candidates)) {
				$products = [];
				$total = 0;
				$pagination = new Pagination($page, $perpage, $total);
				$searchWidgetContext = [];

				$this->setMeta('Поиск по: ' . h($raw));
				$this->set(compact('products', 'query', 'pagination', 'total', 'searchWidgetContext'));
				return;
			}

			$ranked = $this->ranker->rankAll($parsed, $candidates);

			$total = count($ranked);
			$pagination = new Pagination($page, $perpage, $total);
			$start = $pagination->getStart();

			$products = array_slice($ranked, $start, $perpage);
			$searchWidgetContext = \app\widgets\product\Product::buildContext($products);

			$this->setMeta('Поиск по: ' . h($raw));
			$this->set(compact('products', 'query', 'pagination', 'total', 'searchWidgetContext'));

		} catch (\Throwable $e) {
			error_log('[SearchController::indexAction] ' . $e->getMessage());
			throw $e;
		}
	}
}