<?php
namespace app\services;

/** Restores the relation between legacy complete-set ads and catalogue sets. */
class CompleteAvitoLinker
{
    public static function reconcile(): array
    {
        $stats = ['linked' => 0, 'already_linked' => 0, 'unmatched' => 0, 'ambiguous' => 0, 'methods' => []];
        if (!in_array('avito_ad', \R::inspect(), true) || !in_array('plagins_complete', \R::inspect(), true)) {
            return $stats;
        }
        if (!\R::getCell("SHOW COLUMNS FROM avito_ad LIKE 'complete_id'")) {
            \R::exec('ALTER TABLE avito_ad ADD complete_id INT NULL DEFAULT NULL, ADD INDEX idx_avito_complete (complete_id)');
        }

        $completes = \R::getAll('SELECT id, name, alias, img FROM plagins_complete');
        $indexes = self::indexes($completes);
        $where = \R::getCell("SHOW COLUMNS FROM avito_ad LIKE 'deletion_requested_at'")
            ? ' WHERE deletion_requested_at IS NULL' : '';
        $ads = \R::getAll("SELECT id, complete_id, ad_external_id, article, title, description, images_json FROM avito_ad{$where}");

        foreach ($ads as $ad) {
            if (!empty($ad['complete_id'])) {
                $stats['already_linked']++;
                continue;
            }
            $match = self::match($ad, $indexes);
            if (!$match['id']) {
                $stats[$match['ambiguous'] ? 'ambiguous' : 'unmatched']++;
                continue;
            }
            \R::exec('UPDATE avito_ad SET complete_id = ? WHERE id = ? AND complete_id IS NULL', [$match['id'], $ad['id']]);
            $stats['linked']++;
            $method = $match['method'];
            $stats['methods'][$method] = ($stats['methods'][$method] ?? 0) + 1;
        }
        return $stats;
    }

    public static function indexes(array $completes): array
    {
        $result = ['ids' => [], 'names' => [], 'aliases' => [], 'images' => []];
        foreach ($completes as $complete) {
            $id = (int)($complete['id'] ?? 0);
            if ($id <= 0) continue;
            $result['ids'][$id] = $id;
            self::add($result['names'], self::normalise((string)($complete['name'] ?? '')), $id);
            self::add($result['aliases'], mb_strtolower(trim((string)($complete['alias'] ?? '')), 'UTF-8'), $id);
            self::add($result['images'], mb_strtolower(basename((string)($complete['img'] ?? '')), 'UTF-8'), $id);
        }
        return $result;
    }

    public static function match(array $ad, array $indexes): array
    {
        foreach (['ad_external_id', 'article'] as $field) {
            $value = trim((string)($ad[$field] ?? ''));
            if (preg_match('~^(?:its[-_])?complete[-_:]?(\d+)$~i', $value, $m)) {
                $id = (int)$m[1];
                if (isset($indexes['ids'][$id])) return ['id' => $id, 'method' => 'external_id', 'ambiguous' => false];
            }
        }

        $haystack = implode("\n", [(string)($ad['description'] ?? ''), (string)($ad['images_json'] ?? '')]);
        if (preg_match_all('~(?:https?://[^\s"\']+)?/complete/([a-z0-9_-]+)~iu', $haystack, $matches)) {
            $found = self::candidates($indexes['aliases'], array_map(fn($v) => mb_strtolower($v, 'UTF-8'), $matches[1]));
            if (count($found) === 1) return ['id' => $found[0], 'method' => 'complete_url', 'ambiguous' => false];
            if (count($found) > 1) return ['id' => 0, 'method' => '', 'ambiguous' => true];
        }

        $imageNames = [];
        if (preg_match_all('~/images/complete/(?:baseimg|mini|gallery)/([^?"\']+)~iu', $haystack, $matches)) {
            foreach ($matches[1] as $image) $imageNames[] = mb_strtolower(basename(rawurldecode($image)), 'UTF-8');
        }
        $found = self::candidates($indexes['images'], $imageNames);
        if (count($found) === 1) return ['id' => $found[0], 'method' => 'complete_image', 'ambiguous' => false];
        if (count($found) > 1) return ['id' => 0, 'method' => '', 'ambiguous' => true];

        $title = (string)($ad['title'] ?? '');
        if (preg_match('~\bкомплект\b~iu', $title)) {
            $name = self::normalise($title);
            $found = array_values(array_unique($indexes['names'][$name] ?? []));
            if (count($found) === 1) return ['id' => $found[0], 'method' => 'exact_name', 'ambiguous' => false];
            if (count($found) > 1) return ['id' => 0, 'method' => '', 'ambiguous' => true];
        }
        return ['id' => 0, 'method' => '', 'ambiguous' => false];
    }

    private static function normalise(string $value): string
    {
        $value = mb_strtolower(str_replace(['ё', '×'], ['е', 'x'], strip_tags($value)), 'UTF-8');
        $value = preg_replace('~[^\pL\pN]+~u', ' ', $value);
        return trim(preg_replace('~\s+~u', ' ', $value));
    }

    private static function add(array &$index, string $key, int $id): void
    {
        if ($key === '') return;
        $index[$key][] = $id;
    }

    private static function candidates(array $index, array $keys): array
    {
        $ids = [];
        foreach ($keys as $key) foreach ($index[$key] ?? [] as $id) $ids[] = (int)$id;
        return array_values(array_unique($ids));
    }
}
