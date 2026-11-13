<?php
// Restore the existing PR attribute in ATV card names; leave product data and URLs intact.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__) . '/config/init.php';
require CONF . '/db_bootstrap.php';
$row = R::getRow("SELECT id, name FROM plagins_inseo WHERE id = 2 AND category_id = 2 AND tip = 'product' AND hide = 'show'");
if (!$row) { throw new RuntimeException('Expected active ATV template not found'); }
$old = (string)$row['name'];
$expected = "{tiposize}\n{protector}\n{brand}\nШины для квадроциклов";
$new = "{tiposize}\r\n{PR}PR\r\n{protector}\r\n{brand}\r\nШины для квадроциклов";
if (str_replace("\r\n", "\n", $old) === str_replace("\r\n", "\n", $new)) {
    echo "Already applied\n";
    exit;
}
if (str_replace("\r\n", "\n", $old) !== $expected) { throw new RuntimeException('Template changed; refusing to overwrite'); }
$backup = ROOT . '/storage/atv-card-ply-template-before-20261021.json';
if (!is_file($backup) && file_put_contents($backup, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
    throw new RuntimeException('Backup failed');
}
$changed = R::exec('UPDATE plagins_inseo SET name = ? WHERE id = ? AND name = ?', [$new, $row['id'], $old]);
if ($changed !== 1) { throw new RuntimeException('Template update failed'); }
foreach ([997, 1000, 1214, 1215] as $id) {
    echo $id . ': ' . \ishop\App::seoreplace($new, $id) . PHP_EOL;
}
