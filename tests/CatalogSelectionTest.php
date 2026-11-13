<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogSelection as Listing;
function checkListing($condition,$message){if(!$condition)throw new RuntimeException($message);}
checkListing(Listing::state(['sort'=>'price'])['sort']==='price_asc','Legacy price');
checkListing(Listing::state(['sort'=>'rate'])['sort']==='rating','Legacy rating');
checkListing(Listing::state([])['perpage']===25,'Five-column page default');
foreach([25=>25,50=>50,100=>100,24=>25,48=>50,96=>100]as$input=>$expected)checkListing(Listing::state(['perpage'=>$input])['perpage']===$expected,'Page size and old link compatibility');
checkListing(Listing::state(['sort'=>'DROP TABLE product','status'=>['hit'],'perpage'=>100000])===Listing::state([]),'Untrusted state whitelist');
checkListing(Listing::where(Listing::state(['status'=>'hit']))==="AND product.hit='1'",'Manual hit status');
checkListing(strpos(Listing::order(Listing::state(['sort'=>'rating'])),'AVG(rv.point)')!==false,'Rating is based on reviews');
checkListing(strpos(Listing::stock(),'product.reserve')===false&&strpos(Listing::stock(),'SUM(m.quantity)')!==false,'API free stock includes modifications without subtracting reserve twice');
echo "Catalog selection whitelist, editorial statuses, stock and review sorting OK\n";
checkListing(Listing::categoryState([])['perpage']===24,'Category grid default');
foreach([24=>24,48=>48,96=>96,25=>24,50=>48,100=>96,7=>24]as$input=>$expected)checkListing(Listing::categoryState(['perpage'=>$input])['perpage']===$expected,'Category full rows and old links');
foreach([24,48,96]as$count)foreach([1,2,3,4]as$columns)checkListing($count%$columns===0,'Full rows at every supported breakpoint');
echo "Category page sizes: 24/48/96, legacy aliases, 1/2/3/4-column divisibility OK\n";
