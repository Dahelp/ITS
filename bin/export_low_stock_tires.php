<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
$root = dirname(__DIR__);
$_SERVER += ['HTTP_HOST' => 'its-center.ru', 'SERVER_NAME' => 'its-center.ru', 'HTTPS' => 'on', 'REQUEST_URI' => '/admin/report', 'REMOTE_ADDR' => '127.0.0.1', 'DOCUMENT_ROOT' => $root . '/public'];
require $root . '/config/init.php';
require LIBS . '/functions.php';
require CONF . '/db_bootstrap.php';

$rows = \R::getAll(<<<'SQL'
    SELECT
        CAST(p.article AS CHAR) AS article,
        p.name,
        GREATEST(0, COALESCE(p.quantity, p.rest, 0) - COALESCE(p.reserve, 0)) AS free_stock,
        c.name AS category
    FROM product p
    JOIN category c ON c.id = p.category_id
    WHERE p.hide = 'show'
      AND GREATEST(0, COALESCE(p.quantity, p.rest, 0) - COALESCE(p.reserve, 0)) IN (1, 2)
      AND LOWER(c.name) LIKE '%шин%'
      AND NOT EXISTS (
          SELECT 1
          FROM avito_ad aa
          WHERE aa.complete_id IS NULL
            AND CONVERT(TRIM(COALESCE(aa.article, '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci
                = CONVERT(TRIM(COALESCE(CAST(p.article AS CHAR), '')) USING utf8mb4) COLLATE utf8mb4_unicode_ci
            AND aa.deletion_requested_at IS NULL
      )
    ORDER BY free_stock ASC, p.name ASC, p.article ASC
SQL);

$out = fopen('php://output', 'wb');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Артикул', 'Наименование товара', 'Наличие', 'Категория'], ';');
foreach ($rows as $row) {
    fputcsv($out, [$row['article'], $row['name'], (int)$row['free_stock'], $row['category']], ';');
}
fclose($out);
