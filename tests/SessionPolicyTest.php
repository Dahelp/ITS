<?php
require dirname(__DIR__).'/app/services/SessionPolicy.php';
require dirname(__DIR__).'/app/services/LeadForm.php';
require dirname(__DIR__).'/vendor/ishop/core/base/View.php';
use app\services\SessionPolicy;
use app\services\LeadForm;
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
foreach (['/','/catalog/shiny','/category/test','/product/test','/user-guide'] as $path) {
    check(!SessionPolicy::needsSession($path,'GET',[],[]),'anonymous public GET: '.$path);
}
foreach (['/user/login','/cart/view','/checkout','/admin','/favorites/items','/comparison/addcomparison'] as $path) {
    check(SessionPolicy::needsSession($path,'GET',[],[]),'private route: '.$path);
}
check(SessionPolicy::needsSession('/product/test','POST',[],[]),'POST starts stateful flow');
check(SessionPolicy::needsSession('/category/test','GET',[session_name()=>'existing'],[]),'existing visitor');
check(!SessionPolicy::needsSession('/category/test','GET',[session_name()=>['bad']],[]),'malformed session cookie');
check(SessionPolicy::needsSession('/category/test','GET',[],['lead_form'=>'1']),'explicit no-JS preparation');
$_SERVER['REQUEST_URI']='/catalog/shiny?utm_source=test'; $_GET=['catalog/shiny'=>''];
$field=LeadForm::tokenField('callback');
check(session_status()===PHP_SESSION_NONE && str_contains($field,'value=""'),'public render remains session-free');
check(str_contains($field,'utm_source=test&amp;lead_form=1') && !str_contains($field,'catalog%2Fshiny'),'fallback excludes rewrite query key');
$dir=dirname(__DIR__).'/tmp/session-render-test';
@mkdir($dir.'/views/Test/Probe',0777,true);
file_put_contents($dir.'/views/Test/Probe/index.php','<?php $GLOBALS["render_session"] = session_status() === PHP_SESSION_ACTIVE ? "active" : "guest"; unset($_SESSION["success"]);');
define('APP',$dir);define('TEMPLATE','Test');define('LAYOUT','unused');
$view=new ishop\base\View(['controller'=>'Probe','prefix'=>''],[],false,'index');
ob_start();$view->render([]);$html=ob_get_clean();
check($GLOBALS['render_session']==='guest' && session_status()===PHP_SESSION_NONE,'view must not start guest session');
session_save_path(dirname(__DIR__).'/tmp');session_start();$_SESSION['success']='test';session_write_close();
ob_start();$view->render([]);$html=ob_get_clean();
check($GLOBALS['render_session']==='active' && !isset($_SESSION['success']),'private render resumes existing session and consumes flash');
session_write_close();session_start();check(!isset($_SESSION['success']),'flash consumption persisted');session_destroy();
unlink($dir.'/views/Test/Probe/index.php');rmdir($dir.'/views/Test/Probe');rmdir($dir.'/views/Test');rmdir($dir.'/views');rmdir($dir);
echo "Session policy: lazy bootstrap, private boundaries, no-JS fallback, session-free view and persisted flashes OK\n";
