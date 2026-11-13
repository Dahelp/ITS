<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use app\services\MerchantTyreAttributes as A;
function merchantCheck($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$a = A::enrich('Шина Superguider QH207 6.50-10 134A5', ['Размер шины'=>'6.50-10','Тип'=>'TTF','Применение'=>'Вилочный погрузчик']);
merchantCheck($a['Диаметр'] === '10' && $a['Индекс нагрузки'] === '134' && $a['Индекс скорости'] === 'A5', 'Known size and marked indexes');
merchantCheck($a['Способ герметизации'] === 'камерные', 'TTF sealing');
foreach (['17.5-25'=>'25','12.5/80-18'=>'18','28x9-15'=>'15','21L-24'=>'24','18.00R25'=>'25'] as $size=>$rim) {
    $a = A::enrich('Шина '.$size, ['Размер шины'=>$size]);
    merchantCheck($a['Диаметр'] === $rim && !isset($a['Индекс нагрузки']), 'Size is not load index: '.$size);
}
$a = A::enrich('Шина 6.50-10 134A5', ['Диаметр'=>'confirmed','Индекс нагрузки'=>'130','Индекс скорости'=>'A8']);
merchantCheck($a['Диаметр'] === 'confirmed' && $a['Индекс нагрузки'] === '130' && $a['Индекс скорости'] === 'A8', 'Never overwrite supplied facts');
$a = A::enrich('Шина цельнолитая EKKA', ['Тип шины'=>'цельнолитые']);
merchantCheck(!isset($a['Способ герметизации']) && !isset($a['Индекс нагрузки']), 'No invented fields for solid tyres');
merchantCheck(A::enrich('Диск 6.50-10', ['Размер шины'=>'6.50-10']) === ['Размер шины'=>'6.50-10'], 'Rims unaffected');
$a = A::enrich('Шина 15х4 1/2-8', ['Размер шины'=>'15x4 1/2-8']);
merchantCheck(!isset($a['Диаметр']), 'Ambiguous size left for review');
echo "Merchant tyre attribute checks OK\n";
