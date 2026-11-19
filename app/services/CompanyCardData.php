<?php
namespace app\services;

/** Order snapshots must not change when the organization profile is edited. */
final class CompanyCardData
{
    public static function store(int $orderId, array $fields, array $contact): void
    {
        \R::exec('INSERT INTO checkout_order_company (order_id,fields_json,contact_json) VALUES (?,?,?)', [
            $orderId, json_encode($fields, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
            json_encode($contact, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)
        ]);
    }

    public static function forOrder(array $order): array
    {
        $profileSource=false;
        $snapshot=\R::getRow('SELECT fields_json,contact_json FROM checkout_order_company WHERE order_id=?', [(int)$order['id']]);
        if ($snapshot) {
            $fields=CheckoutCompany::fields(json_decode($snapshot['fields_json'],true,512,JSON_THROW_ON_ERROR));
            $contact=json_decode($snapshot['contact_json'],true,512,JSON_THROW_ON_ERROR);
        } else {
            $fields=self::legacyFields((string)($order['note']??''));
            // Never replace historical order details with a subsequently edited profile.
            if (!$fields) {
                $company=!empty($order['comp_id'])
                    ? \R::getRow('SELECT * FROM company WHERE id=? AND user_id=?', [(int)$order['comp_id'],(int)$order['user_id']])
                    : \R::getRow('SELECT * FROM company WHERE user_id=? ORDER BY id LIMIT 1',[(int)$order['user_id']]);
                if ($company) { $fields=CheckoutCompany::fields($company); $profileSource=true; }
            }
            $contact=\R::getRow('SELECT name,telefon,email FROM checkout_order_contact WHERE order_id=?',[(int)$order['id']]);
            if (!$contact) $contact=\R::getRow('SELECT name,telefon,email FROM user WHERE id=?',[(int)$order['user_id']]);
        }
        $fields=CheckoutCompany::fields($fields);
        $available=!empty($fields['comp_name']) || !empty($fields['inn']) || (int)($order['payment_customer_type']??0)===4 || ($order['payment_method']??'')==='bank_transfer';
        return ['fields'=>$fields,'context'=>[
            'number'=>(string)($order['inv']??$order['id']),'date'=>(string)($order['date']??''),'source_profile'=>$profileSource,
            'name'=>(string)($contact['name']??''),'phone'=>(string)($contact['telefon']??''),'email'=>(string)($contact['email']??''),'has_attachment'=>!empty($contact['has_attachment'])
        ],'available'=>$available];
    }

    public static function legacyFields(string $note): array
    {
        $marker="Реквизиты и цель покупки:\n";
        $start=strrpos(str_replace("\r\n","\n",$note),$marker);
        if ($start===false) return [];
        $tail=substr(str_replace("\r\n","\n",$note),$start+strlen($marker));
        $labels=array_flip(CheckoutCompany::LABELS);$fields=[];
        foreach (explode("\n",$tail) as $line) {
            $parts=explode(': ',$line,2);
            if (count($parts)!==2) continue;
            if (isset($labels[$parts[0]])) $fields[$labels[$parts[0]]]=$parts[1];
            elseif ($parts[0]==='Цель покупки') {
                $purpose=array_search($parts[1],CheckoutCompany::PURPOSES,true);
                if ($purpose!==false) $fields['purchase_purpose']=$purpose;
            }
        }
        return $fields;
    }
}
