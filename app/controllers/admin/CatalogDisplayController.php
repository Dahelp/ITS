<?php
namespace app\controllers\admin;
use app\services\CatalogDisplay;
use app\services\CatalogPropertyAccess;
use app\services\CatalogFacets;

final class CatalogDisplayController extends AppController
{
    public function indexAction(): void
    {
        $method=$_SERVER['REQUEST_METHOD']??'GET';CatalogPropertyAccess::check($_SESSION,$method,$_POST['_csrf']??null);
        if(empty($_SESSION['catalog_properties_csrf']))$_SESSION['catalog_properties_csrf']=bin2hex(random_bytes(32));
        $csrf=$_SESSION['catalog_properties_csrf'];$service=new CatalogDisplay(\R::getPDO());$error='';
        $raw=$method==='POST'?($_POST['category_id']??null):($_GET['id']??'0');
        if(!is_scalar($raw)||!preg_match('/^(0|[1-9][0-9]{0,8})$/D',(string)$raw))throw new \InvalidArgumentException('Некорректная категория.',400);
        $categoryId=(int)$raw;
        $categories=\R::getAll('SELECT id,name,parent_id FROM category ORDER BY name');
        $category=null;foreach($categories as$row)if((int)$row['id']===$categoryId)$category=$row;
        if($categoryId&&!$category)throw new \RuntimeException('Категория не найдена.',404);
        $groups=\R::getAll('SELECT id,title FROM attribute_group ORDER BY title');
        if($method==='POST')try{
            foreach(['version','columns']as$key)if(!is_string($_POST[$key]??null)||!preg_match('/^[0-9]{1,9}$/D',$_POST[$key]))throw new \InvalidArgumentException('Некорректные параметры формы.');
            if(!in_array($_POST['group_mode']??null,['auto','custom'],true))throw new \InvalidArgumentException('Выберите режим групп фильтров.');
            $selected=null;
            if($categoryId&&$_POST['group_mode']==='custom'){
                $selected=CatalogFacets::ids($_POST['groups']??[]);$positions=$_POST['positions']??[];
                if(!is_array($positions))throw new \InvalidArgumentException('Некорректный порядок групп.');
                foreach($selected as$id)if(!is_string($positions[$id]??null)||!preg_match('/^[0-9]{1,5}$/D',$positions[$id]))throw new \InvalidArgumentException('Порядок: число от 0 до 99999.');
                usort($selected,static fn($a,$b)=>(int)$positions[$a]<=>(int)$positions[$b]?:$a<=>$b);
            }
            $service->save($categoryId,(int)$_POST['version'],(int)$_POST['columns'],$selected,(int)$_SESSION['user']['id']);
            $_SESSION['success']='Настройки вывода каталога сохранены.';redirect(ADMIN.'/catalog-display?id='.$categoryId);return;
        }catch(\InvalidArgumentException|\RuntimeException $e){$error=$e->getMessage();}
        $settings=$service->read($categoryId);
        $automaticGroups=[];
        if($categoryId){$tree=(new \app\models\Category())->getIds($categoryId);$categoryIds=CatalogFacets::ids(($tree?:'').$categoryId);$automaticGroups=array_map('intval',\R::getCol('SELECT DISTINCT group_id FROM attribute_category WHERE category_id IN ('.implode(',',$categoryIds).')'));}
        $this->setMeta('Вывод каталога: сетка и фильтры');$this->set(compact('csrf','error','categoryId','categories','category','groups','settings','automaticGroups'));
    }
}
