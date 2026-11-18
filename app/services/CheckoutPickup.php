<?php
namespace app\services;

final class CheckoutPickup
{
    public static function resolve(array $branches, $requested): array
    {
        $id=is_scalar($requested)?trim((string)$requested):'';
        foreach($branches as$branch)if($id!=='' && (string)$branch['branch_id']===$id)return $branch;
        if($id==='' && count($branches)===1)return reset($branches);
        throw new \DomainException('Выберите действующий пункт самовывоза.');
    }

    public static function details(string $branchName): array
    {
        // The shop's current pickup point; blank names cover historical orders
        // whose zero-valued branch ID was previously discarded by empty().
        if(trim($branchName)==='' || mb_stripos($branchName,'Климовск')!==false){
            return ['city'=>'Климовск (г. Подольск)','address'=>'142115, Московская область, г. Подольск, мкр. Климовск, ул. Коммунальная, д. 26, стр. 2'];
        }
        return ['city'=>$branchName,'address'=>''];
    }
}
