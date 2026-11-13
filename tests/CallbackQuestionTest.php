<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

foreach (['', '   ', "\n\t\r", "\u{00A0}\u{200B}\u{FEFF}"] as $text) {
    if (app\models\User::hasCallbackQuestion($text)) {
        throw new RuntimeException('Blank callback question accepted');
    }
}
foreach (['Нужны 4 шины. Подготовьте счёт.', 'Уточните доставку в Казань', "\nНужна консультация\n"] as $text) {
    if (!app\models\User::hasCallbackQuestion($text)) {
        throw new RuntimeException('Non-empty callback question rejected');
    }
}
echo "Callback question validation tests passed\n";
