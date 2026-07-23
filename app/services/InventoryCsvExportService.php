<?php

namespace app\services;

final class InventoryCsvExportService
{
    private const HEADER = [
        'НоменклатураКод', 'ЦенаРозн', 'ЦенаОпт', 'Свободно', 'Резерв',
        'Климовск', 'КлимовскР', 'Краснодар', 'КраснодарР', 'Воронеж',
        'ВоронежР', 'СанктПетербург', 'СанктПетербургР', 'Екатеринбург',
        'ЕкатеринбургР', 'Ожидается', 'Дата', '',
    ];

    private const EXPORTS = [
        'filtrs.csv' => [10, 11, 12, 13, 14, 15, 16, 17],
        'kamery.csv' => [31, 32, 33],
        'tovars.csv' => [2, 9, 18, 19, 20, 21, 22, 23, 24, 26, 27, 28, 29, 30, 35],
    ];

    private $client;

    public function __construct(?InventoryApiClient $client = null)
    {
        $this->client = $client ?: new InventoryApiClient();
    }

    public function export(array $outputDirectories): array
    {
        $directories = [];
        foreach ($outputDirectories as $directory) {
            $directory = rtrim(trim((string)$directory), '/\\');
            if ($directory !== '') $directories[$directory] = true;
        }
        if (!$directories) throw new \InvalidArgumentException('At least one output directory is required');

        $stats = ['generated_at' => date('c'), 'directories' => array_keys($directories), 'files' => []];
        foreach (self::EXPORTS as $fileName => $categories) {
            $rows = $this->loadRows($categories);
            if (!$rows) throw new \RuntimeException("Refusing to publish empty inventory export: {$fileName}");
            foreach (array_keys($directories) as $directory) $this->writeAtomically($directory, $fileName, $rows);
            $stats['files'][$fileName] = count($rows);
        }
        return $stats;
    }

    private function loadRows(array $categories): array
    {
        $in = implode(',', array_map('intval', $categories));
        $products = \R::getAll(
            "SELECT id, article, price, opt_price, quantity, rest, reserve
             FROM product WHERE category_id IN ({$in}) AND article IS NOT NULL AND article <> ''
             ORDER BY article"
        );
        $modifications = \R::getAll(
            "SELECT m.article, m.price, m.opt_price, m.quantity, 0 AS rest, 0 AS reserve
             FROM modification m
             INNER JOIN product p ON p.id = m.product_id
             WHERE p.category_id IN ({$in}) AND m.article IS NOT NULL AND m.article <> ''
             ORDER BY m.article"
        );

        $byArticle = [];
        foreach (array_merge($products, $modifications) as $item) {
            $article = InventoryApiClient::normalizeArticle((string)$item['article']);
            if ($article === '') continue;
            $byArticle[$article] = $this->makeRow($article, $item);
        }
        ksort($byArticle, SORT_NATURAL);
        return array_values($byArticle);
    }

    private function makeRow(string $article, array $item): array
    {
        $quantity = max(0, (int)$item['quantity']);
        $reserve = max(0, (int)($item['reserve'] ?? 0));
        $cached = $this->client->getLastSuccessfulData($article);
        $warehouseKeys = ['klimovsk', 'klimovsk_r', 'krasnodar', 'krasnodar_r', 'voronezh', 'voronezh_r', 'peter', 'peter_r', 'ekat', 'ekat_r'];
        $warehouses = array_fill(0, count($warehouseKeys), 0);

        if (is_array($cached) && (int)($cached['rest'] ?? -1) === $quantity) {
            foreach ($warehouseKeys as $index => $key) $warehouses[$index] = max(0, (int)($cached[$key] ?? 0));
            $reserve = max(0, (int)($cached['reserve'] ?? $reserve));
        } else {
            $warehouses[0] = $quantity;
        }

        $code = ctype_digit($article) ? str_pad($article, 11, '0', STR_PAD_LEFT) : $article;
        return array_merge([
            $code,
            $this->formatNumber((float)$item['price']),
            $this->formatNumber((float)$item['opt_price']),
            $quantity,
            $reserve,
        ], $warehouses, [
            is_array($cached) ? ($cached['wait'] ?? '') : '',
            is_array($cached) ? ($cached['wait_date'] ?? '') : '',
            '',
        ]);
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format(max(0, $value), 2, '.', ''), '0'), '.');
    }

    private function writeAtomically(string $directory, string $fileName, array $rows): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException("Cannot create export directory: {$directory}");
        }
        if (!is_writable($directory)) throw new \RuntimeException("Export directory is not writable: {$directory}");

        $target = $directory . DIRECTORY_SEPARATOR . $fileName;
        $temp = $target . '.' . getmypid() . '.tmp';
        $handle = @fopen($temp, 'wb');
        if (!$handle) throw new \RuntimeException("Cannot open temporary export: {$temp}");
        try {
            fputcsv($handle, $this->toWindows1251(self::HEADER), ';', '"', '\\', "\r\n");
            foreach ($rows as $row) fputcsv($handle, $this->toWindows1251($row), ';', '"', '\\', "\r\n");
            if (!fflush($handle)) throw new \RuntimeException("Cannot flush export: {$temp}");
        } finally {
            fclose($handle);
        }
        if (@filesize($temp) < 100 || !@rename($temp, $target)) {
            @unlink($temp);
            throw new \RuntimeException("Cannot publish export atomically: {$target}");
        }
        @chmod($target, 0644);
    }

    private function toWindows1251(array $row): array
    {
        return array_map(static function ($value) {
            return mb_convert_encoding((string)$value, 'Windows-1251', 'UTF-8');
        }, $row);
    }
}
