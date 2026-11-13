<?php
namespace app\services;

/** Per-product verified size index. Does not rewrite legacy search fields or synonyms. */
final class CatalogSizeSearch
{
    private static ?array $cache=null;
    public function __construct(private \PDO $db) {}
    public static function install(\PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS catalog_size_relation_anchor (product_id INT UNSIGNED NOT NULL, normalized_size VARCHAR(80) NOT NULL, primary_size VARCHAR(80) NOT NULL, relation_hash CHAR(64) NOT NULL, profile_version INT UNSIGNED NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(product_id,normalized_size)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    public function installed(): bool
    {
        try{$this->db->query('SELECT product_id FROM catalog_size_relation_anchor LIMIT 1');return true;}catch(\PDOException$e){if($e->getCode()==='42S02')return false;throw$e;}
    }
    public static function key(string $size): string
    {
        $s=CatalogSize::normalize($size);
        return preg_replace_callback('/\d+(?:\.\d+)?/',static function($m){[$a,$b]=array_pad(explode('.',$m[0],2),2,'');$a=ltrim($a,'0')?:'0';$b=rtrim($b,'0');return$a.($b!==''?'.'.$b:'');},$s);
    }
    public static function hash(array $row,string $type): string
    {
        return hash('sha256',json_encode([$type,CatalogSize::normalize($row['size']),$row['role'],trim($row['source_ref']??''),trim($row['conditions']??'')],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    }
    public static function primary(array $profile): string
    {
        $r=$profile['values']['tire_size']??[];
        return($r['state']??'')==='known'?CatalogSize::normalize((string)$r['value']):'';
    }
    public static function assess(array $profile,array $legacy,array $anchors): array
    {
        $out=[];$invalid='';try{$normal=CatalogProperties::normalize($profile);$primary=self::primary($normal);}catch(\InvalidArgumentException$e){$invalid=$e->getMessage();$normal=$profile;$primary='';}
        $legacyKeys=[];foreach($legacy as$v)try{$legacyKeys[self::key($v)]=true;}catch(\InvalidArgumentException$e){}
        $type=$profile['type']??'unclassified';$main=$profile['values']['tire_size']??[];
        $primaryVerified=($main['verification']??'')==='confirmed'&&trim($main['source_ref']??'')!=='';
        foreach($profile['sizes']??[]as$r){
            $reason='';$eligible=true;
            if(($r['verification']??'')!=='confirmed'||trim($r['source_ref']??'')==='')$reason='Связь не подтверждена источником';
            elseif($invalid!=='')$reason='Профиль требует проверки: '.$invalid;
            elseif(trim($r['conditions']??'')!=='')$reason='Есть условия применимости: автоматический поиск отключён';
            elseif($r['role']==='equivalent'){
                $a=$anchors[$r['size']]??null;
                if(!in_array($type,['tyre_pneumatic','tyre_solid','tyre_bandage','wheel_assembly'],true))$reason='Взаимозаменяемость не применяется к этому типу';
                elseif($primary==='')$reason='Не задан основной размер';
                elseif(!$primaryVerified&&(count($legacyKeys)!==1||!isset($legacyKeys[self::key($primary)])))$reason='Основной размер не совпадает с действующей карточкой';
                else{try{$valid=$a&&self::key($a['primary_size'])===self::key($primary)&&$a['relation_hash']===self::hash($r,$type);}catch(\InvalidArgumentException$e){$valid=false;}if(!$valid)$reason='Основной размер или связь изменились: требуется повторное подтверждение';}
            }elseif($r['role']==='compatible'){
                if(!in_array($type,['tube','flap','chain','rim','rim_ring','seal'],true))$reason='Совместимость не подключена к поиску для этого типа';
            }else$reason='Неизвестный вид связи';
            $eligible=$reason==='';$out[]=array_merge($r,['eligible'=>$eligible,'reason'=>$eligible?($r['role']==='equivalent'?'Доступен поиск по альтернативному размеру':'Доступен поиск при явном указании типа изделия'):$reason,'primary'=>$primary,'type'=>$type]);
        }
        return$out;
    }
    /** Update anchor metadata in the caller's product-save transaction. */
    public function sync(int $id,array $before,array $after,int $version): void
    {
        if(!$this->installed())return;
        if(!$this->db->inTransaction())throw new \RuntimeException('Anchor sync requires a transaction');
        $q=$this->db->prepare('SELECT normalized_size FROM catalog_size_relation_anchor WHERE product_id=? FOR UPDATE');$q->execute([$id]);$q->fetchAll();
        $old=[];foreach($before['sizes']??[]as$r)$old[$r['size']]=$r;
        $primary=self::primary($after);$oldPrimary=self::primary($before);$keep=[];
        foreach($after['sizes']??[]as$r){if($r['role']!=='equivalent'||$r['verification']!=='confirmed')continue;$keep[]=$r['size'];$same=isset($old[$r['size']])&&($old[$r['size']]['verification']??'')==='confirmed'&&self::hash($old[$r['size']],$after['type'])===self::hash($r,$after['type']);
            if($same&&(self::key($primary)!==self::key($oldPrimary)||$before['type']!==$after['type']))throw new \InvalidArgumentException('Основной размер или тип изменён. Перепроверьте связь '.$r['size'].' и укажите новое основание или сохраните её как предложение.');
            if($same)continue; // Existing or missing anchors must never be silently renewed.
            $q=$this->db->prepare('INSERT INTO catalog_size_relation_anchor(product_id,normalized_size,primary_size,relation_hash,profile_version,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE primary_size=VALUES(primary_size),relation_hash=VALUES(relation_hash),profile_version=VALUES(profile_version),updated_at=NOW()');$q->execute([$id,$r['size'],$primary,self::hash($r,$after['type']),$version]);
        }
        $sql='DELETE FROM catalog_size_relation_anchor WHERE product_id=?';$args=[$id];if($keep){$sql.=' AND normalized_size NOT IN ('.implode(',',array_fill(0,count($keep),'?')).')';$args=array_merge($args,$keep);}$q=$this->db->prepare($sql);$q->execute($args);self::$cache=null;
    }
    public function load(?int $onlyProduct=null): array
    {
        if(!$this->installed())return[];
        $sql="SELECT DISTINCT product_id FROM catalog_product_size WHERE verification='confirmed'".($onlyProduct!==null?' AND product_id='.(int)$onlyProduct:'');$ids=array_map('intval',$this->db->query($sql)->fetchAll(\PDO::FETCH_COLUMN));if($onlyProduct!==null)$ids=[$onlyProduct];if(!$ids)return[];
        $profiles=[];$service=new CatalogProperties($this->db);foreach(array_chunk($ids,200)as$chunk)$profiles+= $service->readAllProfiles($chunk);$legacy=[];$anchors=[];$products=[];
        foreach(array_chunk($ids,200)as$chunk){$csv=implode(',',$chunk);foreach($this->db->query('SELECT product_id,attribute_text FROM product_attribute WHERE attribute_id=4 AND product_id IN ('.$csv.')')->fetchAll(\PDO::FETCH_ASSOC)as$r)$legacy[$r['product_id']][]=$r['attribute_text'];foreach($this->db->query('SELECT * FROM catalog_size_relation_anchor WHERE product_id IN ('.$csv.')')->fetchAll(\PDO::FETCH_ASSOC)as$r)$anchors[$r['product_id']][$r['normalized_size']]=$r;foreach($this->db->query('SELECT p.*,c.name AS category_name FROM product p LEFT JOIN category c ON c.id=p.category_id WHERE p.id IN ('.$csv.')')->fetchAll(\PDO::FETCH_ASSOC)as$r)$products[(int)$r['id']]=$r;}
        $out=[];foreach($profiles as$id=>$p)if(isset($products[$id]))$out[$id]=['product'=>$products[$id],'relations'=>self::assess($p,$legacy[$id]??[],$anchors[$id]??[])];return$out;
    }
    public static function live(?\PDO $db=null): array{return self::$cache??= (new self($db??\R::getPDO()))->load();}
    public static function reset(): void{self::$cache=null;}
    /** Explicit deployment-only metadata snapshot; never confirms or edits a relation. */
    public function bootstrapExisting(): int
    {
        if(PHP_SAPI!=='cli')throw new \RuntimeException('Только установщик CLI');$count=0;
        foreach($this->load()as$id=>$entry)foreach($entry['relations']as$r){
            if($r['role']!=='equivalent'||$r['verification']!=='confirmed'||$r['reason']!=='Основной размер или связь изменились: требуется повторное подтверждение')continue;
            $q=$this->db->prepare('INSERT IGNORE INTO catalog_size_relation_anchor(product_id,normalized_size,primary_size,relation_hash,profile_version,updated_at) SELECT ?,?,?,?,version,NOW() FROM catalog_product_profile WHERE product_id=?');$q->execute([$id,$r['size'],$r['primary'],self::hash($r,$r['type']),$id]);$count+=$q->rowCount();
        }
        self::$cache=null;return$count;
    }
    public static function candidates(array $parsed): array
    {
        $keys=[];foreach($parsed['sizes']??[]as$s)try{$keys[self::key($s['raw'])]=true;}catch(\InvalidArgumentException$e){}if(!$keys)return[];
        $types=$parsed['product_types']??[];$out=[];$compatible=['tube'=>'tube','flap'=>'flap','chain'=>'chain','rim'=>'rim','rim_ring'=>'ring','seal'=>'ring'];
        foreach(self::live()as$entry){$p=$entry['product'];if($p['hide']!=='show')continue;foreach($entry['relations']as$r){if(!$r['eligible'])continue;$role=$r['role'];$match=$r['size'];if(!isset($keys[self::key($match)])){if($role==='equivalent'&&isset($keys[self::key($r['primary'])])){$role='primary';$match=$r['primary'];}else continue;}
            if($r['role']==='compatible'&&(!isset($compatible[$r['type']])||!in_array($compatible[$r['type']],$types,true)))continue;
            if($r['role']==='equivalent'&&$types&&!array_intersect($types,['tire','wheel']))continue;
            $p['catalog_size_match']=['size'=>$match,'role'=>$role,'primary'=>$r['primary']];$out[]=$p;break;
        }}return$out;
    }
    /** Add existing primary/alternative facet IDs for this product only. */
    public static function facetAdditions(): array
    {
        $targets=[];foreach(self::live()as$id=>$entry)foreach($entry['relations']as$r)if($r['eligible']&&$r['role']==='equivalent'){$targets[$id][self::key($r['size'])]=true;$targets[$id][self::key($r['primary'])]=true;}if(!$targets)return[];
        $groups=CatalogProperties::definitions()['tire_size']['legacy_filter_group_ids']??[];$groups=CatalogFacets::ids($groups);if(!$groups)return[];$out=[];
        foreach(\R::getAll("SELECT id,attr_group_id,value FROM attribute_value WHERE hide='show' AND attr_group_id IN (".implode(',',$groups).')')as$v){try{$key=self::key($v['value']);}catch(\InvalidArgumentException$e){continue;}foreach($targets as$id=>$keys)if(isset($keys[$key]))$out[(int)$v['attr_group_id']][(int)$id][]=(int)$v['id'];}return$out;
    }
}
