<?php
namespace app\services;

/** Supplier facts are kept separate from internal identifiers and sales terms. */
class TsepChainCatalog
{
    public static function isChain($product): bool
    {
        $article = is_array($product) ? ($product['article'] ?? '') : ($product->article ?? '');
        return strpos((string)$article, 'TSEP-') === 0;
    }

    public static function priceOnRequest($product): bool
    {
        $price = is_array($product) ? ($product['price'] ?? 0) : ($product->price ?? 0);
        return self::isChain($product) && (float)$price <= 0;
    }

    public static function orderText(): string
    {
        return 'Изготовление под заказ';
    }

    public static function publicSpecifications(array $rows): array
    {
        return array_values(array_filter($rows, static function ($row) {
            return !in_array($row['attribute_name'] ?? $row['name'] ?? '', ['Производитель цепей', 'Обозначение у производителя'], true);
        }));
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function products(array $catalog): array
    {
        $products = [];
        foreach ($catalog['series'] as $series) {
            if (!in_array($series['group'], ['special', 'forklift'], true)) throw new \RuntimeException('Unexpected category');
            $rows = [];
            foreach ($series['tables'] as $table) {
                if ($table['section'] !== 'Характеристики') continue;
                foreach ($table['rows'] as $row) {
                    if (strpos($row[0], 'ЦП-') === 0) $rows[] = $row;
                }
            }
            if (!$rows) $rows = [['', '', '']];
            foreach ($rows as $row) {
                $special = $series['group'] === 'special';
                $pattern = strpos($series['series'], 'Лесенка') === 0 ? 'Лесенка' : (strpos($series['series'], 'Зигзаг') !== false ? 'Сота (Зигзаг)' : 'Сота');
                $caliber = $series['options']['Толщина'];
                [$working, $nominalSide] = explode('/', $caliber);
                $side = $nominalSide;
                $tech = implode(' ', $series['sections']['Тех. характеристики'] ?? []);
                if (preg_match('/Боковая часть из цепи (\d+)мм или (\d+)мм/u', $tech, $m)) $side = $m[1].' или '.$m[2].' (в зависимости от модели)';
                $size = preg_replace('/^ЦП-/u', '', $row[0]);
                $sizes = $size !== '' ? array_map('trim', explode(',', str_replace(['х', 'Х', '×'], 'x', $size))) : [];
                $pack = $row[2] ?: (isset($series['sections']['Характеристики']) && $special ? '2 цепи, упаковка, инструкция' : '');
                $specs = [
                    'Производитель цепей'=>'ЦЕПЬ-НН',
                    'Назначение цепи'=>$special ? 'Спецтехника' : 'Вилочные погрузчики',
                    'Модель цепи'=>$series['series'],
                    'Рисунок цепи'=>$pattern,
                    'Калибр цепи (рабочая/боковая), мм'=>$caliber,
                    'Калибр рабочей цепи, мм'=>$working,
                    'Калибр боковой цепи, мм'=>$side,
                    'Шипы'=>'Без шипов',
                    'Прочность цепи'=>'Обычная',
                    'Размер шин для цепи'=>$size ?: 'Подбор по размеру шин заказчика',
                    'Условия поставки цепей'=>'Изготовление под заказ',
                ];
                if ($row[0] !== '') $specs['Обозначение у производителя'] = $row[0];
                if ($row[1] !== '') $specs['Вес комплекта цепей, кг'] = str_replace(',', '.', $row[1]);
                if ($pack !== '') $specs['Комплектация цепей'] = $pack;
                if (preg_match('/Высота звена (\d+)мм/u', $tech, $m)) $specs['Высота звена цепи, мм'] = $m[1];
                if (preg_match('/Соединение\s*-\s*([^\.]+)\./u', $tech, $m)) $specs['Соединение частей цепи'] = trim($m[1]);
                if (strpos($tech, 'Соединительное звено из закаленной стали') !== false) $specs['Материал соединительного звена'] = 'Закаленная сталь';
                $specs['Элементы крепления цепи'] = implode('; ', $series['sections']['Элементы крепления'] ?? []);
                foreach ($series['tables'] as $table) {
                    if ($table['section'] !== 'Тех. характеристики') continue;
                    foreach ($table['rows'] as $clearance) {
                        if (count($clearance) === 3 && preg_match('/Цепи\s*(\d+)\s*мм/u', $clearance[0], $m) && $m[1] === $working) {
                            $specs['Минимальный зазор A, мм'] = $clearance[1];
                            $specs['Минимальный зазор C, мм'] = $clearance[2];
                        }
                    }
                }
                $name = ($size !== '' ? $size.' ' : '').'Цепи противоскольжения «'.$pattern.'» '.$caliber.' мм для '.($special ? 'спецтехники' : 'вилочных погрузчиков');
                $key = $series['source'].'|'.$row[0];
                $hash = substr(sha1($key), 0, 12);
                $slug = ($special ? 'spectehnika-' : 'pogruzchiki-').($pattern==='Лесенка' ? 'lesenka' : ($pattern==='Сота' ? 'sota' : 'sota-zigzag')).'-'.str_replace('/','-',$caliber);
                $alias = 'cepi-'.$slug.'-'.($sizes ? preg_replace('/[^0-9a-z-]+/', '-', implode('-', $sizes)) : 'pod-zakaz');
                $content = '<p>'.self::escape(self::orderText()).'</p>';
                foreach ($series['intro'] ?? [] as $intro) $content .= '<p>'.self::escape($intro).'</p>';
                if ($size !== '') $content .= '<p>Исполнение для шин: <strong>'.self::escape($size).'</strong>.</p>';
                else $content .= '<p>Размер цепей подбирается по маркировке шин и параметрам техники. Размерный ряд для этой серии в каталоге производителя не опубликован.</p>';
                foreach ($series['sections'] as $title => $items) {
                    // The supplier's size tables have become individual products; keep this product's facts.
                    if ($title === 'Характеристики') continue;
                    $content .= '<h2>'.self::escape($title).'</h2><ul>';
                    foreach ($items as $item) $content .= '<li>'.self::escape($item).'</li>';
                    $content .= '</ul>';
                }
                if ($pack !== '') $content .= '<h2>Комплектация</h2><p>'.self::escape($pack).'.</p>';
                $content .= '<h2>Подбор и установка</h2><p>Перед заказом сообщите менеджеру маркировку шин, модель техники и свободные зазоры у колеса. Калибр боковой цепи и тип соединения, если для серии предусмотрено несколько вариантов, уточняются при согласовании изготовления.</p>';
                foreach ($series['tables'] as $table) {
                    if ($table['section'] !== 'Тех. характеристики') continue;
                    $content .= '<h3>Минимальные зазоры по данным производителя</h3><table><tbody>';
                    foreach ($table['rows'] as $cells) {
                        $content .= '<tr>'; foreach ($cells as $cell) $content .= '<td>'.self::escape($cell).'</td>'; $content .= '</tr>';
                    }
                    $content .= '</tbody></table><p>A и C — обозначения зазоров на схеме производителя. Проверку применимости выполняют перед изготовлением.</p>';
                }
                $filters = [
                    'chain_application'=>[$specs['Назначение цепи']], 'chain_pattern'=>[$pattern],
                    'chain_caliber'=>[$caliber], 'chain_working'=>[$working], 'chain_side'=>[$side],
                    'chain_thorns'=>['Без шипов'], 'chain_strength'=>['Обычная'],
                ];
                if ($sizes) $filters['size'] = $sizes;
                if ($pack) $filters['chain_package'] = [$pack];
                $products[] = ['source'=>$series['source'], 'source_key'=>$key, 'group'=>$series['group'],
                    'article'=>'TSEP-'.strtoupper($hash), 'alias'=>$alias, 'name'=>$name, 'model'=>$series['series'],
                    'content'=>$content, 'specs'=>$specs, 'filters'=>$filters,
                    'weight'=>$specs['Вес комплекта цепей, кг'] ?? '',
                    'description'=>'Цепи противоскольжения '.$pattern.' '.$caliber.' мм'.($size ? ' для шин '.$size : ' для спецтехники').'. Изготовление под заказ. Цена и срок поставки по запросу.'];
            }
        }
        return $products;
    }
}
