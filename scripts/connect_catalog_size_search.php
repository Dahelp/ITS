<?php
$root=dirname(__DIR__);$bundle=json_decode(file_get_contents($root.'/outputs/size-search-baseline.json'),true);$stage=$root.'/outputs/size-search-release/files';
$patch=static function($p,$s){
if(str_ends_with($p,'SearchCandidateProvider.php')){$needle='    public function findCandidates(array $parsed, int $limit = 200): array';$new=<<<'CODE'
    public function findCandidates(array $parsed, int $limit = 200): array
    {
        $limit=max(1,min(1000,$limit));
        $verified=\app\services\CatalogSizeSearch::candidates($parsed);
        $legacy=$this->findLegacyCandidates($parsed,$limit);
        $byId=[];foreach($verified as$r)$byId[(int)$r['id']]=$r;
        foreach($legacy as&$r)if(isset($byId[(int)$r['id']]))$r['catalog_size_match']=$byId[(int)$r['id']]['catalog_size_match'];unset($r);
        return $this->uniqueById(array_merge($verified,$legacy),$limit);
    }
    private function findLegacyCandidates(array $parsed, int $limit = 200): array
CODE;
}elseif(str_ends_with($p,'SearchRanker.php')){$needle='        // 2. size relevance';$new=<<<'CODE'
        if(!empty($row['catalog_size_match'])){
            $score+=850;$reasons['catalog_confirmed_size_relation']=850;
        }
        // 2. size relevance
CODE;
}else{$needle='        <div class="prdt-top">';$new=<<<'CODE'
        <?php if(array_filter($products??[],static fn($p)=>!empty($p['catalog_size_match']))):?><p class="alert alert-info">В результатах также есть товары, найденные по подтверждённому альтернативному или совместимому размеру. Основной размер и применимость указаны в характеристиках товара.</p><?php endif ?>
        <div class="prdt-top">
CODE;
}if(substr_count($s,$needle)!==1)throw new RuntimeException('Patch target mismatch '.$p);return str_replace($needle,$new,$s);};
foreach($bundle as$p=>$r){$s=base64_decode($r['content']);if(hash('sha256',$s)!==$r['sha256'])throw new RuntimeException('Baseline mismatch');$dest=$stage.'/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$patch($p,$s));$local=file_get_contents($root.'/'.$p);file_put_contents($root.'/'.$p,$patch($p,$local));}echo "Search connected from verified live baselines\n";
