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
$_SERVER['REQUEST_URI']='/category/test';
TrafficSource::capture(['yclid'=>'test']);
if (session_status() !== PHP_SESSION_NONE || TrafficSource::code() !== TrafficSource::DIRECT) throw new RuntimeException('Anonymous capture must not start session');
$_COOKIE[TrafficSource::COOKIE_NAME]=json_encode(['code'=>'search','landing_url'=>'/first','referer'=>'google.com','captured_at'=>'2026-11-25']);
if (TrafficSource::capture([])!==TrafficSource::SEARCH || session_status()!==PHP_SESSION_NONE) throw new RuntimeException('Read first touch without session');
session_save_path(dirname(__DIR__).'/tmp');
session_start();
TrafficSource::capture([]);
if (($_SESSION['traffic_source']['landing_url']??'')!=='/first') throw new RuntimeException('Copy first touch when session becomes necessary');
$_COOKIE[TrafficSource::COOKIE_NAME]=json_encode(['code'=>'yandex_direct']);
if (TrafficSource::capture([])!==TrafficSource::SEARCH) throw new RuntimeException('Existing session first touch wins');
unset($_SESSION['traffic_source']);
$_COOKIE[TrafficSource::COOKIE_NAME]=json_encode(['code'=>'<script>bad</script>','label'=>'unsafe']);
if (TrafficSource::capture([])!==TrafficSource::DIRECT_VISIT) throw new RuntimeException('Unknown client metadata must be rejected');
$_COOKIE[TrafficSource::COOKIE_NAME]=['bad'];
if (TrafficSource::capture([])!==TrafficSource::DIRECT_VISIT) throw new RuntimeException('Malformed cookie must be rejected');
session_destroy();
echo "TrafficSource: classification, session-free capture, first touch migration and malformed cookies OK".PHP_EOL;
