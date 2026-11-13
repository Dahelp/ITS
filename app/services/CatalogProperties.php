<?php
namespace app\services;

/** Isolated stage-2A repository. Does not write product, filters, InSEO or feeds. */
final class CatalogProperties
{
    private static ?array $managedDefinitions=null;
    public function __construct(private \PDO $db) {self::useDictionary($db);}
    public static function useDictionary(\PDO $db): void
    {
        $dictionary=new CatalogDictionary($db);
        self::$managedDefinitions=$dictionary->installed()?$dictionary->definitions():null;
    }

    public static function definitions(): array
    {
        if(self::$managedDefinitions!==null)return self::$managedDefinitions;
        static $definitions;
        return $definitions ??= json_decode(file_get_contents((defined('APP')?APP:dirname(__DIR__)).'/data/catalog-properties-v1.json'),true,512,JSON_THROW_ON_ERROR)['properties'];
    }
    public static function types(): array
    {
        return ['unclassified'=>'Не классифицировано','tyre_pneumatic'=>'Пневматическая шина','tyre_solid'=>'Цельнолитая шина','tyre_bandage'=>'Бандажная шина','wheel_assembly'=>'Колесо в сборе','rim'=>'Диск','rim_ring'=>'Кольцо диска','tube'=>'Камера','flap'=>'Ободная лента','seal'=>'Уплотнение','filter'=>'Фильтр','chain'=>'Цепь','service'=>'Услуга'];
    }
    public static function applicability(string $code,string $type): string
    {
        if(isset(self::definitions()[$code]['rules'][$type]))return self::definitions()[$code]['rules'][$type];
        if ($type==='unclassified') return 'optional';
        if ($type==='service') return 'not_applicable';
        $def=self::definitions()[$code];
        if(isset($def['profile_types']))return in_array($type,$def['profile_types'],true)?'optional':'not_applicable';
        if (in_array($type,$def['not_applicable_for']??[],true)) return 'not_applicable';
        $common=['brand','manufacturer','country_of_manufacture','manufacturer_part_number','barcode','model','weight_kg','volume_m3','package'];
        $tyre=['tread_model','tire_size','rim_diameter_in','section_width_in','section_width_mm','aspect_ratio_pct','overall_diameter_mm','nominal_height_in','tire_type','manufacturer_load_kg','tread_depth_mm','tread_width_mm','tread_pattern','equipment_application','surface','rim_designation','service_ratings','equivalent_tire_sizes','seasonality','section_height_mm','manufacturer_max_speed_kmh','recommended_axle'];
        $pneumatic=['construction','sealing','ply_rating','star_rating','inflation_pressure_kpa','legacy_max_pressure_psi','legacy_max_pressure_kgf_cm2'];
        $solid=['solid_mounting','non_marking','colour'];
        $rim=['rim_construction','rim_designation','rim_width_in','rim_diameter_in','offset_mm','hub_mm','pcd_mm','bolt_count','rim_ring_type','compatible_tire_sizes','equipment_application'];
        $allowed=match($type){'tyre_pneumatic'=>array_merge($tyre,$pneumatic),'tyre_solid'=>array_merge($tyre,$solid),'tyre_bandage'=>$tyre,'wheel_assembly'=>array_merge($tyre,$pneumatic,$solid,$rim),'rim'=>$rim,'rim_ring'=>['rim_designation','rim_ring_type','rim_diameter_in','compatible_tire_sizes','equipment_application'],'tube'=>['valve','compatible_tire_sizes','rim_diameter_in','equipment_application'],'flap'=>['compatible_tire_sizes','equipment_application'],'seal'=>['seal_section_diameter_mm','compatible_tire_sizes'],'chain'=>['compatible_tire_sizes','equipment_application'],default=>[]};
        if(!in_array($code,array_merge($common,$allowed),true))return 'not_applicable';
        $required=['tyre_pneumatic'=>['tire_size','tire_type','construction','sealing','package','equipment_application'], 'tyre_solid'=>['tire_size','tire_type','solid_mounting','equipment_application'], 'tyre_bandage'=>['tire_size','tire_type'], 'wheel_assembly'=>['tire_size','tire_type','rim_designation'], 'rim'=>['rim_designation','rim_width_in'],'rim_ring'=>['rim_designation','rim_ring_type'], 'tube'=>['valve'], 'seal'=>['seal_section_diameter_mm']];
        if (in_array($code,$required[$type]??[],true)) return 'required';
        return 'optional';
    }
    public function installed(): bool
    {
        try { return (int)$this->db->query('SELECT version FROM catalog_foundation_version WHERE id=1')->fetchColumn()===1; }
        catch (\PDOException $e) {return false;}
    }
    private function query(string $sql,array $args=[]): \PDOStatement
    {
        $q=$this->db->prepare($sql);$q->execute($args);return $q;
    }
    public function read(int $productId): array
    {
        $profile=$this->query('SELECT p.version,t.code FROM catalog_product_profile p JOIN catalog_type t ON t.id=p.type_id WHERE p.product_id=?',[$productId])->fetch(\PDO::FETCH_ASSOC);
        $values=[];
        foreach ($this->query('SELECT v.*,p.code,p.kind FROM catalog_product_property v JOIN catalog_property p ON p.id=v.property_id WHERE product_id=?',[$productId])->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $value=$row['numeric_value']??$row['bool_value']??$row['text_value']??'';
            if($row['kind']==='integer'&&$row['numeric_value']!==null)$value=(string)(int)$row['numeric_value'];
            if (in_array($row['kind'],['option','multi_option'],true)) $value=$this->query('SELECT o.code FROM catalog_product_property_option v JOIN catalog_property_option o ON o.id=v.option_id WHERE v.value_id=? ORDER BY o.code',[$row['id']])->fetchAll(\PDO::FETCH_COLUMN);
            $values[$row['code']]=['state'=>$row['value_state'],'verification'=>$row['verification'],'value'=>$value,'source_ref'=>$row['source_ref']];
        }
        $sizes=$this->query('SELECT normalized_size AS size,role,verification,source_ref,conditions_text AS conditions FROM catalog_product_size WHERE product_id=? ORDER BY id',[$productId])->fetchAll(\PDO::FETCH_ASSOC);
        $ratings=$this->query('SELECT raw_marking,load_single,load_dual,speed_symbol,operating_mode,pressure_kpa,verification,source_ref FROM catalog_tire_service_rating WHERE product_id=? ORDER BY sequence_no',[$productId])->fetchAll(\PDO::FETCH_ASSOC);
        return ['type'=>$profile['code']??'unclassified','version'=>(int)($profile['version']??0),'values'=>$values,'sizes'=>$sizes,'ratings'=>$ratings];
    }
    /** Batch reader for the quality queue: a fixed number of SELECTs, no per-product requests. */
    public function readAllProfiles(?array $productIds=null): array
    {
        if($productIds!==null){if(count($productIds)>200)throw new \InvalidArgumentException('Не более 200 товаров в пакете.');$productIds=CatalogFacets::ids($productIds);if(!$productIds)return[];}
        $scope=$productIds===null?'':' WHERE product_id IN ('.implode(',',$productIds).')';
        $result=[];
        foreach($this->db->query('SELECT p.product_id,p.version,t.code FROM catalog_product_profile p JOIN catalog_type t ON t.id=p.type_id'.$scope)->fetchAll(\PDO::FETCH_ASSOC) as $r)$result[(int)$r['product_id']]=['type'=>$r['code'],'version'=>(int)$r['version'],'values'=>[],'sizes'=>[],'ratings'=>[]];
        $options=[];
        foreach($this->db->query('SELECT v.value_id,o.code FROM catalog_product_property_option v JOIN catalog_property_option o ON o.id=v.option_id'.($productIds===null?'':' JOIN catalog_product_property pv ON pv.id=v.value_id WHERE pv.product_id IN ('.implode(',',$productIds).')').' ORDER BY o.code')->fetchAll(\PDO::FETCH_ASSOC) as $r)$options[(int)$r['value_id']][]=$r['code'];
        foreach($this->db->query('SELECT v.*,p.code,p.kind FROM catalog_product_property v JOIN catalog_property p ON p.id=v.property_id'.$scope)->fetchAll(\PDO::FETCH_ASSOC) as $r){
            $value=in_array($r['kind'],['option','multi_option'],true)?($options[(int)$r['id']]??[]):($r['numeric_value']??$r['bool_value']??$r['text_value']??'');
            if($r['kind']==='integer'&&$r['numeric_value']!==null)$value=(string)(int)$r['numeric_value'];
            $result[(int)$r['product_id']]['values'][$r['code']]=['state'=>$r['value_state'],'verification'=>$r['verification'],'value'=>$value,'source_ref'=>$r['source_ref']];
        }
        foreach($this->db->query('SELECT product_id,normalized_size AS size,role,verification,source_ref,conditions_text AS conditions FROM catalog_product_size'.$scope.' ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC) as $r){$id=(int)$r['product_id'];unset($r['product_id']);$result[$id]['sizes'][]=$r;}
        foreach($this->db->query('SELECT product_id,raw_marking,load_single,load_dual,speed_symbol,operating_mode,pressure_kpa,verification,source_ref FROM catalog_tire_service_rating'.$scope.' ORDER BY sequence_no')->fetchAll(\PDO::FETCH_ASSOC) as $r){$id=(int)$r['product_id'];unset($r['product_id']);$result[$id]['ratings'][]=$r;}
        return $result;
    }
    private static function scalar($value,string $label,int $max=4000): string
    {
        if (!is_string($value)&&!is_int($value)&&!is_float($value)&&$value!==null) throw new \InvalidArgumentException('Некорректное поле: '.$label);
        $value=trim((string)$value);
        if (strlen($value)>$max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$value)) throw new \InvalidArgumentException('Недопустимое значение: '.$label);
        return $value;
    }
    public static function normalize(array $input): array
    {
        $type=self::scalar($input['type']??'unclassified','Тип изделия',64);
        if (!isset(self::types()[$type])) throw new \InvalidArgumentException('Неизвестный тип изделия.');
        if (!is_array($input['values']??[])) throw new \InvalidArgumentException('Некорректные свойства.');
        $defs=self::definitions();$values=[];$issues=[];
        if($type==='unclassified')$issues[]='Нужно подтвердить тип изделия';
        foreach ($input['values']??[] as $code=>$row) {
            if (!isset($defs[$code])||!is_array($row)||in_array($defs[$code]['kind'],['external','ratings','size_relations'],true)) throw new \InvalidArgumentException('Свойство недоступно для записи: '.$code);
        }
        foreach ($defs as $code=>$def) {
            if (in_array($def['kind'],['external','ratings','size_relations'],true)) continue;
            $row=$input['values'][$code]??[];
            $state=self::scalar($row['state']??'unknown',$def['label']);
            $verification=self::scalar($row['verification']??'candidate',$def['label']);
            if (!in_array($state,['unknown','known','not_applicable'],true)||!in_array($verification,['candidate','legacy_unverified','confirmed','conflict'],true)) throw new \InvalidArgumentException('Некорректный статус: '.$def['label']);
            $rule=self::applicability($code,$type);
            if ($rule==='not_applicable'&&$state==='known') throw new \InvalidArgumentException($def['label'].': не применяется к выбранному типу изделия.');
            if ($rule==='required'&&$state==='not_applicable') throw new \InvalidArgumentException($def['label'].': обязательное свойство не может быть неприменимым.');
            $source=self::scalar($row['source_ref']??'','Источник',1000);
            $value='';
            if ($state==='known') {
                if ($verification==='confirmed'&&$source==='') throw new \InvalidArgumentException($def['label'].': укажите источник подтверждения.');
                $kind=$def['kind'];
                if (in_array($kind,['option','multi_option'],true)) {
                    $value=$row['value']??[];
                    if (!is_array($value)) $value=[$value];
                    foreach ($value as $v) if (!is_string($v)||!array_key_exists($v,$def['options'])) throw new \InvalidArgumentException($def['label'].': выберите допустимое значение списка.');
                    $value=array_values(array_unique($value));sort($value);
                    if (!$value || ($kind==='option'&&count($value)!==1)) throw new \InvalidArgumentException($def['label'].': неверное количество значений.');
                } else {
                    $value=self::scalar($row['value']??'',$def['label']);
                    if ($value==='') throw new \InvalidArgumentException($def['label'].': заполните значение или выберите «Неизвестно».');
                    if (in_array($kind,['integer','decimal'],true)) {
                        $value=str_replace(',','.',$value);
                        $pattern=$kind==='integer'?'/^[+-]?\d{1,9}$/D':'/^[+-]?\d{1,9}(?:\.\d{1,6})?$/D';
                        if (!preg_match($pattern,$value)||(!in_array($code,['offset_mm'],true)&&(float)$value<=0)) throw new \InvalidArgumentException($def['label'].': укажите число в единице '.$def['unit']);
                    }
                    if ($kind==='boolean'&&!in_array($value,['0','1'],true)) throw new \InvalidArgumentException($def['label'].': выберите Да или Нет.');
                    if ($code==='tire_size') $value=CatalogSize::normalize($value);
                    if ($code==='barcode' && (!preg_match('/^(?:\d{8}|\d{12}|\d{13})$/D',$value)||!self::barcodeValid($value))) throw new \InvalidArgumentException('Неверная длина или контрольная цифра штрихкода.');
                }
            } else {
                $raw=$row['value']??'';
                if (is_array($raw)?count($raw)>0:self::scalar($raw,$def['label'])!=='') throw new \InvalidArgumentException($def['label'].': очистите значение для статуса «Неизвестно/Не применяется».');
            }
            if ($rule==='required'&&$state!=='known') $issues[]=$def['label'].': пока не заполнено';
            if ($state==='known'&&$verification!=='confirmed') $issues[]=$def['label'].': требует подтверждения';
            $values[$code]=['state'=>$state,'verification'=>$verification,'value'=>$value,'source_ref'=>$source];
        }
        $expectedType=['tyre_pneumatic'=>'pneumatic','tyre_solid'=>'solid','tyre_bandage'=>'bandage'][$type]??null;
        if ($expectedType&&($values['tire_type']['state']??'')==='known'&&$values['tire_type']['value']!==[$expectedType]) throw new \InvalidArgumentException('Тип шины противоречит профилю изделия.');
        $solidWheel=$type==='wheel_assembly'&&($values['tire_type']['state']??'')==='known'&&array_intersect($values['tire_type']['value'],['solid','bandage']);
        if($solidWheel)foreach(['inflation_pressure_kpa','legacy_max_pressure_psi','legacy_max_pressure_kgf_cm2'] as $pressure)if(($values[$pressure]['state']??'')==='known')throw new \InvalidArgumentException('Давление неприменимо к цельнолитому или бандажному колесу.');
        if (!is_array($input['sizes']??[])||!is_array($input['ratings']??[])) throw new \InvalidArgumentException('Некорректные размеры или режимы.');
        $primary=($values['tire_size']['state']??'')==='known'?$values['tire_size']['value']:'';
        $sizes=CatalogSize::relations($primary,$input['sizes']??[]);
        foreach($sizes as$s)if($s['verification']!=='confirmed')$issues[]='Размер '.$s['size'].': требует подтверждения';
        if ($sizes&&$type==='service') throw new \InvalidArgumentException('Услуге не назначаются шинные размеры.');
        if (array_filter($sizes,static fn($s)=>$s['role']==='equivalent')&&$primary==='') throw new \InvalidArgumentException('Сначала укажите основной типоразмер.');
        if (count($input['ratings']??[])>30) throw new \InvalidArgumentException('Слишком много строк режимов нагрузки.');
        $ratings=[];
        foreach ($input['ratings']??[] as $row) {
            if(!is_array($row))throw new \InvalidArgumentException('Некорректный режим нагрузки.');
            $r=[];foreach(['raw_marking','speed_symbol','operating_mode','verification','source_ref'] as $field)$r[$field]=self::scalar($row[$field]??'',$field,$field==='source_ref'?1000:255);
            if ($r['raw_marking']===''&&self::scalar($row['load_single']??'','Нагрузка')===''&&self::scalar($row['load_dual']??'','Нагрузка')===''&&self::scalar($row['pressure_kpa']??'','Давление')===''&&$r['speed_symbol']==='') continue;
            if (!in_array($r['verification'],['candidate','legacy_unverified','confirmed','conflict'],true)||!in_array($r['operating_mode'],['single','dual','cyclic','static','manufacturer_other'],true)) throw new \InvalidArgumentException('Укажите режим и статус нагрузки.');
            foreach(['load_single','load_dual'] as $field){$v=self::scalar($row[$field]??'',$field);if($v!==''&&(!preg_match('/^\d{1,3}$/D',$v)||(int)$v>500))throw new \InvalidArgumentException('Индекс нагрузки должен быть отдельным числом; исходную маркировку сохраните выше.');$r[$field]=$v===''?null:(int)$v;}
            if ($r['speed_symbol']!==''&&!preg_match('/^(?:A[1-8]|[B-Z])$/D',$r['speed_symbol'])) throw new \InvalidArgumentException('Индекс скорости должен быть отдельным обозначением, например A5.');
            $v=self::scalar($row['pressure_kpa']??'','Давление');if($v!==''&&(!preg_match('/^\d{1,5}(?:\.\d{1,3})?$/D',$v)||(float)$v<=0))throw new \InvalidArgumentException('Некорректное давление режима, кПа.');$r['pressure_kpa']=$v===''?null:$v;
            if($v!==''&&(in_array($type,['tyre_solid','tyre_bandage'],true)||$solidWheel))throw new \InvalidArgumentException('Давление неприменимо к цельнолитой или бандажной шине.');
            if ($r['verification']==='confirmed'&&($r['source_ref']===''||$r['load_single']===null||($r['speed_symbol']===''&&$r['operating_mode']!=='static')))throw new \InvalidArgumentException('Для подтверждённого режима нужны источник, нагрузка и связанная скорость (кроме статического).');
            $r['speed_symbol']=$r['speed_symbol']?:null;$ratings[]=$r;
        }
        if(count($ratings)>12)throw new \InvalidArgumentException('Допускается не более 12 режимов нагрузки.');
        foreach($ratings as$r)if($r['verification']!=='confirmed')$issues[]='Маркировка нагрузки/скорости: требуется проверить условия';
        if($ratings&&self::applicability('service_ratings',$type)==='not_applicable')throw new \InvalidArgumentException('Индексы шины не применяются к выбранному изделию.');
        return ['type'=>$type,'values'=>$values,'sizes'=>$sizes,'ratings'=>$ratings,'issues'=>$issues];
    }
    private static function barcodeValid(string $code): bool
    {
        $sum=0;$weight=3;for($i=strlen($code)-2;$i>=0;$i--){$sum+=(int)$code[$i]*$weight;$weight=$weight===3?1:3;}return (10-$sum%10)%10===(int)substr($code,-1);
    }
    public function save(int $productId,int $expectedVersion,array $input,int $actorId): int
    {
        if($this->db->inTransaction())throw new \RuntimeException('Catalog save requires its own transaction.');
        $this->db->beginTransaction();
        try{$version=$this->saveInTransaction($productId,$expectedVersion,$input,$actorId);$this->db->commit();return $version;}
        catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    /** Caller owns commit/rollback; permits atomic migration journal + profile writes. */
    public function saveInTransaction(int $productId,int $expectedVersion,array $input,int $actorId): int
    {
        if(!$this->db->inTransaction())throw new \RuntimeException('Active transaction required.');
        $dictionary=new CatalogDictionary($this->db);
        if($dictionary->installed()){
            $dictionaryVersion=(int)$this->db->query('SELECT version FROM catalog_dictionary_state WHERE id=1 LOCK IN SHARE MODE')->fetchColumn();
            if(isset($input['dictionary_version'])&&(int)$input['dictionary_version']!==$dictionaryVersion)throw new \RuntimeException('Правила справочника изменились. Обновите карточку перед сохранением.');
            self::useDictionary($this->db);
        }
        if($actorId<=0||$productId<=0||$expectedVersion<0)throw new \InvalidArgumentException('Некорректный автор, товар или версия.');
            $this->query('INSERT IGNORE INTO catalog_product_profile(product_id,type_id,version,updated_at) SELECT ?,id,0,NOW() FROM catalog_type WHERE code=?',[$productId,'unclassified']);
            $version=$this->query('SELECT version FROM catalog_product_profile WHERE product_id=? FOR UPDATE',[$productId])->fetchColumn();
            if ($version===false||(int)$version!==$expectedVersion) throw new \RuntimeException('Карточка изменена другим редактором. Перезагрузите страницу и сравните изменения.');
            $historical=$this->query("SELECT MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(before_json,'$.version')) AS UNSIGNED)) FROM catalog_property_revision WHERE product_id=?",[$productId])->fetchColumn();
            $nextVersion=max($expectedVersion+1,$historical===null?1:(int)$historical+2);
            if($nextVersion>4294967295)throw new \RuntimeException('Лимит версий достигнут.');
            $before=$this->read($productId);
            foreach($input['values']??[] as$code=>$row){
                $def=self::definitions()[$code]??[];$previous=$before['values'][$code]??[];
                if(!empty($def['archived'])&&($row['state']??'unknown')==='known'&&(($previous['state']??'unknown')!=='known'||($row['value']??'')!=($previous['value']??'')))throw new \InvalidArgumentException('Архивное свойство нельзя назначить заново: '.$def['label']);
                foreach((array)($row['value']??[])as$option)if(is_scalar($option)&&isset($def['archived_options'][(string)$option])&&!in_array($option,(array)($previous['value']??[]),true))throw new \InvalidArgumentException('Архивный вариант нельзя назначить заново: '.$def['label']);
            }
            $merged=$before;
            foreach(['type','sizes','ratings']as$key)if(array_key_exists($key,$input))$merged[$key]=$input[$key];
            if(isset($input['values'])&&!is_array($input['values']))throw new \InvalidArgumentException('Некорректные свойства.');
            $merged['values']=array_replace($before['values'],$input['values']??[]);
            $after=self::normalize($merged);
            (new CatalogSizeSearch($this->db))->sync($productId,$before,$after,$nextVersion);
            $properties=$this->db->query('SELECT code,id,kind FROM catalog_property')->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
            foreach($after['values'] as $code=>$row){
                $property=$properties[$code];$id=(int)$property['id'];$kind=$property['kind'];
                $number=in_array($kind,['integer','decimal'],true)&&$row['state']==='known'?$row['value']:null;
                $bool=$kind==='boolean'&&$row['state']==='known'?$row['value']:null;
                $text=$kind==='text'&&$row['state']==='known'?$row['value']:null;
                $this->query('INSERT INTO catalog_product_property(product_id,property_id,value_state,verification,numeric_value,text_value,bool_value,source_ref) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE value_state=VALUES(value_state),verification=VALUES(verification),numeric_value=VALUES(numeric_value),text_value=VALUES(text_value),bool_value=VALUES(bool_value),source_ref=VALUES(source_ref)',[$productId,$id,$row['state'],$row['verification'],$number,$text,$bool,$row['source_ref']]);
                $valueId=(int)$this->query('SELECT id FROM catalog_product_property WHERE product_id=? AND property_id=?',[$productId,$id])->fetchColumn();
                $this->query('DELETE FROM catalog_product_property_option WHERE value_id=?',[$valueId]);
                if(is_array($row['value']))foreach($row['value'] as $option){$optionId=$this->query('SELECT id FROM catalog_property_option WHERE property_id=? AND code=?',[$id,$option])->fetchColumn();if(!$optionId)throw new \RuntimeException('Справочник не установлен: '.$code);$this->query('INSERT INTO catalog_product_property_option(value_id,property_id,option_id) VALUES(?,?,?)',[$valueId,$id,$optionId]);}
            }
            $this->query('DELETE FROM catalog_product_size WHERE product_id=?',[$productId]);
            foreach($after['sizes'] as $row)$this->query('INSERT INTO catalog_product_size(product_id,normalized_size,role,verification,source_ref,conditions_text) VALUES(?,?,?,?,?,?)',[$productId,$row['size'],$row['role'],$row['verification'],$row['source_ref'],$row['conditions']]);
            $this->query('DELETE FROM catalog_tire_service_rating WHERE product_id=?',[$productId]);
            foreach($after['ratings'] as $i=>$r)$this->query('INSERT INTO catalog_tire_service_rating(product_id,sequence_no,raw_marking,load_single,load_dual,speed_symbol,operating_mode,pressure_kpa,verification,source_ref) VALUES(?,?,?,?,?,?,?,?,?,?)',[$productId,$i,$r['raw_marking'],$r['load_single'],$r['load_dual'],$r['speed_symbol'],$r['operating_mode'],$r['pressure_kpa'],$r['verification'],$r['source_ref']]);
            $this->query('UPDATE catalog_product_profile SET type_id=(SELECT id FROM catalog_type WHERE code=?),version=?,updated_at=NOW() WHERE product_id=?',[$after['type'],$nextVersion,$productId]);
            unset($after['issues']);
            $this->query('INSERT INTO catalog_property_revision(product_id,actor_id,before_json,after_json,changed_at) VALUES(?,?,?,?,NOW())',[$productId,$actorId,json_encode($before,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($after,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
        return $nextVersion;
    }
    public function history(int $productId): array
    {
        return $this->query('SELECT id,actor_id,changed_at FROM catalog_property_revision WHERE product_id=? ORDER BY id DESC LIMIT 20',[$productId])->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function restore(int $productId,int $revisionId,int $expectedVersion,int $actorId): int
    {
        $json=$this->query('SELECT before_json FROM catalog_property_revision WHERE id=? AND product_id=?',[$revisionId,$productId])->fetchColumn();
        if($json===false)throw new \InvalidArgumentException('Изменение не найдено у этого товара.');
        $before=self::normalize(json_decode($json,true,512,JSON_THROW_ON_ERROR));
        return $this->save($productId,$expectedVersion,$before,$actorId);
    }
}
