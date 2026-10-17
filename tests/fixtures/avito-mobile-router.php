<?php
namespace app\models { class AppModel { public function __construct() {} } }
namespace {
    if (!getenv('AVITO_MOBILE_TEST_DB')) { http_response_code(404); exit; }
    define('ROOT',dirname(__DIR__,2));
    require dirname(__DIR__,2).'/vendor/autoload.php';
    \ishop\App::$app = \ishop\Registry::instance();
    \ishop\App::$app->setProperty('avito',['client_id'=>'test','client_secret'=>'test']);
    class_alias(\RedBeanPHP\R::class,'R'); \R::setup('sqlite:'.getenv('AVITO_MOBILE_TEST_DB'));
    if (!isset($_GET['insecure'])) $_SERVER['HTTPS']='on';
    require dirname(__DIR__,2).'/config/routes.php';
    \ishop\Router::dispatch(trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/'));
}
