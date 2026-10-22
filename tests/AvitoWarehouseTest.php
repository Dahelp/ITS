<?php
require dirname(__DIR__).'/vendor/autoload.php';
class_alias(\RedBeanPHP\R::class,'R'); R::setup('sqlite::memory:'); R::freeze(true);
use app\services\AvitoWarehouse as Warehouse;
R::exec('CREATE TABLE avito_ad(id INTEGER,address TEXT,latitude TEXT,longitude TEXT,seller_address_id TEXT,complete_id INTEGER,status TEXT)');
R::exec('INSERT INTO avito_ad VALUES(1,?,?,?,?,NULL,?),(2,?,?,?,?,7,?),(3,?,?,?,?,NULL,?)',[
    'Старый адрес','55','37','old','active',
    'Другой адрес','56','38','other','archived',
    Warehouse::ADDRESS,'55.1','37.1','warehouse','active',
]);
if(Warehouse::updateExisting()!==2)throw new RuntimeException('All product/set ads must be corrected');
if(R::getCell('SELECT COUNT(*) FROM avito_ad WHERE address<>?',[Warehouse::ADDRESS]))throw new RuntimeException('Wrong address remains');
if(R::getCell('SELECT latitude FROM avito_ad WHERE id=1')!==null)throw new RuntimeException('Stale coordinates remain');
if(R::getCell('SELECT seller_address_id FROM avito_ad WHERE id=2')!==null)throw new RuntimeException('Stale seller address remains');
if(R::getCell('SELECT seller_address_id FROM avito_ad WHERE id=3')!=='warehouse')throw new RuntimeException('Known warehouse reference changed');
if(Warehouse::updateExisting()!==0)throw new RuntimeException('Migration must be idempotent');
$draft=(object)['address'=>'Старый адрес','latitude'=>'55','longitude'=>'37','seller_address_id'=>'old'];Warehouse::apply($draft);
if($draft->address!==Warehouse::ADDRESS||$draft->latitude!==null||$draft->longitude!==null||$draft->seller_address_id!==null)throw new RuntimeException('Draft normalization');
if(Warehouse::normalize(['address'=>''])['address']!==Warehouse::ADDRESS)throw new RuntimeException('Empty export address');
echo "Avito warehouse: products, sets, drafts, stale location references and repeat migration OK\n";
