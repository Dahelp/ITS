<?php
if(PHP_SAPI!=='cli')exit;
$file=dirname(__DIR__).'/public/css/style.css';$css=file_get_contents($file);
$marker='/* Shared panel surface: matches the product detail card. */';
$at=strpos($css,$marker);if($at===false)throw new RuntimeException('Missing shared panel rules');
$before=substr($css,0,$at);$rules=substr($css,$at);
$rules=str_replace('--site-panel-shadow: 0 10px 30px rgba(15, 23, 42, .06);','--site-panel-shadow: 0 2px 10px rgba(15, 23, 42, .04);',$rules);
$rules=str_replace('border: 1px solid var(--site-panel-border);','border: 0;',$rules);
file_put_contents($file,$before.$rules);echo "Matched rendered product card: no outline, soft 2px/10px shadow\n";
