<?php

$db = require dirname(__DIR__) . '/config/config_db.php';
$pdo = new PDO($db['dsn'], $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$queries = [
    "CREATE TABLE IF NOT EXISTS certificates (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        document_type ENUM('certificate','declaration') NOT NULL DEFAULT 'declaration',
        number VARCHAR(190) NOT NULL,
        name VARCHAR(255) NOT NULL DEFAULT '',
        date_start DATE NULL,
        date_end DATE NULL,
        registry_url VARCHAR(1000) NOT NULL,
        file_url VARCHAR(1000) NOT NULL DEFAULT '',
        issuer VARCHAR(255) NOT NULL DEFAULT '',
        applicant VARCHAR(255) NOT NULL DEFAULT '',
        regulations TEXT NULL,
        status ENUM('active','suspended','expired','archived') NOT NULL DEFAULT 'active',
        note TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id), UNIQUE KEY uq_certificate_number (number),
        KEY idx_certificate_status_dates (status, date_end)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS certificate_assignments (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        certificate_id INT UNSIGNED NOT NULL,
        target_type ENUM('product','category','brand','category_brand') NOT NULL,
        product_id INT UNSIGNED NULL,
        category_id INT UNSIGNED NULL,
        brand_id INT UNSIGNED NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_certificate_target (certificate_id, target_type, product_id, category_id, brand_id),
        KEY idx_assignment_product (target_type, product_id),
        KEY idx_assignment_category_brand (target_type, category_id, brand_id),
        CONSTRAINT fk_assignment_certificate FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($queries as $query) {
    $pdo->exec($query);
}

$hasColumn = static function (PDO $pdo, string $column): bool {
    $query = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product' AND COLUMN_NAME = ?"
    );
    $query->execute([$column]);
    return (bool)$query->fetchColumn();
};

if (!$hasColumn($pdo, 'certification_required')) {
    $pdo->exec('ALTER TABLE product ADD certification_required TINYINT(1) NULL DEFAULT NULL AFTER note');
}
if (!$hasColumn($pdo, 'tn_ved_code')) {
    $pdo->exec("ALTER TABLE product ADD tn_ved_code VARCHAR(32) NOT NULL DEFAULT '' AFTER certification_required");
}

echo "Certificates schema installed.\n";
