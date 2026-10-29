<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use app\services\CompleteAvitoLinker;

function linkerCheck($condition, string $message): void { if (!$condition) throw new RuntimeException($message); }

$index = CompleteAvitoLinker::indexes([
    ['id' => 26, 'name' => 'Комплект шин 27x9-14/27x11-14 P350 EKKA', 'alias' => 'komplekt-shin-27x9-14-p350', 'img' => 'set-26.webp'],
    ['id' => 31, 'name' => 'Комплект фильтров JCB', 'alias' => 'komplekt-filtrov-jcb', 'img' => 'set-31.webp'],
]);

$match = CompleteAvitoLinker::match(['ad_external_id' => 'its-complete-26'], $index);
linkerCheck($match['id'] === 26 && $match['method'] === 'external_id', 'Legacy external id');
$match = CompleteAvitoLinker::match(['description' => 'Подробнее https://its-center.ru/complete/komplekt-filtrov-jcb'], $index);
linkerCheck($match['id'] === 31 && $match['method'] === 'complete_url', 'Complete URL');
$match = CompleteAvitoLinker::match(['images_json' => '["https://its-center.ru/images/complete/baseimg/set-26.webp"]'], $index);
linkerCheck($match['id'] === 26 && $match['method'] === 'complete_image', 'Complete image');
$match = CompleteAvitoLinker::match(['title' => 'Комплект шин 27×9-14 / 27x11-14 P350 EKKA'], $index);
linkerCheck($match['id'] === 26 && $match['method'] === 'exact_name', 'Normalised exact name');
$match = CompleteAvitoLinker::match(['title' => 'Шина 27x9-14 P350 EKKA'], $index);
linkerCheck($match['id'] === 0, 'A single tyre must not be linked');
$match = CompleteAvitoLinker::match(['title' => 'Шины 27x9-14 / 27x11-14 P350 EKKA Комплект 4PR'], $index);
linkerCheck($match['id'] === 26 && $match['method'] === 'title_signature', 'Legacy set title signature');
echo "Complete Avito linker tests passed\n";
