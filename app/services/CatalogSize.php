<?php
namespace app\services;

/** Lexical normalization only: never infer interchangeability or rim geometry. */
final class CatalogSize
{
    public static function normalize(string $value): string
    {
        $value = strtr(trim($value), ['×'=>'x','х'=>'x','Х'=>'x','X'=>'x',','=>'.','–'=>'-','—'=>'-','р'=>'R','Р'=>'R','r'=>'R','l'=>'L']);
        if(preg_match('/^\d+(?:\.\d+)?\s*\*\s*\d+(?:\.\d+)?-\d+(?:\.\d+)?$/D',$value))$value=str_replace('*','x',$value);
        $value = preg_replace('/\s*([x\/\-])\s*/u', '$1', $value);
        $value = preg_replace('/\s+/u', ' ', $value ?? '');
        if (!$value || strlen($value) > 80 || !preg_match('~^[0-9][0-9A-Za-z. /x-]+$~D', $value)) {
            throw new \InvalidArgumentException('Укажите один размер в маркировке, например 12-16.5 или 33x12-20.');
        }
        return $value;
    }

    public static function relations(string $primary, array $rows): array
    {
        if (count($rows) > 100) throw new \InvalidArgumentException('Слишком много строк размеров.');
        $main = $primary !== '' ? self::normalize($primary) : '';
        $result = $seen = [];
        foreach ($rows as $row) {
            if (!is_array($row)) throw new \InvalidArgumentException('Некорректная связь размера.');
            $raw = trim((string)($row['size'] ?? ''));
            if ($raw === '') continue;
            $size = self::normalize($raw);
            if ($size === $main) throw new \InvalidArgumentException('Основной размер нельзя повторять как взаимозаменяемый.');
            if (isset($seen[$size])) throw new \InvalidArgumentException('Повтор размера: '.$size);
            $role = (string)($row['role'] ?? '');
            if (!in_array($role, ['equivalent','compatible'], true)) throw new \InvalidArgumentException('Выберите назначение связи размера.');
            $verification = (string)($row['verification'] ?? 'candidate');
            if (!in_array($verification,['candidate','legacy_unverified','confirmed','conflict'],true)) throw new \InvalidArgumentException('Некорректный статус размера.');
            $source = trim((string)($row['source_ref'] ?? ''));
            $conditions = trim((string)($row['conditions'] ?? ''));
            if (strlen($source)>1000 || strlen($conditions)>2000) throw new \InvalidArgumentException('Слишком длинное основание связи размера.');
            if ($verification==='confirmed' && $source==='') throw new \InvalidArgumentException('Подтверждённому размеру нужно основание.');
            $seen[$size]=true;
            $result[]=['size'=>$size,'role'=>$role,'verification'=>$verification,'source_ref'=>$source,'conditions'=>$conditions];
        }
        if(count($result)>30)throw new \InvalidArgumentException('Допускается не более 30 связей размеров.');
        return $result;
    }

    /** Intended for future consumers. Conditions remain explicit; no global size union. */
    public static function matches(string $query, string $primary, array $relations, array $acceptedConditions = []): bool
    {
        $query=self::normalize($query);
        if ($primary!=='' && $query===self::normalize($primary)) return true;
        foreach ($relations as $row) {
            if (($row['role']??'')!=='equivalent' || ($row['verification']??'')!=='confirmed') continue;
            $condition=trim((string)($row['conditions']??''));
            if ($condition!=='' && !in_array($condition,$acceptedConditions,true)) continue;
            if ($query===self::normalize((string)$row['size'])) return true;
        }
        return false;
    }
}
