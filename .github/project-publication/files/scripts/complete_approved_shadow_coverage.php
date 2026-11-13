<?php
if(PHP_SAPI!=='cli')exit;
$file=dirname(__DIR__).'/public/css/style.css';$css=file_get_contents($file);
$marker='/* Approved block shadow: exact .desc-prod-inner.bg-light.shadow reference. */';
$at=strpos($css,$marker);if($at===false)throw new RuntimeException('Missing approved rules');
$before=substr($css,0,$at);$rules=substr($css,$at);
$old=".card,\n.shadow {";
$new=".card,\n.decide.decide--pro .decide-trust-item,\n.decide.decide--pro .decide-right-form,\n.site-footer.site-footer--deluxe,\n.shadow-sm,\n.shadow-lg,\n.shadow {";
if(substr_count($rules,$old)!==1)throw new RuntimeException('Unexpected selector context');
file_put_contents($file,$before.str_replace($old,$new,$rules));echo "Additional shared surfaces matched\n";
