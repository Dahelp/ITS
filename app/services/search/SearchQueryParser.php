<?php

namespace app\services\search;

class SearchQueryParser
{
    private array $typeDictionary = [
        'air_filter' => [
            'воздушный фильтр',
            'воздушные фильтры',
        ],
        'oil_filter' => [
            'масляный фильтр',
            'масляные фильтры',
        ],
        'fuel_filter' => [
            'топливный фильтр',
            'топливные фильтры',
        ],
        'hydraulic_filter' => [
            'гидравлический фильтр',
            'гидравлические фильтры',
        ],
        'coolant_filter' => [
            'фильтр охлаждающей жидкости',
            'фильтры охлаждающей жидкости',
        ],
        'cabin_filter' => [
            'фильтр кабины',
            'фильтры кабины',
            'фильтр салона',
            'фильтры салона',
        ],
        'breather_filter' => [
            'фильтр сапуна',
            'фильтры сапуна',
        ],
        'dryer_filter' => [
            'фильтр осушитель',
            'фильтр осушителя',
            'фильтры осушители',
        ],
        'filter' => [
            'фильтр',
            'фильтры',
        ],
        'tube' => [
            'автокамера',
            'камера',
            'камеры',
        ],
        'flap' => [
            'ободная лента',
            'ободные ленты',
            'флиппер',
            'лента',
        ],
        'rim' => [
            'диск',
            'диски',
        ],
        'wheel' => [
            'колесо',
            'колёса',
            'колеса',
        ],
        'ring' => [
            'уплотнительное кольцо',
            'кольцо',
            'кольца',
        ],
        'valve' => [
            'вентиль',
            'вентили',
            'ниппель',
            'ниппели',
        ],
        'tire' => [
            'покрышка',
            'покрышки',
            'шина',
            'шины',
        ],
    ];

    private array $featureDictionary = [
        'tr13' => ['tr13'],
        'tr15' => ['tr15'],
        'tr87' => ['tr87'],
        'solid' => ['цельнолитая', 'цельнолитые', 'solid'],
        'non_marking' => ['non-marking', 'nonmarking', 'nm', 'непачкающая', 'белая'],
        'pneumatic' => ['пневматическая', 'пневматические'],
        'tt' => ['tt'],
        'ttf' => ['ttf'],
        'tl' => ['tl'],
        'butyl' => ['бутиловая', 'бутиловые', 'бутил'],
        'reinforced' => ['усиленная', 'усиленные'],
    ];

    private array $brandDictionary = [
        'ekka' => ['ekka', 'екка'],
        'superguider' => ['superguider'],
        'ist' => ['ist'],
        'huiton' => ['huiton'],
        'boto' => ['boto'],
        'kama' => ['kama', 'кама'],
        'asfil' => ['asfil'],
        'solidstar' => ['solid star', 'solidstar'],
        'lantian' => ['lantian'],
        'galaxy' => ['galaxy'],
        'wanda' => ['wanda'],
    ];

    private array $stopWords = [
        'для', 'под', 'при', 'или', 'как', 'это', 'the', 'and', 'with',
        'в', 'на', 'с', 'со', 'к', 'ко', 'от', 'до', 'из', 'без', 'над',
        'по', 'и', 'или', 'же', 'ли',
    ];

    public function parse(string $query): array
    {
        $raw = trim($query);
        $normalized = $this->normalizeQuery($raw);

        if ($normalized === '') {
            return [
                'raw' => $raw,
                'normalized' => '',
                'query_type' => 'empty',
                'sizes' => [],
                'product_types' => [],
                'product_type_labels' => [],
                'features' => [],
                'feature_labels' => [],
                'brands' => [],
                'brand_labels' => [],
                'articles' => [],
                'skus' => [],
                'crosses' => [],
                'text_terms' => [],
                'tokens_all' => [],
            ];
        }

        $sizes = $this->extractSizes($normalized);

        if (empty($sizes)) {
            $sizes = $this->extractCompactSizes($normalized);
        }

        $usedTokens = $this->collectUsedTokensFromSizes($sizes);

        $types = $this->extractDictionaryEntities($normalized, $this->typeDictionary, $usedTokens);
        $features = $this->extractDictionaryEntities($normalized, $this->featureDictionary, $usedTokens);
        $brands = $this->extractDictionaryEntities($normalized, $this->brandDictionary, $usedTokens);

        $usedTokens = array_merge(
            $usedTokens,
            $types['matched_tokens'],
            $features['matched_tokens'],
            $brands['matched_tokens']
        );

        $codeTokens = $this->extractCodeTokens($normalized, $usedTokens);
        $usedTokens = array_merge($usedTokens, $codeTokens['matched_tokens']);

        $textTerms = $this->extractTextTerms($normalized, $usedTokens);

        $parsed = [
            'raw' => $raw,
            'normalized' => $normalized,
            'query_type' => '',
            'sizes' => $sizes,
            'numeric_compacts' => $this->extractNumericCompacts($normalized),
            'product_types' => $types['codes'],
            'product_type_labels' => $types['labels'],
            'features' => $features['codes'],
            'feature_labels' => $features['labels'],
            'brands' => $brands['codes'],
            'brand_labels' => $brands['labels'],
            'articles' => $codeTokens['articles'],
            'skus' => $codeTokens['skus'],
            'crosses' => $codeTokens['crosses'],
            'text_terms' => $textTerms,
            'tokens_all' => $this->buildTokensAll(
                $sizes,
                $types['labels'],
                $features['labels'],
                $brands['labels'],
                $codeTokens,
                $textTerms
            ),
        ];

        $parsed['query_type'] = $this->detectQueryType($parsed);

        return $parsed;
    }

    private function extractNumericCompacts(string $normalized): array
	{
		$normalized = trim($normalized);
		if ($normalized === '') {
			return [];
		}

		$result = [];

		// только чисто цифровой запрос: 5008 / 10165 / 201010 / 1318822
		if (preg_match('~^\d{4,8}$~u', $normalized)) {
			$result[] = $normalized;
		}

		// spaced numeric: 5 00 8 / 13 5 6 / 145 70 6 / 131 8822
		// ВАЖНО: только пробелы, не дефисы и не слэши
		if (preg_match('~^\d+(?:\s+\d+){1,3}$~u', $normalized)) {
			if (preg_match_all('~\d+~u', $normalized, $m) && !empty($m[0])) {
				$parts = array_values(array_filter(array_map('trim', $m[0])));
				$compact = implode('', $parts);

				if (preg_match('~^\d{4,8}$~u', $compact)) {
					$result[] = $compact;
				}
			}
		}

		return array_values(array_unique(array_filter($result)));
	}

    private function extractCompactSizes(string $normalized): array
	{
		$result = [];
		$seen = [];

		$raw = trim($normalized);
		if ($raw === '') {
			return [];
		}

		// Разрешаем только truly spaced numeric input:
		// 5 00 8 / 13 5 6 / 20 10 10 / 145 70 6
		// Но НЕ 131-8822, НЕ 131/8822, НЕ 131.8822
		if (!preg_match('~^\d+(?:\s+\d+){1,3}$~u', $raw)) {
			return [];
		}

		if (preg_match_all('~\d+~u', $raw, $m) && !empty($m[0])) {
			$parts = array_values(array_filter(array_map('trim', $m[0])));
			if (count($parts) >= 2 && count($parts) <= 4) {
				$candidate = $this->buildSpacedSizeCandidate($raw, $parts);
				if ($candidate && !isset($seen[$candidate['canonical']])) {
					$seen[$candidate['canonical']] = true;
					$result[] = $candidate;
				}
			}
		}

		return $result;
	}

    private function looksLikeCompactSize(string $raw, string $digitsOnly): bool
    {
        $raw = trim(mb_strtolower($raw, 'UTF-8'));
        $len = mb_strlen($digitsOnly, 'UTF-8');

        // Только чисто цифровой ввод
        if (!preg_match('~^\d+$~u', $raw)) {
            return false;
        }

        // 4 цифры:
        // разрешаем только если это очень похоже на классический размер типа 5008,
        // где 3-я и 4-я цифра не образуют "11", "12", "13" и т.д. как артикул
        if ($len === 4) {
            $a = mb_substr($digitsOnly, 0, 1, 'UTF-8');
            $b = mb_substr($digitsOnly, 1, 2, 'UTF-8');
            $c = mb_substr($digitsOnly, 3, 1, 'UTF-8');

            // 5008 / 4008 / 6009 и т.п.
            // middle block должен быть типичным "00", "50", "70", "80", "90"
            // а последняя цифра — обычно диск 4..15
            $allowedMiddle = ['00', '50', '60', '70', '75', '80', '90', '95'];
            $last = (int)$c;

            if (in_array($b, $allowedMiddle, true) && $last >= 4 && $last <= 15) {
                return true;
            }            

            return false;
        }

        if ($len === 5) {
            $p1 = (int)mb_substr($digitsOnly, 0, 2, 'UTF-8');
            $p2 = (int)mb_substr($digitsOnly, 2, 1, 'UTF-8');
            $p3 = (int)mb_substr($digitsOnly, 3, 2, 'UTF-8');

            // 10-16.5 / 16.9-28 / 23.5-25
            if ($p1 >= 10 && $p1 <= 99 && $p2 >= 1 && $p2 <= 9 && $p3 >= 8 && $p3 <= 99) {
                return true;
            }

            return false;
        }

        // 6 цифр — осторожно разрешаем как ATV/садовый размер, например 201010
        if ($len === 6) {
            $p1 = (int)mb_substr($digitsOnly, 0, 2, 'UTF-8');
            $p2 = (int)mb_substr($digitsOnly, 2, 2, 'UTF-8');
            $p3 = (int)mb_substr($digitsOnly, 4, 2, 'UTF-8');

            if ($p1 >= 10 && $p1 <= 35 && $p2 >= 4 && $p2 <= 15 && $p3 >= 4 && $p3 <= 15) {
                return true;
            }

            return false;
        }

        return false;
    }

    private function buildSpacedSizeCandidate(string $raw, array $parts): ?array
    {
        $parts = array_values(array_filter(array_map(static function ($v) {
            return preg_replace('~[^0-9]+~u', '', (string)$v) ?? '';
        }, $parts)));

        if (count($parts) === 3) {
            // 5 00 8 -> 5.00-8
            if (mb_strlen($parts[0], 'UTF-8') <= 2 && mb_strlen($parts[1], 'UTF-8') <= 2 && mb_strlen($parts[2], 'UTF-8') <= 2) {
                if ($parts[1] === '00') {
                    $canonical = $parts[0] . '.00-' . $parts[2];
                    return [
                        'raw' => $raw,
                        'canonical' => $canonical,
                        'variants' => array_values(array_unique(array_filter([
                            trim($raw),
                            str_replace('.', ',', $canonical),
                            $canonical,
                            preg_replace('~[^0-9]+~u', '', $canonical),
                        ]))),
                        'family' => 'size_spaced_dash',
                    ];
                }

                // 13 5 6 -> 13x5-6
                $canonical = $parts[0] . 'x' . $parts[1] . '-' . $parts[2];
                return [
                    'raw' => $raw,
                    'canonical' => $canonical,
                    'variants' => array_values(array_unique(array_filter([
                        trim($raw),
                        $canonical,
                        str_replace('x', 'х', $canonical),
                        str_replace('x', '×', $canonical),
                        preg_replace('~[^0-9]+~u', '', $canonical),
                    ]))),
                    'family' => 'size_spaced_atv',
                ];
            }
        }

        if (count($parts) === 2) {
            // 250 15 -> 250-15
            $canonical = $parts[0] . '-' . $parts[1];
            return [
                'raw' => $raw,
                'canonical' => $canonical,
                'variants' => array_values(array_unique(array_filter([
                    trim($raw),
                    $canonical,
                    preg_replace('~[^0-9]+~u', '', $canonical),
                ]))),
                'family' => 'size_spaced_two_parts',
            ];
        }

        return null;
    }

    private function buildCompactSizeCandidate(string $raw, string $digitsOnly): ?array
    {
        $len = mb_strlen($digitsOnly, 'UTF-8');

        // Сначала пробуем специальные/наиболее вероятные ATV-формы через spaced parser,
        // а compact-only используем для классических размеров и длинных ATV
        if ($len === 4) {
            // Для 5008 предпочтительнее трактовать как 5.00-8
            $canonical = mb_substr($digitsOnly, 0, 1, 'UTF-8') . '.00-' . mb_substr($digitsOnly, 3, 1, 'UTF-8');

            return [
                'raw' => $raw,
                'canonical' => $canonical,
                'variants' => array_values(array_unique(array_filter([
                    trim($raw),
                    $digitsOnly,
                    $canonical,
                    str_replace('.', ',', $canonical),
                    mb_substr($digitsOnly, 0, 1, 'UTF-8') . ' 00 ' . mb_substr($digitsOnly, 3, 1, 'UTF-8'),
                ]))),
                'family' => 'size_compact_4',
            ];
        }

        if ($len === 5) {
            $canonical = mb_substr($digitsOnly, 0, 2, 'UTF-8') . '-' .
                mb_substr($digitsOnly, 2, 1, 'UTF-8') . '.' .
                mb_substr($digitsOnly, 3, 2, 'UTF-8');

            return [
                'raw' => $raw,
                'canonical' => $canonical,
                'variants' => array_values(array_unique(array_filter([
                    trim($raw),
                    $digitsOnly,
                    $canonical,
                    str_replace('.', ',', $canonical),
                    mb_substr($digitsOnly, 0, 2, 'UTF-8') . ' ' .
                        mb_substr($digitsOnly, 2, 1, 'UTF-8') . ' ' .
                        mb_substr($digitsOnly, 3, 2, 'UTF-8'),
                ]))),
                'family' => 'size_compact_5',
            ];
        }

        // 201010 -> 20x10-10
        if ($len === 6) {
            $canonical = mb_substr($digitsOnly, 0, 2, 'UTF-8') . 'x' . mb_substr($digitsOnly, 2, 2, 'UTF-8') . '-' . mb_substr($digitsOnly, 4, 2, 'UTF-8');

            return [
                'raw' => $raw,
                'canonical' => $canonical,
                'variants' => array_values(array_unique(array_filter([
                    trim($raw),
                    $digitsOnly,
                    $canonical,
                    str_replace('x', 'х', $canonical),
                    str_replace('x', '×', $canonical),
                    mb_substr($digitsOnly, 0, 2, 'UTF-8') . ' ' . mb_substr($digitsOnly, 2, 2, 'UTF-8') . ' ' . mb_substr($digitsOnly, 4, 2, 'UTF-8'),
                ]))),
                'family' => 'size_compact_6',
            ];
        }

        return null;
    }

    private function normalizeQuery(string $query): string
    {
        $query = trim(mb_strtolower($query, 'UTF-8'));

        if ($query === '') {
            return '';
        }

        $query = strtr($query, [
            '×' => 'x',
            'х' => 'x',
            'Х' => 'x',
            '–' => '-',
            '—' => '-',
            '−' => '-',
            'ё' => 'е',
        ]);

        // схлопываем пробелы, но не убираем полезные символы размеров
        $query = preg_replace('~\s+~u', ' ', $query) ?? $query;

        // убираем пробелы вокруг разделителей размеров
        $query = preg_replace('~\s*([x/\-])\s*~u', '$1', $query) ?? $query;

        return trim($query);
    }

    private function extractSizes(string $normalized): array
	{
		$result = [];
		$seen = [];

		$patterns = [
			// 225/75r17.5, 10.0/75-15.3
			'~\b\d{1,3}(?:[.,]\d{1,2})?/\d{1,3}(?:[.,]\d{1,2})?(?:r|-)\d{1,3}(?:[.,]\d{1,2})?\b~u',
			// 23.5r25, 17.5l-24
			'~\b\d{1,3}(?:[.,]\d{1,2})?[a-z]-\d{1,3}(?:[.,]\d{1,2})?\b~u',
			'~\b\d{1,3}(?:[.,]\d{1,2})?r\d{1,3}(?:[.,]\d{1,2})?\b~u',
			// 13x5-6, 33x12-20, 9.75x16.5
			'~\b\d{1,3}(?:[.,]\d{1,2})?x\d{1,3}(?:[.,]\d{1,2})?(?:-\d{1,3}(?:[.,]\d{1,2})?)?\b~u',
			// 5.00-8, 12-16.5, 250-15, но не OEM вроде 131-8822 — это отфильтруем в canonicalizeSize()
			'~\b\d{1,3}(?:[.,]\d{1,2})?-\d{1,4}(?:[.,]\d{1,2})?\b~u',
			// 15x4 1/2-8
			'~\b\d{1,3}(?:[.,]\d{1,2})?x\d{1,3}\s*1/2-\d{1,3}(?:[.,]\d{1,2})?\b~u',
		];

		foreach ($patterns as $pattern) {
			if (!preg_match_all($pattern, $normalized, $m)) {
				continue;
			}

			foreach ($m[0] as $rawSize) {
				$item = $this->canonicalizeSize($rawSize);
				if (!$item) {
					continue;
				}

				$key = $item['canonical'];
				if (isset($seen[$key])) {
					continue;
				}

				$seen[$key] = true;
				$result[] = $item;
			}
		}

		return $result;
	}

	private function canonicalizeSize(string $rawSize): ?array
	{
		$rawSize = trim($rawSize);
		if ($rawSize === '') {
			return null;
		}

		$s = mb_strtolower($rawSize, 'UTF-8');
		$s = strtr($s, [
			'×' => 'x',
			'х' => 'x',
			'Х' => 'x',
			',' => '.',
		]);

		$s = preg_replace('~\s+~u', ' ', $s) ?? $s;
		$s = trim($s);

		// 15x4 1/2-8
		if (preg_match('~^\d{1,3}(?:\.\d{1,2})?x\d{1,3}\s*1/2-\d{1,3}(?:\.\d{1,2})?$~u', $s)) {
			$canonical = preg_replace('~\s+~u', '', $s) ?? $s;
			return [
				'raw' => $rawSize,
				'canonical' => $canonical,
				'variants' => $this->buildSizeVariants($canonical),
				'family' => 'size_fractional',
			];
		}

		// 225/75r17.5
		if (preg_match('~^\d{1,3}(?:\.\d{1,2})?/\d{1,3}(?:\.\d{1,2})?r\d{1,3}(?:\.\d{1,2})?$~u', $s)) {
			return [
				'raw' => $rawSize,
				'canonical' => $s,
				'variants' => $this->buildSizeVariants($s),
				'family' => 'size_radial_slash',
			];
		}

		// 10.0/75-15.3
		if (preg_match('~^\d{1,3}(?:\.\d{1,2})?/\d{1,3}(?:\.\d{1,2})?-\d{1,3}(?:\.\d{1,2})?$~u', $s)) {
			return [
				'raw' => $rawSize,
				'canonical' => $s,
				'variants' => $this->buildSizeVariants($s),
				'family' => 'size_slash_dash',
			];
		}

		// 23.5r25
		if (preg_match('~^\d{1,3}(?:\.\d{1,2})?r\d{1,3}(?:\.\d{1,2})?$~u', $s)) {
			return [
				'raw' => $rawSize,
				'canonical' => $s,
				'variants' => $this->buildSizeVariants($s),
				'family' => 'size_radial',
			];
		}

		// 17.5l-24
		if (preg_match('~^\d{1,3}(?:\.\d{1,2})?[a-z]-\d{1,3}(?:\.\d{1,2})?$~u', $s)) {
			return [
				'raw' => $rawSize,
				'canonical' => $s,
				'variants' => $this->buildSizeVariants($s),
				'family' => 'size_letter_dash',
			];
		}

		// 13x5-6 / 33x12-20 / 9.75x16.5
		if (preg_match('~^\d{1,3}(?:\.\d{1,2})?x\d{1,3}(?:\.\d{1,2})?(?:-\d{1,3}(?:\.\d{1,2})?)?$~u', $s)) {
			return [
				'raw' => $rawSize,
				'canonical' => $s,
				'variants' => $this->buildSizeVariants($s),
				'family' => 'size_atv',
			];
		}

		// 5.00-8 / 12-16.5 / 250-15
		// OEM-коды вроде 131-8822 сюда НЕ должны попадать
		if (preg_match('~^(\d{1,3}(?:\.\d{1,2})?)-(\d{1,4}(?:\.\d{1,2})?)$~u', $s, $m)) {
			$left = $m[1];
			$right = $m[2];

			$leftInt = (int)$left;
			$rightInt = (int)$right;

			$isLikelyDashSize = false;

			// 5.00-8 / 6.50-10 / 16.9-28
			if (str_contains($left, '.') && $rightInt >= 4 && $rightInt <= 54) {
				$isLikelyDashSize = true;
			}

			// 12-16.5 / 10-16.5 / 14-17.5
			if (!str_contains($left, '.') && $leftInt >= 4 && $leftInt <= 54 && $rightInt >= 4 && $rightInt <= 54) {
				$isLikelyDashSize = true;
			}

			// 250-15 / 300-15 / 445-65
			if ($leftInt >= 100 && $leftInt <= 999 && $rightInt >= 4 && $rightInt <= 99) {
				$isLikelyDashSize = true;
			}

			// OEM вроде 131-8822 отсекаем:
			// если правая часть слишком длинная/большая для диска — это не размер
			if ($rightInt > 999) {
				$isLikelyDashSize = false;
			}

			if ($isLikelyDashSize) {
				$canonical = $this->normalizeDashSizePrecision($s);

				return [
					'raw' => $rawSize,
					'canonical' => $canonical,
					'variants' => $this->buildSizeVariants($canonical),
					'family' => 'size_dash',
				];
			}
		}

		return null;
	}

    private function normalizeDashSizePrecision(string $size): string
    {
        // только для классических размеров вида 5.00-8 хотим сохранять две цифры после точки
        if (preg_match('~^(\d{1,3})\.(\d{1,2})-(\d{1,3}(?:\.\d{1,2})?)$~u', $size, $m)) {
            $leftInt = $m[1];
            $leftDec = str_pad($m[2], 2, '0');
            return $leftInt . '.' . $leftDec . '-' . $m[3];
        }

        return $size;
    }

    private function buildSizeVariants(string $canonical): array
    {
        $variants = [];
        $push = static function (string $value) use (&$variants): void {
            $value = trim($value);
            if ($value === '') {
                return;
            }
            if (!in_array($value, $variants, true)) {
                $variants[] = $value;
            }
        };

        $push($canonical);
        $push(str_replace('.', ',', $canonical));

        if (mb_strpos($canonical, 'x', 0, 'UTF-8') !== false) {
            $push(str_replace('x', 'х', $canonical));
            $push(str_replace('x', '×', $canonical));
        }

        $digitsOnly = preg_replace('~[^0-9]+~u', '', $canonical) ?? '';
        if ($digitsOnly !== '' && mb_strlen($digitsOnly, 'UTF-8') >= 4) {
            $push($digitsOnly);
        }

        return $variants;
    }

    private function extractDictionaryEntities(string $normalized, array $dictionary, array $usedTokens = []): array
    {
        $codes = [];
        $labels = [];
        $matchedTokens = [];

        $usedMap = [];
        foreach ($usedTokens as $token) {
            $usedMap[mb_strtolower($token, 'UTF-8')] = true;
        }

        // сначала длинные варианты
        $pairs = [];
        foreach ($dictionary as $code => $variants) {
            foreach ($variants as $variant) {
                $pairs[] = [
                    'code' => $code,
                    'variant' => mb_strtolower(trim($variant), 'UTF-8'),
                    'label' => $variant,
                ];
            }
        }

        usort($pairs, static function (array $a, array $b): int {
            return mb_strlen($b['variant'], 'UTF-8') <=> mb_strlen($a['variant'], 'UTF-8');
        });

        foreach ($pairs as $pair) {
            $variant = $pair['variant'];
            if ($variant === '') {
                continue;
            }

            if (isset($usedMap[$variant])) {
                continue;
            }

            if (!$this->containsPhrase($normalized, $variant)) {
                continue;
            }

            if (!in_array($pair['code'], $codes, true)) {
                $codes[] = $pair['code'];
            }

            if (!in_array($pair['label'], $labels, true)) {
                $labels[] = $pair['label'];
            }

            $matchedTokens[] = $variant;
            $usedMap[$variant] = true;
        }

        return [
            'codes' => $codes,
            'labels' => $labels,
            'matched_tokens' => $matchedTokens,
        ];
    }

    private function containsPhrase(string $haystack, string $needle): bool
    {
        $needle = trim($needle);
        if ($needle === '') {
            return false;
        }

        if (mb_strpos($needle, ' ', 0, 'UTF-8') !== false) {
            return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
        }

        return (bool)preg_match('~(^|[^\p{L}\p{N}])' . preg_quote($needle, '~') . '($|[^\p{L}\p{N}])~u', $haystack);
    }

    private function extractCodeTokens(string $normalized, array $usedTokens = []): array
    {
        $articles = [];
        $skus = [];
        $crosses = [];
        $matchedTokens = [];

        $usedMap = [];
        foreach ($usedTokens as $token) {
            $usedMap[mb_strtolower(trim($token), 'UTF-8')] = true;
        }

        $parts = preg_split('~\s+~u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($parts as $part) {
            $token = trim($part);
            if ($token === '') {
                continue;
            }

            $tokenLc = mb_strtolower($token, 'UTF-8');

            if (isset($usedMap[$tokenLc])) {
                continue;
            }

            // пропускаем размеры
            if ($this->canonicalizeSize($tokenLc) !== null) {
                continue;
            }

            // кодоподобные формы
            $hasLetters = (bool)preg_match('~[a-zа-я]~u', $tokenLc);
            $hasDigits = (bool)preg_match('~\d~u', $tokenLc);
            $digitsOnlyLen = mb_strlen((string)(preg_replace('~\D+~u', '', $tokenLc) ?? ''), 'UTF-8');

            $isCodeLike =
                preg_match('~^[a-z0-9\-./]+$~u', $tokenLc) &&
                (
                    ($hasLetters && $hasDigits) ||
                    $digitsOnlyLen >= 4
                );

            if (!$isCodeLike) {
                continue;
            }

            $matchedTokens[] = $tokenLc;
            $usedMap[$tokenLc] = true;

            // Пока без точного деления article/sku/cross:
            // article — буквенно-цифровые с дефисом/буквами
            // crosses — просто длинные буквенно-цифровые
            // skus — IT0000...
            if (preg_match('~^it\d{6,}$~u', $tokenLc)) {
                $skus[] = $tokenLc;
                continue;
            }

            if (preg_match('~^[a-z]{1,5}\-?\d+[a-z0-9\-]*$~u', $tokenLc)) {
                $articles[] = $tokenLc;
                continue;
            }

            $crosses[] = $tokenLc;
        }

        return [
            'articles' => array_values(array_unique($articles)),
            'skus' => array_values(array_unique($skus)),
            'crosses' => array_values(array_unique($crosses)),
            'matched_tokens' => array_values(array_unique($matchedTokens)),
        ];
    }

    private function extractTextTerms(string $normalized, array $usedTokens = []): array
    {
        $text = ' ' . $normalized . ' ';

        // Удаляем сначала использованные длинные токены
        usort($usedTokens, static function (string $a, string $b): int {
            return mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        });

        foreach ($usedTokens as $token) {
            $token = trim(mb_strtolower($token, 'UTF-8'));
            if ($token === '') {
                continue;
            }

            $text = preg_replace(
                '~(^|[^\p{L}\p{N}])' . preg_quote($token, '~') . '($|[^\p{L}\p{N}])~u',
                ' ',
                $text
            ) ?? $text;
        }

        $parts = preg_split('~[^\p{L}\p{N}]+~u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = [];
        $seen = [];

        foreach ($parts as $part) {
            $part = trim(mb_strtolower($part, 'UTF-8'));
            if ($part === '') {
                continue;
            }

            if (in_array($part, $this->stopWords, true)) {
                continue;
            }

            if (mb_strlen($part, 'UTF-8') < 2) {
                continue;
            }

            if (preg_match('~^\d+$~u', $part)) {
                continue;
            }

            if (isset($seen[$part])) {
                continue;
            }

            $seen[$part] = true;
            $result[] = $part;
        }

        return $result;
    }

    private function detectQueryType(array $parsed): string
    {
        $hasSize = !empty($parsed['sizes']);
        $hasType = !empty($parsed['product_types']);
        $hasFeature = !empty($parsed['features']);
        $hasBrand = !empty($parsed['brands']);
        $hasCodes = !empty($parsed['articles']) || !empty($parsed['skus']) || !empty($parsed['crosses']);
        $hasNumericCompacts = !empty($parsed['numeric_compacts']);
        $hasText = !empty($parsed['text_terms']);

        if (!$hasSize && !$hasType && !$hasFeature && !$hasBrand && !$hasCodes && !$hasText) {
            return 'empty';
        }

        if ($hasSize && !$hasType && !$hasFeature && !$hasBrand && !$hasCodes && !$hasText) {
            return 'size_only';
        }

        if ($hasSize && $hasType && !$hasFeature && !$hasBrand && !$hasCodes && !$hasText) {
            return 'size_plus_type';
        }

        if ($hasSize && $hasType && $hasFeature && !$hasBrand && !$hasCodes && !$hasText) {
            return 'size_plus_type_plus_feature';
        }

        if ($hasSize && !$hasType && $hasFeature && !$hasBrand && !$hasCodes && !$hasText) {
            return 'size_plus_feature';
        }

        if (!$hasSize && !$hasType && !$hasFeature && !$hasBrand && !$hasText && !$hasCodes && $hasNumericCompacts) {
			return 'numeric_compact';
		}

        if (!$hasSize && $hasCodes && !$hasText && !$hasType && !$hasFeature && !$hasBrand) {
            return 'article_or_sku';
        }

        if (!$hasSize && !$hasCodes && ($hasText || $hasType || $hasBrand)) {
            return 'text_search';
        }

        return 'mixed';
    }

    private function collectUsedTokensFromSizes(array $sizes): array
    {
        $result = [];

        foreach ($sizes as $size) {
            if (!empty($size['raw'])) {
                $result[] = mb_strtolower((string)$size['raw'], 'UTF-8');
            }
            if (!empty($size['canonical'])) {
                $result[] = mb_strtolower((string)$size['canonical'], 'UTF-8');
            }
            if (!empty($size['variants']) && is_array($size['variants'])) {
                foreach ($size['variants'] as $variant) {
                    $result[] = mb_strtolower((string)$variant, 'UTF-8');
                }
            }
        }

        return array_values(array_unique(array_filter($result)));
    }

    private function buildTokensAll(
        array $sizes,
        array $typeLabels,
        array $featureLabels,
        array $brandLabels,
        array $codeTokens,
        array $textTerms
    ): array {
        $result = [];

        foreach ($sizes as $size) {
            if (!empty($size['canonical'])) {
                $result[] = (string)$size['canonical'];
            }
        }

        foreach ($typeLabels as $v) {
            $result[] = (string)$v;
        }

        foreach ($featureLabels as $v) {
            $result[] = (string)$v;
        }

        foreach ($brandLabels as $v) {
            $result[] = (string)$v;
        }

        foreach (['articles', 'skus', 'crosses'] as $group) {
            if (!empty($codeTokens[$group])) {
                foreach ($codeTokens[$group] as $v) {
                    $result[] = (string)$v;
                }
            }
        }

        foreach ($textTerms as $v) {
            $result[] = (string)$v;
        }

        $clean = [];
        $seen = [];

        foreach ($result as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $key = mb_strtolower($item, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = $item;
        }

        return $clean;
    }
}