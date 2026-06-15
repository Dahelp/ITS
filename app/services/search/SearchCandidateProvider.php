<?php

namespace app\services\search;

class SearchCandidateProvider
{
    public function findCandidates(array $parsed, int $limit = 200): array
	{
		$queryType = (string)($parsed['query_type'] ?? '');
		$rows = [];

		switch ($queryType) {
			case 'size_only':
				$rows = array_merge($rows, $this->findBySizeOnly($parsed, $limit));
				$rows = array_merge($rows, $this->findBySizeTextPrefix($parsed, $limit));
				return $this->uniqueById($rows, $limit);

			case 'size_plus_type':
			case 'size_plus_type_plus_feature':
			case 'size_plus_feature':
				$rows = array_merge($rows, $this->findBySizeContext($parsed, $limit));
				$rows = array_merge($rows, $this->findBySizeTextPrefix($parsed, $limit));
				return $this->uniqueById($rows, $limit);

			case 'numeric_compact':
				return $this->findByNumericCompact($parsed, $limit);

			case 'article_or_sku':
				$rows = array_merge($rows, $this->findByCode($parsed, $limit));
				$rows = array_merge($rows, $this->findBySizeTextPrefix($parsed, $limit));
				return $this->uniqueById($rows, $limit);

			case 'text_search':
				$rows = array_merge($rows, $this->findByText($parsed, $limit));
				$rows = array_merge($rows, $this->findBySizeTextPrefix($parsed, $limit));
				return $this->uniqueById($rows, $limit);

			case 'mixed':
				$rows = array_merge($rows, $this->findMixed($parsed, $limit));
				$rows = array_merge($rows, $this->findBySizeTextPrefix($parsed, $limit));
				return $this->uniqueById($rows, $limit);

			default:
				return $this->findBySizeTextPrefix($parsed, $limit);
		}
	}

    private function findByNumericCompact(array $parsed, int $limit): array
    {
        $compacts = (array)($parsed['numeric_compacts'] ?? []);
        if (!$compacts) {
            return [];
        }

        $rows = [];

        // 1. primary code/article/sku/model
        $rows = array_merge($rows, $this->findByPrimaryCode($parsed, $limit));

        // 2. size compact
        $rows = array_merge($rows, $this->findBySizeCompactOnly($parsed, $limit));

        // 3. cross fallback — только после primary/code и size
        $rows = array_merge($rows, $this->findByCrossFallback($parsed, $limit));

        return $this->uniqueById($rows, $limit);
    }
	
	private function extractNormalizedSizeTextPrefix(string $normalized): array
	{
		$normalized = trim((string)$normalized);
		if ($normalized === '') {
			return [];
		}

		$normalized = mb_strtolower($normalized, 'UTF-8');

		// Только ввод, похожий на размер
		if (!preg_match('~^[0-9xх×/.,\-\s]{4,}$~u', $normalized)) {
			return [];
		}

		return [$normalized];
	}

    private function findBySizeCompactOnly(array $parsed, int $limit): array
    {
        $compactCandidates = (array)($parsed['numeric_compacts'] ?? []);
        if (!$compactCandidates) {
            return [];
        }

        $where = [];
        $params = [];

        $compactPlaceholders = implode(',', array_fill(0, count($compactCandidates), '?'));
        $where[] = "p.size_search_compact IN ({$compactPlaceholders})";
        $params = array_merge($params, $compactCandidates);

        $prefixSql = $this->buildJsonTokenLikeSql($compactCandidates, 'p.size_search_prefixes');
        if ($prefixSql !== '0') {
            $where[] = '(' . $prefixSql . ')';
            $params = array_merge($params, $this->buildJsonTokenLikeParams($compactCandidates));
        }

        foreach ($compactCandidates as $compact) {
            $where[] = 'p.size_tokens LIKE ?';
            $params[] = '%' . $compact . '%';
        }

        $sql = "
            SELECT DISTINCT
                p.id,
                p.category_id,
                p.brand_id,
                p.name,
                p.article,
                p.sku,
                p.model,
                p.alias,
                p.img,
                p.price,
                p.opt_price,
                p.price_rrs,
                p.hit,
                p.new_product,
                p.sale,
                p.stock_status_id,
                p.quantity,
                p.rest,
                p.reserve,
                p.wait,
                p.search_type,
                p.search_subtype,
                p.search_brand,
                p.size_canonical,
                p.size_variants,
                p.size_search_compact,
                p.size_search_prefixes,
                p.size_tokens,
                p.search_features,
                c.name AS category_name
            FROM product p
            LEFT JOIN category c ON c.id = p.category_id
            WHERE p.hide = 'show'
            AND (" . implode(' OR ', $where) . ")
            LIMIT " . (int)$limit;

        return \R::getAll($sql, $params) ?: [];
    }

    private function findByPrimaryCode(array $parsed, int $limit): array
    {
        $articles = $parsed['articles'] ?? [];
        $skus = $parsed['skus'] ?? [];
        $crosses = $parsed['crosses'] ?? [];

        $where = [];
        $params = [];

        $normalized = (string)($parsed['normalized'] ?? '');
        if (preg_match('~^\d{4,}$~u', $normalized)) {
            $where[] = "p.article = ?";
            $params[] = $normalized;

            $where[] = "p.article LIKE ?";
            $params[] = $normalized . '%';

            $where[] = "p.model = ?";
            $params[] = $normalized;
        }

        foreach ($articles as $v) {
            $where[] = "p.article = ?";
            $params[] = $v;

            $where[] = "p.article LIKE ?";
            $params[] = $v . '%';

            $where[] = "p.model = ?";
            $params[] = $v;
        }

        foreach ($skus as $v) {
            $where[] = "p.sku = ?";
            $params[] = $v;

            $where[] = "p.sku LIKE ?";
            $params[] = $v . '%';
        }

        foreach ($crosses as $v) {
            if (preg_match('~^\d{4,}$~', (string)$v)) {
                $where[] = "p.article = ?";
                $params[] = $v;

                $where[] = "p.article LIKE ?";
                $params[] = $v . '%';

                $where[] = "p.model = ?";
                $params[] = $v;
            }
        }

        if (!$where) {
            return [];
        }

        $sql = "
            SELECT DISTINCT
                p.id,
                p.category_id,
                p.brand_id,
                p.name,
                p.article,
                p.sku,
                p.model,
                p.alias,
                p.img,
                p.price,
                p.opt_price,
                p.price_rrs,
                p.hit,
                p.new_product,
                p.sale,
                p.stock_status_id,
                p.quantity,
                p.rest,
                p.reserve,
                p.wait,
                p.search_type,
                p.search_subtype,
                p.search_brand,
                p.size_canonical,
                p.size_variants,
                p.size_search_compact,
                p.size_search_prefixes,
                p.size_tokens,
                p.search_features,
                c.name AS category_name
            FROM product p
            LEFT JOIN category c ON c.id = p.category_id
            WHERE p.hide = 'show'
            AND (" . implode(' OR ', $where) . ")
            LIMIT " . (int)$limit;

        return \R::getAll($sql, $params) ?: [];
    }

    private function findByCrossFallback(array $parsed, int $limit): array
	{
		$tokens = $this->extractCodeLikeTokens($parsed);
		if (!$tokens) {
			return [];
		}

		$where = [];
		$params = [];

		foreach ($tokens as $token) {
			$tokenLc = mb_strtolower(trim($token), 'UTF-8');
			$compact = $this->normalizeCodeToken($tokenLc);

			if ($tokenLc !== '') {
				$where[] = "LOWER(pc.cross_name) = ?";
				$params[] = $tokenLc;

				$where[] = "LOWER(pc.cross_name) LIKE ?";
				$params[] = $tokenLc . '%';

				$where[] = "LOWER(pc.cross_name) LIKE ?";
				$params[] = '%' . $tokenLc . '%';

				$where[] = "LOWER(pc.cross_abbreviated_name) = ?";
				$params[] = $tokenLc;

				$where[] = "LOWER(pc.cross_abbreviated_name) LIKE ?";
				$params[] = $tokenLc . '%';
			}

			if ($compact !== '') {
				$where[] = "LOWER(pc.cross_abbreviated_name) = ?";
				$params[] = $compact;

				$where[] = "LOWER(pc.cross_abbreviated_name) LIKE ?";
				$params[] = $compact . '%';

				$where[] = "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(pc.cross_name), '-', ''), ' ', ''), '/', ''), '.', '') = ?";
				$params[] = $compact;

				$where[] = "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(pc.cross_name), '-', ''), ' ', ''), '/', ''), '.', '') LIKE ?";
				$params[] = $compact . '%';
			}
		}

		$sql = "
			SELECT DISTINCT
				p.id,
				p.category_id,
				p.brand_id,
				p.name,
				p.article,
				p.sku,
				p.model,
				p.alias,
				p.img,
				p.price,
				p.opt_price,
				p.price_rrs,
				p.hit,
				p.new_product,
				p.sale,
				p.stock_status_id,
				p.quantity,
				p.rest,
				p.reserve,
				p.wait,
				p.search_type,
				p.search_subtype,
				p.search_brand,
				p.size_canonical,
				p.size_variants,
				p.size_search_compact,
				p.size_search_prefixes,
				p.size_tokens,
				p.search_features,
				pc.cross_name AS matched_cross_name,
				pc.cross_abbreviated_name AS matched_cross_abbreviated_name,
				c.name AS category_name
			FROM product p
			LEFT JOIN category c ON c.id = p.category_id
			LEFT JOIN plagins_cross pc ON pc.product_id = p.id
			WHERE p.hide = 'show'
			  AND (" . implode(' OR ', $where) . ")
			LIMIT " . (int)$limit;

		return \R::getAll($sql, $params) ?: [];
	}

    private function extractCompactCandidates(array $parsed): array
    {
        $result = [];

        foreach ((array)($parsed['sizes'] ?? []) as $size) {
            $canonical = (string)($size['canonical'] ?? '');
            if ($canonical !== '') {
                $digits = $this->digitsOnly($canonical);
                if ($digits !== '' && mb_strlen($digits, 'UTF-8') >= 4) {
                    $result[] = $digits;
                }
            }

            foreach ((array)($size['variants'] ?? []) as $variant) {
                $digits = $this->digitsOnly((string)$variant);
                if ($digits !== '' && mb_strlen($digits, 'UTF-8') >= 4) {
                    $result[] = $digits;
                }
            }
        }

        // поддержка "грязного" ввода без parser-size, например: 5008 / 13 5 6
        $normalized = (string)($parsed['normalized'] ?? '');
        $compactFromQuery = $this->digitsOnly($normalized);
        if ($compactFromQuery !== '' && mb_strlen($compactFromQuery, 'UTF-8') >= 4) {
            $result[] = $compactFromQuery;
        }

        return array_values(array_unique($result));
    }

    private function buildJsonTokenLikeSql(array $tokens, string $field): string
    {
        if (!$tokens) {
            return '0';
        }

        $parts = [];
        foreach ($tokens as $token) {
            $parts[] = "{$field} LIKE ?";
        }

        return implode(' OR ', $parts);
    }

    private function buildJsonTokenLikeParams(array $tokens): array
    {
        $params = [];
        foreach ($tokens as $token) {
            $params[] = '%"' . $token . '"%';
        }
        return $params;
    }

    private function findBySizeOnly(array $parsed, int $limit): array
    {
        $sizes = $parsed['sizes'] ?? [];
        $compactCandidates = $this->extractCompactCandidates($parsed);

        $canonical = !empty($sizes[0]['canonical']) ? (string)$sizes[0]['canonical'] : '';
        $variants = !empty($sizes[0]['variants']) ? (array)$sizes[0]['variants'] : [];

        if ($canonical === '' && empty($compactCandidates)) {
            return [];
        }

        $where = [];
        $params = [];

        if ($canonical !== '') {
            $where[] = 'p.size_canonical = ?';
            $params[] = $canonical;

            $digits = $this->digitsOnly($canonical);
            if ($digits !== '') {
                $where[] = 'p.size_norm_digits = ?';
                $params[] = $digits;
            }

            $variantSql = $this->buildVariantsLikeSql($variants, 'p.size_variants');
            if ($variantSql !== '0') {
                $where[] = '(' . $variantSql . ')';
                $params = array_merge($params, $this->buildVariantsLikeParams($variants));
            }
        }

        if (!empty($compactCandidates)) {
            $compactPlaceholders = implode(',', array_fill(0, count($compactCandidates), '?'));
            $where[] = "p.size_search_compact IN ({$compactPlaceholders})";
            $params = array_merge($params, $compactCandidates);

            $prefixSql = $this->buildJsonTokenLikeSql($compactCandidates, 'p.size_search_prefixes');
            if ($prefixSql !== '0') {
                $where[] = '(' . $prefixSql . ')';
                $params = array_merge($params, $this->buildJsonTokenLikeParams($compactCandidates));
            }

            // legacy fallback
            foreach ($compactCandidates as $compact) {
                $where[] = 'p.size_tokens LIKE ?';
                $params[] = '%' . $compact . '%';
            }
        }
		
		$textPrefixes = $this->extractNormalizedSizeTextPrefix($parsed['normalized'] ?? '');

		if (!empty($textPrefixes)) {
			$textPrefixSql = $this->buildJsonTokenLikeSql($textPrefixes, 'p.size_search_text_prefixes');
			if ($textPrefixSql !== '0') {
				$where[] = '(' . $textPrefixSql . ')';
				$params = array_merge($params, $this->buildJsonTokenLikeParams($textPrefixes));
			}
		}

        $sql = "
            SELECT DISTINCT
                p.id,
                p.category_id,
                p.brand_id,
                p.name,
                p.article,
                p.sku,
                p.model,
                p.alias,
                p.img,
                p.price,
                p.opt_price,
                p.price_rrs,
                p.hit,
                p.new_product,
                p.sale,
                p.stock_status_id,
                p.quantity,
                p.rest,
                p.reserve,
                p.wait,
                p.search_type,
                p.search_subtype,
                p.search_brand,
                p.size_canonical,
                p.size_variants,
                p.size_search_compact,
                p.size_search_prefixes,
                p.size_tokens,
                p.search_features,
                c.name AS category_name
            FROM product p
            LEFT JOIN category c ON c.id = p.category_id
            WHERE p.hide = 'show'
            AND (" . implode(' OR ', $where) . ")
            LIMIT " . (int)$limit;

        return \R::getAll($sql, $params) ?: [];
    }
	
	private function findBySizeTextPrefix(array $parsed, int $limit): array
	{
		$normalized = trim((string)($parsed['normalized'] ?? ''));
		if ($normalized === '') {
			return [];
		}

		$normalized = mb_strtolower($normalized, 'UTF-8');
		$normalized = strtr($normalized, [
			'×' => 'x',
			'х' => 'x',
			'Х' => 'x',
			',' => '.',
		]);
		$normalized = preg_replace('~\s+~u', ' ', $normalized) ?? $normalized;
		$normalized = trim($normalized);

		// Ищем только ввод, похожий на размер, даже если он неполный:
		// 12.5/
		// 12.5/8
		// 12.5/80
		// 12.5/80-1
		// 16x
		// 16x8
		// 16x8-
		if (!preg_match('~^[0-9]+(?:[.,][0-9]+)?(?:[/x\-][0-9]*)?(?:[/x\-][0-9.]*)?$~u', $normalized)) {
			return [];
		}

		$variants = array_values(array_unique(array_filter([
			$normalized,
			str_replace('.', ',', $normalized),
		])));

		$where = [];
		$params = [];

		foreach ($variants as $variant) {
			$where[] = "p.size_search_text_prefixes LIKE ?";
			$params[] = '%"' . $variant . '"%';
		}

		if (!$where) {
			return [];
		}

		$sql = "
			SELECT DISTINCT
				p.id,
				p.category_id,
				p.brand_id,
				p.name,
				p.article,
				p.sku,
				p.model,
				p.alias,
				p.img,
				p.price,
				p.opt_price,
				p.price_rrs,
				p.hit,
				p.new_product,
				p.sale,
				p.stock_status_id,
				p.quantity,
				p.rest,
				p.reserve,
				p.wait,
				p.search_type,
				p.search_subtype,
				p.search_brand,
				p.size_canonical,
				p.size_variants,
				p.size_search_compact,
				p.size_search_prefixes,
				p.size_search_text_prefixes,
				p.size_tokens,
				p.search_features,
				c.name AS category_name
			FROM product p
			LEFT JOIN category c ON c.id = p.category_id
			WHERE p.hide = 'show'
			  AND (" . implode(' OR ', $where) . ")
			LIMIT " . (int)$limit;

		return \R::getAll($sql, $params) ?: [];
	}

    private function findBySizeContext(array $parsed, int $limit): array
    {
        $sizes = $parsed['sizes'] ?? [];
        $compactCandidates = $this->extractCompactCandidates($parsed);

        $canonical = !empty($sizes[0]['canonical']) ? (string)$sizes[0]['canonical'] : '';
        $variants = !empty($sizes[0]['variants']) ? (array)$sizes[0]['variants'] : [];

        if ($canonical === '' && empty($compactCandidates)) {
            return [];
        }

        $where = [];
        $params = [];

        if ($canonical !== '') {
            $where[] = 'p.size_canonical = ?';
            $params[] = $canonical;

            $digits = $this->digitsOnly($canonical);
            if ($digits !== '') {
                $where[] = 'p.size_norm_digits = ?';
                $params[] = $digits;
            }

            $variantSql = $this->buildVariantsLikeSql($variants, 'p.size_variants');
            if ($variantSql !== '0') {
                $where[] = '(' . $variantSql . ')';
                $params = array_merge($params, $this->buildVariantsLikeParams($variants));
            }
        }

        if (!empty($compactCandidates)) {
            $compactPlaceholders = implode(',', array_fill(0, count($compactCandidates), '?'));
            $where[] = "p.size_search_compact IN ({$compactPlaceholders})";
            $params = array_merge($params, $compactCandidates);

            $prefixSql = $this->buildJsonTokenLikeSql($compactCandidates, 'p.size_search_prefixes');
            if ($prefixSql !== '0') {
                $where[] = '(' . $prefixSql . ')';
                $params = array_merge($params, $this->buildJsonTokenLikeParams($compactCandidates));
            }

            // legacy fallback
            foreach ($compactCandidates as $compact) {
                $where[] = 'p.size_tokens LIKE ?';
                $params[] = '%' . $compact . '%';
            }
        }

        $sql = "
            SELECT DISTINCT
                p.id,
                p.category_id,
                p.brand_id,
                p.name,
                p.article,
                p.sku,
                p.model,
                p.alias,
                p.img,
                p.price,
                p.opt_price,
                p.price_rrs,
                p.hit,
                p.new_product,
                p.sale,
                p.stock_status_id,
                p.quantity,
                p.rest,
                p.reserve,
                p.wait,
                p.search_type,
                p.search_subtype,
                p.search_brand,
                p.size_canonical,
                p.size_variants,
                p.size_search_compact,
                p.size_search_prefixes,
                p.size_tokens,
                p.search_features,
                c.name AS category_name
            FROM product p
            LEFT JOIN category c ON c.id = p.category_id
            WHERE p.hide = 'show'
            AND (" . implode(' OR ', $where) . ")
            LIMIT " . (int)$limit;

        return \R::getAll($sql, $params) ?: [];
    }

	private function findByCode(array $parsed, int $limit): array
	{
		$rows = [];

		$rows = array_merge($rows, $this->findByPrimaryCode($parsed, $limit));
		$rows = array_merge($rows, $this->findByFilterModel($parsed, $limit));
		$rows = array_merge($rows, $this->findByCrossFallback($parsed, $limit));

		return $this->uniqueById($rows, $limit);
	}
	
	private function findByFilterModel(array $parsed, int $limit): array
	{
		$tokens = $this->extractCodeLikeTokens($parsed);
		if (!$tokens) {
			return [];
		}

		$where = [];
		$params = [];

		foreach ($tokens as $token) {
			$compact = $this->normalizeCodeToken($token);

			if ($token !== '') {
				$where[] = "p.model_search_canonical = ?";
				$params[] = mb_strtolower($token, 'UTF-8');

				$where[] = "p.model_search_variants LIKE ?";
				$params[] = '%"' . mb_strtolower($token, 'UTF-8') . '"%';

				$where[] = "LOWER(p.model) LIKE ?";
				$params[] = mb_strtolower($token, 'UTF-8') . '%';
			}

			if ($compact !== '' && $compact !== mb_strtolower($token, 'UTF-8')) {
				$where[] = "p.model_search_variants LIKE ?";
				$params[] = '%"' . $compact . '"%';

				$where[] = "p.model_search_prefixes LIKE ?";
				$params[] = '%"' . $compact . '"%';
			}
		}

		if (!$where) {
			return [];
		}

		$sql = "
			SELECT DISTINCT
				p.id,
				p.category_id,
				p.brand_id,
				p.name,
				p.article,
				p.sku,
				p.model,
				p.alias,
				p.img,
				p.price,
				p.opt_price,
				p.price_rrs,
				p.hit,
				p.new_product,
				p.sale,
				p.stock_status_id,
				p.quantity,
				p.rest,
				p.reserve,
				p.wait,
				p.search_type,
				p.search_subtype,
				p.search_brand,
				p.size_canonical,
				p.size_variants,
				p.size_search_compact,
				p.size_search_prefixes,
				p.size_tokens,
				p.search_features,
				c.name AS category_name
			FROM product p
			LEFT JOIN category c ON c.id = p.category_id
			WHERE p.hide = 'show'
			  AND p.search_type IN (
				  'filter',
				  'air_filter',
				  'hydraulic_filter',
				  'oil_filter',
				  'fuel_filter',
				  'cabin_filter',
				  'dryer_filter',
				  'coolant_filter',
				  'breather_filter'
			  )
			  AND (" . implode(' OR ', $where) . ")
			LIMIT " . (int)$limit;

		return \R::getAll($sql, $params) ?: [];
	}
	
	private function extractCodeLikeTokens(array $parsed): array
	{
		$tokens = [];

		foreach (['articles', 'skus', 'crosses'] as $group) {
			foreach ((array)($parsed[$group] ?? []) as $token) {
				$token = trim((string)$token);
				if ($token !== '') {
					$tokens[] = $token;
				}
			}
		}

		$normalized = trim((string)($parsed['normalized'] ?? ''));
		if ($normalized !== '' && preg_match('~[a-zа-я0-9\-./]+~ui', $normalized)) {
			$tokens[] = $normalized;
		}

		return array_values(array_unique(array_filter($tokens)));
	}

	private function normalizeCodeToken(string $value): string
	{
		$value = mb_strtolower(trim($value), 'UTF-8');
		if ($value === '') {
			return '';
		}

		return (string)(preg_replace('~[^a-z0-9]+~u', '', $value) ?? '');
	}

    private function findByText(array $parsed, int $limit): array
    {
        $terms = $parsed['text_terms'] ?? [];
        $types = $parsed['product_types'] ?? [];
        $brands = $parsed['brands'] ?? [];

        $where = [];
        $params = [];

        foreach ($terms as $term) {
            $where[] = "p.name LIKE ?";
            $params[] = '%' . $term . '%';

            $where[] = "c.name LIKE ?";
            $params[] = '%' . $term . '%';

            $where[] = "p.model LIKE ?";
            $params[] = '%' . $term . '%';
        }

        foreach ($types as $type) {
            $where[] = "p.search_type = ?";
            $params[] = $type;

            $where[] = "p.search_subtype = ?";
            $params[] = $type;
        }

        foreach ($brands as $brand) {
            $where[] = "p.search_brand = ?";
            $params[] = $brand;
        }

        if (!$where) {
            return [];
        }

        $sql = "
            SELECT DISTINCT
                p.id,
                p.category_id,
                p.brand_id,
                p.name,
                p.article,
                p.sku,
                p.model,
                p.alias,
                p.img,
                p.price,
                p.opt_price,
                p.stock_status_id,
                p.quantity,
                p.rest,
                p.reserve,
                p.wait,
                p.price_rrs,
                p.hit,
                p.new_product,
                p.sale,
                p.size_search_compact,
                p.size_search_prefixes,
                p.size_tokens,
                p.search_type,
                p.search_subtype,
                p.search_brand,
                p.size_canonical,
                p.size_variants,
                p.search_features,
                c.name AS category_name
            FROM product p
            LEFT JOIN category c ON c.id = p.category_id
            WHERE p.hide = 'show'
              AND (" . implode(' OR ', $where) . ")
            LIMIT " . (int)$limit;

        return \R::getAll($sql, $params) ?: [];
    }

    private function findMixed(array $parsed, int $limit): array
	{
		$rows = [];

		$rows = array_merge($rows, $this->findBySizeContext($parsed, $limit));
		$rows = array_merge($rows, $this->findByCode($parsed, $limit));
		$rows = array_merge($rows, $this->findByText($parsed, $limit));

		return $this->uniqueById($rows, $limit);
	}

    private function buildVariantsLikeSql(array $variants, string $field): string
    {
        if (!$variants) {
            return '0';
        }

        $parts = [];
        foreach ($variants as $variant) {
            $parts[] = "{$field} LIKE ?";
        }

        return implode(' OR ', $parts);
    }

    private function buildVariantsLikeParams(array $variants): array
    {
        $params = [];
        foreach ($variants as $variant) {
            $params[] = '%"' . $variant . '"%';
        }
        return $params;
    }

    private function digitsOnly(string $value): string
    {
        return (string)(preg_replace('~[^0-9]+~u', '', $value) ?? '');
    }

    private function uniqueById(array $rows, int $limit): array
    {
        $result = [];
        $seen = [];

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $result[] = $row;

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }
}