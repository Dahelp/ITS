<?php
namespace app\services;

/** Non-destructive projection onto existing facet IDs. No product/link writes. */
final class CatalogFacetBridge
{
    private static ?array $live=null;
    public function __construct(private \PDO $db) {}
    public static function install(\PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS catalog_facet_binding (group_id INT UNSIGNED PRIMARY KEY, property_code VARCHAR(64) NOT NULL, mode VARCHAR(16) NOT NULL, version INT UNSIGNED NOT NULL, updated_by INT UNSIGNED NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS catalog_facet_value_map (group_id INT UNSIGNED NOT NULL, source_value VARCHAR(255) NOT NULL COLLATE utf8mb4_bin, attr_id INT UNSIGNED NOT NULL, PRIMARY KEY(group_id,source_value)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS catalog_facet_binding_revision (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, group_id INT UNSIGNED NOT NULL, actor_id INT UNSIGNED NOT NULL, before_json MEDIUMTEXT NOT NULL, after_json MEDIUMTEXT NOT NULL, changed_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    public function installed(): bool
    {
        try{$this->db->query('SELECT group_id FROM catalog_facet_binding LIMIT 1');return true;}catch(\PDOException$e){if($e->getCode()==='42S02')return false;throw$e;}
    }
    public function bindings(bool $activeOnly=false): array
    {
        if(!$this->installed())return[];
        $out=$this->db->query('SELECT * FROM catalog_facet_binding'.($activeOnly?" WHERE mode='active'":'').' ORDER BY group_id')->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
        foreach($out as&$b){$b['values']=[];$b['valid_values']=[];$b['invalid_values']=[];}unset($b);
        foreach($this->db->query('SELECT m.*,v.attr_group_id,v.hide FROM catalog_facet_value_map m LEFT JOIN attribute_value v ON v.id=m.attr_id ORDER BY m.group_id,m.source_value')->fetchAll(\PDO::FETCH_ASSOC)as$r)if(isset($out[$r['group_id']])){
            $out[$r['group_id']]['values'][$r['source_value']]=(int)$r['attr_id'];
            if((int)$r['attr_group_id']===(int)$r['group_id']&&$r['hide']==='show')$out[$r['group_id']]['valid_values'][$r['source_value']]=(int)$r['attr_id'];
            else$out[$r['group_id']]['invalid_values'][]='Значение '.$r['source_value'].' связано с недоступным пунктом ID '.$r['attr_id'];
        }
        return$out;
    }
    public static function token(string $code,array $def,$value): string
    {
        if(!is_scalar($value))throw new \InvalidArgumentException('Значение соответствия должно быть строкой.');$v=trim((string)$value);
        if($v===''||mb_strlen($v)>255)throw new \InvalidArgumentException('Значение соответствия: от 1 до 255 символов.');
        if(in_array($def['kind'],['option','multi_option'],true)&&!array_key_exists($v,$def['options']))throw new \InvalidArgumentException('Неизвестный код варианта.');
        if(in_array($def['kind'],['decimal','integer'],true)){$v=str_replace(',','.',$v);if(!preg_match('/^[+-]?\d{1,9}(?:\.\d{1,6})?$/D',$v)||($def['kind']==='integer'&&str_contains($v,'.')))throw new \InvalidArgumentException('Некорректное число.');$v=ltrim($v,'+');$negative=str_starts_with($v,'-');$v=ltrim($v,'-');[$whole,$fraction]=array_pad(explode('.',$v,2),2,'');$whole=ltrim($whole,'0')?:'0';$fraction=rtrim($fraction,'0');$v=($negative&&($whole!=='0'||$fraction!=='')?'-':'').$whole.($fraction!==''?'.'.$fraction:'');}
        if($def['kind']==='boolean'&&!in_array($v,['0','1'],true))throw new \InvalidArgumentException('Логическое значение: 0 или 1.');
        if($code==='tire_size')$v=CatalogSize::normalize($v);
        return$v;
    }
    public function save(int $group,int $version,int $dictionaryVersion,string $code,string $mode,array $rows,int $actor): void
    {
        if($group<1||$version<0||$actor<1||!in_array($mode,['off','preview','active'],true)||count($rows)>205)throw new \InvalidArgumentException('Некорректные параметры связи или превышен лимит 200 соответствий.');
        $this->db->beginTransaction();
        try{
            $dv=(int)$this->db->query('SELECT version FROM catalog_dictionary_state WHERE id=1 LOCK IN SHARE MODE')->fetchColumn();if($dv!==$dictionaryVersion)throw new \RuntimeException('Справочник изменился. Обновите форму.');CatalogProperties::useDictionary($this->db);$def=CatalogProperties::definitions()[$code]??null;
            if(!$def||!in_array($def['kind'],['text','decimal','integer','boolean','option','multi_option'],true))throw new \InvalidArgumentException('Выберите простое свойство. Связи размеров подключаются отдельным этапом.');
            $q=$this->db->prepare('SELECT id FROM attribute_group WHERE id=? FOR UPDATE');$q->execute([$group]);if(!$q->fetchColumn())throw new \InvalidArgumentException('Группа не найдена.');
            $q=$this->db->prepare('SELECT * FROM catalog_facet_binding WHERE group_id=? FOR UPDATE');$q->execute([$group]);$before=$q->fetch(\PDO::FETCH_ASSOC)?:[];if((int)($before['version']??0)!==$version)throw new \RuntimeException('Связь изменена другим редактором. Обновите страницу.');
            $q=$this->db->prepare('SELECT source_value,attr_id FROM catalog_facet_value_map WHERE group_id=? ORDER BY source_value');$q->execute([$group]);$before['values']=$q->fetchAll(\PDO::FETCH_KEY_PAIR);
            $values=[];$q=$this->db->prepare("SELECT id FROM attribute_value WHERE id=? AND attr_group_id=? AND hide='show'");
            foreach($rows as$r){if(!is_array($r)||!is_string($r['source']??null)||!is_string($r['attr_id']??null))throw new \InvalidArgumentException('Некорректная строка соответствия.');if(trim($r['source'])===''&&$r['attr_id']==='')continue;$token=self::token($code,$def,$r['source']);if(!preg_match('/^[1-9]\d{0,8}$/D',$r['attr_id'])||isset($values[$token]))throw new \InvalidArgumentException('Дубли значения или неверный ID фильтра.');$q->execute([(int)$r['attr_id'],$group]);if(!$q->fetchColumn())throw new \InvalidArgumentException('Выберите видимое значение из этой группы.');$values[$token]=(int)$r['attr_id'];}
            if(count($values)>200)throw new \InvalidArgumentException('Не более 200 соответствий на группу.');
            if($mode==='active'&&!$values)throw new \InvalidArgumentException('Для включения нужны соответствия значений.');
            $q=$this->db->prepare('INSERT INTO catalog_facet_binding(group_id,property_code,mode,version,updated_by,updated_at) VALUES(?,?,?,1,?,NOW()) ON DUPLICATE KEY UPDATE property_code=VALUES(property_code),mode=VALUES(mode),version=version+1,updated_by=VALUES(updated_by),updated_at=NOW()');$q->execute([$group,$code,$mode,$actor]);
            $q=$this->db->prepare('DELETE FROM catalog_facet_value_map WHERE group_id=?');$q->execute([$group]);$q=$this->db->prepare('INSERT INTO catalog_facet_value_map(group_id,source_value,attr_id) VALUES(?,?,?)');foreach($values as$v=>$id)$q->execute([$group,(string)$v,$id]);
            $after=['property_code'=>$code,'mode'=>$mode,'values'=>$values,'version'=>$version+1];$q=$this->db->prepare('INSERT INTO catalog_facet_binding_revision(group_id,actor_id,before_json,after_json,changed_at) VALUES(?,?,?,?,NOW())');$q->execute([$group,$actor,json_encode($before,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($after,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$this->db->commit();self::$live=null;
        }catch(\Throwable$e){if($this->db->inTransaction())$this->db->rollBack();throw$e;}
    }
    public static function project(array $bindings,array $profiles,array $definitions): array
    {
        $out=['overrides'=>[],'issues'=>[]];
        foreach($profiles as$id=>$profile){
            if(($profile['type']??'unclassified')==='unclassified')continue;
            $relevant=false;foreach($bindings as$b)if(($profile['values'][$b['property_code']]['verification']??'')==='confirmed'){$relevant=true;break;}if(!$relevant)continue;
            try{$normal=CatalogProperties::normalize($profile);}catch(\InvalidArgumentException$e){$out['issues'][]=['product_id'=>$id,'group_id'=>0,'reason'=>'Профиль не прошёл проверку: '.$e->getMessage()];continue;}
            foreach($bindings as$group=>$b){$code=$b['property_code'];$def=$definitions[$code]??null;$row=$normal['values'][$code]??null;
                if(!$def||!$row||$row['state']!=='known'||$row['verification']!=='confirmed'||trim($row['source_ref'])==='')continue;
                $map=$b['valid_values']??$b['values'];$ids=[];$missing=[];try{foreach(is_array($row['value'])?$row['value']:[$row['value']]as$v){$token=self::token($code,$def,$v);if(!isset($map[$token]))$missing[]=$token;else$ids[]=(int)$map[$token];}}catch(\InvalidArgumentException$e){$out['issues'][]=['product_id'=>$id,'group_id'=>(int)$group,'reason'=>$e->getMessage()];continue;}
                if($missing){$out['issues'][]=['product_id'=>$id,'group_id'=>(int)$group,'reason'=>'Нет соответствия: '.implode(', ',$missing)];continue;}
                if($ids){$ids=array_values(array_unique($ids));sort($ids);$out['overrides'][(int)$group][(int)$id]=$ids;}
            }
        }
        return$out;
    }
    public function preview(?array $bindings=null): array
    {
        $bindings??=$this->bindings();if(!$bindings)return['overrides'=>[],'issues'=>[]];
        $profiles=CatalogPublishedAttributes::load($this->db);$result=self::project($bindings,$profiles,CatalogProperties::definitions());$result['differences']=[];$old=[];
        if($result['overrides'])foreach($this->db->query('SELECT DISTINCT a.product_id,v.attr_group_id,a.attr_id FROM attribute_product a JOIN attribute_value v ON v.id=a.attr_id WHERE v.attr_group_id IN ('.implode(',',array_keys($result['overrides'])).') ORDER BY a.attr_id')->fetchAll(\PDO::FETCH_ASSOC)as$r)$old[(int)$r['attr_group_id']][(int)$r['product_id']][]=(int)$r['attr_id'];
        foreach($result['overrides']as$group=>$products)foreach($products as$id=>$target){$legacy=$old[$group][$id]??[];if($legacy!==$target)$result['differences'][]=['product_id'=>$id,'group_id'=>$group,'legacy'=>$legacy,'target'=>$target];}
        return$result;
    }
    private static function live(): array
    {
        if(self::$live!==null)return self::$live;
        $service=new self(\R::getPDO());$bindings=$service->bindings(true);$overrides=[];
        if($bindings){$profiles=CatalogPublishedAttributes::load(\R::getPDO());$overrides=self::project($bindings,$profiles,CatalogProperties::definitions())['overrides'];}
        $additions=CatalogSizeSearch::facetAdditions();
        if($additions){$legacy=[];foreach(\R::getAll('SELECT DISTINCT a.product_id,v.attr_group_id,a.attr_id FROM attribute_product a JOIN attribute_value v ON v.id=a.attr_id WHERE v.attr_group_id IN ('.implode(',',array_keys($additions)).')')as$r)$legacy[(int)$r['attr_group_id']][(int)$r['product_id']][]=(int)$r['attr_id'];foreach($additions as$group=>$products)foreach($products as$id=>$ids){$values=array_values(array_unique(array_merge($overrides[$group][$id]??($legacy[$group][$id]??[]),$ids)));sort($values);$overrides[$group][$id]=$values;}}
        return self::$live=$overrides;
    }
    public static function reset(): void{self::$live=null;CatalogSizeSearch::reset();}
    /** Safe SQL uses validated aliases and integer IDs only. */
    public static function condition(int $group,array $values,string $product='product',?string $valueColumn=null): string
    {
        if(!in_array($product,['product','p','src'],true)||($valueColumn!==null&&$valueColumn!=='av.id'))throw new \InvalidArgumentException('Invalid facet alias');
        $values=CatalogFacets::ids($values);$test=$valueColumn!==null?'fp.attr_id='.$valueColumn:($values?'fp.attr_id IN ('.implode(',',$values).')':'1=0');
        $overrides=self::live()[$group]??[];$legacy="EXISTS (SELECT 1 FROM attribute_product fp WHERE fp.product_id=$product.id AND $test)";
        if(!$overrides)return$legacy;
        $legacy='('.$legacy.' AND '.$product.'.id NOT IN ('.implode(',',array_keys($overrides)).'))';$new=[];
        foreach($overrides as$id=>$target){if($valueColumn!==null)$new[]="($product.id=$id AND $valueColumn IN (".implode(',',$target).'))';elseif(array_intersect($values,$target))$new[]=$id;}
        if($valueColumn!==null)return'('.$legacy.($new?' OR '.implode(' OR ',$new):'').')';
        return'('.$legacy.($new?' OR '.$product.'.id IN ('.implode(',',$new).')':'').')';
    }
    public static function hasOverrides(int $group): bool{return!empty(self::live()[$group]);}
    /** Joinable effective edges. With no overrides this is the original indexed table. */
    public static function relationSql(): string
    {
        $overrides=self::live();if(!$overrides)return'attribute_product';$exclude=[];$extra=[];
        foreach($overrides as$group=>$products){$exclude[]='(oldav.attr_group_id='.(int)$group.' AND oldap.product_id IN ('.implode(',',array_keys($products)).'))';foreach($products as$id=>$target)foreach($target as$value)$extra[]='SELECT '.(int)$id.' AS product_id,'.(int)$value.' AS attr_id';}
        return'(SELECT oldap.product_id,oldap.attr_id FROM attribute_product oldap JOIN attribute_value oldav ON oldav.id=oldap.attr_id WHERE NOT ('.implode(' OR ',$exclude).') UNION ALL '.implode(' UNION ALL ',$extra).')';
    }
    public static function columnCondition(string $product='p'): string
    {
        if(!in_array($product,['product','p','src'],true))throw new \InvalidArgumentException('Invalid product alias');
        $legacy="EXISTS (SELECT 1 FROM attribute_product fp WHERE fp.product_id=$product.id AND fp.attr_id=av.id)";$exclude=[];$include=[];
        foreach(self::live()as$group=>$products){$exclude[]='(av.attr_group_id='.(int)$group.' AND '.$product.'.id IN ('.implode(',',array_keys($products)).'))';foreach($products as$id=>$target)$include[]="($product.id=$id AND av.id IN (".implode(',',$target).'))';}
        return!$exclude?$legacy:'(('.$legacy.' AND NOT ('.implode(' OR ',$exclude).')) OR '.implode(' OR ',$include).')';
    }
}
