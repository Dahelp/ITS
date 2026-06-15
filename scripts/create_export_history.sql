CREATE TABLE IF NOT EXISTS `export_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `format` VARCHAR(64) NOT NULL,
  `format_title` VARCHAR(255) NOT NULL DEFAULT '',
  `file_ext` VARCHAR(16) NOT NULL DEFAULT '',
  `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
  `products_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `articles` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_export_history_created_at` (`created_at`),
  KEY `idx_export_history_format` (`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
