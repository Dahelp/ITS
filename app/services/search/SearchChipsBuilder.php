<?php

namespace app\services\search;

class SearchChipsBuilder
{
    public function build(array $parsed, array $rankedRows, int $limit = 8): array
    {
        if (empty($rankedRows)) {
            return [];
        }

        $pool = array_slice($rankedRows, 0, 100);

        $typeCounts = [];
        $subtypeCounts = [];
        $brandCounts = [];
        $featureCounts = [];

        foreach ($pool as $row) {
            $searchType = trim((string)($row['search_type'] ?? ''));
            $searchSubtype = trim((string)($row['search_subtype'] ?? ''));
            $searchBrand = trim((string)($row['search_brand'] ?? ''));
            $searchFeatures = $this->decodeJsonArray($row['search_features'] ?? null);

            if ($searchType !== '') {
                $typeCounts[$searchType] = ($typeCounts[$searchType] ?? 0) + 1;
            }

            if ($searchSubtype !== '') {
                $subtypeCounts[$searchSubtype] = ($subtypeCounts[$searchSubtype] ?? 0) + 1;
            }

            if ($searchBrand !== '') {
                $brandCounts[$searchBrand] = ($brandCounts[$searchBrand] ?? 0) + 1;
            }

            foreach ($searchFeatures as $feature) {
                $feature = trim((string)$feature);
                if ($feature === '') {
                    continue;
                }
                $featureCounts[$feature] = ($featureCounts[$feature] ?? 0) + 1;
            }
        }

        arsort($typeCounts);
        arsort($subtypeCounts);
        arsort($brandCounts);
        arsort($featureCounts);

        $result = [];
        $seen = [];

        $queryType = (string)($parsed['query_type'] ?? '');

        if ($queryType === 'size_only') {
            $this->appendTypeChips($result, $seen, $parsed, $typeCounts, $limit);
            $this->appendFeatureChips($result, $seen, $parsed, $featureCounts, $limit);
            $this->appendBrandChips($result, $seen, $parsed, $brandCounts, $limit);
            return array_slice($result, 0, $limit);
        }

        if (in_array($queryType, ['size_plus_type', 'size_plus_type_plus_feature', 'size_plus_feature'], true)) {
            $this->appendFeatureChips($result, $seen, $parsed, $featureCounts, $limit);
            $this->appendBrandChips($result, $seen, $parsed, $brandCounts, $limit);
            $this->appendSubtypeChips($result, $seen, $parsed, $subtypeCounts, $limit);
            return array_slice($result, 0, $limit);
        }

        if ($queryType === 'article_or_sku') {
            $this->appendSubtypeChips($result, $seen, $parsed, $subtypeCounts, $limit);
            $this->appendTypeChips($result, $seen, $parsed, $typeCounts, $limit);
            $this->appendBrandChips($result, $seen, $parsed, $brandCounts, $limit);
            return array_slice($result, 0, $limit);
        }

        if ($queryType === 'text_search') {
            $this->appendSubtypeChips($result, $seen, $parsed, $subtypeCounts, $limit);
            $this->appendTypeChips($result, $seen, $parsed, $typeCounts, $limit);
            $this->appendBrandChips($result, $seen, $parsed, $brandCounts, $limit);
            $this->appendFeatureChips($result, $seen, $parsed, $featureCounts, $limit);
            return array_slice($result, 0, $limit);
        }

        // mixed / fallback
        $this->appendTypeChips($result, $seen, $parsed, $typeCounts, $limit);
        $this->appendFeatureChips($result, $seen, $parsed, $featureCounts, $limit);
        $this->appendBrandChips($result, $seen, $parsed, $brandCounts, $limit);

        return array_slice($result, 0, $limit);
    }

    private function appendTypeChips(array &$result, array &$seen, array $parsed, array $typeCounts, int $limit): void
    {
        foreach ($typeCounts as $type => $count) {
            if ($count < 2) {
                continue;
            }

            $label = $this->typeCodeToLabel($type);
            if ($label === '') {
                continue;
            }

            if ($this->isAlreadyInQuery($parsed, $label, $type)) {
                continue;
            }

            $this->pushChip($result, $seen, [
                'type' => 'product_type',
                'value' => $label,
                'label' => $label,
                'code' => $type,
                'count' => $count,
            ], $limit);

            if (count($result) >= $limit) {
                return;
            }
        }
    }

    private function appendSubtypeChips(array &$result, array &$seen, array $parsed, array $subtypeCounts, int $limit): void
    {
        foreach ($subtypeCounts as $subtype => $count) {
            if ($count < 2) {
                continue;
            }

            $label = $this->subtypeCodeToLabel($subtype);
            if ($label === '') {
                continue;
            }

            if ($this->isAlreadyInQuery($parsed, $label, $subtype)) {
                continue;
            }

            $this->pushChip($result, $seen, [
                'type' => 'product_subtype',
                'value' => $label,
                'label' => $label,
                'code' => $subtype,
                'count' => $count,
            ], $limit);

            if (count($result) >= $limit) {
                return;
            }
        }
    }

    private function appendBrandChips(array &$result, array &$seen, array $parsed, array $brandCounts, int $limit): void
    {
        foreach ($brandCounts as $brand => $count) {
            if ($count < 2) {
                continue;
            }

            $label = $this->brandCodeToLabel($brand);
            if ($label === '') {
                continue;
            }

            if ($this->isAlreadyInQuery($parsed, $label, $brand)) {
                continue;
            }

            $this->pushChip($result, $seen, [
                'type' => 'brand',
                'value' => $label,
                'label' => $label,
                'code' => $brand,
                'count' => $count,
            ], $limit);

            if (count($result) >= $limit) {
                return;
            }
        }
    }

    private function appendFeatureChips(array &$result, array &$seen, array $parsed, array $featureCounts, int $limit): void
    {
        foreach ($featureCounts as $feature => $count) {
            if ($count < 2) {
                continue;
            }

            $label = $this->featureCodeToLabel($feature);
            if ($label === '') {
                continue;
            }

            if ($this->isAlreadyInQuery($parsed, $label, $feature)) {
                continue;
            }

            $this->pushChip($result, $seen, [
                'type' => 'feature',
                'value' => $label,
                'label' => $label,
                'code' => $feature,
                'count' => $count,
            ], $limit);

            if (count($result) >= $limit) {
                return;
            }
        }
    }

    private function pushChip(array &$result, array &$seen, array $chip, int $limit): void
    {
        if (count($result) >= $limit) {
            return;
        }

        $key = mb_strtolower(trim((string)($chip['label'] ?? '')), 'UTF-8');
        if ($key === '') {
            return;
        }

        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $result[] = $chip;
    }

    private function isAlreadyInQuery(array $parsed, string $label, string $code): bool
    {
        $labelLc = mb_strtolower(trim($label), 'UTF-8');
        $codeLc = mb_strtolower(trim($code), 'UTF-8');

        $normalized = mb_strtolower((string)($parsed['normalized'] ?? ''), 'UTF-8');

        if ($labelLc !== '' && mb_strpos($normalized, $labelLc, 0, 'UTF-8') !== false) {
            return true;
        }

        if ($codeLc !== '' && mb_strpos($normalized, $codeLc, 0, 'UTF-8') !== false) {
            return true;
        }

        foreach ((array)($parsed['product_types'] ?? []) as $v) {
            if (mb_strtolower((string)$v, 'UTF-8') === $codeLc) {
                return true;
            }
        }

        foreach ((array)($parsed['features'] ?? []) as $v) {
            if (mb_strtolower((string)$v, 'UTF-8') === $codeLc) {
                return true;
            }
        }

        foreach ((array)($parsed['brands'] ?? []) as $v) {
            if (mb_strtolower((string)$v, 'UTF-8') === $codeLc) {
                return true;
            }
        }

        return false;
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

    private function typeCodeToLabel(string $code): string
    {
        $map = [
            'tire' => 'шина',
            'tube' => 'камера',
            'flap' => 'ободная лента',
            'rim' => 'диск',
            'wheel' => 'колесо',
            'filter' => 'фильтр',
            'ring' => 'кольцо',
            'valve' => 'вентиль',

            'air_filter' => 'воздушный фильтр',
            'oil_filter' => 'масляный фильтр',
            'fuel_filter' => 'топливный фильтр',
            'hydraulic_filter' => 'гидравлический фильтр',
            'coolant_filter' => 'фильтр охлаждающей жидкости',
            'cabin_filter' => 'фильтр кабины',
            'breather_filter' => 'фильтр сапуна',
            'dryer_filter' => 'фильтр осушитель',
        ];

        return $map[$code] ?? '';
    }

    private function subtypeCodeToLabel(string $code): string
    {
        $map = [
            'atv_tire' => 'шина ATV',
            'skid_steer_tire' => 'шина для минипогрузчика',
            'forklift_tire' => 'шина для вилочного погрузчика',
            'backhoe_tire' => 'шина для экскаватора-погрузчика',
            'loader_tire' => 'шина для фронтального погрузчика',
            'wheeled_excavator_tire' => 'шина для колесного экскаватора',
            'roller_tire' => 'шина для катка',
            'grader_tire' => 'шина для грейдера',
            'mining_tire' => 'шина для шахтной техники',
            'truck_tire' => 'грузовая шина',

            'tube' => 'камера',
            'flap' => 'ободная лента',
            'ring' => 'кольцо',

            'forklift_rim' => 'диск для вилочного погрузчика',
            'skid_steer_rim' => 'диск для минипогрузчика',
            'backhoe_rim' => 'диск для экскаватора-погрузчика',
            'truck_rim' => 'диск для грузовой техники',

            'air_filter' => 'воздушный фильтр',
            'oil_filter' => 'масляный фильтр',
            'fuel_filter' => 'топливный фильтр',
            'hydraulic_filter' => 'гидравлический фильтр',
            'coolant_filter' => 'фильтр охлаждающей жидкости',
            'cabin_filter' => 'фильтр кабины',
            'breather_filter' => 'фильтр сапуна',
            'dryer_filter' => 'фильтр осушитель',
        ];

        return $map[$code] ?? '';
    }

    private function brandCodeToLabel(string $code): string
    {
        $map = [
            'ekka' => 'EKKA',
            'superguider' => 'Superguider',
            'ist' => 'IST',
            'huiton' => 'Huiton',
            'boto' => 'BOTO',
            'kama' => 'КАМА',
            'asfil' => 'ASFil',
            'solidstar' => 'SOLID STAR',
            'lantian' => 'LANTIAN',
            'galaxy' => 'Galaxy',
            'wanda' => 'Wanda',
        ];

        return $map[$code] ?? strtoupper($code);
    }

    private function featureCodeToLabel(string $code): string
    {
        $map = [
            'tr13' => 'TR13',
            'tr15' => 'TR15',
            'tr87' => 'TR87',
            'solid' => 'цельнолитая',
            'non_marking' => 'non-marking',
            'pneumatic' => 'пневматическая',
            'tt' => 'TT',
            'ttf' => 'TTF',
            'tl' => 'TL',
            'butyl' => 'бутиловая',
            'reinforced' => 'усиленная',
        ];

        return $map[$code] ?? '';
    }
}