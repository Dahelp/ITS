<?php
namespace app\services;

/** Authoritative definitions after explicit installation. Never writes product facts. */
final class CatalogDictionary
{
    public function __construct(private \PDO $db) {}
    public static function install(\PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS catalog_dictionary_state (id INT PRIMARY KEY, version INT UNSIGNED NOT NULL) ENGINE=InnoDB');
        $db->exec('CREATE TABLE IF NOT EXISTS catalog_dictionary_revision (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, property_code VARCHAR(64) NOT NULL, actor_id INT UNSIGNED NOT NULL, before_json MEDIUMTEXT NULL, after_json MEDIUMTEXT NOT NULL, changed_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $db->exec('INSERT IGNORE INTO catalog_dictionary_state(id,version) VALUES(1,1)');
    }
    public function installed(): bool
    {
        try{return (bool)$this->db->query('SELECT version FROM catalog_dictionary_state WHERE id=1')->fetchColumn();}
        catch(\PDOException $e){if($e->getCode()==='42S02')return false;throw$e;}
    }
    public function definitions(bool $withRules=true): array
    {
        $out=[];foreach($this->db->query('SELECT code,label,kind,unit_code,definition_json FROM catalog_property ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC) as$r){
            $d=json_decode($r['definition_json'],true,512,JSON_THROW_ON_ERROR);$d['label']=$r['label'];$d['kind']=$r['kind'];$d['unit']=$r['unit_code'];$out[$r['code']]=$d;
        }
        if(!$out)throw new \RuntimeException('Справочник пуст. Восстановите определения до продолжения.');
        if($withRules){
            foreach($out as&$d)$d['rules']=[];unset($d);
            foreach($this->db->query('SELECT p.code,t.code AS type,r.applicability FROM catalog_property_rule r JOIN catalog_property p ON p.id=r.property_id JOIN catalog_type t ON t.id=r.type_id')->fetchAll(\PDO::FETCH_ASSOC)as$r)$out[$r['code']]['rules'][$r['type']]=$r['applicability'];
            foreach($out as$d)foreach(CatalogProperties::types()as$type=>$_)if(!isset($d['rules'][$type]))throw new \RuntimeException('Неполные правила применимости справочника.');
        }
        return$out;
    }
    public function version(): int{return(int)$this->db->query('SELECT version FROM catalog_dictionary_state WHERE id=1')->fetchColumn();}
    public function history(): array{return$this->db->query('SELECT id,property_code,actor_id,changed_at,before_json,after_json FROM catalog_dictionary_revision ORDER BY id DESC LIMIT 30')->fetchAll(\PDO::FETCH_ASSOC);}
    public function usage(string $code): array
    {
        $q=$this->db->prepare('SELECT COUNT(*) FROM catalog_product_property v JOIN catalog_property p ON p.id=v.property_id WHERE p.code=? AND v.value_state=\'known\'');$q->execute([$code]);$known=(int)$q->fetchColumn();
        $q=$this->db->prepare('SELECT o.code,COUNT(*) AS total FROM catalog_product_property_option v JOIN catalog_property_option o ON o.id=v.option_id JOIN catalog_property p ON p.id=o.property_id WHERE p.code=? GROUP BY o.code');$q->execute([$code]);return['known'=>$known,'options'=>$q->fetchAll(\PDO::FETCH_KEY_PAIR)];
    }
    public static function validate(array $input,?array $old): array
    {
        foreach(['code','label','kind','unit','note']as$k)if(!is_string($input[$k]??null))throw new \InvalidArgumentException('Некорректное поле '.$k);
        if(!preg_match('/^[a-z][a-z0-9_]{1,63}$/D',$input['code']))throw new \InvalidArgumentException('Код: латинские буквы, цифры и подчёркивание.');
        if(trim($input['label'])===''||mb_strlen($input['label'])>255||mb_strlen($input['note'])>4000)throw new \InvalidArgumentException('Укажите название до 255 символов и пояснение до 4000.');
        if($old){if($old['kind']!==$input['kind']||($old['unit']??'')!==$input['unit'])throw new \InvalidArgumentException('Тип и единицу существующего свойства меняют отдельной миграцией.');}
        elseif(!in_array($input['kind'],['text','decimal','integer','boolean','option','multi_option'],true))throw new \InvalidArgumentException('Выберите поддерживаемый тип.');
        if(!in_array($input['unit'],['','in','mm','kg','m3','kPa','PSI','kgf/cm2','km/h','%'],true))throw new \InvalidArgumentException('Неизвестная единица.');
        $d=$old??['options'=>[],'legacy_attribute_ids'=>[],'legacy_filter_group_ids'=>[]];$d['label']=trim($input['label']);$d['kind']=$input['kind'];$d['unit']=$input['unit'];$d['note']=trim($input['note']);$d['archived']=($input['archived']??'0')==='1';
        $rules=$input['rules']??[];if(!is_array($rules))throw new \InvalidArgumentException('Некорректные правила.');
        $d['rules']=[];foreach(CatalogProperties::types()as$type=>$_){$rule=$rules[$type]??($old?CatalogProperties::applicability($input['code'],$type):'not_applicable');if(!in_array($rule,['required','optional','not_applicable'],true))throw new \InvalidArgumentException('Некорректная применимость.');if(in_array($type,['service','unclassified'],true)&&$rule==='required')throw new \InvalidArgumentException('Для услуги и неопределённого типа обязательность не задаётся.');$d['rules'][$type]=$rule;}
        $labels=$input['option_labels']??[];$archived=$input['option_archived']??[];if(!is_array($labels)||!is_array($archived))throw new \InvalidArgumentException('Некорректные варианты.');
        $d['archived_options']=$old['archived_options']??[];
        foreach($labels as$code=>$label){if(!isset($d['options'][$code])||!is_string($label)||trim($label)===''||mb_strlen($label)>255)throw new \InvalidArgumentException('Некорректное название варианта.');$d['options'][$code]=trim($label);if(isset($archived[$code]))$d['archived_options'][$code]=true;else unset($d['archived_options'][$code]);}
        $newCode=$input['new_option_code']??'';$newLabel=$input['new_option_label']??'';if(!is_string($newCode)||!is_string($newLabel))throw new \InvalidArgumentException('Некорректный новый вариант.');
        if($newCode!==''||$newLabel!==''){
            if(!in_array($d['kind'],['option','multi_option'],true)||!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/D',$newCode)||isset($d['options'][$newCode])||trim($newLabel)===''||mb_strlen($newLabel)>255)throw new \InvalidArgumentException('Новый вариант: уникальный код и название до 255 символов.');$d['options'][$newCode]=trim($newLabel);
        }
        if(in_array($d['kind'],['option','multi_option'],true)&&!$d['options'])throw new \InvalidArgumentException('Для списка добавьте хотя бы один вариант.');
        if($d['archived'])foreach($d['rules']as$type=>$rule)if($rule==='required')$d['rules'][$type]='optional';
        return$d;
    }
    public function save(array $input,int $version,int $actor): void
    {
        if($actor<1||$version<1)throw new \InvalidArgumentException('Некорректная версия или автор.');$this->db->beginTransaction();
        try{
            $current=(int)$this->db->query('SELECT version FROM catalog_dictionary_state WHERE id=1 FOR UPDATE')->fetchColumn();if($current!==$version)throw new \RuntimeException('Справочник изменён другим редактором. Обновите страницу.');
            CatalogProperties::useDictionary($this->db);$defs=$this->definitions();$code=$input['code']??'';if(!is_string($code))throw new \InvalidArgumentException('Некорректный код свойства.');$old=$defs[$code]??null;
            if(($input['create']??'0')==='1'&&$old)throw new \InvalidArgumentException('Такой код уже существует.');if(($input['create']??'0')!=='1'&&!$old)throw new \InvalidArgumentException('Свойство не найдено.');
            $d=self::validate($input,$old);
            // Narrowing applicability is forbidden while stored known values exist.
            $q=$this->db->prepare('SELECT t.code,COUNT(*) AS total FROM catalog_product_property v JOIN catalog_property p ON p.id=v.property_id JOIN catalog_product_profile f ON f.product_id=v.product_id JOIN catalog_type t ON t.id=f.type_id WHERE p.code=? AND v.value_state=\'known\' GROUP BY t.code');$q->execute([$code]);foreach($q->fetchAll(\PDO::FETCH_KEY_PAIR)as$type=>$count)if($d['rules'][$type]==='not_applicable'&&CatalogProperties::applicability($code,$type)!=='not_applicable')throw new \InvalidArgumentException('Нельзя отключить применимость: у товаров этого типа уже есть значения.');
            $json=json_encode($d,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            if($old){$q=$this->db->prepare('UPDATE catalog_property SET label=?,definition_json=? WHERE code=?');$q->execute([$d['label'],$json,$code]);}
            else{$q=$this->db->prepare('INSERT INTO catalog_property(code,label,kind,unit_code,definition_json) VALUES(?,?,?,?,?)');$q->execute([$code,$d['label'],$d['kind'],$d['unit'],$json]);}
            $q=$this->db->prepare('SELECT id FROM catalog_property WHERE code=?');$q->execute([$code]);$id=(int)$q->fetchColumn();
            $q=$this->db->prepare('INSERT INTO catalog_property_option(property_id,code,label) VALUES(?,?,?) ON DUPLICATE KEY UPDATE label=VALUES(label)');foreach($d['options']as$key=>$label)$q->execute([$id,$key,$label]);
            $q=$this->db->prepare('INSERT INTO catalog_property_rule(type_id,property_id,applicability) SELECT id,?,? FROM catalog_type WHERE code=? ON DUPLICATE KEY UPDATE applicability=VALUES(applicability)');foreach($d['rules']as$type=>$rule)$q->execute([$id,$rule,$type]);
            $q=$this->db->prepare('INSERT INTO catalog_dictionary_revision(property_code,actor_id,before_json,after_json,changed_at) VALUES(?,?,?,?,NOW())');$q->execute([$code,$actor,$old?json_encode($old,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR):null,$json]);$this->db->exec('UPDATE catalog_dictionary_state SET version=version+1 WHERE id=1');$this->db->commit();CatalogProperties::useDictionary($this->db);
        }catch(\Throwable$e){if($this->db->inTransaction())$this->db->rollBack();CatalogProperties::useDictionary($this->db);throw$e;}
    }
}
