<?php
namespace app\services;

/** Navigation and read-only aggregation over the existing catalog editors. */
final class CatalogWorkflow
{
    public const HISTORY=['properties'=>'Характеристики','dictionary'=>'Справочники','facets'=>'Связи фильтров','names'=>'Названия','reviews'=>'Проверки замечаний','display'=>'Последнее сохранение вывода категории'];
    public function __construct(private \PDO $db) {}
    private function query(string $sql,array $args=[]): \PDOStatement {$q=$this->db->prepare($sql);$q->execute($args);return$q;}
    private function exists(string $table): bool {return(bool)$this->query('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table])->fetchColumn();}

    public function overview(array $quality): array
    {
        $reviews=$this->exists('catalog_review_issue')?$this->db->query('SELECT status,COUNT(*) FROM catalog_review_issue GROUP BY status')->fetchAll(\PDO::FETCH_KEY_PAIR):[];
        return ['summary'=>$quality['summary'],'reviews'=>$reviews,'landings'=>$this->landings(),'generated_at'=>date('Y-m-d H:i:s')];
    }

    /** Existing landing maps without a visible matching product; no automatic fixes. */
    public function landings(): array
    {
        $maps=$this->db->query("SELECT s.id,s.category_id,s.attr_value_id,c.name AS category_name,c.alias AS category_alias,a.value,a.alias AS filter_alias FROM attribute_value_category_canonical s JOIN category c ON c.id=s.category_id JOIN attribute_value a ON a.id=s.attr_value_id WHERE s.is_active=1 AND s.mode='landing' AND c.hide='show' AND a.hide='show' ORDER BY c.name,a.value,s.id")->fetchAll(\PDO::FETCH_ASSOC);
        $children=[];foreach($this->db->query('SELECT id,parent_id FROM category')as$c)$children[(int)$c['parent_id']][]=(int)$c['id'];
        $descendants=static function(int $id,array $seen=[])use(&$descendants,$children):array {if(isset($seen[$id]))return[];$seen[$id]=true;$out=[$id];foreach($children[$id]??[]as$child)$out=array_merge($out,$descendants($child,$seen));return$out;};
        $relation=CatalogFacetBridge::relationSql();$edges=[];
        foreach($this->db->query("SELECT DISTINCT ap.attr_id,p.category_id FROM $relation ap JOIN product p ON p.id=ap.product_id WHERE p.hide='show'")as$edge)$edges[(int)$edge['attr_id']][(int)$edge['category_id']]=true;
        $tree=[];$issues=[];
        foreach($maps as$row){$cid=(int)$row['category_id'];$tree[$cid]??=$descendants($cid);$found=false;foreach($tree[$cid]as$child)if(isset($edges[(int)$row['attr_value_id']][$child])){$found=true;break;}if(!$found){$row['url']='https://its-center.ru/category/'.$row['category_alias'].'/'.$row['filter_alias'];$issues[]=$row;}}
        return ['checked'=>count($maps),'issues'=>$issues];
    }

    public static function productLinks(int $id,string $sku,string $name,string $alias=''): array
    {
        if($id<=0)throw new \InvalidArgumentException('Неверный товар.');
        $q=rawurlencode($sku!==''?$sku:$name);
        $links=['work'=>['Рабочая карточка','/catalog-workflow/product?id='.$id],'product'=>['Основная карточка, SEO и фото','/product/edit?id='.$id],'properties'=>['Характеристики и размеры','/catalog-properties/edit?id='.$id],'names'=>['Названия и предпросмотр','/catalog-names?mode=product&id='.$id],'quality'=>['Перепроверить качество','/catalog-properties/quality?q='.$q],'reviews'=>['Замечания и источники','/catalog-properties/reviews?q='.$q],'feed'=>['Предпросмотр YML спецшин','/plagins/yandex-tovars?preview=1&q='.$q],'history'=>['История товара','/catalog-workflow/history?product_id='.$id]];
        if($alias!=='')$links['public']=['Открыть на сайте','https://its-center.ru/product/'.str_replace('%2F','/',rawurlencode($alias))];
        return$links;
    }

    public function history(string $kind='',int $productId=0,int $page=1): array
    {
        if($kind!==''&&!isset(self::HISTORY[$kind]))throw new \InvalidArgumentException('Неизвестный раздел истории.',400);
        $sources=[
            'properties'=>['catalog_property_revision',"SELECT 'properties' kind,r.id event_id,r.product_id product_id,r.actor_id,r.changed_at,CAST(r.product_id AS CHAR) subject FROM catalog_property_revision r"],
            'dictionary'=>['catalog_dictionary_revision',"SELECT 'dictionary' kind,r.id event_id,NULL product_id,r.actor_id,r.changed_at,r.property_code subject FROM catalog_dictionary_revision r"],
            'facets'=>['catalog_facet_binding_revision',"SELECT 'facets' kind,r.id event_id,NULL product_id,r.actor_id,r.changed_at,CAST(r.group_id AS CHAR) subject FROM catalog_facet_binding_revision r"],
            'names'=>['catalog_naming_revision',"SELECT 'names' kind,r.id event_id,CASE WHEN r.scope LIKE 'product:%' THEN CAST(SUBSTRING(r.scope,9) AS UNSIGNED) ELSE NULL END product_id,r.actor_id,r.changed_at,r.scope subject FROM catalog_naming_revision r"],
            'reviews'=>['catalog_review_event',"SELECT 'reviews' kind,r.id event_id,i.product_id,r.actor_id,r.created_at changed_at,CAST(r.issue_id AS CHAR) subject FROM catalog_review_event r JOIN catalog_review_issue i ON i.id=r.issue_id"],
            'display'=>['catalog_category_display',"SELECT 'display' kind,r.version event_id,NULL product_id,r.updated_by actor_id,r.updated_at changed_at,CAST(r.category_id AS CHAR) subject FROM catalog_category_display r"],
        ];
        $selects=[];$available=[];foreach($sources as$key=>[$table,$sql])if($this->exists($table)){$available[]=$key;if($kind===''||$kind===$key)$selects[]=$sql;}
        if(!$selects)return ['items'=>[],'total'=>0,'pages'=>1,'page'=>1,'available'=>$available];
        $union='('.implode(' UNION ALL ',$selects).') events';$where=$productId>0?' WHERE events.product_id=?':'';$args=$productId>0?[$productId]:[];
        $total=(int)$this->query('SELECT COUNT(*) FROM '.$union.$where,$args)->fetchColumn();$pages=max(1,(int)ceil($total/50));$page=min($pages,max(1,$page));$offset=($page-1)*50;
        $items=$this->query('SELECT events.*,p.sku,p.name FROM '.$union.' LEFT JOIN product p ON p.id=events.product_id'.$where.' ORDER BY events.changed_at DESC,events.kind,events.event_id DESC LIMIT 50 OFFSET '.$offset,$args)->fetchAll(\PDO::FETCH_ASSOC);
        foreach($items as&$row){$pid=(int)$row['product_id'];$row['url']=match($row['kind']){'properties'=>'/catalog-properties/edit?id='.$pid,'dictionary'=>'/catalog-properties/dictionary-edit?code='.rawurlencode($row['subject']),'facets'=>'/catalog-facet-bindings?group='.(int)$row['subject'],'names'=>$pid?'/catalog-names?mode=product&id='.$pid:'/catalog-names?mode=rules&type='.rawurlencode(substr($row['subject'],5)),'reviews'=>'/catalog-properties/reviews?q='.rawurlencode($row['sku']?:$row['name']??''),'display'=>'/catalog-display?id='.(int)$row['subject']};}unset($row);
        return compact('items','total','pages','page','available');
    }
}
