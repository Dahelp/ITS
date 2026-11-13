<?php
namespace app\controllers\admin;
use app\services\CatalogProperties;
use app\services\CatalogPropertyAccess;
use app\services\CatalogLegacyPreview;

final class CatalogPropertiesController extends AppController
{
    public function reviewsAction(): void
    {
        $method=$_SERVER['REQUEST_METHOD']??'GET';CatalogPropertyAccess::check($_SESSION,$method,$_POST['_csrf']??null);
        if(empty($_SESSION['catalog_properties_csrf']))$_SESSION['catalog_properties_csrf']=bin2hex(random_bytes(32));$csrf=$_SESSION['catalog_properties_csrf'];
        $service=new \app\services\CatalogReview(\R::getPDO());$installed=$service->installed();$error='';
        $status=is_string($_GET['status']??null)?substr($_GET['status'],0,16):'';$search=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,100):'';$requestedPage=is_string($_GET['page']??null)?max(1,(int)$_GET['page']):1;
        if($method==='POST')try{
            if(!$installed)throw new \RuntimeException('Очередь ещё не установлена.');
            foreach(['issue_id','version'] as $key)if(!is_string($_POST[$key]??null)||!preg_match('/^[1-9]\d{0,9}$/D',$_POST[$key]))throw new \InvalidArgumentException('Неверная версия замечания.');
            foreach(['status','review_comment','source_ref'] as $key)if(!is_string($_POST[$key]??null))throw new \InvalidArgumentException('Некорректные поля проверки.');
            $service->update((int)$_POST['issue_id'],(int)$_POST['version'],$_POST['status'],$_POST['review_comment'],$_POST['source_ref'],(int)$_SESSION['user']['id']);$_SESSION['success']='Результат проверки сохранён. Характеристики и публикация товара меняются отдельно.';redirect(ADMIN.'/catalog-properties/reviews?'.http_build_query(['q'=>$search,'status'=>$status,'page'=>$requestedPage]));return;
        }catch(\InvalidArgumentException|\RuntimeException $e){$error=$e->getMessage();}
        try{$report=$installed?$service->listing($status,$search,$requestedPage):['items'=>[],'counts'=>[],'total'=>0,'pages'=>1,'page'=>1];}catch(\InvalidArgumentException $e){$error=$e->getMessage();$status='';$report=$service->listing('',$search,$requestedPage);}
        $this->setMeta('Проверка замечаний каталога');$this->set(compact('installed','csrf','error','status','search','report'));
    }
    public function indexAction(): void
    {
        CatalogPropertyAccess::check($_SESSION,$_SERVER['REQUEST_METHOD']??'GET');
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')throw new \RuntimeException('Метод не поддерживается.',405);
        $service=new CatalogProperties(\R::getPDO());$installed=$service->installed();
        $search=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,100):'';
        $q=\R::getPDO()->prepare('SELECT id,sku,name,hide FROM product WHERE sku LIKE ? OR name LIKE ? ORDER BY id DESC LIMIT 50');
        $q->execute(['%'.$search.'%','%'.$search.'%']);$products=$q->fetchAll(\PDO::FETCH_ASSOC);
        $this->setMeta('Единые характеристики — пилот 2А');$this->set(compact('installed','search','products'));
    }
    public function qualityAction(): void
    {
        CatalogPropertyAccess::check($_SESSION,$_SERVER['REQUEST_METHOD']??'GET');
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')throw new \RuntimeException('Метод не поддерживается.',405);
        $report=\app\services\CatalogQuality::fromDatabase(\R::getPDO());
        $criteria=[];foreach(['q','type','status','source','category','stock','seo'] as $key)$criteria[$key]=is_string($_GET[$key]??null)?mb_substr(trim($_GET[$key]),0,100):'';
        $filtered=\app\services\CatalogQuality::filter($report['items'],$criteria);$total=count($filtered);$pages=max(1,(int)ceil($total/50));
        $page=min($pages,max(1,(int)(is_string($_GET['page']??null)?$_GET['page']:1)));$items=array_slice($filtered,($page-1)*50,50);
        $types=CatalogProperties::types();$this->setMeta('Очередь качества товаров');$this->set(compact('report','criteria','total','pages','page','items','types'));
    }
    public function dictionariesAction(): void
    {
        CatalogPropertyAccess::check($_SESSION,$_SERVER['REQUEST_METHOD']??'GET');
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')throw new \RuntimeException('Метод не поддерживается.',405);
        $report=\app\services\CatalogQuality::fromDatabase(\R::getPDO());$definitions=CatalogProperties::definitions();$types=CatalogProperties::types();
        $this->setMeta('Справочники и правила характеристик');$this->set(compact('report','definitions','types'));
    }
    public function dictionaryEditAction(): void
    {
        $method=$_SERVER['REQUEST_METHOD']??'GET';CatalogPropertyAccess::check($_SESSION,$method,$_POST['_csrf']??null);
        $dictionary=new \app\services\CatalogDictionary(\R::getPDO());
        if(!$dictionary->installed())throw new \RuntimeException('Редактор справочников ещё не установлен.',503);
        CatalogProperties::useDictionary(\R::getPDO());
        if(empty($_SESSION['catalog_properties_csrf']))$_SESSION['catalog_properties_csrf']=bin2hex(random_bytes(32));$csrf=$_SESSION['catalog_properties_csrf'];
        $code=is_string($_GET['code']??null)?$_GET['code']:'';
        if($code!==''&&!preg_match('/^[a-z][a-z0-9_]{1,63}$/D',$code))throw new \InvalidArgumentException('Некорректный код.',400);
        $definitions=CatalogProperties::definitions();$existing=$code!=='';
        if($existing&&!isset($definitions[$code]))throw new \RuntimeException('Свойство не найдено.',404);
        $error='';$version=$dictionary->version();$types=CatalogProperties::types();
        $definition=$existing?$definitions[$code]:['label'=>'','kind'=>'text','unit'=>'','note'=>'','options'=>[],'archived_options'=>[],'rules'=>[]];
        if($method==='POST'){
            try{
                if(!is_string($_POST['version']??null)||!preg_match('/^[1-9][0-9]{0,9}$/D',$_POST['version']))throw new \InvalidArgumentException('Неверная версия формы.');
                if($existing&&($_POST['code']??null)!==$code)throw new \InvalidArgumentException('Код существующего свойства менять нельзя.');
                $input=$_POST;$input['create']=$existing?'0':'1';$dictionary->save($input,(int)$_POST['version'],(int)$_SESSION['user']['id']);
                $_SESSION['success']='Справочник сохранён. Значения товаров не изменены.';redirect(ADMIN.'/catalog-properties/dictionary-edit?code='.rawurlencode($input['code']));return;
            }catch(\InvalidArgumentException|\RuntimeException $e){$error=$e->getMessage();}
        }
        $usage=$existing?$dictionary->usage($code):['known'=>0,'options'=>[]];$history=$dictionary->history();
        $this->setMeta('Управление справочником характеристик');$this->set(compact('code','definition','existing','error','csrf','version','types','usage','history'));
    }
    public function migrationsAction(): void
    {
        CatalogPropertyAccess::check($_SESSION,$_SERVER['REQUEST_METHOD']??'GET');
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')throw new \RuntimeException('Метод не поддерживается.',405);
        $db=\R::getPDO();$installed=(int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('catalog_migration_batch','catalog_migration_item')")->fetchColumn()===2;
        $batches=$installed?$db->query('SELECT id,state,actor_id,created_at FROM catalog_migration_batch ORDER BY id DESC LIMIT 30')->fetchAll(\PDO::FETCH_ASSOC):[];
        $id=is_string($_GET['batch']??null)&&preg_match('/^[1-9]\d{0,9}$/D',$_GET['batch'])?(int)$_GET['batch']:(int)($batches[0]['id']??0);
        $batch=null;$items=[];$counts=[];$page=1;$pages=1;$status=is_string($_GET['status']??null)?substr($_GET['status'],0,32):'';
        if($installed&&$id){
            $batch=(new \app\services\CatalogMigration($db))->status($id);
            $q=$db->prepare('SELECT status,COUNT(*) FROM catalog_migration_item WHERE batch_id=? GROUP BY status');$q->execute([$id]);$counts=$q->fetchAll(\PDO::FETCH_KEY_PAIR);
            $args=[$id];$where='batch_id=?';if($status!==''){$where.=' AND i.status=?';$args[]=$status;}
            $q=$db->prepare('SELECT COUNT(*) FROM catalog_migration_item i WHERE i.'.$where);$q->execute($args);$pages=max(1,(int)ceil((int)$q->fetchColumn()/50));$page=min($pages,max(1,(int)(is_string($_GET['page']??null)?$_GET['page']:1)));$offset=($page-1)*50;
            $q=$db->prepare('SELECT i.*,p.sku,p.name FROM catalog_migration_item i LEFT JOIN product p ON p.id=i.product_id WHERE i.'.$where.' ORDER BY i.id LIMIT 50 OFFSET '.$offset);$q->execute($args);$items=$q->fetchAll(\PDO::FETCH_ASSOC);
        }
        $this->setMeta('Журнал переноса характеристик');$this->set(compact('installed','batches','batch','items','counts','page','pages','status'));
    }
    public function editAction(): void
    {
        $method=$_SERVER['REQUEST_METHOD']??'GET';
        CatalogPropertyAccess::check($_SESSION,$method,$_POST['_csrf']??null);
        $id=$this->getRequestID();
        $product=\R::getRow('SELECT id,sku,name,alias,brand_id FROM product WHERE id=?',[$id]);
        if(!$product)throw new \RuntimeException('Товар не найден.',404);
        $service=new CatalogProperties(\R::getPDO());
        if(!$service->installed())throw new \RuntimeException('Пилотная схема ещё не установлена.',503);
        if(empty($_SESSION['catalog_properties_csrf']))$_SESSION['catalog_properties_csrf']=bin2hex(random_bytes(32));
        $csrf=$_SESSION['catalog_properties_csrf'];$error='';$notice='';
        $saved=$service->read($id);$form=$saved;
        $legacy=CatalogLegacyPreview::fromDatabase(\R::getPDO(),$id);
        if($method==='GET'&&($_GET['preview']??'')==='1'){$form=$legacy['proposed'];$form['version']=$saved['version'];$notice='Предложения перенесены только в форму. Рабочие данные не изменены. Проверьте источники и непренесённые строки ниже.';}
        if($method==='POST'){
            $action=$_POST['action']??'save';
            try{
                $version=$_POST['version']??'';
                if(!is_string($version)||!preg_match('/^\d{1,9}$/D',$version))throw new \InvalidArgumentException('Неверная версия формы.');
                if($action==='restore'){
                    $revision=$_POST['revision_id']??'';if(!is_string($revision)||!preg_match('/^[1-9]\d{0,17}$/D',$revision))throw new \InvalidArgumentException('Неверный номер изменения.');
                    $service->restore($id,(int)$revision,(int)$version,(int)$_SESSION['user']['id']);
                }elseif($action==='save'){
                    $input=['type'=>$_POST['type']??'unclassified','values'=>$_POST['values']??[],'sizes'=>$_POST['sizes']??[],'ratings'=>$_POST['ratings']??[],'dictionary_version'=>$_POST['dictionary_version']??0];
                    $service->save($id,(int)$version,$input,(int)$_SESSION['user']['id']);
                }else throw new \InvalidArgumentException('Неизвестное действие.');
                $_SESSION['success']='Характеристики сохранены. Подтверждённые поля используются в карточке и при следующем обновлении YML. Исторические и отложенные значения не подтверждаются автоматически.';
                redirect(ADMIN.'/catalog-properties/edit?id='.$id);return;
            }catch(\InvalidArgumentException|\RuntimeException$e){$error=$e->getMessage();if($action==='save'){$form=['type'=>is_string($_POST['type']??null)?$_POST['type']:'unclassified','values'=>is_array($_POST['values']??null)?$_POST['values']:[],'sizes'=>is_array($_POST['sizes']??null)?$_POST['sizes']:[],'ratings'=>is_array($_POST['ratings']??null)?$_POST['ratings']:[],'version'=>is_string($_POST['version']??null)?$_POST['version']:$saved['version']];}}
        }
        $dictionary=new \app\services\CatalogDictionary(\R::getPDO());$dictionaryVersion=$dictionary->installed()?$dictionary->version():0;
        $sizeSearch=(new \app\services\CatalogSizeSearch(\R::getPDO()))->load($id)[$id]['relations']??[];
        $definitions=CatalogProperties::definitions();$types=CatalogProperties::types();$history=$service->history($id);
        $q=\R::getPDO()->prepare('SELECT pa.attribute_id,pa.attribute_group_id,a.attribute_name,pa.attribute_text FROM product_attribute pa JOIN attribute a ON a.id=pa.attribute_id WHERE pa.product_id=? ORDER BY pa.id');$q->execute([$id]);$publication=\app\services\CatalogPublishedAttributes::project($q->fetchAll(\PDO::FETCH_ASSOC),$saved);
        $brand=(string)\R::getCell('SELECT name FROM brand WHERE id=?',[$product['brand_id']]);
        try{$issues=CatalogProperties::normalize($form)['issues'];}catch(\InvalidArgumentException$e){$issues=[$e->getMessage()];}
        $this->setMeta('Характеристики '.$product['sku']);$this->set(compact('id','product','brand','definitions','types','form','saved','legacy','history','csrf','error','notice','issues','publication','dictionaryVersion','sizeSearch'));
    }
}
