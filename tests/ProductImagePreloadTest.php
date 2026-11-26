<?php
require dirname(__DIR__).'/app/services/ProductImagePreload.php';
use app\services\ProductImagePreload;
function imageCheck($value,$message){if(!$value)throw new RuntimeException($message);}
$picture='<picture><source type="image/avif" srcset="/main.avif"><source type="image/webp" srcset="/main.webp"><img src="/main.jpg"></picture>';
$link=ProductImagePreload::render($picture);imageCheck(str_contains($link,'href="/main.avif"')&&!str_contains($link,'main.webp'),'preload one preferred format only');
imageCheck(ProductImagePreload::render('<img src="/main.webp?a=1&amp;b=2">')==='<link rel="preload" as="image" href="/main.webp?a=1&amp;b=2" fetchpriority="high">','fallback URL exactly escaped once');
imageCheck(ProductImagePreload::render('')===''&&ProductImagePreload::render('<img src="javascript:bad">')==='','empty/unsafe source skipped');
echo "Image preload: exact preferred picture source, single request, fallback and escaping passed\n";
