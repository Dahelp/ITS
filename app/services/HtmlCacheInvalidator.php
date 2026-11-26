<?php
namespace app\services;

/** Observe the application's RedBean adapter, including CLI inventory imports. */
final class HtmlCacheInvalidator implements \RedBeanPHP\Observer
{
    private static ?\WeakMap $adapters = null;
    public static function attach($adapter, ?self $observer=null): void
    {
        self::$adapters ??= new \WeakMap();
        if (isset(self::$adapters[$adapter])) return;
        $adapter->addEventListener('sql_exec',$observer ?? new self());
        self::$adapters[$adapter]=true;
    }
    private bool $registered = false;
    private HtmlPageCache $cache;
    private string $objectDir;
    public function __construct(?HtmlPageCache $cache=null, ?string $objectDir=null)
    {
        $this->cache=$cache ?? HtmlPageCache::instance();
        $this->objectDir=$objectDir ?? dirname(__DIR__,2).'/tmp/cache';
    }
    public static function affectsCatalog(string $sql): bool
    {
        $sql=preg_replace('~/\*.*?\*/|--[^\r\n]*(?:\r?\n|$)~s',' ',$sql);
        if (!preg_match('~^\s*(?:INSERT(?:\s+IGNORE)?\s+INTO|REPLACE\s+INTO|UPDATE(?:\s+(?:IGNORE|LOW_PRIORITY))*|DELETE\s+FROM|TRUNCATE(?:\s+TABLE)?|ALTER\s+TABLE|DROP\s+TABLE(?:\s+IF\s+EXISTS)?)\s+(?:`?[a-z0-9_]+`?\.)?`?([a-z0-9_]+)~i',$sql,$m)) return false;
        return (bool)preg_match('~^(?:product|category|modification|attribute|filter|catalog_|brand|currency|options$|actions$|plagins|related_product|similar_product|service_product|review|certificate|stock)~i',$m[1]);
    }
    public function onEvent($eventname,$adapter)
    {
        if ($eventname==='sql_exec' && self::affectsCatalog($adapter->getSQL())) {
            if (!$this->registered) {
                $this->registered=true;
                $this->clearCommonObjects();
            }
            $this->cache->onMutationEnd('common-objects',function():void{$this->clearCommonObjects();});
            $this->cache->beginMutation();
        }
    }
    public function clearCommonObjects(): void
    {
        // Clear both before SQL and at shutdown, after transaction completion.
        $root=$this->objectDir;
        foreach (['cats','ishop_menu','banners_front','filter_group','filter_attrs'] as $key) @unlink($root.'/'.md5($key).'.txt');
    }
}
