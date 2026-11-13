<?php
namespace app\controllers\admin;
use app\services\CatalogFacetBridge;
use app\services\CatalogProperties;
use app\services\CatalogDictionary;
use app\services\CatalogPropertyAccess;

final class CatalogFacetBindingsController extends AppController
{
    public function indexAction(): void
    {
        $method=$_SERVER['REQUEST_METHOD']??'GET';CatalogPropertyAccess::check($_SESSION,$method,$_POST['_csrf']??null);
        $db=\R::getPDO();$service=new CatalogFacetBridge($db);if(!$service->installed())throw new \RuntimeException('Связи фильтров ещё не установлены.',503);
        CatalogProperties::useDictionary($db);$definitions=array_filter(CatalogProperties::definitions(),static fn($d)=>in_array($d['kind'],['text','decimal','integer','boolean','option','multi_option'],true));
        $raw=$method==='POST'?($_POST['group_id']??''):($_GET['group']??'0');if(!is_string($raw)||!preg_match('/^\d{1,9}$/D',$raw))throw new \InvalidArgumentException('Некорректная группа.',400);$groupId=(int)$raw;
        $groups=\R::getAll('SELECT id,title FROM attribute_group ORDER BY title');$group=null;foreach($groups as$g)if((int)$g['id']===$groupId)$group=$g;if($groupId&&!$group)throw new \RuntimeException('Группа не найдена.',404);
        if(empty($_SESSION['catalog_properties_csrf']))$_SESSION['catalog_properties_csrf']=bin2hex(random_bytes(32));$csrf=$_SESSION['catalog_properties_csrf'];$dictionaryVersion=(new CatalogDictionary($db))->version();$error='';
        if($method==='POST')try{
            foreach(['version','dictionary_version']as$key)if(!is_string($_POST[$key]??null)||!preg_match('/^\d{1,10}$/D',$_POST[$key]))throw new \InvalidArgumentException('Некорректная версия.');
            if(!is_string($_POST['property_code']??null)||!is_string($_POST['mode']??null)||!is_array($_POST['rows']??null))throw new \InvalidArgumentException('Некорректные поля формы.');
            if(!is_string($_POST['rows_count']??null)||!ctype_digit($_POST['rows_count'])||(int)$_POST['rows_count']!==count($_POST['rows']))throw new \InvalidArgumentException('Форма передана не полностью. Настройки не изменены.');
            $service->save($groupId,(int)$_POST['version'],(int)$_POST['dictionary_version'],$_POST['property_code'],$_POST['mode'],$_POST['rows'],(int)$_SESSION['user']['id']);$_SESSION['success']='Соответствия сохранены. Старые связи и SEO-адреса не переписаны.';redirect(ADMIN.'/catalog-facet-bindings?group='.$groupId);return;
        }catch(\InvalidArgumentException|\RuntimeException$e){$error=$e->getMessage();}
        $bindings=$service->bindings();$binding=$bindings[$groupId]??['property_code'=>'','mode'=>'preview','version'=>0,'values'=>[]];
        $values=$groupId?\R::getAll("SELECT id,value,alias FROM attribute_value WHERE attr_group_id=? AND hide='show' ORDER BY value",[$groupId]):[];
        $preview=$service->preview($groupId&&isset($bindings[$groupId])?[$groupId=>$binding]:[]);
        $suggestions=[];if($groupId)foreach($definitions as$code=>$d)if(in_array($groupId,$d['legacy_filter_group_ids']??[],true))$suggestions[$code]=$d['label'];
        $history=\R::getAll('SELECT group_id,actor_id,changed_at,before_json,after_json FROM catalog_facet_binding_revision ORDER BY id DESC LIMIT 20');
        $this->setMeta('Связи характеристик и фильтров');$this->set(compact('groups','groupId','group','csrf','dictionaryVersion','error','definitions','bindings','binding','values','preview','suggestions','history'));
    }
}
