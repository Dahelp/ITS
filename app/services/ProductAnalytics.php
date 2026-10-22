<?php
namespace app\services;

/** One definition of every counter and of its matching product-table filter. */
final class ProductAnalytics
{
    public static function labels(): array
    {
        return [
            'all'=>'Все товары', 'active'=>'Активные на сайте', 'stock'=>'В наличии', 'out_of_stock'=>'Без свободного остатка',
            'avito_linked'=>'Есть объявление Авито', 'avito_missing'=>'Нет объявления Авито',
            'avito_missing_stock'=>'В наличии без объявления', 'avito_prepare'=>'Активные с ценой и остатком без объявления',
            'avito_published'=>'Опубликованы на Авито',
            'seo_complete'=>'Собственное SEO заполнено', 'seo_incomplete'=>'Собственное SEO неполное',
            'meta_uncovered'=>'Нет Title или Description даже с учётом InSEO',
            'title_missing'=>'Нет собственного Title', 'description_missing'=>'Нет собственного Description',
            'image_missing'=>'Нет основного фото', 'title_duplicate'=>'Повторяющийся собственный Title',
            'related_missing'=>'Нет связанных товаров', 'similar_missing'=>'Нет похожих товаров',
            'content_missing'=>'Нет ссылок из материалов', 'incoming_missing'=>'Нет входящих ссылок',
            'isolated'=>'Полностью без перелинковки', 'linked'=>'Есть перелинковка',
        ];
    }

    public static function normalize($value): string
    {
        return is_string($value) && isset(self::labels()[$value]) ? $value : 'all';
    }

    public static function predicates(string $alias = 'a'): array
    {
        $a = $alias.'.';
        return [
            'all'=>'1=1', 'active'=>"{$a}hide = 'show'", 'stock'=>"{$a}free_stock > 0", 'out_of_stock'=>"{$a}free_stock = 0",
            'avito_linked'=>"{$a}has_avito = 1", 'avito_missing'=>"{$a}has_avito = 0",
            'avito_missing_stock'=>"{$a}has_avito = 0 AND {$a}free_stock > 0",
            'avito_prepare'=>"{$a}has_avito = 0 AND {$a}free_stock > 0 AND {$a}hide = 'show' AND {$a}price > 0",
            'avito_published'=>"{$a}avito_published = 1",
            'seo_complete'=>"{$a}seo_score = 100", 'seo_incomplete'=>"{$a}seo_score < 100",
            'meta_uncovered'=>"{$a}meta_uncovered = 1",
            'title_missing'=>"TRIM(COALESCE({$a}title,'')) = ''", 'description_missing'=>"TRIM(COALESCE({$a}description,'')) = ''",
            'image_missing'=>"{$a}has_image = 0", 'title_duplicate'=>"{$a}duplicate_title = 1",
            'related_missing'=>"{$a}related_count = 0", 'similar_missing'=>"{$a}similar_count = 0",
            'content_missing'=>"{$a}content_count = 0", 'incoming_missing'=>"{$a}incoming_count = 0",
            'isolated'=>"{$a}related_count + {$a}similar_count + {$a}incoming_count = 0",
            'linked'=>"{$a}related_count + {$a}similar_count + {$a}incoming_count > 0",
        ];
    }

    public static function where($filter, string $alias = 'a'): string
    {
        return self::predicates($alias)[self::normalize($filter)];
    }

    public static function query(int $category = 0, string $badge = ''): string
    {
        $scope = [];
        if ($category > 0) $scope[] = 'a.category_id = '.(int)$category;
        if (in_array($badge,['new_product','hit','sale'],true)) $scope[] = "a.$badge = '1'";
        $where = $scope ? ' WHERE '.implode(' AND ',$scope) : '';
        $nonempty = static fn($field) => "TRIM(COALESCE(a.$field,'')) != ''";
        $template = static fn($field) => "TRIM(COALESCE(template.$field,'')) != ''";
        $image = "TRIM(COALESCE(a.img,'')) NOT IN ('','no_image.jpg','noimage.png','no-image.jpg')";
        $score = '(CASE WHEN '.$nonempty('title').' THEN 20 ELSE 0 END + CASE WHEN '.$nonempty('description').' THEN 20 ELSE 0 END + CASE WHEN '.$nonempty('keywords').' THEN 20 ELSE 0 END + CASE WHEN '.$nonempty('content').' THEN 20 ELSE 0 END + CASE WHEN '.$image.' THEN 20 ELSE 0 END)';
        $templateScores = [];
        foreach (['title','description','keywords','content','name'] as $field) $templateScores[] = '(CASE WHEN '.$template($field).' THEN 20 ELSE 0 END)';
        $related = 'COALESCE(rc.total,0)';
        $similar = 'COALESCE(sc.total,0)';
        $content = 'COALESCE(cc.total,0)';
        $incoming = 'COALESCE(ri.total,0) + COALESCE(si.total,0) + COALESCE(cc.total,0)';
        return "SELECT a.*,
            CASE WHEN COALESCE(a.quantity,a.rest,0) - COALESCE(a.reserve,0) > 0 THEN COALESCE(a.quantity,a.rest,0) - COALESCE(a.reserve,0) ELSE 0 END AS free_stock,
            CASE WHEN ads.total > 0 THEN 1 ELSE 0 END AS has_avito,
            COALESCE(ads.published,0) AS avito_published,
            ads.ad_id AS avito_ad_id,
            $score AS seo_score, (".implode(' + ',$templateScores).") AS inseo_score,
            CASE WHEN $image THEN 1 ELSE 0 END AS has_image,
            CASE WHEN (NOT (".$nonempty('title').") AND NOT (".$template('title').")) OR (NOT (".$nonempty('description').") AND NOT (".$template('description').")) THEN 1 ELSE 0 END AS meta_uncovered,
            CASE WHEN dup.total IS NOT NULL THEN 1 ELSE 0 END AS duplicate_title,
            $related AS related_count, $similar AS similar_count, $content AS content_count, ($incoming) AS incoming_count
            FROM product a
            LEFT JOIN (SELECT article, COUNT(*) AS total, COALESCE(MAX(CASE WHEN deletion_requested_at IS NULL AND LOWER(COALESCE(avito_publication_status,'')) IN ('active','published') THEN id END),MAX(id)) AS ad_id, MAX(CASE WHEN deletion_requested_at IS NULL AND LOWER(COALESCE(avito_publication_status,'')) IN ('active','published') THEN 1 ELSE 0 END) AS published FROM avito_ad WHERE complete_id IS NULL AND TRIM(COALESCE(article,'')) != '' GROUP BY article) ads ON ads.article = a.article
            LEFT JOIN (SELECT MIN(id) AS template_id,category_id FROM plagins_inseo WHERE tip = 'product' AND hide = 'show' GROUP BY category_id) tm ON tm.category_id = a.category_id
            LEFT JOIN plagins_inseo template ON template.id = tm.template_id
            LEFT JOIN (SELECT r.product_id,COUNT(DISTINCT r.related_id) AS total FROM related_product r JOIN product target ON target.id = r.related_id WHERE r.product_id != r.related_id GROUP BY r.product_id) rc ON rc.product_id = a.id
            LEFT JOIN (SELECT s.product_id,COUNT(DISTINCT s.similar_id) AS total FROM similar_product s JOIN product target ON target.id = s.similar_id WHERE s.product_id != s.similar_id GROUP BY s.product_id) sc ON sc.product_id = a.id
            LEFT JOIN (SELECT cr.related_id,COUNT(DISTINCT cr.content_id) AS total FROM content_related cr JOIN contents source ON source.id = cr.content_id GROUP BY cr.related_id) cc ON cc.related_id = a.id
            LEFT JOIN (SELECT r.related_id,COUNT(DISTINCT r.product_id) AS total FROM related_product r JOIN product source ON source.id = r.product_id WHERE r.product_id != r.related_id GROUP BY r.related_id) ri ON ri.related_id = a.id
            LEFT JOIN (SELECT s.similar_id,COUNT(DISTINCT s.product_id) AS total FROM similar_product s JOIN product source ON source.id = s.product_id WHERE s.product_id != s.similar_id GROUP BY s.similar_id) si ON si.similar_id = a.id
            LEFT JOIN (SELECT TRIM(title) AS title_key,COUNT(*) AS total FROM product WHERE TRIM(COALESCE(title,'')) != '' GROUP BY TRIM(title) HAVING COUNT(*) > 1) dup ON dup.title_key = TRIM(a.title)$where";
    }

    public static function summary(int $category = 0, string $badge = ''): array
    {
        $fields = [];
        foreach (self::predicates('q') as $key=>$condition) $fields[] = "COALESCE(SUM(CASE WHEN $condition THEN 1 ELSE 0 END),0) AS `$key`";
        return array_map('intval',\R::getRow('SELECT '.implode(',',$fields).' FROM ('.self::query($category,$badge).') q'));
    }
}
