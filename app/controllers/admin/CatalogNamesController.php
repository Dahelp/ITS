<?php
namespace app\controllers\admin;
use app\services\CatalogNaming;
use app\services\CatalogProperties;
use app\services\CatalogPropertyAccess;

final class CatalogNamesController extends AppController
{
    public function indexAction(): void
    {
        $method=$_SERVER['REQUEST_METHOD']??'GET';CatalogPropertyAccess::check($_SESSION,$method,$_POST['_csrf']??null);$db=\R::getPDO();$service=new CatalogNaming($db);$state=$service->snapshot();if(!$state['installed'])throw new \RuntimeException('Управление названиями ещё не установлено.',503);
        $mode=is_string($_GET['mode']??null)?$_GET['mode']:'rules';if(!in_array($mode,['rules','product'],true))throw new \InvalidArgumentException('Неверный режим.',400);
        $type=is_string($_GET['type']??null)?$_GET['type']:'*';if($type!=='*'&&!isset(CatalogProperties::types()[$type]))throw new \InvalidArgumentException('Неизвестный тип изделия.',400);
        $raw=is_string($_GET['id']??null)?$_GET['id']:'0';if(!preg_match('/^\d{1,9}$/D',$raw))throw new \InvalidArgumentException('Неверный товар.',400);$id=(int)$raw;
        $q=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,100):'';$search=\R::getAll('SELECT id,sku,name FROM product WHERE sku LIKE ? OR name LIKE ? ORDER BY id DESC LIMIT 20',['%'.$q.'%','%'.$q.'%']);
        $product=$id?\R::getRow('SELECT p.*,b.name AS naming_brand FROM product p LEFT JOIN brand b ON b.id=p.brand_id WHERE p.id=?',[$id]):null;if($id&&!$product)throw new \RuntimeException('Товар не найден.',404);
        if(empty($_SESSION['catalog_properties_csrf']))$_SESSION['catalog_properties_csrf']=bin2hex(random_bytes(32));$csrf=$_SESSION['catalog_properties_csrf'];$channels=CatalogNaming::channels();$types=CatalogProperties::types();$version=$state['version'];$error='';$notice='';$rows=[];
        foreach($channels as$channel=>$_)$rows[$channel]=$mode==='product'?['value'=>$state['overrides'][$id][$channel]??'','enabled'=>false]:['value'=>$state['rules'][$type][$channel]['template']??'','enabled'=>$state['rules'][$type][$channel]['enabled']??false];
        $draft=$state;
        if($method==='POST')try{
            if(!is_string($_POST['version']??null)||!preg_match('/^[1-9]\d{0,9}$/D',$_POST['version'])||($_POST['complete_form']??null)!=='1'||!is_array($_POST['rows']??null))throw new \InvalidArgumentException('Форма передана не полностью.');$version=(int)$_POST['version'];
            if($mode==='product'&&!$id)throw new \InvalidArgumentException('Сначала выберите товар.');$rows=[];
            foreach($channels as$channel=>$_){$r=$_POST['rows'][$channel]??null;if(!is_array($r)||!is_string($r['value']??null))throw new \InvalidArgumentException('Некорректный канал названия.');$rows[$channel]=['value'=>CatalogNaming::validateText($r['value'],$mode==='rules'),'enabled'=>($r['enabled']??'')==='1'];if($mode==='rules'&&$rows[$channel]['enabled']&&$rows[$channel]['value']==='')throw new \InvalidArgumentException('Для включения правила нужен текст.');}
            $action=$_POST['action']??'';if(!in_array($action,['save','preview'],true))throw new \InvalidArgumentException('Неверное действие.');
            if($action==='save'){$service->save($mode==='product'?'product:'.$id:'type:'.$type,$_POST['rows'],$version,(int)$_SESSION['user']['id']);$_SESSION['success']='Настройки названий сохранены. Исходные поля, URL и артикулы товаров не изменены.';redirect(ADMIN.'/catalog-names?'.http_build_query(['mode'=>$mode,'type'=>$type,'id'=>$id]));return;}
            foreach($rows as$channel=>$r)if($mode==='product'){unset($draft['overrides'][$id][$channel]);if($r['value']!=='')$draft['overrides'][$id][$channel]=$r['value'];}else$draft['rules'][$type][$channel]=['template'=>$r['value'],'enabled'=>$r['enabled']];$notice='Предпросмотр без сохранения. Каталог и выгрузка не изменены.';
        }catch(\InvalidArgumentException|\RuntimeException$e){$error=$e->getMessage();}
        $preview=[];$context=[];$legacy=[];
        if($product){$profile=(new CatalogProperties($db))->read($id);$context=CatalogNaming::context($product,$profile,(string)($product['naming_brand']??''));$templates=\R::getAll("SELECT * FROM plagins_inseo WHERE category_id=? AND tip='product' AND hide='show' ORDER BY id",[$product['category_id']]);
            $h1=trim((string)($product['seo_h1']??''));$h1=$h1!==''?\ishop\App::seoreplace($h1,$id):(!empty($templates[0]['name'])?\ishop\App::seoreplace($templates[0]['name'],$id):$product['name']);
            $legacy=['catalog'=>!empty($templates[0]['name'])?\ishop\App::seoreplace($templates[0]['name'],$id):$product['name'],'h1'=>$h1,'title'=>$product['title']?:\ishop\App::seoreplace($templates[0]['title']??'',$id),'feed'=>$product['name']];
            if(count($templates)>1)$notice.=' Найдено несколько активных шаблонов InSEO; пример использует первый по ID, проверьте настройку категории.';
            foreach($channels as$channel=>$_)$preview[$channel]=['current'=>CatalogNaming::resolve($channel,$legacy[$channel],$context,$state),'draft'=>CatalogNaming::resolve($channel,$legacy[$channel],$context,$draft)];
            if($mode==='rules'&&$type!=='*'&&$context['profile_type']!==$type)$notice.=' Пример относится к другому типу; выбранное правило на него не действует.';
        }
        $history=\R::getAll('SELECT scope,actor_id,before_json,after_json,changed_at FROM catalog_naming_revision ORDER BY id DESC LIMIT 20');$this->setMeta('Названия каталога, H1, Title и YML');$this->set(compact('mode','type','id','q','search','product','csrf','channels','types','version','error','notice','rows','preview','context','history'));
    }
}
