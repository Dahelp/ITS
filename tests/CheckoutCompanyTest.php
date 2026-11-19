<?php
require dirname(__DIR__).'/app/services/CheckoutCompany.php';
use app\services\CheckoutCompany as Company;
function checkCompany($value,$message){if(!$value)throw new RuntimeException($message);}
$base=['comp_name'=>'ООО «Тестовая компания»','inn'=>'5614073401','kpp'=>'561401001','url_address'=>'Тестовый юридический адрес','bank'=>'ТЕСТОВЫЙ БАНК','bik'=>'044525225','raschet'=>'40702810000000000001','korschet'=>'30101810000000000001','purchase_purpose'=>'own_use'];
foreach(['own_use','resale'] as $purpose){$data=Company::fields(array_replace($base,['purchase_purpose'=>$purpose]));Company::validate($data,true,false);$text=Company::text($data);$html=Company::html($data);foreach(array_keys($base)as$key)checkCompany(str_contains($text,$key==='purchase_purpose'?Company::PURPOSES[$purpose]:$base[$key]),'Missing saved field '.$key);checkCompany(str_contains($html,'044525225'),'Leading zero in BIK lost');}
foreach([['bik'=>'123'],['raschet'=>'4070281000000000000'],['korschet'=>'letters'],['purchase_purpose'=>''],['bank'=>'']]as$invalid){try{Company::validate(Company::fields(array_replace($base,$invalid)),true,false);throw new LogicException('Invalid accepted');}catch(DomainException $expected){}}
$fileOnly=Company::fields(['purchase_purpose'=>'resale']);Company::validate($fileOnly,true,true);
$withoutInn=Company::fields(array_replace($base,['inn'=>'','kpp'=>'']));Company::validate($withoutInn,true,false);
checkCompany(!str_contains(Company::html(Company::fields(array_replace($base,['comp_name'=>'<script>alert(1)</script>']))),'<script>'),'HTML injection');
checkCompany(Company::fields(['bank'=>['bad']])['bank']==='','Array accepted');
echo "Company details: both purposes, bank validation, optional INN, attachment alternative, persistence and escaping OK\n";
