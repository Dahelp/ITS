<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use app\services\TrafficSource;
$cases=[
    [['yclid'=>'123'],'',TrafficSource::DIRECT],
    [['utm_source'=>'yandex','utm_medium'=>'cpc'],'',TrafficSource::DIRECT],
    [['utm_source'=>'geoadv_yabs'],'',TrafficSource::DIRECT],
    [[], 'https://yandex.ru/search/?text=tyres',TrafficSource::SEARCH],
    [[], 'https://www.google.com/search?q=tyres',TrafficSource::SEARCH],
    [[], '',TrafficSource::DIRECT_VISIT],
];
foreach($cases as $index=>[$query,$referer,$expected]){
    if(TrafficSource::classify($query,$referer)!==$expected){fwrite(STDERR,'TrafficSource case '.$index.' failed'.PHP_EOL);exit(1);}
}
echo "TrafficSource tests passed".PHP_EOL;
