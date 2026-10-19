<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/services/AvitoListingCard.php';

use app\services\AvitoListingCard;

foreach ([[10,3,7],[3,3,0],[2,5,0],[0,0,0],[8,-2,8]] as [$stock,$reserve,$expected]) {
    if (AvitoListingCard::availableQuantity($stock, $reserve) !== $expected) {
        fwrite(STDERR, "FAILED: stock {$stock}, reserve {$reserve}\n"); exit(1);
    }
}
echo "Avito listing card stock tests passed\n";
