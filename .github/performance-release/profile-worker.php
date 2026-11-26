<?php
namespace ishop { final class Db { public static function instance(){return new self();} } }
namespace {
if(PHP_SAPI!=='cli')exit(1);
final class ReadProfile {
 public array $queries=[];
 public static function guard(string $sql):void {
  $clean=preg_replace('~/\*.*?\*/|--[^\r\n]*(?:\r?\n|$)~s',' ',$sql);
  if(!preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN|SET)\b/i',$clean)||preg_match('/\b(INTO\s+(OUTFILE|DUMPFILE)|FOR\s+UPDATE)\b/i',$clean))throw new \RuntimeException('Read-only profiling rejected a write/lock');
 }
 public function add(string $sql,array $params,float $ms):int{$this->queries[]=['sql'=>$sql,'params'=>$params,'ms'=>$ms];return count($this->queries)-1;}
}
final class ReadStatement extends \PDOStatement {
 private ReadProfile $profile;private array $bound=[];private int $record=-1;
 protected function __construct(ReadProfile $profile){$this->profile=$profile;}
 public function bindValue(string|int $param,mixed $value,int $type=\PDO::PARAM_STR):bool{$this->bound[$param]=$value;return parent::bindValue($param,$value,$type);}
 public function bindParam(string|int $param,mixed &$var,int $type=\PDO::PARAM_STR,int $maxLength=0,mixed $driverOptions=null):bool{$this->bound[$param]=&$var;return parent::bindParam($param,$var,$type,$maxLength,$driverOptions);}
 public function execute(?array $params=null):bool {
  ReadProfile::guard($this->queryString);$bound=$this->bound;
  if($params!==null){$bound=[];foreach($params as$k=>$v)$bound[is_int($k)?$k+1:$k]=$v;}
  $t=microtime(true);try{return parent::execute($params);}finally{$this->record=$this->profile->add($this->queryString,$bound,1000*(microtime(true)-$t));}
 }
 public function fetchAll(int $mode=\PDO::FETCH_DEFAULT,mixed ...$args):array{$t=microtime(true);try{return parent::fetchAll($mode,...$args);}finally{if($this->record>=0)$this->profile->queries[$this->record]['ms']+=1000*(microtime(true)-$t);}}
}
final class ReadPDO extends \PDO {
 public function exec(string $statement):int|false{ReadProfile::guard($statement);return parent::exec($statement);}
 public function query(string $query,?int $fetchMode=null,mixed ...$args):\PDOStatement|false{ReadProfile::guard($query);return $fetchMode===null?parent::query($query):parent::query($query,$fetchMode,...$args);}
}
if(($argv[1]??'')==='--self-test'){
 $file=tempnam(sys_get_temp_dir(),'readonly-profile-');$seed=new \PDO('sqlite:'.$file);$seed->exec('CREATE TABLE product(id INTEGER PRIMARY KEY,price INTEGER)');$seed->exec('INSERT INTO product VALUES(1,100)');$seed=null;
 $collector=new ReadProfile();$test=new ReadPDO('sqlite:'.$file,null,null,[\PDO::ATTR_STATEMENT_CLASS=>[ReadStatement::class,[$collector]]]);$stmt=$test->prepare('SELECT price FROM product WHERE id=?');$stmt->bindValue(1,1,\PDO::PARAM_INT);$stmt->execute();$rows=$stmt->fetchAll(\PDO::FETCH_ASSOC);
 if($rows[0]['price']!=100||count($collector->queries)!==1)throw new \RuntimeException('Read profile failed');
 try{$test->prepare('UPDATE product SET price=999')->execute();throw new \LogicException('Write accepted');}catch(\RuntimeException $expected){}
 if($test->query('SELECT price FROM product')->fetchColumn()!=100)throw new \RuntimeException('Write occurred');$stmt=null;$test=null;unlink($file);echo "Profiler self-test: exact SQL timing/bindings and write rejection OK\n";exit;
}
$root=realpath($argv[1]??'');$url=$argv[2]??'';$output=$argv[3]??'';
$allowed=['/category/shiny-dlya-vilochnyh-pogruzchikov','/category/shiny-dlya-vilochnyh-pogruzchikov/6.50-10','/product/6-50-10-12pr-ttf-qh207-134a5-superguider-shina-dlya-vilochnogo-pogruzchika'];
if(!$root||!in_array($url,$allowed,true)||!$output)exit(2);
ini_set('display_errors','0');set_time_limit(90);
$_SERVER=array_replace($_SERVER,['HTTP_HOST'=>'its-center.ru','SERVER_NAME'=>'its-center.ru','HTTPS'=>'on','REQUEST_SCHEME'=>'https','REQUEST_URI'=>$url,'QUERY_STRING'=>ltrim($url,'/'),'REQUEST_METHOD'=>'GET','HTTP_CACHE_CONTROL'=>'no-cache','PHP_SELF'=>'/index.php','SCRIPT_NAME'=>'/index.php','REMOTE_ADDR'=>'127.0.0.1']);
$_GET=$_POST=$_COOKIE=$_REQUEST=[];
require $root.'/config/init.php';
$settings=require $root.'/config/config_db.php';$profile=new ReadProfile();$connect=microtime(true);
$pdo=new ReadPDO($settings['dsn'],$settings['user'],$settings['pass'],[\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,\PDO::ATTR_STATEMENT_CLASS=>[ReadStatement::class,[$profile]]]);
try{$pdo->exec('SET SESSION TRANSACTION READ ONLY');}catch(\Throwable$ignored){}
$connectionMs=1000*(microtime(true)-$connect);
\RedBeanPHP\R::setup($pdo);\RedBeanPHP\R::freeze(true);class_alias('\RedBeanPHP\R','R');
\R::ext('xdispense',static fn($type)=>\R::getRedBean()->dispense($type));
$start=microtime(true);http_response_code(200);ob_start(static fn($body)=>'');
register_shutdown_function(static function()use($profile,$pdo,$start,$connectionMs,$url,$output):void{
 $elapsed=1000*(microtime(true)-$start);$queries=$profile->queries;$totalSql=array_sum(array_column($queries,'ms'));usort($queries,static fn($a,$b)=>$b['ms']<=>$a['ms']);$top=array_slice($queries,0,12);
 foreach($top as&$q){if(!preg_match('/^\s*SELECT\b/i',$q['sql']))continue;try{$stmt=$pdo->prepare('EXPLAIN '.$q['sql']);foreach($q['params']as$k=>$v)$stmt->bindValue($k,$v,is_int($v)?\PDO::PARAM_INT:\PDO::PARAM_STR);$stmt->execute();$q['explain']=$stmt->fetchAll(\PDO::FETCH_ASSOC);}catch(\Throwable$e){$q['explain_error']=get_class($e);}}unset($q);
 file_put_contents($output,json_encode(['url'=>$url,'read_only'=>true,'http_status'=>http_response_code(),'total_ms'=>$elapsed,'connect_ms'=>$connectionMs,'sql_ms'=>$totalSql,'query_count'=>count($queries),'top'=>$top],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
});
require $root.'/public/index.php';
}
