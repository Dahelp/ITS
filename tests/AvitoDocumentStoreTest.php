<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\AvitoDocumentStore;
function documentCheck($ok, $label) { if (!$ok) throw new RuntimeException($label); }
$directory = sys_get_temp_dir().'/avito-doc-test-'.bin2hex(random_bytes(8));
mkdir($directory, 0700);
$pdf = $directory.'/source.pdf'; $docx = $directory.'/source.docx';
file_put_contents($pdf, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n");
$zip = new ZipArchive(); $zip->open($docx, ZipArchive::CREATE);
$zip->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
$zip->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>');
$zip->close();
$store = new AvitoDocumentStore($directory); $now = time();
try {
    $prepared = $store->store($pdf, "../Счёт\r\n42.pdf", 'chat-a', 1, $now);
    documentCheck(strlen($prepared['token']) === 64, 'Cryptographically random bearer token');
    $meta = $store->get($prepared['token'], $now+1);
    documentCheck($meta['name'] === 'Счёт__42.pdf' && $meta['mime'] === 'application/pdf', 'Safe filename and actual MIME');
    documentCheck(file_get_contents($meta['path']) === file_get_contents($pdf), 'Download bytes match source');
    documentCheck(strpos($meta['path'], $prepared['token']) === false, 'Raw token never used as storage name');
    documentCheck($meta['expires_at'] === $now+7*86400, 'Seven-day lifetime');
    documentCheck($store->forChat($prepared['token'], 'chat-a')['created_by'] === 1, 'Document belongs to selected chat');
    try { $store->forChat($prepared['token'], 'chat-b'); throw new RuntimeException('Wrong chat accepted'); }
    catch (InvalidArgumentException $expected) {}
    foreach (['../'.$prepared['token'], str_repeat('a', 63), str_repeat('0', 64)] as $invalid) {
        try { $store->get($invalid, $now); throw new RuntimeException('Invalid token accepted'); }
        catch (RuntimeException $e) { documentCheck($e->getCode() === 404, 'Missing and invalid tokens return 404'); }
    }
    try { $store->get($prepared['token'], $now+7*86400); throw new RuntimeException('Expired link accepted'); }
    catch (RuntimeException $e) { documentCheck($e->getCode() === 410, 'Expiry is enforced at exact boundary'); }
    $word = $store->store($docx, 'invoice.docx', 'chat-a', 1, $now);
    documentCheck($store->get($word['token'])['mime'] === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'DOCX supported');
    $store->revoke($word['token'], 'chat-a');
    try { $store->get($word['token']); throw new RuntimeException('Revoked token accepted'); }
    catch (RuntimeException $e) { documentCheck($e->getCode() === 410, 'Revocation immediately blocks download'); }
    foreach ([[$pdf,'invoice.docx'], [$docx,'invoice.pdf'], [$pdf,'invoice.exe']] as [$path,$name]) {
        try { AvitoDocumentStore::validate($path,$name); throw new RuntimeException('Spoofed type accepted'); }
        catch (InvalidArgumentException $expected) {}
    }
    $zip->open($docx); $zip->addFromString('word/vbaProject.bin','macro'); $zip->close();
    try { AvitoDocumentStore::validate($docx,'invoice.docx'); throw new RuntimeException('Macro accepted'); }
    catch (InvalidArgumentException $expected) {}
    $fresh = $store->store($pdf, 'fresh.pdf', 'chat-a', 1, $now+86400);
    $store->cleanup($now+7*86400);
    documentCheck(!is_file($meta['path']), 'Expired temporary copy removed');
    documentCheck(is_file($store->get($fresh['token'], $now+7*86400)['path']), 'Cleanup preserves unexpired documents');
    echo "AvitoDocumentStore: OK\n";
} finally {
    foreach (glob($directory.'/*') ?: [] as $file) { if (is_file($file)) unlink($file); }
    if (is_file($directory.'/.htaccess')) unlink($directory.'/.htaccess');
    rmdir($directory);
}
