<?php

namespace app\services\search;

class SearchRanker
{
    public function rankAll(array $parsed, array $candidates): array
    {
        $ranked = [];

        foreach ($candidates as $row) {
            $scoreData = $this->scoreOne($parsed, $row);

            $row['rank_score'] = $scoreData['score'];
            $row['rank_reasons'] = $scoreData['reasons'];

            $ranked[] = $row;
        }

        usort($ranked, function (array $a, array $b): int {
            $scoreCmp = ((int)$b['rank_score']) <=> ((int)$a['rank_score']);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            $stockCmp = $this->stockWeight($b) <=> $this->stockWeight($a);
            if ($stockCmp !== 0) {
                return $stockCmp;
            }

            return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });

        return $ranked;
    }

    public function scoreOne(array $parsed, array $row): array
    {
        $score = 0;
        $reasons = [];

        $queryType = (string)($parsed['query_type'] ?? '');
        $name = mb_strtolower((string)($row['name'] ?? ''), 'UTF-8');
        $categoryName = mb_strtolower((string)($row['category_name'] ?? ''), 'UTF-8');
        $article = mb_strtolower((string)($row['article'] ?? ''), 'UTF-8');
        $sku = mb_strtolower((string)($row['sku'] ?? ''), 'UTF-8');
        $model = mb_strtolower((string)($row['model'] ?? ''), 'UTF-8');
		$matchedCrossName = mb_strtolower((string)($row['matched_cross_name'] ?? ''), 'UTF-8');
		$matchedCrossAbbr = mb_strtolower((string)($row['matched_cross_abbreviated_name'] ?? ''), 'UTF-8');
        $searchType = (string)($row['search_type'] ?? '');
        $searchSubtype = (string)($row['search_subtype'] ?? '');
        $searchBrand = (string)($row['search_brand'] ?? '');
        $sizeCanonical = (string)($row['size_canonical'] ?? '');
        $sizeVariants = $this->decodeJsonArray($row['size_variants'] ?? null);
        $searchFeatures = $this->decodeJsonArray($row['search_features'] ?? null);

        // 1. exact article / sku / model / cross-like
        foreach ((array)($parsed['articles'] ?? []) as $token) {
            $token = mb_strtolower((string)$token, 'UTF-8');

            if ($token !== '' && $article === $token) {
                $score += 1200;
                $reasons['article_exact'] = ($reasons['article_exact'] ?? 0) + 1200;
            }

            if ($token !== '' && str_starts_with($article, $token)) {
                $score += 700;
                $reasons['article_prefix'] = ($reasons['article_prefix'] ?? 0) + 700;
            }

            if ($token !== '' && $model === $token) {
                $score += 900;
                $reasons['model_exact'] = ($reasons['model_exact'] ?? 0) + 900;
            }

            if ($token !== '' && mb_strpos($name, $token, 0, 'UTF-8') !== false) {
                $score += 180;
                $reasons['article_in_name'] = ($reasons['article_in_name'] ?? 0) + 180;
            }
        }

        foreach ((array)($parsed['skus'] ?? []) as $token) {
            $token = mb_strtolower((string)$token, 'UTF-8');

            if ($token !== '' && $sku === $token) {
                $score += 1200;
                $reasons['sku_exact'] = ($reasons['sku_exact'] ?? 0) + 1200;
            }

            if ($token !== '' && str_starts_with($sku, $token)) {
                $score += 650;
                $reasons['sku_prefix'] = ($reasons['sku_prefix'] ?? 0) + 650;
            }
        }

        foreach ((array)($parsed['crosses'] ?? []) as $token) {
			$token = mb_strtolower((string)$token, 'UTF-8');
			if ($token === '') {
				continue;
			}

			$compactToken = (string)(preg_replace('~[^a-z0-9]+~u', '', $token) ?? '');
			$matchedCrossNameCompact = (string)(preg_replace('~[^a-z0-9]+~u', '', $matchedCrossName) ?? '');
			$matchedCrossAbbrCompact = (string)(preg_replace('~[^a-z0-9]+~u', '', $matchedCrossAbbr) ?? '');

			if ($matchedCrossName !== '' && $matchedCrossName === $token) {
				$score += 1100;
				$reasons['cross_name_exact'] = ($reasons['cross_name_exact'] ?? 0) + 1100;
			}

			if ($matchedCrossAbbr !== '' && $matchedCrossAbbr === $token) {
				$score += 1000;
				$reasons['cross_abbr_exact'] = ($reasons['cross_abbr_exact'] ?? 0) + 1000;
			}

			if ($compactToken !== '' && $matchedCrossNameCompact === $compactToken) {
				$score += 980;
				$reasons['cross_name_compact_exact'] = ($reasons['cross_name_compact_exact'] ?? 0) + 980;
			}

			if ($compactToken !== '' && $matchedCrossAbbrCompact === $compactToken) {
				$score += 950;
				$reasons['cross_abbr_compact_exact'] = ($reasons['cross_abbr_compact_exact'] ?? 0) + 950;
			}

			if ($matchedCrossName !== '' && str_starts_with($matchedCrossName, $token)) {
				$score += 500;
				$reasons['cross_name_prefix'] = ($reasons['cross_name_prefix'] ?? 0) + 500;
			}

			if ($matchedCrossAbbr !== '' && str_starts_with($matchedCrossAbbr, $compactToken !== '' ? $compactToken : $token)) {
				$score += 450;
				$reasons['cross_abbr_prefix'] = ($reasons['cross_abbr_prefix'] ?? 0) + 450;
			}

			if ($token !== '' && mb_strpos($name, $token, 0, 'UTF-8') !== false) {
				$score += 220;
				$reasons['cross_in_name'] = ($reasons['cross_in_name'] ?? 0) + 220;
			}

			if ($token !== '' && mb_strpos($model, $token, 0, 'UTF-8') !== false) {
				$score += 260;
				$reasons['cross_in_model'] = ($reasons['cross_in_model'] ?? 0) + 260;
			}
		}
		
		foreach ((array)($parsed['articles'] ?? []) as $token) {
			$token = mb_strtolower((string)$token, 'UTF-8');
			if ($token === '') {
				continue;
			}

			if (mb_strtolower((string)($row['model_search_canonical'] ?? ''), 'UTF-8') === $token) {
				$score += 950;
				$reasons['filter_model_exact'] = ($reasons['filter_model_exact'] ?? 0) + 950;
			}

			$modelVariants = $this->decodeJsonArray($row['model_search_variants'] ?? null);
			$modelVariantsLc = array_map(
				static fn($v) => mb_strtolower((string)$v, 'UTF-8'),
				$modelVariants
			);

			if (in_array($token, $modelVariantsLc, true)) {
				$score += 700;
				$reasons['filter_model_variant'] = ($reasons['filter_model_variant'] ?? 0) + 700;
			}

			$modelPrefixes = $this->decodeJsonArray($row['model_search_prefixes'] ?? null);
			$modelPrefixesLc = array_map(
				static fn($v) => mb_strtolower((string)$v, 'UTF-8'),
				$modelPrefixes
			);

			$compactToken = (string)(preg_replace('~[^a-z0-9]+~u', '', $token) ?? '');
			if ($compactToken !== '' && in_array($compactToken, $modelPrefixesLc, true)) {
				$score += 500;
				$reasons['filter_model_prefix'] = ($reasons['filter_model_prefix'] ?? 0) + 500;
			}
		}

        foreach ((array)($parsed['numeric_compacts'] ?? []) as $compact) {
            $compact = (string)$compact;
            if ($compact === '') {
                continue;
            }

            if ((string)($row['size_search_compact'] ?? '') === $compact) {
                $score += 980;
                $reasons['size_compact_exact'] = ($reasons['size_compact_exact'] ?? 0) + 980;
            }

            $prefixes = $this->decodeJsonArray($row['size_search_prefixes'] ?? null);
            if (in_array($compact, $prefixes, true)) {
                $score += 500;
                $reasons['size_compact_prefix'] = ($reasons['size_compact_prefix'] ?? 0) + 500;
            }
        }

        // 2. size relevance
        foreach ((array)($parsed['sizes'] ?? []) as $size) {
            $canonical = mb_strtolower((string)($size['canonical'] ?? ''), 'UTF-8');
            $variants = array_map(
                static fn($v) => mb_strtolower((string)$v, 'UTF-8'),
                (array)($size['variants'] ?? [])
            );

            if ($canonical !== '' && $sizeCanonical !== '' && mb_strtolower($sizeCanonical, 'UTF-8') === $canonical) {
                $score += 1000;
                $reasons['size_exact_canonical'] = ($reasons['size_exact_canonical'] ?? 0) + 1000;
            } elseif ($canonical !== '' && in_array($canonical, array_map('mb_strtolower', $sizeVariants), true)) {
                $score += 850;
                $reasons['size_exact_variant'] = ($reasons['size_exact_variant'] ?? 0) + 850;
            } else {
                foreach ($variants as $variant) {
                    if ($variant !== '' && mb_strpos($name, $variant, 0, 'UTF-8') !== false) {
                        $score += 320;
                        $reasons['size_in_name'] = ($reasons['size_in_name'] ?? 0) + 320;
                        break;
                    }
                }
            }
        }

        // 3. product type relevance
        foreach ((array)($parsed['product_types'] ?? []) as $type) {
            $type = (string)$type;

            if ($type !== '' && $searchType === $type) {
                $score += 500;
                $reasons['type_exact'] = ($reasons['type_exact'] ?? 0) + 500;
            } elseif ($type !== '' && $searchSubtype === $type) {
                $score += 350;
                $reasons['subtype_exact'] = ($reasons['subtype_exact'] ?? 0) + 350;
            } else {
                $typeWords = $this->typeCodeToWords($type);

                foreach ($typeWords as $word) {
                    if (mb_strpos($name, $word, 0, 'UTF-8') !== false) {
                        $score += 180;
                        $reasons['type_in_name'] = ($reasons['type_in_name'] ?? 0) + 180;
                        break;
                    }

                    if (mb_strpos($categoryName, $word, 0, 'UTF-8') !== false) {
                        $score += 120;
                        $reasons['type_in_category'] = ($reasons['type_in_category'] ?? 0) + 120;
                        break;
                    }
                }
            }
        }

        // 4. feature relevance
        foreach ((array)($parsed['features'] ?? []) as $feature) {
            $feature = mb_strtolower((string)$feature, 'UTF-8');
            if ($feature === '') {
                continue;
            }

            $rowFeaturesLc = array_map(
                static fn($v) => mb_strtolower((string)$v, 'UTF-8'),
                $searchFeatures
            );

            if (in_array($feature, $rowFeaturesLc, true)) {
                $score += 220;
                $reasons['feature_exact'] = ($reasons['feature_exact'] ?? 0) + 220;
                continue;
            }

            if (mb_strpos($name, $feature, 0, 'UTF-8') !== false) {
                $score += 120;
                $reasons['feature_in_name'] = ($reasons['feature_in_name'] ?? 0) + 120;
            }
        }

        // 5. brand relevance
        foreach ((array)($parsed['brands'] ?? []) as $brand) {
            $brand = mb_strtolower((string)$brand, 'UTF-8');
            if ($brand === '') {
                continue;
            }

            if (mb_strtolower($searchBrand, 'UTF-8') === $brand) {
                $score += 180;
                $reasons['brand_exact'] = ($reasons['brand_exact'] ?? 0) + 180;
            } elseif (mb_strpos($name, $brand, 0, 'UTF-8') !== false) {
                $score += 100;
                $reasons['brand_in_name'] = ($reasons['brand_in_name'] ?? 0) + 100;
            }
        }

        // 6. text terms relevance
        foreach ((array)($parsed['text_terms'] ?? []) as $term) {
            $term = mb_strtolower((string)$term, 'UTF-8');
            if ($term === '') {
                continue;
            }

            if (mb_strpos($name, $term, 0, 'UTF-8') !== false) {
                $score += 90;
                $reasons['term_in_name'] = ($reasons['term_in_name'] ?? 0) + 90;
            }

            if (mb_strpos($categoryName, $term, 0, 'UTF-8') !== false) {
                $score += 60;
                $reasons['term_in_category'] = ($reasons['term_in_category'] ?? 0) + 60;
            }

            if (mb_strpos($model, $term, 0, 'UTF-8') !== false) {
                $score += 50;
                $reasons['term_in_model'] = ($reasons['term_in_model'] ?? 0) + 50;
            }
        }

        // 7. query type boosts / penalties
        if ($queryType === 'size_only') {
            if ($sizeCanonical !== '') {
                $score += 40;
                $reasons['query_type_size_only_bonus'] = 40;
            }
        }

        if ($queryType === 'numeric_compact') {
            $hasPrimaryCodeMatch =
                isset($reasons['article_exact']) ||
                isset($reasons['article_prefix']) ||
                isset($reasons['sku_exact']) ||
                isset($reasons['sku_prefix']) ||
                isset($reasons['model_exact']);

            $hasSizeCompactMatch =
                isset($reasons['size_compact_exact']) ||
                isset($reasons['size_compact_prefix']) ||
                isset($reasons['size_exact_canonical']) ||
                isset($reasons['size_exact_variant']);

            if (!$hasPrimaryCodeMatch && !$hasSizeCompactMatch && isset($reasons['cross_in_name'])) {
                $score -= 250;
                $reasons['cross_only_penalty'] = -250;
            }
        }

        if (in_array($queryType, ['size_plus_type', 'size_plus_type_plus_feature', 'size_plus_feature'], true)) {
            if ($sizeCanonical === '') {
                $score -= 250;
                $reasons['no_size_penalty'] = -250;
            }
        }

        if ($queryType === 'article_or_sku') {
            if ($article === '' && $sku === '' && $model === '') {
                $score -= 150;
                $reasons['no_code_fields_penalty'] = -150;
            }
        }

        // 8. commercial score
        $commercial = $this->commercialScore($row);
        $score += $commercial['score'];

        foreach ($commercial['reasons'] as $k => $v) {
            $reasons[$k] = ($reasons[$k] ?? 0) + $v;
        }

        return [
            'score' => $score,
            'reasons' => $reasons,
        ];
    }

    private function commercialScore(array $row): array
    {
        $score = 0;
        $reasons = [];

        $rest = (int)($row['rest'] ?? 0);
        $quantity = (int)($row['quantity'] ?? 0);
        $wait = (int)($row['wait'] ?? 0);
        $stockStatusId = (int)($row['stock_status_id'] ?? 0);
        $price = (float)($row['price'] ?? 0);

        if ($rest > 0) {
            $score += 45;
            $reasons['stock_rest'] = 45;
        }

        if ($quantity > 0) {
            $score += 35;
            $reasons['stock_quantity'] = 35;
        }

        if ($stockStatusId === 1) {
            $score += 25;
            $reasons['stock_status_1'] = 25;
        }

        if ($wait > 0) {
            $score += 5;
            $reasons['has_wait'] = 5;
        }

        if ($price > 0) {
            $score += 5;
            $reasons['has_price'] = 5;
        }

        return [
            'score' => $score,
            'reasons' => $reasons,
        ];
    }

    private function stockWeight(array $row): int
    {
        $weight = 0;

        if ((int)($row['rest'] ?? 0) > 0) {
            $weight += 100;
        }

        if ((int)($row['quantity'] ?? 0) > 0) {
            $weight += 60;
        }

        if ((int)($row['stock_status_id'] ?? 0) === 1) {
            $weight += 30;
        }

        if ((float)($row['price'] ?? 0) > 0) {
            $weight += 10;
        }

        return $weight;
    }

    private function decodeJsonArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($v) {
            return is_scalar($v) ? (string)$v : '';
        }, $decoded)));
    }

    private function typeCodeToWords(string $type): array
    {
        $map = [
            'tire' => ['шина', 'шины', 'покрышка'],
            'tube' => ['камера', 'автокамера'],
            'flap' => ['ободная лента', 'флиппер', 'лента'],
            'rim' => ['диск', 'диски'],
            'wheel' => ['колесо', 'колеса', 'колёса'],
            'filter' => ['фильтр', 'фильтры'],
            'air_filter' => ['воздушный фильтр'],
            'oil_filter' => ['масляный фильтр'],
            'fuel_filter' => ['топливный фильтр'],
            'hydraulic_filter' => ['гидравлический фильтр'],
            'coolant_filter' => ['фильтр охлаждающей жидкости'],
            'cabin_filter' => ['фильтр кабины', 'фильтр салона'],
            'breather_filter' => ['фильтр сапуна'],
            'dryer_filter' => ['фильтр осушитель', 'фильтр осушителя'],
            'ring' => ['кольцо'],
            'valve' => ['вентиль'],
        ];

        return $map[$type] ?? [$type];
    }
}