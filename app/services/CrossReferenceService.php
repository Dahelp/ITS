<?php

namespace app\services;

/**
 * Единая точка чтения кросс-номеров для фильтров, дисков и будущих типов товаров.
 * Новая схема используется при наличии таблиц; старая plagins_cross остаётся fallback.
 */
class CrossReferenceService
{
    private static $schemaAvailable;

    public static function hasUnifiedSchema(): bool
    {
        if (self::$schemaAvailable !== null) {
            return self::$schemaAvailable;
        }

        try {
            self::$schemaAvailable = (bool)\R::getCell("SHOW TABLES LIKE 'cross_reference'")
                && (bool)\R::getCell("SHOW TABLES LIKE 'product_cross'");
        } catch (\Throwable $e) {
            self::$schemaAvailable = false;
        }

        return self::$schemaAvailable;
    }

    public static function normalizeCode(string $code): string
    {
        $code = mb_strtoupper(trim($code), 'UTF-8');
        return preg_replace('~[^A-ZА-ЯЁ0-9]+~u', '', $code) ?: '';
    }

    public static function slug(string $code): string
    {
        return mb_strtolower(self::normalizeCode($code), 'UTF-8');
    }

    public static function productType($product): string
    {
        $categoryId = (int)(is_object($product) ? ($product->category_id ?? 0) : ($product['category_id'] ?? 0));
        $seen = [];

        while ($categoryId > 0 && !isset($seen[$categoryId])) {
            $seen[$categoryId] = true;
            $category = \R::getRow('SELECT id, parent_id FROM category WHERE id = ? LIMIT 1', [$categoryId]);
            if (!$category) {
                break;
            }
            if ((int)$category['id'] === 3 || (int)$category['parent_id'] === 3) {
                return 'wheel';
            }
            if ((int)$category['id'] === 4 || (int)$category['parent_id'] === 4) {
                return 'filter';
            }
            $categoryId = (int)$category['parent_id'];
        }

        return 'generic';
    }

    public static function routeKind(string $productType): string
    {
        return $productType === 'wheel' ? 'diski' : '';
    }

    public static function url(array $cross, ?string $productType = null): string
    {
        $slug = (string)($cross['slug'] ?? $cross['cross_abbreviated_name'] ?? '');
        $slug = mb_strtolower(trim($slug), 'UTF-8');
        $productType = $productType ?: (string)($cross['product_type'] ?? 'filter');
        $prefix = self::routeKind($productType);
        return '/cross/' . ($prefix !== '' ? $prefix . '/' : '') . rawurlencode($slug);
    }

    public static function findMatches(string $slug, ?string $routeKind = null): array
    {
        $normalized = self::normalizeCode(rawurldecode($slug));
        if ($normalized === '') {
            return [];
        }

        if (self::hasUnifiedSchema()) {
            $rows = \R::getAll(
                "SELECT cr.id AS cross_reference_id, cr.display_code AS cross_name,
                        cr.slug AS cross_abbreviated_name, cr.normalized_code,
                        cr.vendor_id, COALESCE(crc.manufacturer, cv.name) AS vendor_name,
                        crc.manufacturer AS cross_manufacturer,
                        crc.equipment_models AS cross_equipment_models,
                        crc.source_note AS cross_source_note,
                        crc.confidence AS cross_confidence,
                        pc.id AS product_cross_id, pc.product_id, pc.relation_type,
                        pc.part_role, pc.product_type, pc.source,
                        p.*, c.name AS category_name
                 FROM cross_reference cr
                 JOIN product_cross pc ON pc.cross_id = cr.id AND pc.is_active = 1
                 JOIN product p ON p.id = pc.product_id AND p.hide != 'hide'
                 LEFT JOIN plagins_cross_vendor cv ON cv.id = cr.vendor_id
                 LEFT JOIN cross_reference_context crc ON crc.cross_id = cr.id
                 LEFT JOIN category c ON c.id = p.category_id
                 WHERE cr.normalized_code = ?
                 ORDER BY pc.product_type, p.quantity DESC, p.id",
                [$normalized]
            );

            if ($routeKind === 'diski') {
                $rows = array_values(array_filter($rows, static function ($row) {
                    return ($row['product_type'] ?? '') === 'wheel';
                }));
            } elseif ($routeKind !== null && $routeKind !== '') {
                $rows = array_values(array_filter($rows, static function ($row) use ($routeKind) {
                    return ($row['product_type'] ?? '') === $routeKind;
                }));
            } else {
                // Старый URL сохраняется за фильтрами. Для других типов он работает,
                // только если фильтров с таким номером нет.
                $filters = array_values(array_filter($rows, static function ($row) {
                    return ($row['product_type'] ?? '') === 'filter';
                }));
                if ($filters) {
                    $rows = $filters;
                }
            }

            return $rows;
        }

        return \R::getAll(
            "SELECT pc.cross_name, pc.cross_abbreviated_name, pc.vendor_id,
                    cv.name AS vendor_name, pc.product_id,
                    CASE pc.equipment_vendor WHEN 1 THEN 'oem' ELSE 'analog' END AS relation_type,
                    pc.tip_cross AS part_role, 'filter' AS product_type, p.*
             FROM plagins_cross pc
             JOIN product p ON p.id = pc.product_id AND p.hide != 'hide'
             LEFT JOIN plagins_cross_vendor cv ON cv.id = pc.vendor_id
             WHERE UPPER(pc.cross_abbreviated_name) = ?
             ORDER BY p.quantity DESC, p.id",
            [$normalized]
        );
    }

    public static function forProduct(int $productId): array
    {
        if (self::hasUnifiedSchema()) {
            return \R::getAll(
                "SELECT COALESCE(cv.name, '') AS name, cr.display_code AS cross_name,
                        cr.slug AS cross_abbreviated_name, cr.slug,
                        pc.relation_type,
                        CASE pc.relation_type WHEN 'oem' THEN 1 ELSE 2 END AS equipment_vendor,
                        pc.part_role,
                        CASE pc.part_role WHEN 'outer' THEN 1 WHEN 'inner' THEN 2
                             WHEN 'kit' THEN 4 ELSE 3 END AS tip_cross,
                        pc.product_type
                 FROM product_cross pc
                 JOIN cross_reference cr ON cr.id = pc.cross_id
                 LEFT JOIN plagins_cross_vendor cv ON cv.id = cr.vendor_id
                 WHERE pc.product_id = ? AND pc.is_active = 1
                 ORDER BY pc.relation_type, cr.display_code",
                [$productId]
            );
        }

        return \R::getAll(
            "SELECT pcv.name, pc.cross_name, pc.cross_abbreviated_name,
                    pc.tip_cross, pc.equipment_vendor,
                    CASE pc.equipment_vendor WHEN 1 THEN 'oem' ELSE 'analog' END AS relation_type,
                    'filter' AS product_type
             FROM plagins_cross pc
             LEFT JOIN plagins_cross_vendor pcv ON pc.vendor_id = pcv.id
             WHERE pc.product_id = ?",
            [$productId]
        );
    }

    public static function context($product, array $cross, string $productType): array
    {
        $code = (string)($cross['cross_name'] ?? '');
        $vendor = trim((string)($cross['vendor_name'] ?? $cross['name'] ?? ''));
        $vendorSuffix = $vendor !== '' && mb_strtoupper($vendor, 'UTF-8') !== 'OEM' ? ' ' . $vendor : '';

        if ($productType === 'wheel') {
            $manufacturer = trim((string)($cross['cross_manufacturer'] ?? $vendor));
            if (mb_strtoupper($manufacturer, 'UTF-8') === 'OEM') $manufacturer = '';
            $models = self::shortModels((string)($cross['cross_equipment_models'] ?? ''), 4);
            $application = trim($manufacturer . ($models !== '' ? ' ' . $models : ''));
            $applicationSuffix = $application !== '' ? ' для ' . $application : '';
            $manufacturerSuffix = $manufacturer !== '' ? ' ' . $manufacturer : '';
            return [
                'type' => 'wheel',
                'subject' => 'диск',
                'subjectGenitive' => 'диска',
                'heading' => 'Диск — аналог OEM ' . $code . $manufacturerSuffix,
                'title' => 'Диск-аналог OEM ' . $code . $applicationSuffix . ' — купить | ИТС-Центр',
                'description' => 'Купить диск-аналог OEM ' . $code . $applicationSuffix . '. Подходит по параметрам к диску ' . (string)$product->name . '. Доставка по России.',
                'keywords' => 'диск ' . $code . ', аналог OEM ' . $code . ($manufacturer !== '' ? ', диск ' . $manufacturer : ''),
                'intro' => 'Для OEM-номера ' . $code . $applicationSuffix . ' предлагаем совместимый диск ' . (string)$product->name . '.',
                'manufacturer' => $manufacturer,
                'equipmentModels' => (string)($cross['cross_equipment_models'] ?? ''),
                'sourceNote' => (string)($cross['cross_source_note'] ?? ''),
                'showPartRole' => false,
                'routeKind' => 'diski',
            ];
        }

        if ($productType === 'filter') {
            return [
                'type' => 'filter',
                'subject' => 'фильтр',
                'subjectGenitive' => 'фильтра',
                'heading' => (string)$product->name . ' — аналог фильтра ' . $code . $vendorSuffix,
                'title' => 'Купить аналог фильтра ' . $code . $vendorSuffix . ' по низким ценам | ИТС-Центр',
                'description' => (string)$product->name . ' является аналогом фильтра' . $vendorSuffix . ' с OEM номером ' . $code . '. Доставка по России.',
                'keywords' => 'купить ' . trim($vendorSuffix) . ', фильтр ' . $code . ', цена',
                'intro' => 'Аналогом фильтра ' . $code . $vendorSuffix . ' является ' . (string)$product->name . '.',
                'showPartRole' => true,
                'routeKind' => '',
            ];
        }

        return [
            'type' => 'generic',
            'subject' => 'товар',
            'subjectGenitive' => 'товара',
            'heading' => (string)$product->name . ' — аналог ' . $code,
            'title' => 'Купить аналог ' . $code . ' | ИТС-Центр',
            'description' => (string)$product->name . ' соответствует номеру ' . $code . '. Доставка по России.',
            'keywords' => $code . ', аналог, купить',
            'intro' => 'Номеру ' . $code . ' соответствует ' . (string)$product->name . '.',
            'showPartRole' => false,
            'routeKind' => '',
        ];
    }

    private static function shortModels(string $models, int $limit): string
    {
        $items = array_values(array_filter(array_map('trim', preg_split('~\s*,\s*~u', $models) ?: [])));
        if (!$items) return '';
        $short = array_slice($items, 0, $limit);
        return implode(', ', $short) . (count($items) > $limit ? ' и другие модели' : '');
    }

    public static function productContextData(int $productId): array
    {
        if (!self::hasUnifiedSchema() || !\R::getCell("SHOW TABLES LIKE 'product_cross_context'")) {
            return [];
        }
        return \R::getRow('SELECT * FROM product_cross_context WHERE product_id = ? LIMIT 1', [$productId]) ?: [];
    }

    /**
     * Применяемость диска из импорта. Ссылка добавляется только для модели,
     * которая действительно существует в каталоге техники сайта.
     */
    public static function wheelApplicationsForProduct(int $productId): array
    {
        $context = self::productContextData($productId);
        $fitment = trim((string)($context['fitment_text'] ?? ''));
        if ($fitment === '') return [];

        $catalog = \R::getAll(
            'SELECT t.model,t.alias,tm.name FROM technics t
             JOIN technics_manufacturer tm ON tm.id=t.manufacturer_id
             WHERE t.alias IS NOT NULL AND t.alias != ? ORDER BY tm.name,t.model',
            ['']
        );
        $catalogByBrand = [];
        foreach ($catalog as $item) {
            $brandKey = self::applicationKey((string)$item['name']);
            $catalogByBrand[$brandKey][] = $item;
        }

        $brandAliases = [
            'DAEWOO' => 'DOOSAN', 'DOOSAN' => 'DOOSAN', 'HYUNDAI' => 'HYUNDAI',
            'JAC' => 'JAC', 'KOMATSU' => 'KOMATSU', 'LIUGONG' => 'LIUGONG',
            'LONKING' => 'LONKING', 'MITSUBISHI' => 'MITSUBISHI', 'TCM' => 'TCM',
            'TOYOTA' => 'TOYOTA',
        ];
        $sourceBrands = [
            'DAEWOO', 'DOOSAN', 'KOMATSU', 'MITSUBISHI', 'NISSAN', 'TOYOTA',
            'DALIAN', 'EP', 'GOODSENSE', 'HC', 'HELI', 'JAC', 'LIUGONG',
            'LONKING', 'MAXIMAL', 'TCM', 'TEU', 'HYUNDAI',
        ];

        $result = [];
        $lastBrand = '';
        foreach (preg_split('~\s*,\s*~u', $fitment) ?: [] as $rawItem) {
            $rawItem = trim($rawItem);
            if ($rawItem === '') continue;

            $brand = '';
            $model = $rawItem;
            foreach ($sourceBrands as $candidate) {
                if (preg_match('~^' . preg_quote($candidate, '~') . '\s+(.+)$~iu', $rawItem, $match)) {
                    $brand = $candidate;
                    $model = trim($match[1]);
                    break;
                }
            }
            if ($brand === '' && $lastBrand !== '') {
                $brand = $lastBrand;
            } elseif ($brand !== '') {
                $lastBrand = $brand;
            }

            $siteBrandKey = $brandAliases[mb_strtoupper($brand, 'UTF-8')] ?? '';
            $matches = [];
            if ($siteBrandKey !== '' && !empty($catalogByBrand[$siteBrandKey])) {
                $modelKey = self::applicationModelKey($model);
                $modelWithoutSeries = preg_replace('~^[A-Z0-9]+\s+(?=[A-Z]{2,}\d)~u', '', mb_strtoupper($model, 'UTF-8')) ?: $model;
                $modelWithoutSeriesKey = self::applicationModelKey($modelWithoutSeries);
                $rangeMatch = [];
                preg_match('~^([A-Z]+)(\d+)-(\d+)~', mb_strtoupper(trim($modelWithoutSeries), 'UTF-8'), $rangeMatch);
                foreach ($catalogByBrand[$siteBrandKey] as $technic) {
                    $catalogKey = self::applicationModelKey((string)$technic['model']);
                    if ($catalogKey === $modelKey || $catalogKey === $modelWithoutSeriesKey) {
                        $matches[] = $technic;
                        continue;
                    }
                    if ($rangeMatch
                        && preg_match('~^' . preg_quote($rangeMatch[1], '~') . '(\d+)$~', $catalogKey, $number)
                        && in_array((int)$number[1], [(int)$rangeMatch[2], (int)$rangeMatch[3]], true)) {
                        $matches[] = $technic;
                    }
                }
            }

            if ($matches) {
                foreach ($matches as $technic) {
                    $key = self::applicationKey((string)$technic['name'] . ' ' . (string)$technic['model']);
                    $result[$key] = [
                        'name' => (string)$technic['name'],
                        'model' => (string)$technic['model'],
                        'alias' => (string)$technic['alias'],
                    ];
                }
            } else {
                $displayBrand = $brand !== '' ? $brand : '—';
                $key = self::applicationKey($displayBrand . ' ' . $model);
                $result[$key] = ['name' => $displayBrand, 'model' => $model, 'alias' => ''];
            }
        }

        return array_values($result);
    }

    private static function applicationKey(string $value): string
    {
        return preg_replace('~[^A-ZА-ЯЁ0-9]+~u', '', mb_strtoupper(trim($value), 'UTF-8')) ?: '';
    }

    private static function applicationModelKey(string $value): string
    {
        $value = preg_replace('~\s*\(([^)]+)\)\s*~u', '$1', trim($value));
        return self::applicationKey((string)$value);
    }

    public static function syncLegacyRow(int $legacyRowId): void
    {
        if (!self::hasUnifiedSchema()) return;
        $row = \R::getRow('SELECT * FROM plagins_cross WHERE id = ? LIMIT 1', [$legacyRowId]);
        if (!$row) return;

        $sourceKey = (string)$row['cross_id'];
        \R::exec("DELETE FROM product_cross WHERE source='legacy' AND source_key=?", [$sourceKey]);
        $normalized = self::normalizeCode((string)$row['cross_name']);
        if ($normalized === '') return;

        $product = \R::getRow('SELECT id,category_id FROM product WHERE id=? LIMIT 1', [(int)$row['product_id']]);
        if (!$product) return;
        $productType = self::productType($product);
        if ($productType === 'wheel' && \R::getCell(
            "SELECT pc.id FROM product_cross pc JOIN cross_reference cr ON cr.id=pc.cross_id
             WHERE pc.product_id=? AND pc.source='wheel_cross_excel' AND cr.normalized_code=? LIMIT 1",
            [(int)$row['product_id'], $normalized]
        )) {
            return;
        }

        $vendorId = (int)$row['vendor_id'] ?: null;
        $crossId = (int)\R::getCell(
            'SELECT id FROM cross_reference WHERE normalized_code=? AND vendor_id <=> ? LIMIT 1',
            [$normalized, $vendorId]
        );
        if (!$crossId) {
            \R::exec(
                'INSERT INTO cross_reference (display_code,normalized_code,slug,vendor_id) VALUES (?,?,?,?)',
                [(string)$row['cross_name'], $normalized, self::slug((string)($row['cross_abbreviated_name'] ?: $row['cross_name'])), $vendorId]
            );
            $crossId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
        }

        $partMap = [1 => 'outer', 2 => 'inner', 4 => 'kit'];
        \R::exec(
            'INSERT IGNORE INTO product_cross
             (cross_id,product_id,relation_type,part_role,product_type,source,source_key)
             VALUES (?,?,?,?,?,?,?)',
            [
                $crossId,
                (int)$row['product_id'],
                (int)$row['equipment_vendor'] === 1 ? 'oem' : 'analog',
                $partMap[(int)$row['tip_cross']] ?? 'unspecified',
                $productType,
                'legacy',
                $sourceKey,
            ]
        );
    }

    public static function syncAllLegacy(): void
    {
        if (!self::hasUnifiedSchema()) return;
        foreach (\R::getCol('SELECT id FROM plagins_cross ORDER BY id') as $id) {
            self::syncLegacyRow((int)$id);
        }
    }

    public static function removeLegacyRelation(string $legacyCrossId): void
    {
        if (!self::hasUnifiedSchema() || $legacyCrossId === '') return;
        \R::exec("DELETE FROM product_cross WHERE source='legacy' AND source_key=?", [$legacyCrossId]);
    }
}
