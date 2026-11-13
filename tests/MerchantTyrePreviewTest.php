<?php
$source = simplexml_load_file($argv[1], 'SimpleXMLElement', LIBXML_NONET);
$preview = simplexml_load_file($argv[2], 'SimpleXMLElement', LIBXML_NONET);
if (!$source || !$preview) throw new RuntimeException('Invalid XML');
$before = iterator_to_array($source->shop->offers->offer, false);
$after = iterator_to_array($preview->shop->offers->offer, false);
if (count($before) !== count($after)) throw new RuntimeException('Offer count changed');
foreach ($before as $i=>$offer) {
    foreach (['id','available'] as $key) {
        if ((string)$offer[$key] !== (string)$after[$i][$key]) throw new RuntimeException('Changed attribute '.$key);
    }
    foreach (['url','price','oldprice','currencyId','categoryId','picture','name','model','vendor'] as $key) {
        if ((string)$offer->$key !== (string)$after[$i]->$key) throw new RuntimeException('Changed field '.$key);
    }
    $params = [];
    foreach ($after[$i]->param as $p) {
        $name = (string)$p['name'];
        if (isset($params[$name])) throw new RuntimeException('Duplicate param '.$name);
        $params[$name] = (string)$p;
    }
    foreach ($offer->param as $p) {
        if (($params[(string)$p['name']] ?? null) !== (string)$p) throw new RuntimeException('Changed original param');
    }
}
echo count($before), " offers: XML valid, original fields and parameters preserved, no duplicate params\n";
