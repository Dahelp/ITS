<?php
namespace app\services;

/** A single pickup location for all product and complete-set advertisements. */
final class AvitoWarehouse
{
    public const ADDRESS = 'Московская область, Подольск, микрорайон Климовск, Коммунальная улица, 26с2';

    public static function normalize(array $row): array
    {
        if (trim((string)($row['address'] ?? '')) !== self::ADDRESS) {
            // These references can override the text address. Do not reuse a
            // previous location's coordinates or seller address identifier.
            foreach (['latitude','longitude','seller_address_id'] as $field) $row[$field] = null;
        }
        $row['address'] = self::ADDRESS;
        return $row;
    }

    public static function apply($ad): void
    {
        $row = self::normalize([
            'address'=>$ad->address ?? '', 'latitude'=>$ad->latitude ?? null,
            'longitude'=>$ad->longitude ?? null, 'seller_address_id'=>$ad->seller_address_id ?? null,
        ]);
        foreach ($row as $field=>$value) $ad->$field = $value;
    }

    public static function updateExisting(): int
    {
        return \R::exec('UPDATE avito_ad SET address=?,latitude=NULL,longitude=NULL,seller_address_id=NULL WHERE COALESCE(address,\'\') <> ?', [self::ADDRESS,self::ADDRESS]);
    }
}
