<?php
namespace app\services;

/** Per-product transactions; journal and values commit together. No legacy/public writes. */
final class CatalogMigration
{
    public function __construct(private \PDO $db){CatalogProperties::useDictionary($db);}
    private function q(string $sql,array $args=[]): \PDOStatement{$s=$this->db->prepare($sql);$s->execute($args);return $s;}
    public static function install(\PDO $db): void
    {
        if($db->inTransaction())throw new \RuntimeException('DDL requires no active transaction.');
        if(!(new CatalogProperties($db))->installed())throw new \RuntimeException('Сначала установите основу характеристик.');
        $engines=$db->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('product','product_attribute','attribute_product','attribute_value','brand')")->fetchAll(\PDO::FETCH_KEY_PAIR);
        if(count($engines)!==5||array_filter($engines,static fn($e)=>strtolower($e)!=='innodb'))throw new \RuntimeException('Исходные таблицы должны поддерживать блокировки InnoDB.');
        $db->exec("CREATE TABLE IF NOT EXISTS catalog_migration_batch (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, token CHAR(32) NOT NULL UNIQUE, plan_sha256 CHAR(64) NOT NULL, rules_fingerprint CHAR(64) NOT NULL, actor_id INT UNSIGNED NOT NULL, state VARCHAR(32) NOT NULL, counts_json TEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS catalog_migration_item (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_id BIGINT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, status VARCHAR(32) NOT NULL, source_fingerprint CHAR(64) NOT NULL, proposed_json MEDIUMTEXT NULL, reason TEXT NOT NULL, revision_id BIGINT UNSIGNED NULL, applied_version INT UNSIGNED NULL, rollback_revision_id BIGINT UNSIGNED NULL, updated_at DATETIME NOT NULL, UNIQUE KEY batch_product(batch_id,product_id), KEY batch_status(batch_id,status,id), FOREIGN KEY(batch_id) REFERENCES catalog_migration_batch(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    public function create(array $plan,int $actorId): int
    {
        if($actorId<=0||($plan['format_version']??null)!==2||($plan['mode']??'')!=='dry_run_only'||!hash_equals(CatalogMigrationSource::rulesFingerprint(),$plan['rules_fingerprint']??''))throw new \InvalidArgumentException('Неверный формат плана или изменились правила.');
        if(!is_array($plan['items']??null)||!$plan['items'])throw new \InvalidArgumentException('Пустой план.');
        $seen=[];foreach($plan['items'] as $r){$id=$r['product_id']??0;if(!is_int($id)||$id<=0||isset($seen[$id])||!in_array($r['decision']??'',['draft_candidate','hold','skip_saved'],true)||!preg_match('/^[a-f0-9]{64}$/D',$r['source_fingerprint']??''))throw new \InvalidArgumentException('Неверная строка плана.');$seen[$id]=true;if($r['decision']==='draft_candidate')CatalogProperties::normalize($r['proposed']);}
        if($this->db->inTransaction())throw new \RuntimeException('Migration requires own transaction.');
        $this->db->beginTransaction();
        try{
            $this->q('INSERT INTO catalog_migration_batch(token,plan_sha256,rules_fingerprint,actor_id,state,counts_json,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())',[bin2hex(random_bytes(16)),hash('sha256',json_encode($plan,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)),$plan['rules_fingerprint'],$actorId,'prepared','{}']);$batch=(int)$this->db->lastInsertId();
            foreach($plan['items'] as $r)$this->q('INSERT INTO catalog_migration_item(batch_id,product_id,status,source_fingerprint,proposed_json,reason,updated_at) VALUES(?,?,?,?,?,?,NOW())',[$batch,$r['product_id'],['draft_candidate'=>'pending','hold'=>'held','skip_saved'=>'skip_saved'][$r['decision']],$r['source_fingerprint'],isset($r['proposed'])?json_encode($r['proposed'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR):null,implode('; ',$r['reasons']??[])]);
            $this->refresh($batch,'prepared');$this->db->commit();return $batch;
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    private function refresh(int $batch,string $state): array
    {
        $counts=$this->q('SELECT status,COUNT(*) FROM catalog_migration_item WHERE batch_id=? GROUP BY status',[$batch])->fetchAll(\PDO::FETCH_KEY_PAIR);$counts=array_map('intval',$counts);
        $this->q('UPDATE catalog_migration_batch SET state=?,counts_json=?,updated_at=NOW() WHERE id=?',[$state,json_encode($counts,JSON_THROW_ON_ERROR),$batch]);return $counts;
    }
    public function status(int $batch): array
    {
        $r=$this->q('SELECT * FROM catalog_migration_batch WHERE id=?',[$batch])->fetch(\PDO::FETCH_ASSOC);if(!$r)throw new \InvalidArgumentException('Партия не найдена.');$r['counts']=json_decode($r['counts_json'],true,512,JSON_THROW_ON_ERROR);unset($r['counts_json']);return $r;
    }
    private function lock(): void
    {if($this->db->inTransaction()||(int)$this->q("SELECT GET_LOCK('catalog-properties-migration',0)")->fetchColumn()!==1)throw new \RuntimeException('Другая операция переноса уже выполняется.');}
    private function unlock(): void{$this->db->query("SELECT RELEASE_LOCK('catalog-properties-migration')");}
    public function run(int $batch,int $limit=0): array
    {
        if($limit<0)throw new \InvalidArgumentException('Неверный лимит.');$this->lock();
        try{
            $b=$this->status($batch);if(in_array($b['state'],['rolled_back','partial_rollback'],true))throw new \RuntimeException('Возвращённую партию нельзя повторно применить.');
            if(!hash_equals(CatalogMigrationSource::rulesFingerprint(),$b['rules_fingerprint']))throw new \RuntimeException('Правила изменились; пересоздайте план.');
            $this->refresh($batch,'running');$items=$this->q("SELECT id FROM catalog_migration_item WHERE batch_id=? AND status IN ('pending','error') ORDER BY id",[$batch])->fetchAll(\PDO::FETCH_COLUMN);if($limit)$items=array_slice($items,0,$limit);
            foreach($items as $id){
                try{$this->applyItem($batch,(int)$id,(int)$b['actor_id'],$b['rules_fingerprint']);}
                catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();$this->q("UPDATE catalog_migration_item SET status='error',reason=?,updated_at=NOW() WHERE id=?",['Операция отменена: '.get_class($e).' / '.$e->getCode(),$id]);$this->refresh($batch,'failed');throw $e;}
            }
            $pending=(int)$this->q("SELECT COUNT(*) FROM catalog_migration_item WHERE batch_id=? AND status IN ('pending','error')",[$batch])->fetchColumn();$this->refresh($batch,$pending?'partial':'completed');return $this->status($batch);
        }finally{$this->unlock();}
    }
    private function applyItem(int $batch,int $itemId,int $actor,string $rules): void
    {
        if(!hash_equals(CatalogMigrationSource::rulesFingerprint(),$rules))throw new \RuntimeException('Правила изменились во время переноса.');
        $this->db->beginTransaction();
        $item=$this->q('SELECT * FROM catalog_migration_item WHERE id=? AND batch_id=? FOR UPDATE',[$itemId,$batch])->fetch(\PDO::FETCH_ASSOC);if(!$item||!in_array($item['status'],['pending','error'],true)){$this->db->commit();return;}
        $source=CatalogMigrationSource::capture($this->db,(int)$item['product_id']);
        $existing=$this->q('SELECT version FROM catalog_product_profile WHERE product_id=? FOR UPDATE',[$item['product_id']])->fetchColumn();
        if($existing!==false){$this->finishItem($itemId,'skip_existing','Новый профиль уже существует; оставлен без изменений');$this->db->commit();return;}
        if(!hash_equals($item['source_fingerprint'],CatalogMigrationSource::fingerprint($source))){$this->finishItem($itemId,'skip_changed','Исходные данные изменились после плана');$this->db->commit();return;}
        $legacy=CatalogMigrationSource::preview($source);$decision=CatalogMigrationPlan::decide($legacy,false);$input=json_decode($item['proposed_json'],true,512,JSON_THROW_ON_ERROR);
        if($decision['decision']!=='draft_candidate'||CatalogProperties::normalize($input)!==CatalogProperties::normalize($decision['proposed'])){$this->finishItem($itemId,'skip_payload','План не совпадает с повторным разбором источника');$this->db->commit();return;}
        $version=(new CatalogProperties($this->db))->saveInTransaction((int)$item['product_id'],0,$input,$actor);$revision=(int)$this->db->lastInsertId();
        $this->q("UPDATE catalog_migration_item SET status='applied',reason='',revision_id=?,applied_version=?,updated_at=NOW() WHERE id=?",[$revision,$version,$itemId]);$this->db->commit();
    }
    private function finishItem(int $id,string $status,string $reason): void
    {$this->q('UPDATE catalog_migration_item SET status=?,reason=?,updated_at=NOW() WHERE id=?',[$status,$reason,$id]);}
    public function rollback(int $batch,int $actor): array
    {
        if($actor<=0)throw new \InvalidArgumentException('Укажите автора.');$this->lock();
        try{
            $this->status($batch);$items=$this->q("SELECT id FROM catalog_migration_item WHERE batch_id=? AND status='applied' ORDER BY id DESC",[$batch])->fetchAll(\PDO::FETCH_COLUMN);
            foreach($items as $id){
                $this->db->beginTransaction();
                try{
                    $item=$this->q('SELECT * FROM catalog_migration_item WHERE id=? FOR UPDATE',[$id])->fetch(\PDO::FETCH_ASSOC);
                    $version=$this->q('SELECT version FROM catalog_product_profile WHERE product_id=? FOR UPDATE',[$item['product_id']])->fetchColumn();
                    $latest=$this->q('SELECT id FROM catalog_property_revision WHERE product_id=? ORDER BY id DESC LIMIT 1',[$item['product_id']])->fetchColumn();
                    if($version===false||(int)$version!==(int)$item['applied_version']||(int)$latest!==(int)$item['revision_id']){$this->finishItem((int)$id,'rollback_conflict','После переноса были правки; автоматический возврат запрещён');$this->db->commit();continue;}
                    $r=$this->q('SELECT before_json FROM catalog_property_revision WHERE id=? AND product_id=?',[$item['revision_id'],$item['product_id']])->fetchColumn();$before=json_decode($r,true,512,JSON_THROW_ON_ERROR);
                    if(($before['version']??-1)!==0||$before['values']||$before['sizes']||$before['ratings']||$before['type']!=='unclassified')throw new \RuntimeException('Исходный профиль не пуст; удаление запрещено.');
                    (new CatalogProperties($this->db))->saveInTransaction((int)$item['product_id'],(int)$version,CatalogProperties::normalize($before),$actor);$revision=(int)$this->db->lastInsertId();
                    foreach(['catalog_product_size','catalog_tire_service_rating','catalog_product_property','catalog_product_profile'] as $table)$this->q('DELETE FROM '.$table.' WHERE product_id=?',[$item['product_id']]);
                    $this->q("UPDATE catalog_migration_item SET status='rolled_back',rollback_revision_id=?,reason='Возвращено отсутствие нового профиля; история сохранена',updated_at=NOW() WHERE id=?",[$revision,$id]);$this->db->commit();
                }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();$this->refresh($batch,'partial_rollback');throw $e;}
            }
            $this->q("UPDATE catalog_migration_item SET status='cancelled',reason='Отменено возвратом партии',updated_at=NOW() WHERE batch_id=? AND status IN ('pending','error')",[$batch]);
            $conflicts=(int)$this->q("SELECT COUNT(*) FROM catalog_migration_item WHERE batch_id=? AND status IN ('applied','rollback_conflict')",[$batch])->fetchColumn();$this->refresh($batch,$conflicts?'partial_rollback':'rolled_back');return $this->status($batch);
        }finally{$this->unlock();}
    }
}
