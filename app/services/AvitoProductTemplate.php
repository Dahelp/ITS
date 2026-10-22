<?php
namespace app\services;

/** Rules shared by draft generation, the editor and readiness checks. */
final class AvitoProductTemplate
{
    // Existing feed limit; do not raise it on the basis of a UI example (65/90).
    public const TITLE_LIMIT = 50;

    public static function ensureTitleStorage(): void
    {
        $column = \R::getRow("SHOW COLUMNS FROM avito_ad LIKE 'title'");
        if ($column && preg_match('~^varchar\((\d+)\)$~i', (string)$column['Type'], $match) && (int)$match[1] < 255) {
            \R::exec('ALTER TABLE avito_ad MODIFY title VARCHAR(255) NULL DEFAULT NULL');
        }
    }

    public static function title(array $product): string
    {
        return trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags((string)($product['name'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    public static function titleError(string $title): string
    {
        $length = mb_strlen($title, 'UTF-8');
        return $length > self::TITLE_LIMIT
            ? 'Название объявления: '.$length.'/'.self::TITLE_LIMIT.' символов. Сократите название — автоматически оно не обрезается.'
            : '';
    }

    public static function productType(array $product): string
    {
        // Use the actual category hierarchy before examining the product name.
        // Description/attributes can mention other products (e.g. a tyre's rim).
        $category = trim((string)($product['category_name'] ?? ''));
        $id = (int)($product['category_id'] ?? 0);
        $visited = [];
        while ($id > 0 && !isset($visited[$id])) {
            $visited[$id] = true;
            $row = \R::getRow('SELECT name,parent_id FROM category WHERE id=?', [$id]);
            if (!$row) break;
            $category .= ' '.(string)$row['name'];
            $id = (int)$row['parent_id'];
        }
        $type = self::classify($category);
        if ($type !== null) return $type;
        return self::classify((string)($product['name'] ?? '')) ?? 'Шины для грузовиков и спецтехники';
    }

    private static function classify(string $text): ?string
    {
        $text = mb_strtolower($text, 'UTF-8');
        if (preg_match('~фильтр~u', $text)) return 'Фильтры и фильтроэлементы';
        if (preg_match('~мотошин~u', $text)) return 'Мотошины';
        if (preg_match('~(?:шины?|мотошин).*(?:квадроцикл|atv)|(?:квадроцикл|atv).*шин~u', $text)) return 'Мотошины';
        if (preg_match('~^(?:кол[её]сный\s+)?диск(?:\s|$)~u', trim($text))) return 'Диски';
        if (preg_match('~(?<![\p{L}\p{N}])шин[аы](?![\p{L}\p{N}])~u', $text)) return 'Шины для грузовиков и спецтехники';
        if (preg_match('~(?<![\p{L}\p{N}])диск[\p{L}]*~u', $text)) return 'Диски';
        if (preg_match('~радиатор|помпа|термостат|охлажден~u', $text)) return 'Система охлаждения ДВС';
        if (preg_match('~маслян|масляный насос|смазк~u', $text)) return 'Система смазки';
        if (preg_match('~глушител|выхлоп|выпускн~u', $text)) return 'Система выпуска';
        if (preg_match('~топлив|форсунк|тнвд|бензонасос~u', $text)) return 'Топливная система';
        if (preg_match('~сидень|кресл|зеркал|стеклоподъем|салон~u', $text)) return 'Детали салона';
        return null;
    }
}
