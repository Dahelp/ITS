<?php
namespace app\services;

/** Editorial workflow only. Never changes product facts, publication or feeds. */
final class CatalogReview
{
    public const STATUSES=['open'=>'К проверке','deferred'=>'Отложено','resolved'=>'Проверено'];
    public function __construct(private \PDO $db) {}
    public static function install(\PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS catalog_review_issue (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED NOT NULL,issue_key VARCHAR(100) NOT NULL,category VARCHAR(64) NOT NULL,origin_note MEDIUMTEXT NOT NULL,status VARCHAR(16) NOT NULL,version INT UNSIGNED NOT NULL DEFAULT 1,review_comment TEXT NOT NULL,source_ref VARCHAR(1000) NOT NULL DEFAULT '',updated_by INT UNSIGNED NOT NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY product_issue(product_id,issue_key),KEY status_product(status,product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS catalog_review_event (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,issue_id BIGINT UNSIGNED NOT NULL,actor_id INT UNSIGNED NOT NULL,before_json MEDIUMTEXT NOT NULL,after_json MEDIUMTEXT NOT NULL,created_at DATETIME NOT NULL,KEY issue_history(issue_id,id),FOREIGN KEY(issue_id) REFERENCES catalog_review_issue(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    private function query(string $sql,array $args=[]): \PDOStatement {$q=$this->db->prepare($sql);$q->execute($args);return $q;}
    public function installed(): bool {try{$this->db->query('SELECT id FROM catalog_review_issue LIMIT 1');$this->db->query('SELECT id FROM catalog_review_event LIMIT 1');return true;}catch(\PDOException $e){return false;}}
    public static function validate(string $status,string $comment,string $source): array
    {
        $comment=trim($comment);$source=trim($source);
        if(!isset(self::STATUSES[$status])||strlen($comment)>12000||strlen($source)>1000||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$comment.$source))throw new \InvalidArgumentException('Некорректный статус, комментарий или источник.');
        if($status==='resolved'&&($comment===''||$source===''))throw new \InvalidArgumentException('Для проверки укажите результат и источник: фото, документ или ссылку.');
        return [$status,$comment,$source];
    }
    public function update(int $id,int $version,string $status,string $comment,string $source,int $actor): void
    {
        [$status,$comment,$source]=self::validate($status,$comment,$source);if($id<=0||$version<=0||$actor<=0)throw new \InvalidArgumentException('Некорректная карточка проверки.');
        $this->db->beginTransaction();try{
            $before=$this->query('SELECT * FROM catalog_review_issue WHERE id=? FOR UPDATE',[$id])->fetch(\PDO::FETCH_ASSOC);
            if(!$before||(int)$before['version']!==$version)throw new \RuntimeException('Замечание уже изменено. Обновите страницу.');
            $this->query('UPDATE catalog_review_issue SET status=?,review_comment=?,source_ref=?,version=version+1,updated_by=?,updated_at=NOW() WHERE id=?',[$status,$comment,$source,$actor,$id]);
            $after=$this->query('SELECT * FROM catalog_review_issue WHERE id=?',[$id])->fetch(\PDO::FETCH_ASSOC);
            $this->query('INSERT INTO catalog_review_event(issue_id,actor_id,before_json,after_json,created_at) VALUES(?,?,?,?,NOW())',[$id,$actor,json_encode($before,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($after,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function seed(array $items,int $actor): int
    {
        if($actor<=0)throw new \InvalidArgumentException('Укажите автора.');$seen=[];foreach($items as $r){$id=$r['product_id']??0;if(!is_int($id)||$id<=0||isset($seen[$id])||!is_string($r['note']??null)||strlen($r['note'])>50000)throw new \InvalidArgumentException('Некорректный список замечаний.');$seen[$id]=true;}
        $count=0;$this->db->beginTransaction();try{foreach($items as $r){
            if(!$this->query('SELECT id FROM product WHERE id=?',[$r['product_id']])->fetchColumn())throw new \RuntimeException('Товар отсутствует');
            if($this->query('SELECT id FROM catalog_review_issue WHERE product_id=? AND issue_key=?',[$r['product_id'],'foundation-20261112'])->fetchColumn())continue;
            $this->query("INSERT INTO catalog_review_issue(product_id,issue_key,category,origin_note,status,version,review_comment,updated_by,created_at,updated_at) VALUES(?,'foundation-20261112',?,?,'deferred',1,?,?,NOW(),NOW())",[$r['product_id'],$r['category'],$r['note'],'Отложено по решению владельца 12.11.2026. Приоритет — запуск рабочего процесса.',$actor]);$id=(int)$this->db->lastInsertId();$after=$this->query('SELECT * FROM catalog_review_issue WHERE id=?',[$id])->fetch(\PDO::FETCH_ASSOC);$this->query('INSERT INTO catalog_review_event(issue_id,actor_id,before_json,after_json,created_at) VALUES(?,?,?, ?,NOW())',[$id,$actor,'{}',json_encode($after,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$count++;
        }$this->db->commit();return $count;}catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function listing(string $status='',string $search='',int $page=1): array
    {
        if($status!==''&&!isset(self::STATUSES[$status]))throw new \InvalidArgumentException('Неизвестный статус.');$where=[];$args=[];
        if($status!==''){$where[]='i.status=?';$args[]=$status;}if($search!==''){$where[]='(p.sku LIKE ? OR p.name LIKE ?)';$args[]='%'.$search.'%';$args[]='%'.$search.'%';}$where=$where?' WHERE '.implode(' AND ',$where):'';
        $total=(int)$this->query('SELECT COUNT(*) FROM catalog_review_issue i LEFT JOIN product p ON p.id=i.product_id'.$where,$args)->fetchColumn();$pages=max(1,(int)ceil($total/25));$page=min($pages,max(1,$page));$offset=($page-1)*25;
        $items=$this->query('SELECT i.*,p.sku,p.name FROM catalog_review_issue i LEFT JOIN product p ON p.id=i.product_id'.$where.' ORDER BY i.product_id LIMIT 25 OFFSET '.$offset,$args)->fetchAll(\PDO::FETCH_ASSOC);$counts=$this->query('SELECT status,COUNT(*) FROM catalog_review_issue GROUP BY status')->fetchAll(\PDO::FETCH_KEY_PAIR);
        return compact('items','counts','total','pages','page');
    }
}
