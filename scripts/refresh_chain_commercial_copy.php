<?php
/** CLI-only editorial update. Does not run imports or change product business fields. */
function chainCommercialBenefits(): string
{
    $cards = [
        ['Качественные материалы', 'Материал и параметры цепи согласуем при заказе.'],
        ['Под ваш размер шин', 'Подберём плетение, калибр и крепления для вашей техники.'],
        ['Изготовление на заказ', 'Параметры и срок готовности подтверждаем до оформления.'],
        ['Доставка к месту выгрузки', 'Согласуем маршрут и организуем доставку по России.'],
    ];
    $html = '<div data-chain-benefits="2026-11" style="margin:24px 0 28px;padding:clamp(20px,2.4vw,28px);background:#edf8f1;border:2px solid #198754;border-radius:14px;color:#163d2a;line-height:1.6;box-sizing:border-box">'
        . '<h2 style="margin:0 0 20px;font-size:clamp(22px,2vw,26px);line-height:1.3;color:#163d2a">Цепи противоскольжения — под вашу технику</h2>'
        . '<div style="display:flex;flex-wrap:wrap;gap:16px;align-items:stretch">';
    foreach ($cards as [$title, $text]) {
        $html .= '<div style="flex:1 1 220px;min-width:0;min-height:124px;padding:20px;background:#fff;border-radius:10px;box-sizing:border-box">'
            . '<strong style="display:block;margin:0;font-size:19px;line-height:1.35">' . $title . '</strong>'
            . '<p style="margin:10px 0 0;font-size:16px;line-height:1.55">' . $text . '</p></div>';
    }
    return $html . '</div><div style="margin:20px 0 0;padding-top:18px;border-top:1px solid #c9e5d5">'
        . '<p style="margin:0;font-size:17px;line-height:1.6"><strong>Пришлите размер шин и модель техники — подберём исполнение и рассчитаем стоимость.</strong></p></div></div>';
}

function chainCommercialRewrite(string $text): string
{
    $text = preg_replace('~<div\b[^>]*>\s*<h2\b[^>]*>\s*Цепи собственного производства\s*(?:—|&mdash;|&#8212;)\s*под вашу технику\s*</h2>.*?<p\b[^>]*>\s*<strong>Пришлите размер шин.*?</strong>\s*</p>\s*</div>~su', chainCommercialBenefits(), $text);
    if ($text === null) throw new RuntimeException('Benefit block replacement failed');
    $oldQuality = 'Изготавливаем цепи противоскольжения на собственном производстве. Наш опыт в металлообработке — основа точного подбора конструкции и качественного исполнения. Используем качественное сырьё, подбираем калибр цепи под нагрузку и условия работы. Для закалённых элементов важна правильно подобранная твёрдость стали; материал и термообработку выбранного исполнения согласуем при заказе.';
    $newQuality = 'Качественное сырьё и правильно подобранная конструкция — основа надёжной работы цепей. Поможем выбрать калибр звеньев, плетение и крепления с учётом нагрузки и условий эксплуатации. Материал, термообработку и характеристики выбранного исполнения согласуем перед оформлением заказа.';
    $map = [
        $oldQuality => $newQuality,
        str_replace('—', '&mdash;', $oldQuality) => $newQuality,
        'Цепи противоскольжения собственного производства' => 'Цепи противоскольжения под вашу технику',
        'Производим цепи противоскольжения под размеры шин и задачи вашей техники.' => 'Подберём цепи противоскольжения под размеры шин и задачи вашей техники.',
        'Прямое согласование с производством, индивидуальное изготовление и доставка к месту выгрузки.' => 'Изготовление на заказ по согласованным параметрам и доставка к месту выгрузки.',
        'Изготавливаем цепи на колёса для складской, строительной и другой спецтехники.' => 'Предлагаем цепи на колёса для складской, строительной и другой спецтехники.',
        'Изготавливаем цепи для вилочных погрузчиков, работающих на заснеженных и обледенелых площадках.' => 'Подберём цепи для вилочных погрузчиков, работающих на заснеженных и обледенелых площадках.',
        'Собственное производство позволяет напрямую согласовать конструкцию, срок изготовления и доставку.' => 'Конструкцию, срок изготовления и условия доставки согласуем до оформления заказа.',
        'Изготавливаем цепи на шины ' => 'Подберём цепи на шины ',
        'подготовим комплект под конкретную машину' => 'подберём исполнение под конкретную машину',
        'Производство, которому можно доверить вашу задачу' => 'Материалы и исполнение под условия работы',
        'Изготавливаем цепи для разных размеров шин, в том числе нестандартных и крупногабаритных.' => 'Можно заказать цепи для разных размеров шин, в том числе нестандартных и крупногабаритных.',
        'Напрямую согласуем параметры заказа и предложим минимально возможный срок изготовления с учётом объёма и загрузки производства.' => 'Согласуем параметры заказа и срок изготовления с учётом исполнения, количества цепей и условий доставки.',
    ];
    $text = str_replace(array_keys($map), array_values($map), $text);
    // CKEditor may encode spaces and punctuation differently in an SEO template.
    // Replace that marketing paragraph as a whole; keep technical paragraphs intact.
    $text = preg_replace_callback('~<p\b[^>]*>.*?</p>~su', static function ($match) use ($newQuality) {
        $plain = html_entity_decode(strip_tags($match[0]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('/собственн[а-яё]*\s+производств|наш.{0,35}металлообработ/iu', $plain)) return '<p>' . $newQuality . '</p>';
        return $match[0];
    }, $text);
    if ($text === null) throw new RuntimeException('Marketing paragraph replacement failed');
    $text = preg_replace(['/собственного производства/iu', '/собственное производство/iu', '/от производителя/iu'], ['под заказ', 'Качественные материалы', 'под заказ'], $text);
    if ($text === null) throw new RuntimeException('Commercial copy replacement failed');
    return $text;
}

function chainCommercialClaim(string $text): ?string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return preg_match('/собственн\w*\s+производств|(?:^|[^\p{L}])(?:производим|изготавливаем|изготовим|выпускаем)(?:[^\p{L}]|$)|наш.{0,35}металлообработ|от\s+производителя|поставщик/iu', $text, $match) ? $match[0] : null;
}

function chainCommercialForbidden(string $text): bool
{
    return chainCommercialClaim($text) !== null;
}

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;

$args = getopt('', ['root:', 'report-dir:', 'apply']);
$root = realpath($args['root'] ?? dirname(__DIR__));
$reportDir = realpath($args['report-dir'] ?? __DIR__);
if (!$root || !$reportDir || str_starts_with($reportDir . '/', $root . '/public/')) throw new RuntimeException('Private report directory required');
require $root . '/config/init.php';
require CONF . '/db_bootstrap.php';
$db = R::getPDO();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$apply = array_key_exists('apply', $args);
$report = ['mode' => $apply ? 'apply' : 'preview', 'changes' => [], 'issues' => []];
$fetch = static function (string $sql, array $args = []) use ($db): array {
    $q = $db->prepare($sql); $q->execute($args); return $q->fetchAll(PDO::FETCH_ASSOC);
};
$db->beginTransaction();
try {
    $categories = $fetch('SELECT * FROM category WHERE alias IN (?,?,?) ORDER BY id FOR UPDATE', ['cepi-protivoskolzheniya', 'cepi-dlya-spectehniki', 'cepi-dlya-vilochnyh-pogruzchikov']);
    if (count($categories) !== 3) throw new RuntimeException('Expected exactly three chain categories');
    $ids = array_map('intval', array_column($categories, 'id'));
    $slots = implode(',', array_fill(0, count($ids), '?'));
    $products = $fetch("SELECT * FROM product WHERE article LIKE 'TSEP-%' ORDER BY id FOR UPDATE");
    if (count($products) !== 55) throw new RuntimeException('Expected exactly 55 chain products');
    foreach ($products as $p) if (!in_array((int)$p['category_id'], $ids, true)) throw new RuntimeException('Chain category mismatch');
    $sets = [
        'category' => [$categories, ['name', 'h1', 'title', 'description', 'keywords', 'top_content', 'content']],
        'product' => [$products, ['name', 'seo_h1', 'title', 'description', 'keywords', 'content', 'short_description']],
        'plagins_inseo' => [$fetch("SELECT * FROM plagins_inseo WHERE category_id IN ($slots) AND tip IN ('category','product') ORDER BY id FOR UPDATE", $ids), ['name', 'title', 'description', 'keywords', 'content']],
        'attribute_value_category_canonical' => [$fetch("SELECT * FROM attribute_value_category_canonical WHERE category_id IN ($slots) ORDER BY id FOR UPDATE", $ids), ['seo_h1', 'title', 'description', 'keywords', 'top_content', 'content']],
        'category_faq' => [$fetch("SELECT * FROM category_faq WHERE category_id IN ($slots) ORDER BY id FOR UPDATE", $ids), ['question', 'answer']],
        'attribute_value_category_faq' => [$fetch("SELECT * FROM attribute_value_category_faq WHERE category_id IN ($slots) ORDER BY id FOR UPDATE", $ids), ['question', 'answer']],
    ];
    $urls = [];
    $categoryAliases = array_column($categories, 'alias', 'id');
    foreach ($categories as $c) $urls[] = ($c['alias'] === 'cepi-protivoskolzheniya' ? '/catalog/' : '/category/') . $c['alias'];
    foreach ($products as $p) $urls[] = '/product/' . $p['alias'];
    foreach ($sets['attribute_value_category_canonical'][0] as $landing) {
        if ((int)$landing['is_active'] !== 1 || $landing['mode'] !== 'landing') continue;
        $value = $fetch('SELECT alias FROM attribute_value WHERE id=?', [$landing['attr_value_id']]);
        if ($value) $urls[] = '/category/' . $categoryAliases[$landing['category_id']] . '/' . $value[0]['alias'];
    }
    $report['urls'] = array_values(array_unique($urls));
    $urls = [];
    $categoryAliases = array_column($categories, 'alias', 'id');
    foreach ($categories as $c) $urls[] = ($c['alias'] === 'cepi-protivoskolzheniya' ? '/catalog/' : '/category/') . $c['alias'];
    foreach ($products as $p) $urls[] = '/product/' . $p['alias'];
    foreach ($sets['attribute_value_category_canonical'][0] as $landing) {
        if ((int)$landing['is_active'] !== 1 || $landing['mode'] !== 'landing') continue;
        $value = $fetch('SELECT alias FROM attribute_value WHERE id=?', [$landing['attr_value_id']]);
        if ($value) $urls[] = '/category/' . $categoryAliases[$landing['category_id']] . '/' . $value[0]['alias'];
    }
    $report['urls'] = array_values(array_unique($urls));
    $productIds = implode(',', array_map('intval', array_column($products, 'id')));
    $filterBefore = $fetch("SELECT * FROM attribute_product WHERE product_id IN ($productIds) ORDER BY product_id,attr_id FOR UPDATE");
    $plan = [];
    foreach ($sets as $table => [$rows, $fields]) {
        $report['checked'][$table] = count($rows);
        foreach ($rows as $row) {
            $changed = [];
            foreach ($fields as $field) {
                if (!isset($row[$field])) continue;
                $after = chainCommercialRewrite((string)$row[$field]);
                if (chainCommercialForbidden($after)) $report['issues'][] = ['table' => $table, 'id' => $row['id'], 'field' => $field, 'matched_phrase' => chainCommercialClaim($after), 'excerpt' => mb_substr(strip_tags($after), 0, 350)];
                if ($after !== (string)$row[$field]) $changed[$field] = $after;
            }
            if ($table === 'product' && !str_contains($changed['content'] ?? $row['content'], 'data-chain-benefits="2026-11"')) $report['issues'][] = ['table' => $table, 'id' => $row['id'], 'field' => 'content', 'error' => 'Benefit block was not recognized'];
            if (!$changed) continue;
            $plan[] = ['table' => $table, 'id' => $row['id'], 'before' => $row, 'after' => $changed];
            $report['changes'][] = ['table' => $table, 'id' => $row['id'], 'fields' => array_keys($changed)];
        }
    }
    if ($report['issues']) throw new RuntimeException('Unrecognized production claim or benefit block; no data changed');
    if ($apply && $plan) {
        $backup = $reportDir . '/before.json';
        if (file_exists($backup)) throw new RuntimeException('This release already has a backup');
        if (file_put_contents($backup, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) throw new RuntimeException('Backup failed');
        chmod($backup, 0600);
        foreach ($plan as $change) {
            $fields = array_keys($change['after']);
            $set = implode(',', array_map(static fn($f) => "`$f`=?", $fields));
            $q = $db->prepare("UPDATE `{$change['table']}` SET $set WHERE id=?");
            $q->execute(array_merge(array_values($change['after']), [$change['id']]));
            $after = $fetch("SELECT * FROM `{$change['table']}` WHERE id=?", [$change['id']])[0];
            foreach ($change['before'] as $field => $value) if (($change['after'][$field] ?? $value) !== $after[$field]) throw new RuntimeException('Unexpected field drift: ' . $change['table'] . '/' . $field);
        }
        if ($filterBefore !== $fetch("SELECT * FROM attribute_product WHERE product_id IN ($productIds) ORDER BY product_id,attr_id")) throw new RuntimeException('Product filter links changed');
        $db->commit();
        $report['private_backup'] = $backup;
        $report['non_text_fields_unchanged'] = true;
        $report['filters_unchanged'] = true;
    } else {
        $db->rollBack();
    }
    $report['changed_rows'] = count($plan);
    $report['checked_at'] = date(DATE_ATOM);
    file_put_contents($reportDir . '/' . ($apply ? 'publication' : 'preview') . '.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    $report['error'] = $e->getMessage();
    file_put_contents($reportDir . '/failure.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
