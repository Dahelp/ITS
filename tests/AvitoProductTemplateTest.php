<?php
require dirname(__DIR__).'/vendor/autoload.php';
class_alias(\RedBeanPHP\R::class, 'R');
R::setup('sqlite::memory:'); R::freeze(true);
R::exec('CREATE TABLE category(id INTEGER,name TEXT,parent_id INTEGER)');
R::exec("INSERT INTO category VALUES(34,'Шины',0),(1,'Шины для спецтехники',34),(9,'Шины для минипогрузчиков',1),(2,'Шины для квадроциклов',34),(3,'Диски',0),(27,'Диски для минипогрузчиков',3),(4,'Фильтры',0),(12,'Масляные фильтры',4)");
use app\services\AvitoProductTemplate as Template;
$cases = [
    [['category_id'=>9,'name'=>'12-16.5 12PR EKS-1 EKKA Шина','attribute_text'=>'Посадочный диаметр на диск: 16.5','description'=>'Для установки на диск'], 'Шины для грузовиков и спецтехники'],
    [['category_id'=>2,'name'=>'27x11-14 P350 EKKA','description'=>'Колёсный диск не входит'], 'Мотошины'],
    [['category_id'=>27,'name'=>'Диск 9.75x16.5','description'=>'Для шины минипогрузчика'], 'Диски'],
    [['category_id'=>12,'name'=>'Фильтр масляный EK-2095','description'=>'Система смазки'], 'Фильтры и фильтроэлементы'],
    [['name'=>'Шина 12-16.5','attribute_text'=>'Диск: 16.5'], 'Шины для грузовиков и спецтехники'],
    [['name'=>'Диск 9.75x16.5','description'=>'Шины для спецтехники'], 'Диски'],
    [['name'=>'Шина 27x11-14 для квадроцикла'], 'Мотошины'],
];
foreach ($cases as [$product,$expected]) {
    if (Template::productType($product) !== $expected) throw new RuntimeException('Wrong type: '.$product['name']);
}
$long = 'Шина 12-16.5 12PR EKS-1 EKKA для минипогрузчика на любой диск';
if (Template::title(['name'=>$long]) !== $long) throw new RuntimeException('Draft title was truncated');
if (Template::title(['name'=>'<b>Шина</b>  12-16.5 &amp; EKKA']) !== 'Шина 12-16.5 & EKKA') throw new RuntimeException('Title normalization');
if (Template::titleError(str_repeat('Я',50)) !== '' || Template::titleError(str_repeat('Я',51)) === '') throw new RuntimeException('Cyrillic limit boundary');
if (Template::titleError(str_repeat('Я',49).'🙂') !== '') throw new RuntimeException('Unicode character counting');
echo "Avito product template: category precedence, description contamination, complete titles and Unicode limits OK\n";
