<?php

require dirname(__DIR__) . '/config/init.php';
require CONF . '/db_bootstrap.php';

use app\services\CrossReferenceService;

$sql = [
    "CREATE TABLE IF NOT EXISTS cross_reference (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        display_code VARCHAR(255) NOT NULL,
        normalized_code VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        vendor_id INT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_cross_reference_code_vendor (normalized_code, vendor_id),
        KEY idx_cross_reference_slug (slug),
        FULLTEXT KEY ft_cross_reference (display_code, normalized_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS product_cross (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        cross_id INT UNSIGNED NOT NULL,
        product_id INT NOT NULL,
        relation_type ENUM('oem','analog','interchange') NOT NULL DEFAULT 'oem',
        part_role ENUM('outer','inner','kit','unspecified') NOT NULL DEFAULT 'unspecified',
        product_type VARCHAR(32) NOT NULL DEFAULT 'generic',
        source VARCHAR(64) NOT NULL DEFAULT 'legacy',
        source_key VARCHAR(255) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_product_cross (cross_id, product_id, relation_type, part_role),
        KEY idx_product_cross_product (product_id, is_active),
        KEY idx_product_cross_type (product_type, is_active),
        CONSTRAINT fk_product_cross_reference FOREIGN KEY (cross_id) REFERENCES cross_reference(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS product_cross_context (
        product_id INT NOT NULL,
        source_key VARCHAR(255) NULL,
        fitment_text MEDIUMTEXT NULL,
        description TEXT NULL,
        source VARCHAR(64) NOT NULL DEFAULT 'wheel_cross_excel',
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (product_id),
        UNIQUE KEY uq_product_cross_context_source (source, source_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($sql as $statement) {
    \R::exec($statement);
}

$legacy = \R::getAll('SELECT * FROM plagins_cross ORDER BY id');
$migrated = 0;

\R::begin();
try {
    foreach ($legacy as $row) {
        $normalized = CrossReferenceService::normalizeCode((string)$row['cross_name']);
        if ($normalized === '') {
            continue;
        }
        $product = \R::getRow('SELECT id, category_id FROM product WHERE id = ? LIMIT 1', [(int)$row['product_id']]);
        if (!$product) {
            continue;
        }
        $productType = CrossReferenceService::productType($product);
        if ($productType === 'wheel' && \R::getCell(
            "SELECT pc.id FROM product_cross pc JOIN cross_reference cr ON cr.id=pc.cross_id
             WHERE pc.product_id=? AND pc.source='wheel_cross_excel' AND cr.normalized_code=? LIMIT 1",
            [(int)$row['product_id'], $normalized]
        )) {
            continue;
        }
        $slug = CrossReferenceService::slug((string)($row['cross_abbreviated_name'] ?: $row['cross_name']));
        $vendorId = (int)$row['vendor_id'] ?: null;
        $crossId = \R::getCell(
            'SELECT id FROM cross_reference WHERE normalized_code = ? AND vendor_id <=> ? LIMIT 1',
            [$normalized, $vendorId]
        );
        if (!$crossId) {
            \R::exec(
                'INSERT INTO cross_reference (display_code, normalized_code, slug, vendor_id) VALUES (?, ?, ?, ?)',
                [(string)$row['cross_name'], $normalized, $slug, $vendorId]
            );
            $crossId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
        }

        $relation = (int)$row['equipment_vendor'] === 1 ? 'oem' : 'analog';
        $partMap = [1 => 'outer', 2 => 'inner', 4 => 'kit'];
        $partRole = $partMap[(int)$row['tip_cross']] ?? 'unspecified';
        \R::exec(
            'INSERT IGNORE INTO product_cross (cross_id, product_id, relation_type, part_role, product_type, source, source_key) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [(int)$crossId, (int)$row['product_id'], $relation, $partRole, $productType, 'legacy', (string)$row['cross_id']]
        );
        $migrated++;
    }
    \R::commit();
} catch (\Throwable $e) {
    \R::rollback();
    throw $e;
}

echo "Unified cross schema installed. Legacy rows processed: {$migrated}\n";
