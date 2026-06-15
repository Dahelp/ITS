<?php
namespace app\helpers;

use ishop\App;

/**
 * Хелпер генерации JSON-LD для карточки товара и крошек
 */
class SchemaHelper
{
    /** Безопасное экранирование для JSON */
    public static function esc(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }

    /** Дата в ISO (YYYY-MM-DD); если пусто — сегодняшняя */
    public static function isoDate(?string $s): string {
        if (!$s) return date('Y-m-d');
        // вытаскиваем только цифры, дефис, двоеточие, пробуем отрезать время
        $s = preg_replace('~[^0-9\-:]~', '', $s);
        $parts = explode(' ', trim($s));
        $date  = $parts[0] ?? $s;
        // примитивная проверка
        if (!preg_match('~^\d{4}-\d{2}-\d{2}$~', $date)) return date('Y-m-d');
        return $date;
    }

    /** Абсолютный URL (если пришёл относительный) */
    public static function absUrl(string $path): string {
        $base = rtrim((string)App::$app->getProperty('base_url') ?: (defined('PATH') ? PATH : ''), '/');
        if (!$base) return $path; // как есть
        if (preg_match('~^https?://~i', $path)) return $path;
        if ($path && $path[0] !== '/') $path = '/'.$path;
        return $base . $path;
    }

    /** Собирает массив картинок товара */
    public static function productImages($product, array $gallery = []): array {
        $imgs = [];
        if (!empty($product->img)) {
            $imgs[] = self::absUrl("/images/product/baseimg/{$product->img}");
        }
        if ($gallery) {
            foreach ($gallery as $g) {
                $gimg = is_object($g) ? ($g->img ?? null) : ($g['img'] ?? null);
                if ($gimg) $imgs[] = self::absUrl("/images/product/gallery/{$gimg}");
            }
        }
        // убираем дубли
        return array_values(array_unique($imgs));
    }

    /**
     * additionalProperty из таблицы характеристик
     * Ожидает, что структура такая же, как у тебя в шаблоне:
     *  attribute JOIN product_attribute ON product_attribute.attribute_id = attribute.id
     */
    public static function buildAdditionalProps(int $productId): array {
        $rows = \R::getAll("
            SELECT a.attribute_name AS name, pa.attribute_text AS val
            FROM attribute a
            JOIN product_attribute pa ON pa.attribute_id = a.id
            WHERE pa.product_id = ?
            ORDER BY a.attribute_position
        ", [$productId]);

        $props = [];
        foreach ($rows as $r) {
            $name = trim((string)$r['name']);
            $val  = trim((string)$r['val']);
            if ($name === '' || $val === '') continue;
            $props[] = [
                '@type' => 'PropertyValue',
                'name'  => $name,
                'value' => $val,
            ];
        }
        return $props;
    }

    /** Один Offer (без модификаций) */
    public static function buildSingleOffer($product, array $curr, float $price, string $productUrl): array {
        $priceStr   = number_format($price * (float)$curr['value'], 2, '.', '');
        $avail      = ((int)$product->quantity > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
        $validUntil = self::isoDate($product->data_edit_price ?? null);

        return [
            '@type'           => 'Offer',
            'url'             => $productUrl,
            'priceCurrency'   => 'RUB',
            'price'           => $priceStr,
            'priceValidUntil' => $validUntil,
            'availability'    => $avail,
            'itemCondition'   => 'https://schema.org/NewCondition',
        ];
    }

    /**
     * AggregateOffer (если есть модификации с разными ценами)
     * $mods — массив объектов/массивов с полями price, quantity
     */
    public static function buildAggregateOffer($product, array $mods, array $curr): ?array {
        $prices = [];
        $inStock = ((int)$product->quantity > 0);

        if ((float)$product->price > 0) {
            $prices[] = (float)$product->price * (float)$curr['value'];
        }
        foreach ($mods as $m) {
            $mp = is_object($m) ? ($m->price ?? null) : ($m['price'] ?? null);
            $mq = is_object($m) ? ($m->quantity ?? null) : ($m['quantity'] ?? null);
            if ($mp !== null && (float)$mp > 0) $prices[] = (float)$mp * (float)$curr['value'];
            if ((int)$mq > 0) $inStock = true;
        }

        // Уникализируем и сортируем
        $prices = array_values(array_unique($prices));
        sort($prices, SORT_NUMERIC);

        // Если единственная цена — пусть будет обычный Offer
        if (count($prices) <= 1) {
            return null;
        }

        return [
            '@type'         => 'AggregateOffer',
            'priceCurrency' => 'RUB',
            'lowPrice'      => number_format($prices[0], 2, '.', ''),
            'highPrice'     => number_format($prices[count($prices)-1], 2, '.', ''),
            'offerCount'    => (string)count($prices),
            'availability'  => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ];
    }

    /**
     * Основная функция: сформировать JSON-LD Product
     * Параметры:
     *  - $product (объект) — обязателен
     *  - $vendor (объект/массив) — для brand
     *  - $gallery (массив) — для image[]
     *  - $mods (массив) — модификации
     *  - $curr (массив) — валюта сайта ['value' => ...]
     *  - $price (float) — текущая «основная» цена, которая у тебя уже рассчитана в шаблоне
     *  - $inseo (м.б. объект) — чтобы взять контент-заглушку
     *  - $ratingValue (float|null) и $reviewCount (int|null) — агрегированный рейтинг
     *  - $addProps (bool) — подтаскивать ли характеристики как additionalProperty
     */
    public static function renderProductJsonLd(
        $product,
        $vendor,
        array $gallery,
        array $mods,
        array $curr,
        float $price,
        $inseo = null,
        ?float $ratingValue = null,
        ?int $reviewCount = null,
        bool $addProps = true,
        ?array $reviewsRaw = null,
        int $maxReviews = 20
    ): string
    {
        // Имя: если в InSEO есть шаблон, прогоняем через seoreplace
        $name = $product->name;
        if ($inseo && !empty($inseo->name)) {
            $name = \ishop\App::seoreplace($inseo->name, $product->id);
        }
        $name = self::htmlToPlain((string)$name, 300);

        // Описание: приоритет контент товара, иначе InSEO + seoreplace
        if (!empty($product->content)) {
            $descSource = (string)$product->content;
        } elseif ($inseo && !empty($inseo->content)) {
            $descSource = \ishop\App::seoreplace($inseo->content, $product->id);
        } else {
            $descSource = '';
        }
        $description = self::htmlToPlain($descSource, 4000);

        $brandName = is_object($vendor) ? ($vendor->name ?? '') : ($vendor['name'] ?? '');

        $images = self::productImages($product, $gallery);
        $productUrl = self::absUrl("/product/{$product->alias}");

        $data = [
            '@context'     => 'https://schema.org',
            '@type'        => 'Product',
            'name'         => $name,
            'sku'          => (string)$product->article,
            'image'        => $images ?: null,
            'description'  => $description ?: null,
            'brand'        => [
                '@type' => 'Brand',
                'name'  => $brandName,
            ],
            'url'         => self::absUrl('/product/'.$product->alias),
            'mpn'         => (string)$product->article, // артикул как производственный номер

        ];

        // Рейтинг (не добавляем блок, если данных нет)
        if ($ratingValue !== null && $reviewCount !== null && $reviewCount > 0) {
            $data['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string)round($ratingValue, 1),
                'reviewCount' => (string)$reviewCount,
                'bestRating'  => '5',
                'worstRating' => '1',
            ];
        }

        // reviews (список Review)
        if ($reviewsRaw && is_array($reviewsRaw)) {
            $rev = self::buildReviews($reviewsRaw, $maxReviews);
            if (!empty($rev)) {
                $data['review'] = $rev;
            }
        }

        // additionalProperty
        if ($addProps) {
            $props = self::buildAdditionalProps((int)$product->id);
            if (!empty($props)) {
                $data['additionalProperty'] = $props;
            }
        }

        // offers
        $agg = self::buildAggregateOffer($product, $mods, $curr);
        if ($agg) {
            $data['offers'] = $agg;
        } else {
            $data['offers'] = self::buildSingleOffer($product, $curr, $price, $productUrl);
        }

        // чистим null
        $data = self::arrayFilterNulls($data);

        return '<script type="application/ld+json">'.
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).
            '</script>';
    }

    /** JSON-LD для хлебных крошек (если есть массив крошек) */
    public static function renderBreadcrumbsJsonLd(array $breadcrumbsArr): ?string
    {
        if (!$breadcrumbsArr) return null;
        $items = [];
        $pos = 1;
        foreach ($breadcrumbsArr as $cr) {
            $name = (string)($cr['name'] ?? '');
            $link = (string)($cr['link'] ?? '');
            if ($name === '') continue;
            $item = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => $name,
            ];
            if ($link) $item['item'] = $link;
            $items[] = $item;
        }
        if (!$items) return null;

        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
        return '<script type="application/ld+json">'.
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).
            '</script>';
    }

    /** Рекурсивно удаляет null-поля из массива */
    private static function arrayFilterNulls($arr) {
        if (!is_array($arr)) return $arr;
        $out = [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $v = self::arrayFilterNulls($v);
                if ($v === [] ) continue;
            }
            if ($v === null) continue;
            $out[$k] = $v;
        }
        return $out;
    }

    /** Собрать массив Review для JSON-LD из БД-строк $review */
    public static function buildReviews(array $reviewRows, int $limit = 20): array
    {
        $out = [];
        $i = 0;
        foreach ($reviewRows as $rw) {
            if ($i++ >= $limit) break;

            $author = trim((string)($rw['uname'] ?? '')) ?: 'Покупатель';
            $body   = self::htmlToPlain((string)($rw['content'] ?? ''), 2000);
            if ($body === '') continue;

            $date   = self::isoDate((string)($rw['date_post'] ?? null));
            $point  = (int)($rw['point'] ?? 0);
            if ($point < 1) $point = 1;
            if ($point > 5) $point = 5;

            $out[] = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name'  => $author,
                ],
                'datePublished' => $date,
                'reviewBody'    => $body,
                'reviewRating'  => [
                    '@type'       => 'Rating',
                    'ratingValue' => (string)$point,
                    'bestRating'  => '5',
                    'worstRating' => '1',
                ],
            ];
        }
        return $out;
    }

    /** HTML → plain text: decode entities, strip tags, collapse spaces, trim, cut */
    public static function htmlToPlain(string $s, int $maxLen = 4000): string {
        // Декодим HTML-сущности (&nbsp;, &ndash; и т.д.)
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Убираем теги
        $s = strip_tags($s);
        // Схлопываем пробелы/переводы строк
        $s = preg_replace('/\s+/u', ' ', $s);
        // Чистим оставшиеся плейсхолдеры в фигурных скобках — на всякий случай
        $s = preg_replace('/\{[^}]+\}/u', '', $s);
        // Обрезаем
        $s = trim(mb_substr($s, 0, $maxLen));
        return $s;
    }

    // Нормализация телефона к E.164 (насколько возможно из RU-номеров)
    public static function phoneE164(string $raw): string {
        $d = preg_replace('/\D+/', '', $raw);
        if ($d === '') return $raw;
        // RU: начинаются на 7 или 8
        if ($d[0] === '8') $d[0] = '7';
        if ($d[0] !== '7') return '+'.$d; // best effort
        return '+'.$d;
    }

    /** PostalAddress из ассоциативного массива */
    public static function buildPostalAddress(array $a): array {
        $addr = [
            '@type'           => 'PostalAddress',
            'postalCode'      => $a['postalCode']      ?? null,
            'addressCountry'  => $a['addressCountry']  ?? 'RU',
            'addressRegion'   => $a['addressRegion']   ?? null, // Московская область
            'addressLocality' => $a['addressLocality'] ?? null, // Подольск / мкр. Климовск
            'streetAddress'   => $a['streetAddress']   ?? null, // ул. Троицкая, д.1Г, стр.1, пом. ...
        ];
        return self::arrayFilterNulls($addr);
    }

    /** OpeningHoursSpecification: принимает массив вида
     * [
     *   ['days' => ['Monday','Tuesday','Wednesday','Thursday','Friday'], 'opens' => '09:00', 'closes' => '18:00'],
     *   ['days' => ['Saturday'], 'opens' => '10:00', 'closes' => '16:00'],
     *   ['days' => ['Sunday'], 'opens' => null, 'closes' => null] // выходной
     * ]
     */
    public static function buildOpeningHoursSpec(array $rows): array {
        $out = [];
        foreach ($rows as $row) {
            $days  = $row['days']  ?? [];
            $opens = $row['opens'] ?? null;
            $closes= $row['closes']?? null;
            // Если закрыто — schema.org допускает пропуск записи (или указать sameAs с isClosed в расширениях),
            // Здесь просто пропустим пустые смены:
            if (!$opens || !$closes) continue;
            $out[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $days,
                'opens'     => $opens,
                'closes'    => $closes,
            ];
        }
        return $out;
    }

    /** Массив ContactPoint из входных контактов
     * Пример $contacts:
     * [
     *   ['type' => 'sales', 'phone' => '+7 (495) 424-98-90', 'email' => 'sales@...', 'areaServed' => 'RU', 'availableLanguage' => ['ru']],
     *   ['type' => 'customer support', 'phone' => '+7 (495) 424-98-90', 'email' => 'info@...', 'areaServed' => 'RU', 'availableLanguage' => ['ru']],
     * ]
     */
    public static function buildContactPoints(array $contacts): array {
        $out = [];
        foreach ($contacts as $c) {
            $item = [
                '@type'             => 'ContactPoint',
                'contactType'       => $c['type'] ?? 'customer support',
                'telephone'         => isset($c['phone']) ? self::phoneE164($c['phone']) : null,
                'email'             => $c['email'] ?? null,
                'areaServed'        => $c['areaServed'] ?? null,
                'availableLanguage' => $c['availableLanguage'] ?? null,
            ];
            $item = self::arrayFilterNulls($item);
            if (!empty($item['telephone']) || !empty($item['email'])) {
                $out[] = $item;
            }
        }
        return $out;
    }

    /** Рендер Organization/LocalBusiness JSON-LD
     * $org ожидает:
     *  - type: 'Organization' (по умолчанию) или 'LocalBusiness'
     *  - name, legalName, taxID, vatID, url, email, phones[], logoUrl, imageUrl
     *  - address: ['postalCode','addressRegion','addressLocality','streetAddress','addressCountry']
     *  - geo: ['lat' => ..., 'lng' => ...] (опционально)
     *  - hasMap: URL на карту (опционально)
     *  - openingHours: массив для buildOpeningHoursSpec (опционально)
     *  - sameAs: массив ссылок на соцсети/карты (опционально)
     *  - contactPoints: массив для buildContactPoints (опционально)
     */
    public static function renderOrganizationJsonLd(array $org): string {
        $type = $org['type'] ?? 'Organization';

        $phones = [];
        foreach (($org['phones'] ?? []) as $ph) {
            $phones[] = self::phoneE164($ph);
        }

        $data = [
            '@context'   => 'https://schema.org',
            '@type'      => $type,
            'name'       => $org['name']       ?? null,
            'legalName'  => $org['legalName']  ?? null,
            'taxID'      => $org['taxID']      ?? null, // ИНН
            'vatID'      => $org['vatID']      ?? null, // RU + ИНН (если хочешь)
            'url'        => $org['url']        ?? null,
            'email'      => $org['email']      ?? null,
            'telephone'  => $phones ?: null,
            'logo'       => !empty($org['logoUrl'])  ? self::absUrl($org['logoUrl'])  : null,
            'image'      => !empty($org['imageUrl']) ? self::absUrl($org['imageUrl']) : null,
            'address'    => !empty($org['address'])  ? self::buildPostalAddress($org['address']) : null,
            'sameAs'     => !empty($org['sameAs'])   ? array_values($org['sameAs']) : null,
        ];

        if (!empty($org['geo']['lat']) && !empty($org['geo']['lng'])) {
            $data['geo'] = [
                '@type'    => 'GeoCoordinates',
                'latitude' => (float)$org['geo']['lat'],
                'longitude'=> (float)$org['geo']['lng'],
            ];
        }

        if (!empty($org['hasMap'])) {
            $data['hasMap'] = $org['hasMap'];
        }

        if (!empty($org['openingHours']) && is_array($org['openingHours'])) {
            $data['openingHoursSpecification'] = self::buildOpeningHoursSpec($org['openingHours']);
        }

        if (!empty($org['contactPoints'])) {
            $cps = self::buildContactPoints($org['contactPoints']);
            if ($cps) $data['contactPoint'] = $cps;
        }

        $data = self::arrayFilterNulls($data);

        return '<script type="application/ld+json">'.
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).
            '</script>';
    }

    /** JSON-LD для страницы фильтра/подбора: CollectionPage + ItemList */
    public static function renderCollectionPageJsonLd(
        string $url,
        string $name,
        string $description,
        array $itemUrls,
        ?string $id = null
    ): string
    {
        $urlAbs = self::absUrl($url);
        $idAbs  = $id ? self::absUrl($id) : $urlAbs;

        // чистим текст от HTML (у вас уже есть htmlToPlain)
        $namePlain = self::htmlToPlain($name, 300);
        $descPlain = self::htmlToPlain($description, 2000);

        $items = [];
        $pos = 1;
        foreach ($itemUrls as $u) {
            $u = (string)$u;
            if ($u === '') continue;
            $items[] = [
                '@type' => 'ListItem',
                'position' => (string)$pos++,
                'url' => self::absUrl($u),
            ];
            if ($pos > 200) break; // защита от огромных списков
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'CollectionPage',
            '@id'      => $idAbs,
            'url'      => $urlAbs,
            'name'     => $namePlain ?: null,
            'description' => $descPlain ?: null,
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
                'numberOfItems' => (string)max(0, count($items)),
                'itemListElement' => $items,
            ],
        ];

        $data = self::arrayFilterNulls($data);

        return '<script type="application/ld+json">'.
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).
            '</script>';
    }

    public static function renderFaqPageJsonLd(array $faqRows): string
    {
        $main = [];
        foreach ($faqRows as $row) {
            $q = trim((string)($row['question'] ?? ''));
            $a = trim((string)($row['answer'] ?? ''));
            if ($q === '' || $a === '') continue;

            $main[] = [
                '@type' => 'Question',
                'name'  => self::htmlToPlain($q, 300),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => self::htmlToPlain($a, 2000),
                ],
            ];
            if (count($main) >= 20) break;
        }

        if (!$main) return '';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $main,
        ];

        return '<script type="application/ld+json">' .
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
            '</script>';
    }

}
