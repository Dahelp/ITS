<?php
namespace app\services;

/** Category presentation overrides, independent from product facts and SEO links. */
final class CatalogDisplay
{
    private \PDO $db;
    public function __construct(\PDO $db) {$this->db=$db;}

    public static function install(\PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS catalog_category_display (category_id INT UNSIGNED PRIMARY KEY, columns_count TINYINT UNSIGNED NOT NULL DEFAULT 0, groups_json TEXT NULL, version INT UNSIGNED NOT NULL, updated_by INT UNSIGNED NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function read(int $categoryId): array
    {
        $defaults=['columns'=>4,'own_columns'=>0,'groups'=>null,'version'=>0];
        try {
            $q=$this->db->prepare('SELECT * FROM catalog_category_display WHERE category_id IN (0,?)');$q->execute([$categoryId]);
            $rows=$q->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
        } catch(\PDOException $e) {
            if($e->getCode()==='42S02')return $defaults;
            throw $e;
        }
        $global=(int)($rows[0]['columns_count']??4);
        $row=$rows[$categoryId]??[];$own=(int)($row['columns_count']??0);
        $groups=isset($row['groups_json'])?json_decode($row['groups_json'],true,512,JSON_THROW_ON_ERROR):null;
        return ['columns'=>in_array($own,[3,4],true)?$own:(in_array($global,[3,4],true)?$global:4),'own_columns'=>$own,'groups'=>$groups,'version'=>(int)($row['version']??0)];
    }

    public function save(int $categoryId,int $version,int $columns,?array $groups,int $actor): void
    {
        if($categoryId<0||$version<0||$actor<1||!in_array($columns,$categoryId===0?[3,4]:[0,3,4],true))throw new \InvalidArgumentException('Некорректные настройки сетки.');
        if($categoryId===0&&$groups!==null)throw new \InvalidArgumentException('Группы задаются отдельно в каждой категории.');
        if($categoryId>0){$q=$this->db->prepare('SELECT id FROM category WHERE id=?');$q->execute([$categoryId]);if(!$q->fetchColumn())throw new \InvalidArgumentException('Категория не найдена.');}
        if($groups!==null){
            if(count($groups)>200||CatalogFacets::ids($groups)!==$groups)throw new \InvalidArgumentException('Некорректный список групп.');
            if($groups){$q=$this->db->query('SELECT id FROM attribute_group WHERE id IN ('.implode(',',$groups).')');if(count($q->fetchAll(\PDO::FETCH_COLUMN))!==count($groups))throw new \InvalidArgumentException('Одна из групп больше не существует.');}
        }
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare('SELECT version FROM catalog_category_display WHERE category_id=? FOR UPDATE');$q->execute([$categoryId]);$current=$q->fetchColumn();
            if(($current===false?0:(int)$current)!==$version)throw new \RuntimeException('Настройки уже изменены. Обновите страницу перед сохранением.',409);
            $json=$groups===null?null:json_encode($groups,JSON_THROW_ON_ERROR);
            if($current===false){$q=$this->db->prepare('INSERT INTO catalog_category_display(category_id,columns_count,groups_json,version,updated_by,updated_at) VALUES(?,?,?,1,?,NOW())');$q->execute([$categoryId,$columns,$json,$actor]);}
            else{$q=$this->db->prepare('UPDATE catalog_category_display SET columns_count=?,groups_json=?,version=version+1,updated_by=?,updated_at=NOW() WHERE category_id=?');$q->execute([$columns,$json,$actor,$categoryId]);}
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public static function orderedGroups(array $available,?array $order): array
    {
        if($order===null)return $available;
        $result=[];foreach($order as$id)if(isset($available[$id]))$result[$id]=$available[$id];return $result;
    }
}
