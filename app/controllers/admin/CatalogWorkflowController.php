<?php
namespace app\controllers\admin;
use app\services\CatalogPropertyAccess;
use app\services\CatalogWorkflow;

final class CatalogWorkflowController extends AppController
{
    private function guard(): void
    {
        $method=$_SERVER['REQUEST_METHOD']??'GET';CatalogPropertyAccess::check($_SESSION,$method,$_POST['_csrf']??null);
        if($method!=='GET')throw new \RuntimeException('Рабочий центр доступен только для просмотра.',405);
    }
    private function text(string $key): string {return is_string($_GET[$key]??null)?mb_substr(trim($_GET[$key]),0,100):'';}
    private function number(string $key): int {$s=$this->text($key);if($s==='')return 0;if(!preg_match('/^[1-9]\d{0,8}$/D',$s))throw new \InvalidArgumentException('Неверный идентификатор.',400);return(int)$s;}
    public function indexAction(): void
    {
        $this->guard();$db=\R::getPDO();$quality=\app\services\CatalogQuality::fromDatabase($db);$overview=(new CatalogWorkflow($db))->overview($quality);$q=$this->text('q');
        $products=$q!==''?\app\services\CatalogQuality::filter($quality['items'],['q'=>$q]):[];$matched=count($products);$products=array_slice($products,0,30);
        $feeds=\app\services\YandexProductsCabinet::snapshot(WWW);$this->setMeta('Рабочий центр каталога');$this->set(compact('overview','q','products','matched','feeds'));
    }
    public function productAction(): void
    {
        $this->guard();$id=$this->number('id');$quality=\app\services\CatalogQuality::fromDatabase(\R::getPDO());$item=null;foreach($quality['items']as$row)if($row['id']===$id){$item=$row;break;}
        if(!$item)throw new \RuntimeException('Товар не найден.',404);
        $links=CatalogWorkflow::productLinks($id,$item['sku'],$item['name'],$item['alias']);$history=(new CatalogWorkflow(\R::getPDO()))->history('',$id);$types=\app\services\CatalogProperties::types();
        $this->setMeta('Рабочая карточка: '.$item['sku']);$this->set(compact('item','links','history','types'));
    }
    public function historyAction(): void
    {
        $this->guard();$kind=$this->text('kind');$productId=$this->number('product_id');$page=max(1,$this->number('page'));$history=(new CatalogWorkflow(\R::getPDO()))->history($kind,$productId,$page);
        $this->setMeta('История работы с каталогом');$this->set(compact('kind','productId','history'));
    }
    public function updatesAction(): void
    {
        $this->guard();$health=\app\services\CatalogUpdateHealth::snapshot(\R::getPDO(),ROOT);$this->setMeta('Обновление каталога и YML');$this->set(compact('health'));
    }
    public function landingsAction(): void
    {
        $this->guard();$landings=(new CatalogWorkflow(\R::getPDO()))->landings();$this->setMeta('Проверка SEO-посадочных');$this->set(compact('landings'));
    }
}
