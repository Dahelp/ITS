<?php
if (PHP_SAPI !== 'cli') exit(1);
$root = getenv('PROJECT_RELEASE_TEST_ROOT') ?: getcwd();
// The installer invokes this child while its cwd is the private release folder.
$root = getenv('LEAD_SITE_ROOT') ?: $root;
require $root.'/config/init.php';
require CONF.'/db_bootstrap.php';
foreach (['callback'=>['contact_name','contact_email','comment','source_path'], 'mail_oneclick'=>['quantity','unit_price','source_path','fio_click','prim_click']] as $table=>$required) {
    $columns = array_column(R::getAll("SHOW COLUMNS FROM `$table`"), 'Field');
    if (array_diff($required, $columns)) throw new RuntimeException('Lead schema is incomplete: '.$table);
}
$key = 'lead-smoke-'.bin2hex(random_bytes(12));
if ((int)R::getCell('SELECT GET_LOCK(?, 0)', [$key]) !== 1) throw new RuntimeException('Database advisory locks unavailable');
R::getCell('SELECT RELEASE_LOCK(?)', [$key]);
if (app\services\LeadForm::phone('8 (912) 345-67-89') !== '+79123456789') throw new RuntimeException('Phone normalization failed');
$checks = [];
foreach (['/', '/product/cepi-pogruzchiki-lesenka-6-6-8-15-15-28x9-15', '/js/lead-forms.js?v=20261118', '/cart/view'] as $path) {
    $ch = curl_init('https://its-center.ru'.$path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>30, CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_HTTPHEADER=>['Cache-Control: no-cache']]);
    $body = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($body === false || $status !== 200 || preg_match('~Fatal error:|Uncaught (?:Error|Exception)|Parse error:~', $body)) throw new RuntimeException('HTTP check failed: '.$path);
    if ($path === '/' && !str_contains($body, 'name="lead_token"')) throw new RuntimeException('Callback token absent');
    if (str_starts_with($path, '/product/') && (!str_contains($body, 'Заявка на заказ') || !str_contains($body, 'name="quantity"'))) throw new RuntimeException('Product form missing');
    if (str_starts_with($path, '/js/') && !str_contains($body, 'function validatePhone')) throw new RuntimeException('Outdated lead script');
    $checks[] = ['path'=>$path, 'status'=>$status];
}
file_put_contents(__DIR__.'/smoke.json', json_encode(['schema'=>'ok','database_locks'=>'ok','http'=>$checks,'real_leads_created'=>0,'mail_sent'=>false], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo 'Lead schema, database locks and '.count($checks)." HTTP checks OK; no real leads or mail\n";
