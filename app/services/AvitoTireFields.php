<?php
namespace app\services;

/** Size dictionaries from the seller's Avito XLSX templates, 15 October 2026. */
final class AvitoTireFields
{
    public static function options(string $type, string $field): array
    {
        static $dictionary;
        if ($dictionary === null) $dictionary = json_decode(file_get_contents(dirname(__DIR__).'/data/avito-tire-dictionaries.json'), true, 512, JSON_THROW_ON_ERROR);
        $profile = $type === 'Мотошины' ? 'moto' : 'industrial';
        return $dictionary['profiles'][$profile]['fields'][$field] ?? [];
    }

    public static function canonical(string $type, string $field, string $value): string
    {
        $value = trim(str_replace(',', '.', $value));
        $options = self::options($type, $field);
        if (in_array($value, $options, true)) return $value;
        if (is_numeric($value)) {
            foreach ($options as $option) if (is_numeric($option) && abs((float)$option - (float)$value) < 0.00001) return $option;
        }
        return '';
    }

    public static function product(int $id): array
    {
        return \R::getRow('SELECT * FROM product WHERE id=?', [$id]);
    }

    private static function characteristics(array $product): array
    {
        if (isset($product['characteristics'])) return $product['characteristics'];
        $id = (int)($product['product_id'] ?? $product['id'] ?? 0);
        if (!$id) return [];
        return \R::getAll('SELECT a.attribute_name AS label,pa.attribute_text AS value FROM product_attribute pa JOIN attribute a ON a.id=pa.attribute_id WHERE pa.product_id=?
            UNION ALL SELECT ag.title AS label,av.value FROM attribute_product ap JOIN attribute_value av ON av.id=ap.attr_id JOIN attribute_group ag ON ag.id=av.attr_group_id WHERE ap.product_id=?', [$id,$id]);
    }

    private static function clean(string $text): string
    {
        return trim(preg_replace('~\s+~u', ' ', str_replace(['х','Х','×','X','–','—',','], ['x','x','x','x','-','-','.'], strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')))));
    }

    private static function parse(string $text): array
    {
        $text = self::clean($text);
        $number = '\d+(?:\.\d+)?';
        $patterns = [
            'metric'=>'~(?<![\p{L}\p{N}])('.$number.'[Ll]?)\s*/\s*('.$number.')\s*[-Rr]\s*('.$number.')(?![\d.])~u',
            'flotation'=>'~(?<![\p{L}\p{N}])('.$number.')\s*x\s*('.$number.')\s*[-Rr]\s*('.$number.')(?![\d.])~u',
            'numeric'=>'~(?<![\p{L}\p{N}])('.$number.'[Ll]?)\s*[-Rr]\s*('.$number.')(?![\d.])~u',
        ];
        foreach ($patterns as $format=>$pattern) {
            if (!preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) continue;
            $sizes = [];
            foreach ($matches as $m) {
                $width = $format === 'flotation' ? $m[2] : $m[1];
                $rim = $format === 'numeric' ? $m[2] : $m[3];
                $sizes[$width.'|'.$rim.'|'.($format === 'metric' ? $m[2] : '')] = [
                    'size'=>$m[0], 'format'=>$format, 'tire_section_width'=>$width,
                    'rim_diameter'=>$rim, 'tire_aspect_ratio'=>$format === 'metric' ? $m[2] : 'Другое',
                ];
            }
            return count($sizes) === 1 ? reset($sizes) : ['ambiguous'=>true];
        }
        return [];
    }

    public static function vehicleType(array $product): string
    {
        $path = $product['category_path'] ?? [];
        $id = (int)($product['category_id'] ?? 0);$seen=[];
        while ($id && !isset($seen[$id])) {
            $seen[$id]=true;$row=\R::getRow('SELECT name,parent_id FROM category WHERE id=?',[$id]);
            if (!$row) break;
            $path[]=$row['name'];$id=(int)$row['parent_id'];
        }
        $text=mb_strtolower(implode(' ', $path),'UTF-8');
        if (preg_match('~грузов~u',$text)) return 'Грузовая';
        if (preg_match('~спецтехник|погрузчик|экскаватор|катков|грейдер|шахтн|кранов~u',$text)) return 'Для спецтехники';
        return '';
    }

    public static function suggest(array $product, string $type): array
    {
        if (!in_array($type,['Мотошины','Шины для грузовиков и спецтехники'],true)) return ['fields'=>[], 'notes'=>[], 'size'=>''];
        $attributes=self::characteristics($product);$sizeTexts=[];$explicit=[];$extra=[];
        foreach ($attributes as $attribute) {
            $label=mb_strtolower(self::clean((string)$attribute['label']),'UTF-8');$value=self::clean((string)$attribute['value']);
            if (preg_match('~размер шин|типоразмер~u',$label)) $sizeTexts[]=$value;
            if (preg_match('~ширина профиля|ширина шины~u',$label) && !preg_match('~диска|протектора~u',$label)) $field='tire_section_width';
            elseif (preg_match('~высота профиля~u',$label)) $field='tire_aspect_ratio';
            elseif (preg_match('~посадочный диаметр|диаметр диска~u',$label)) $field='rim_diameter';
            else $field='';
            if ($field && preg_match('~^([0-9]+(?:\.[0-9]+)?[Ll]?)~u',$value,$m)) $explicit[$field]=$m[1];
            if (preg_match('~размер диска|\brim\b|рекомендуем.*обод~iu',$label) && preg_match('~-\s*(\d+(?:\.\d+)?)\s*$~u',$value,$m) && empty($explicit['rim_diameter'])) $explicit['rim_diameter']=$m[1];
            if (preg_match('~слойность~u',$label) && preg_match('~\d+~',$value,$m)) $extra['ply_rating']=$m[0];
            if (preg_match('~индекс нагрузки~u',$label) && preg_match('~^\d+(?:/\d+)?$~',$value)) $extra['load_index']=$value;
            if (preg_match('~индекс.*скорости~u',$label)) $extra['speed_index']=$value;
            if (preg_match('~конструкци~u',$label)) {
                if (mb_stripos($value,'радиал')!==false) $extra['construction']='Радиальный';
                elseif (mb_stripos($value,'диагонал')!==false) $extra['construction']='Диагональный';
            }
            if (preg_match('~тип шины~u',$label)) {
                if (mb_stripos($value,'цельнолит')!==false) $extra['design']='Цельнолитая';
                elseif (mb_stripos($value,'пневмат')!==false) $extra['design']='Пневматическая';
            }
            if ($label==='тип') {
                if (preg_match('~^TL\b~i',$value)) $extra['tube_type']='Бескамерная';
                elseif (preg_match('~^TTF?\b~i',$value)) $extra['tube_type']='Камерная';
            }
        }
        $parsed=[];$notes=[];
        foreach ($sizeTexts ?: [(string)($product['name']??'')] as $text) {
            $candidate=self::parse($text);
            if (!empty($candidate['ambiguous'])) {$parsed=['ambiguous'=>true];break;}
            if ($candidate) {
                if ($parsed && $parsed['size']!==$candidate['size']) {
                    if ($parsed['tire_section_width']!==$candidate['tire_section_width'] || $parsed['rim_diameter']!==$candidate['rim_diameter']) {$parsed=['ambiguous'=>true];break;}
                }
                $parsed=$candidate;
            }
        }
        if (!empty($parsed['ambiguous'])) {$notes[]='В товаре несколько размеров. Выберите параметры нужной шины вручную.';$parsed=[];}
        $source=array_replace($parsed,$explicit);$fields=[];
        foreach (['tire_section_width','tire_aspect_ratio','rim_diameter'] as $field) {
            if (!isset($source[$field])) continue;
            $value=self::canonical($type,$field,(string)$source[$field]);
            if ($value!=='') $fields[$field]=$value;
            else $notes[]='Значения «'.$source[$field].'» нет в справочнике поля «'.$field.'». Выберите допустимое вручную.';
        }
        if (($fields['tire_aspect_ratio']??'')==='Другое') $notes[]='Высота профиля в маркировке не задана: выбрано «Другое», без подстановки диаметра или выдуманного процента.';
        if ($type==='Шины для грузовиков и спецтехники') {
            $vehicle=self::vehicleType($product);if ($vehicle!=='') $fields['vehicle_type']=$vehicle;
        }
        return ['fields'=>array_merge($extra,$fields),'size'=>$parsed['size']??'','format'=>$parsed['format']??'','notes'=>$notes];
    }

    public static function apply($ad, array $product, bool $repairCopiedAspect=false): array
    {
        $type=(string)($ad->product_type??'');$suggestion=self::suggest($product,$type);
        foreach ($suggestion['fields'] as $field=>$value) {
            $current=trim((string)($ad->$field??''));
            if ($current==='' || $field==='vehicle_type') $ad->$field=$value;
            elseif (in_array($field,['tire_section_width','tire_aspect_ratio','rim_diameter'],true)) {
                $canonical=self::canonical($type,$field,$current);if($canonical!=='')$ad->$field=$canonical;
                elseif($repairCopiedAspect)$ad->$field=$value;
            }
        }
        if ($repairCopiedAspect && $type==='Шины для грузовиков и спецтехники' && ($suggestion['fields']['tire_aspect_ratio']??'')==='Другое'
            && $suggestion['format']==='numeric' && is_numeric($ad->tire_aspect_ratio??'') && is_numeric($suggestion['fields']['rim_diameter']??'')
            && (float)$ad->tire_aspect_ratio===(float)$suggestion['fields']['rim_diameter']) $ad->tire_aspect_ratio='Другое';
        return $suggestion;
    }

    public static function validationErrors(array $row): array
    {
        $type=(string)($row['product_type']??'');$errors=[];
        if (!in_array($type,['Мотошины','Шины для грузовиков и спецтехники'],true)) return [];
        foreach (['tire_section_width','tire_aspect_ratio','rim_diameter']as$field) {
            $value=trim((string)($row[$field]??''));
            if ($value!=='' && self::canonical($type,$field,$value)==='') $errors[$field]='Значение «'.$value.'» отсутствует в справочнике Авито';
        }
        return $errors;
    }
}
