<?php
namespace app\services;

/** Opt-in naming channels; legacy fields and URLs are never rewritten. */
final class CatalogNaming
{
    private static ?array $stateCache=null;
    private static array $contextCache=[];
    public function __construct(private \PDO $db){}
    public static function channels(): array{return['catalog'=>'Название в каталоге','h1'=>'H1 карточки','title'=>'SEO Title','feed'=>'Название в YML спецшин'];}
    public static function tokens(): array{return['name'=>'Основное название','sku'=>'Внутренний артикул','brand'=>'Бренд из карточки','size'=>'Подтверждённый размер','model'=>'Подтверждённая модель','tread'=>'Подтверждённый протектор','pr'=>'Подтверждённая слойность PR','manufacturer'=>'Подтверждённый изготовитель'];}
    public static function install(\PDO $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS catalog_naming_state (id INT PRIMARY KEY,version INT UNSIGNED NOT NULL) ENGINE=InnoDB');
        $db->exec('INSERT IGNORE INTO catalog_naming_state(id,version) VALUES(1,1)');
        $db->exec("CREATE TABLE IF NOT EXISTS catalog_name_rule (profile_type VARCHAR(64) NOT NULL,channel VARCHAR(24) NOT NULL,template TEXT NOT NULL,enabled TINYINT NOT NULL,PRIMARY KEY(profile_type,channel)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec('CREATE TABLE IF NOT EXISTS catalog_name_override (product_id INT UNSIGNED NOT NULL,channel VARCHAR(24) NOT NULL,value VARCHAR(255) NOT NULL,PRIMARY KEY(product_id,channel)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $db->exec('CREATE TABLE IF NOT EXISTS catalog_naming_revision (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,scope VARCHAR(80) NOT NULL,actor_id INT UNSIGNED NOT NULL,before_json MEDIUMTEXT NOT NULL,after_json MEDIUMTEXT NOT NULL,changed_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    public function snapshot(): array
    {
        try{$version=$this->db->query('SELECT version FROM catalog_naming_state WHERE id=1')->fetchColumn();}catch(\PDOException$e){if($e->getCode()==='42S02')return['installed'=>false,'version'=>0,'rules'=>[],'overrides'=>[]];throw$e;}
        $out=['installed'=>(bool)$version,'version'=>(int)$version,'rules'=>[],'overrides'=>[]];
        foreach($this->db->query('SELECT * FROM catalog_name_rule')->fetchAll(\PDO::FETCH_ASSOC)as$r)$out['rules'][$r['profile_type']][$r['channel']]=['template'=>$r['template'],'enabled'=>(bool)$r['enabled']];
        foreach($this->db->query('SELECT * FROM catalog_name_override')->fetchAll(\PDO::FETCH_ASSOC)as$r)$out['overrides'][(int)$r['product_id']][$r['channel']]=$r['value'];return$out;
    }
    public static function context(array $product,array $profile=[],string $brand=''): array
    {
        $values=['name'=>(string)($product['name']??''),'sku'=>(string)($product['sku']??''),'brand'=>$brand];$sources=['name'=>'Карточка товара','sku'=>'Карточка товара','brand'=>'Справочник брендов'];
        try{$normal=CatalogProperties::normalize($profile);if(($normal['type']??'unclassified')==='unclassified')$normal['values']=[];}catch(\InvalidArgumentException$e){$normal=['values'=>[]];}
        foreach(['size'=>'tire_size','model'=>'model','tread'=>'tread_model','pr'=>'ply_rating','manufacturer'=>'manufacturer']as$token=>$code){$r=$normal['values'][$code]??[];if(($r['state']??'')==='known'&&($r['verification']??'')==='confirmed'&&trim($r['source_ref']??'')!==''&&is_scalar($r['value'])){$values[$token]=(string)$r['value'];$sources[$token]='Подтверждено: '.$r['source_ref'];}}
        return['product'=>$product,'profile_type'=>$profile['type']??'unclassified','values'=>$values,'sources'=>$sources];
    }
    public static function validateText(string $value,bool $template): string
    {
        if(preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F<>]/u',$value))throw new \InvalidArgumentException('В названии недопустимы HTML и управляющие символы.');
        $value=trim(preg_replace('/\s+/u',' ',$value)??'');if(mb_strlen($value)>255)throw new \InvalidArgumentException('Название или правило: не более 255 символов.');
        if($template){preg_match_all('/\{([a-z_]+)\}/',$value,$matches);foreach($matches[1]as$key)if(!isset(self::tokens()[$key]))throw new \InvalidArgumentException('Неизвестная подстановка: '.$key);if(preg_match('/[{}]/',preg_replace('/\{[a-z_]+\}/','',$value)))throw new \InvalidArgumentException('Проверьте фигурные скобки.');}
        elseif(str_contains($value,'{')||str_contains($value,'}'))throw new \InvalidArgumentException('Индивидуальное название вводится готовым текстом без подстановок.');return$value;
    }
    public static function resolve(string $channel,string $fallback,array $context,array $state): array
    {
        if(!isset(self::channels()[$channel]))throw new \InvalidArgumentException('Неизвестный канал названия.');$product=$context['product'];$id=(int)($product['id']??0);$source='Прежний вывод / InSEO';$warnings=[];
        $override=$state['overrides'][$id][$channel]??'';if($override!=='')return['value'=>$override,'source'=>'Индивидуальное исключение','warnings'=>[]];
        $own=$channel==='h1'?trim((string)($product['seo_h1']??'')):($channel==='title'&&!empty($product['title'])?(string)$product['title']:'');if($own!=='')return['value'=>$fallback,'source'=>'Собственное поле карточки','warnings'=>[]];
        $type=$context['profile_type'];$rule=$state['rules'][$type][$channel]??null;$source='Правило типа: '.$type;
        if(!$rule||!$rule['enabled']){$rule=$state['rules']['*'][$channel]??null;$source='Общее правило';}
        if(!$rule||!$rule['enabled'])return['value'=>$fallback,'source'=>'Прежний вывод / InSEO','warnings'=>[]];
        try{$template=self::validateText($rule['template'],true);}catch(\InvalidArgumentException$e){return['value'=>$fallback,'source'=>'Прежний вывод / InSEO','warnings'=>[$e->getMessage()]];}
        preg_match_all('/\{([a-z_]+)\}/',$template,$matches);$replace=[];foreach($matches[1]as$key){$v=trim((string)($context['values'][$key]??''));if($v==='')$warnings[]='Нет данных для {'.$key.'}';$replace['{'.$key.'}']=$v;}
        if(!$warnings){$value=trim(preg_replace('/\s+/u',' ',strtr($template,$replace))??'');if($value===''||mb_strlen($value)>255||preg_match('/[<>{}\x00-\x1F]/u',$value))$warnings[]='Результат пустой, слишком длинный или содержит недопустимые символы';}
        return$warnings?['value'=>$fallback,'source'=>'Прежний вывод / InSEO','warnings'=>$warnings]:['value'=>$value,'source'=>$source,'warnings'=>[]];
    }
    public static function reset(): void{self::$stateCache=null;self::$contextCache=[];}
    public static function apply(int $id,string $channel,string $fallback): string
    {
        $state=self::$stateCache??=(new self(\R::getPDO()))->snapshot();
        if(isset($state['overrides'][$id][$channel]))return(string)$state['overrides'][$id][$channel];
        if(!isset($state['overrides'][$id][$channel])&&!array_filter($state['rules'],static fn($rules)=>!empty($rules[$channel]['enabled'])))return$fallback;
        if(!isset(self::$contextCache[$id])){$p=\R::getRow('SELECT p.id,p.name,p.sku,p.seo_h1,p.title,b.name AS naming_brand FROM product p LEFT JOIN brand b ON b.id=p.brand_id WHERE p.id=?',[$id]);if(!$p)return$fallback;$profile=(new CatalogProperties(\R::getPDO()))->read($id);self::$contextCache[$id]=self::context($p,$profile,(string)($p['naming_brand']??''));}
        return self::resolve($channel,$fallback,self::$contextCache[$id],$state)['value'];
    }
    public static function prime(array $products,string $channel='catalog'): void
    {
        $state=self::$stateCache??=(new self(\R::getPDO()))->snapshot();if(!array_filter($state['rules'],static fn($rules)=>!empty($rules[$channel]['enabled'])))return;
        $ids=[];foreach($products as$p){$id=(int)($p['id']??$p->id??0);if($id>0&&!isset(self::$contextCache[$id])&&!isset($state['overrides'][$id][$channel]))$ids[]=$id;}if(!$ids)return;
        $service=new CatalogProperties(\R::getPDO());$scoped=(new \ReflectionMethod($service,'readAllProfiles'))->getNumberOfParameters()>0;$all=$scoped?[]:$service->readAllProfiles();
        foreach(array_chunk(array_values(array_unique($ids)),200)as$chunk){$profiles=$scoped?$service->readAllProfiles($chunk):$all;foreach(\R::getAll('SELECT p.id,p.name,p.sku,p.seo_h1,p.title,b.name AS naming_brand FROM product p LEFT JOIN brand b ON b.id=p.brand_id WHERE p.id IN ('.implode(',',$chunk).')')as$p)self::$contextCache[(int)$p['id']]=self::context($p,$profiles[(int)$p['id']]??[],(string)($p['naming_brand']??''));}
    }
    public function save(string $scope,array $rows,int $version,int $actor): void
    {
        if($actor<1||$version<1||(!str_starts_with($scope,'product:')&&!str_starts_with($scope,'type:')))throw new \InvalidArgumentException('Некорректная область настройки.');
        $isProduct=str_starts_with($scope,'product:');$key=substr($scope,strpos($scope,':')+1);
        if($isProduct){if(!preg_match('/^[1-9]\d{0,8}$/D',$key))throw new \InvalidArgumentException('Некорректный товар.');$q=$this->db->prepare('SELECT id FROM product WHERE id=?');$q->execute([(int)$key]);if(!$q->fetchColumn())throw new \InvalidArgumentException('Товар не найден.');}
        elseif($key!=='*'&&!isset(CatalogProperties::types()[$key]))throw new \InvalidArgumentException('Неизвестный тип изделия.');
        $clean=[];foreach(self::channels()as$channel=>$_){$row=$rows[$channel]??null;if(!is_array($row)||!is_string($row['value']??null))throw new \InvalidArgumentException('Форма передана не полностью.');$value=self::validateText($row['value'],!$isProduct);$enabled=($row['enabled']??'0')==='1';if(!$isProduct&&$enabled&&$value==='')throw new \InvalidArgumentException('Для включения правила нужен текст.');$clean[$channel]=['value'=>$value,'enabled'=>$enabled];}
        $this->db->beginTransaction();try{
            $current=(int)$this->db->query('SELECT version FROM catalog_naming_state WHERE id=1 FOR UPDATE')->fetchColumn();if($current!==$version)throw new \RuntimeException('Настройки названий изменились. Обновите страницу.');$before=$this->snapshot();
            foreach($clean as$channel=>$row){if($isProduct){$q=$this->db->prepare('DELETE FROM catalog_name_override WHERE product_id=? AND channel=?');$q->execute([(int)$key,$channel]);if($row['value']!==''){$q=$this->db->prepare('INSERT INTO catalog_name_override(product_id,channel,value) VALUES(?,?,?)');$q->execute([(int)$key,$channel,$row['value']]);}}
                else{$q=$this->db->prepare('INSERT INTO catalog_name_rule(profile_type,channel,template,enabled) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE template=VALUES(template),enabled=VALUES(enabled)');$q->execute([$key,$channel,$row['value'],(int)$row['enabled']]);}}
            $old=$isProduct?($before['overrides'][(int)$key]??[]):($before['rules'][$key]??[]);$q=$this->db->prepare('INSERT INTO catalog_naming_revision(scope,actor_id,before_json,after_json,changed_at) VALUES(?,?,?,?,NOW())');$q->execute([$scope,$actor,json_encode($old,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$this->db->exec('UPDATE catalog_naming_state SET version=version+1 WHERE id=1');$this->db->commit();self::reset();
        }catch(\Throwable$e){if($this->db->inTransaction())$this->db->rollBack();throw$e;}
    }
}
