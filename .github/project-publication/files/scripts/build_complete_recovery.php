<?php
$root=dirname(__DIR__);$state=json_decode(file_get_contents($root.'/tmp/final-restoration-inputs.json'),true,512,JSON_THROW_ON_ERROR);$extra=json_decode(file_get_contents($root.'/tmp/current-complete-linker.json'),true,512,JSON_THROW_ON_ERROR);$state['current']=array_replace($state['current'],$extra['files']);$stage=$root.'/outputs/complete-recovery-20261113';$manifest=[];
function crRead($p){global$state;return str_replace("\r\n","\n",base64_decode($state['current'][$p]));}
function crReplace(&$s,$a,$b,$n=1){if(substr_count($s,$a)!==$n)throw new RuntimeException('Context: '.substr($a,0,110));$s=str_replace($a,$b,$s);}
function crSave($p,$s){global$stage,$manifest,$state;$dest=$stage.'/files/'.$p;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0775,true);file_put_contents($dest,$s);$old=$state['current'][$p]??null;$manifest[$p]=['before'=>$old===null?null:hash('sha256',base64_decode($old)),'after'=>hash('sha256',$s)];}
foreach(['app/services/CompleteAvito.php','app/services/CompleteAvitoPublication.php','bin/sync_complete_avito.php']as$p)crSave($p,str_replace("\r\n","\n",file_get_contents($root.'/'.$p)));
// Restore the published complete synchronizer while retaining later ad metadata and deletion state.
$p='app/services/CompleteAvito.php';$s=file_get_contents($stage.'/files/'.$p);
crReplace($s,'SELECT a.id, a.complete_id, a.review_status, c.name, c.hide, c.img, c.category_id','SELECT a.id, a.complete_id, a.review_status, a.deletion_requested_at, a.images_json, a.product_type, a.price_rub, a.manual_fields_json, c.name, c.hide, c.img, c.category_id');
crReplace($s,"            \$stock = \$ad['name']", "            \$manual = json_decode((string)(\$ad['manual_fields_json'] ?? ''), true) ?: [];\n            if (in_array('price_rub', \$manual, true)) \$totals['price'] = max(0,(int)\$ad['price_rub']);\n            \$stock = \$ad['name']");
crReplace($s,"\$ad['review_status'] !== 'needs_review' ?", "\$ad['review_status'] !== 'needs_review' && empty(\$ad['deletion_requested_at']) ?");
crReplace($s,"if ((int)\$ad['category_id'] === 2)","if ((int)\$ad['category_id'] === 2 && empty(\$ad['product_type']))");
crReplace($s,"if (\$ad['img']) {","if (\$ad['img'] && empty(\$ad['images_json'])) {");
// Imported numeric links are editable; keep the newer unsubmitted draft links intact.
crReplace($s,"WHERE complete_id = ?\", [\$id]);","WHERE complete_id = ? AND avito_id REGEXP '^[0-9]+$'\", [\$id]);");
$needle="        \\R::begin();\n        try {\n            \\R::exec(\"UPDATE avito_ad SET complete_id = NULL";
$insert=<<<'PHP'
        \R::begin();
        try {
            foreach (\R::getAll("SELECT id,manual_fields_json FROM avito_ad WHERE complete_id=? AND avito_id REGEXP '^[0-9]+$'",[$id]) as $previous) {
                if (in_array($previous['id'],$ads)) continue;
                $manual = json_decode((string)($previous['manual_fields_json']??''),true) ?: [];
                $manual[] = 'complete_id';
                \R::exec('UPDATE avito_ad SET manual_fields_json=? WHERE id=?',[json_encode(array_values(array_unique($manual))),$previous['id']]);
            }
            \R::exec("UPDATE avito_ad SET complete_id = NULL
PHP;
crReplace($s,$needle,$insert);
crSave($p,$s);
$p='app/services/CompleteAvitoLinker.php';$s=crRead($p);crReplace($s,'title, description, images_json FROM avito_ad','title, description, images_json, manual_fields_json FROM avito_ad');$needle="            \$match = self::match(\$ad, \$indexes);";crReplace($s,$needle,"            // An explicit removal in the complete editor must survive automatic legacy matching.\n            if (in_array('complete_id', json_decode((string)(\$ad['manual_fields_json'] ?? ''), true) ?: [], true)) continue;\n".$needle);crSave($p,$s);
$p='app/models/admin/PlaginsComplete.php';$before=$state['historical']['tmp/backup-p350-20261016/code.tar.gz']['before'][$p];if($state['current'][$p]!==$before)throw new RuntimeException('Complete model has later changes');crSave($p,str_replace("\r\n","\n",file_get_contents($root.'/'.$p)));
$p='app/models/admin/Avito.php';$s=crRead($p);
crReplace($s,"        return \$updated;\n    }\n\n    public static function publicationStatus", "        return \$updated + \\app\\services\\CompleteAvito::sync();\n    }\n\n    public static function publicationStatus");
crReplace($s,'WHERE p.id IS NULL AND a.deletion_requested_at IS NULL','WHERE p.id IS NULL AND a.complete_id IS NULL AND a.deletion_requested_at IS NULL');
crReplace($s,"        foreach (\$rows as \$r) {\n            \$r = \\app\\services\\AvitoWarehouse::normalize(\$r);","        foreach (\$rows as \$r) {\n            if (!\\app\\services\\CompleteAvito::canPublish(\$r)) continue;\n            \$r = \\app\\services\\AvitoWarehouse::normalize(\$r);");
crReplace($s,'public static function pushLinkedAdsToApi(): array','public static function pushLinkedAdsToApi(int $completeId = 0): array');
$split=strpos($s,'public static function pushLinkedAdsToApi(');$prefix=substr($s,0,$split);$s=substr($s,$split);
crReplace($s,'                a.quantity,\n                p.rest\n            FROM avito_ad a','                a.quantity,\n                a.complete_id, a.status, a.stock_quantity,\n                p.rest\n            FROM avito_ad a');
crReplace($s,"WHERE a.article IS NOT NULL AND a.article != '' AND a.deletion_requested_at IS NULL\\n        \");","WHERE (a.complete_id IS NOT NULL OR (a.article IS NOT NULL AND a.article != '')) AND a.deletion_requested_at IS NULL\\n        \");");
crReplace($s,"        if (!\$rows) {\n            return \$stats;\n        }\n\n        \$availableByAd", "        if (\$completeId > 0) {\n            \\app\\services\\CompleteAvito::sync();\n            \$rows = \\R::getAll('SELECT id AS ad_id,avito_id,ad_external_id,price_rub,quantity,complete_id,status,stock_quantity FROM avito_ad WHERE complete_id=? AND deletion_requested_at IS NULL',[\$completeId]);\n        }\n        if (!\$rows) {\n            return \$stats;\n        }\n\n        \$availableByAd");
crReplace($s,"            \$quantity = (int)(\$availableByAd[(int)\$row['ad_id']] ?? 0);", "            if (!empty(\$row['complete_id']) && !\\app\\services\\CompleteAvito::canPublish(\$row)) { \$stats['skipped']++; continue; }\n            \$quantity = !empty(\$row['complete_id']) ? (int)\$row['stock_quantity'] : (int)(\$availableByAd[(int)\$row['ad_id']] ?? 0);");
crSave($p,$prefix.$s);
$p='app/controllers/admin/PlaginsController.php';$s=crRead($p);$needle='$complete->editProductComplete($id, $data);';crReplace($s,$needle,$needle."\n                \\app\\services\\CompleteAvito::saveLinks((int)\$id, (string)(\$data['avito_ids'] ?? ''));",2);crSave($p,$s);
foreach(['add','edit']as$which){$p='app/views/itscenter/admin/Plagins/complete-'.$which.'.php';$s=crRead($p);$known=str_replace("\r\n","\n",file_get_contents($root.'/'.$p));$a=strpos($known,'<?php' . "\n\$linkedIds");if($a===false){$a=strpos($known,'$linkedIds =');$a=strrpos(substr($known,0,$a),'<?php');}$b=strpos($known,'</small></div>', $a);$b=strpos($known,'</div>',$b+15)+6;$block=substr($known,$a,$b-$a);if(!str_contains($block,'name="avito_ids"'))throw new RuntimeException('Link field source');$marker='<label class="col-sm-3 col-form-label" for="name">';$pos=strpos($s,$marker);$before=strrpos(substr($s,0,$pos),'<div class="form-group row">');if($pos===false||$before===false)throw new RuntimeException('Link field placement');$s=substr_replace($s,$block."\n",$before,0);crSave($p,$s);}
$p='app/controllers/admin/AvitoController.php';$s=crRead($p);crReplace($s,'$autoload = Avito::triggerAutoloadUpload();',"\$publication = \\app\\services\\CompleteAvitoPublication::run();\n            \$autoload = !empty(\$publication['autoload_requested']) ? ['triggered'=>true,'message'=>'запущена; ожидается подтверждение публикаций'] : Avito::triggerAutoloadUpload();");crSave($p,$s);
$p='app/services/InventorySyncService.php';$s=crRead($p);$needle="        return \$stats;\n    }\n\n    /** @return array<int, int> category ID => fixed quantity */";$replacement="        if (\$mode !== 'shadow') {\n            try { \$stats['avito_completes'] = CompleteAvitoPublication::run(); }\n            catch (\\Throwable \$e) { \$stats['avito_completes'] = ['errors'=>[\$e->getMessage()]]; }\n        }\n".$needle;crReplace($s,$needle,$replacement);crSave($p,$s);
$observed=json_decode(file_get_contents($root.'/outputs/recovery-evidence-second/recovery-evidence.json'),true);$protected=[];foreach($observed['hashes']as$p=>$v)$protected[$p]=$v['sha256'];$previous=json_decode(file_get_contents($root.'/outputs/confirmed-recovery-20261113/manifest.json'),true);foreach($previous as$p=>$v)$protected[$p]=$v['after'];foreach($manifest as$p=>$v)unset($protected[$p]);
file_put_contents($stage.'/protected.json',json_encode($protected,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));file_put_contents($stage.'/manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));$installer=file_get_contents($root.'/outputs/rollback-restoration-20261112/install.php');$installer=str_replace('product|modification|product_attribute|category|','avito_ad|avito_complete_publication|plagins_complete|plagins_complete_product|plagins_complete_gallery|plagins_complete_similar|product|modification|product_attribute|category|',$installer);file_put_contents($stage.'/install.php',$installer);echo count($manifest).' restored files; '.count($protected)." protected files\n";
