<?php
require dirname(__DIR__).'/vendor/autoload.php';
class R {
    public static function getAssoc($sql) {
        if(strpos($sql,'attribute_group.id')!==false)return [2=>'Размер шины',16=>'Рисунок цепи'];
        return [451=>['attr_group_id'=>2,'value'=>'8.15-15'],194=>['attr_group_id'=>2,'value'=>'28x9-15'],600=>['attr_group_id'=>16,'value'=>'Сота']];
    }
}
$_POST['id']=$argv[1]??45;
$controller=(new ReflectionClass(\app\controllers\admin\ProductController::class))->newInstanceWithoutConstructor();
$controller->filtersAction();
