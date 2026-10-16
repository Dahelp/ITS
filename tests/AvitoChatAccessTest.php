<?php
require dirname(__DIR__).'/vendor/autoload.php';
class_alias(\RedBeanPHP\R::class, 'R');
R::setup('sqlite::memory:');
R::exec('CREATE TABLE user (id INTEGER PRIMARY KEY, role TEXT, email TEXT, password TEXT, date_last_visit TEXT)');
R::exec('CREATE TABLE avito_chat_manager (user_id INTEGER PRIMARY KEY)');
R::exec('CREATE TABLE user_groups (id INTEGER PRIMARY KEY, role TEXT)');
foreach ([1=>'admin', 2=>'manager', 3=>'user', 4=>'b2buser'] as $id=>$role) {
    R::exec('INSERT INTO user (id, role, email, password) VALUES (?, ?, ?, ?)', [$id, $role, $role.'@example.test', password_hash('test-password', PASSWORD_DEFAULT)]);
    R::exec('INSERT INTO avito_chat_manager (user_id) VALUES (?)', [$id]);
    R::exec('INSERT INTO user_groups (id, role) VALUES (?, ?)', [$id, $role]);
}
function accessCheck($value, $message) { if (!$value) { throw new RuntimeException($message); } }
accessCheck(\app\services\AvitoMessenger::allowed(1), 'Admin can be enabled');
accessCheck(\app\services\AvitoMessenger::allowed(2), 'Manager can be enabled');
accessCheck(!\app\services\AvitoMessenger::allowed(3), 'Customer denied even with stale permission');
accessCheck(!\app\services\AvitoMessenger::allowed(4), 'B2B denied even with stale permission');
\app\services\AvitoMessenger::setManagerAccess(2, false, 1);
accessCheck(!\app\services\AvitoMessenger::allowed(2), 'Revocation takes effect');
accessCheck(\app\services\AvitoMessenger::allowed(1), 'Revocation preserves other staff');
$model = (new ReflectionClass(\app\models\admin\User::class))->newInstanceWithoutConstructor();
$_SESSION = []; $_POST = ['email'=>'manager@example.test', 'password'=>'test-password'];
accessCheck($model->login(true), 'Manager can use staff login');
accessCheck(!\app\models\User::isAdmin(), 'Manager is not elevated to administrator');
$_SESSION = []; $_POST = ['email'=>'b2buser@example.test', 'password'=>'test-password'];
accessCheck(!$model->login(true), 'Customer cannot use staff login');
$controller = (new ReflectionClass(\app\controllers\admin\UserController::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod($controller, 'customerChatAccessForm');
$method->setAccessible(true);
$_SERVER['REQUEST_METHOD'] = 'POST';
foreach ([
    [['user'=>['groups'=>2]], ['avito_chat_csrf'=>'token'], 403],
    [['user'=>['groups'=>1], 'avito_chat_csrf'=>'token'], ['avito_chat_csrf'=>'wrong'], 403],
    [['user'=>['groups'=>1], 'avito_chat_csrf'=>'token'], ['avito_chat_csrf'=>'token','groups'=>3,'role'=>'admin'], 400],
] as [$session, $post, $code]) {
    $_SESSION = $session; $_POST = $post;
    try { $method->invoke($controller); throw new RuntimeException('Unsafe update accepted'); }
    catch (Exception $e) { accessCheck($e->getCode() === $code, 'Reject unauthorized, forged-CSRF or customer-group submission'); }
}
echo "AvitoChatAccess: OK\n";
