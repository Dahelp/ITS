<?php

namespace app\services;

/** Resolves compliance documents assigned directly or inherited by a product. */
class ProductCertificationService
{
    private static ?bool $installed = null;
    private static ?bool $productFieldsAvailable = null;

    public static function isInstalled(): bool
    {
        if (self::$installed === null) {
            self::$installed = (bool)\R::getCell("SHOW TABLES LIKE 'certificates'")
                && (bool)\R::getCell("SHOW TABLES LIKE 'certificate_assignments'");
        }
        return self::$installed;
    }

    public static function hasProductFields(): bool
    {
        if (self::$productFieldsAvailable === null) {
            try {
                self::$productFieldsAvailable = (bool)\R::getCell("SHOW COLUMNS FROM product LIKE 'certification_required'")
                    && (bool)\R::getCell("SHOW COLUMNS FROM product LIKE 'tn_ved_code'");
            } catch (\Throwable $e) {
                self::$productFieldsAvailable = false;
            }
        }
        return self::$productFieldsAvailable;
    }

    public static function forProduct($product, bool $activeOnly = true): array
    {
        if (!self::isInstalled() || !$product || empty($product->id)) {
            return ['documents' => [], 'source' => null];
        }

        $levels = [
            ['ca.target_type = ? AND ca.product_id = ?', ['product', (int)$product->id], 'product'],
            ['ca.target_type = ? AND ca.category_id = ? AND ca.brand_id = ?', ['category_brand', (int)$product->category_id, (int)$product->brand_id], 'category_brand'],
            ['ca.target_type = ? AND ca.brand_id = ?', ['brand', (int)$product->brand_id], 'brand'],
            ['ca.target_type = ? AND ca.category_id = ?', ['category', (int)$product->category_id], 'category'],
        ];

        foreach ($levels as [$where, $params, $source]) {
            $statusSql = $activeOnly
                ? " AND c.status = 'active' AND (c.date_start IS NULL OR c.date_start <= CURDATE()) AND (c.date_end IS NULL OR c.date_end >= CURDATE())"
                : '';
            $rows = \R::getAll(
                "SELECT DISTINCT c.* FROM certificate_assignments ca
                 JOIN certificates c ON c.id = ca.certificate_id
                 WHERE {$where}{$statusSql}
                 ORDER BY c.date_end IS NULL DESC, c.date_end DESC, c.id DESC",
                $params
            );
            if ($rows) {
                return ['documents' => $rows, 'source' => $source];
            }
        }
        return ['documents' => [], 'source' => null];
    }

    public static function dashboardSummary(): array
    {
        $total = (int)\R::count('product');
        $required = 0;
        $notRequired = 0;
        $unknown = $total;
        $covered = 0;

        try {
            $required = (int)\R::getCell('SELECT COUNT(*) FROM product WHERE certification_required = 1');
            $notRequired = (int)\R::getCell('SELECT COUNT(*) FROM product WHERE certification_required = 0');
            $unknown = max(0, $total - $required - $notRequired);
            if (self::isInstalled()) {
                $covered = (int)\R::getCell(
                    "SELECT COUNT(*) FROM product p
                     WHERE p.certification_required = 1
                       AND EXISTS (
                         SELECT 1 FROM certificate_assignments ca
                         JOIN certificates c ON c.id = ca.certificate_id
                         WHERE c.status = 'active'
                           AND (c.date_start IS NULL OR c.date_start <= CURDATE())
                           AND (c.date_end IS NULL OR c.date_end >= CURDATE())
                           AND (
                             (ca.target_type = 'product' AND ca.product_id = p.id)
                             OR (ca.target_type = 'category_brand' AND ca.category_id = p.category_id AND ca.brand_id = p.brand_id)
                             OR (ca.target_type = 'brand' AND ca.brand_id = p.brand_id)
                             OR (ca.target_type = 'category' AND ca.category_id = p.category_id)
                           )
                       )"
                );
            }
        } catch (\Throwable $e) {
            // The installer has not been run yet; dashboard must remain available.
        }

        return [
            'total' => $total,
            'required' => $required,
            'not_required' => $notRequired,
            'unknown' => $unknown,
            'covered' => $covered,
            'filled_percent' => $total > 0 ? round((($required + $notRequired) / $total) * 100, 1) : 0,
            'covered_percent' => $required > 0 ? round(($covered / $required) * 100, 1) : 0,
        ];
    }
}
