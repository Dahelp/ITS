<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\BoundedLogTail;
function tailCheck($ok, $label) { if (!$ok) throw new RuntimeException($label); }
$path = tempnam(sys_get_temp_dir(), 'inventory-log-tail-');
try {
    file_put_contents($path,"first\r\nsecond\r\nthird\r\n");
    tailCheck(BoundedLogTail::read($path,2)['lines'] === ['second','third'], 'Read last lines and strip CRLF');
    file_put_contents($path,'');
    tailCheck(BoundedLogTail::read($path)['lines'] === [], 'Empty log');
    tailCheck(BoundedLogTail::read($path.'.missing')['lines'] === [], 'Missing log is optional');
    $handle = fopen($path,'wb'); ftruncate($handle,64*1024*1024); fseek($handle,0,SEEK_END);
    for ($i=1;$i<=100;$i++) fwrite($handle,'line-'.$i."\n");
    fclose($handle);
    $before = memory_get_usage(true); $large = BoundedLogTail::read($path);
    tailCheck(count($large['lines'])===80 && $large['lines'][0]==='line-21' && end($large['lines'])==='line-100','Correct tail from a 64 MB log');
    tailCheck($large['bytes_read']<=65537 && $large['truncated'],'Bound read independently of file size');
    tailCheck(memory_get_peak_usage(true)-$before<4*1024*1024,'Bounded memory use');
    file_put_contents($path,str_repeat('x',200000));
    $long=BoundedLogTail::read($path,80,1024);
    tailCheck(count($long['lines'])===1 && strlen($long['lines'][0])<1100 && $long['truncated'],'Single huge line cannot exhaust memory');
    file_put_contents($path,"one\ntwo\nthree");
    tailCheck(BoundedLogTail::read($path,80,9)['lines']===['two','three'],'Byte boundary at complete line');
    file_put_contents($path,"bad-byte-\xff\n");
    $encoded=json_encode(BoundedLogTail::read($path),JSON_INVALID_UTF8_SUBSTITUTE);
    tailCheck(is_string($encoded) && json_decode($encoded,true)!==null,'Legacy encoding remains reportable as JSON');
    echo "BoundedLogTail: OK (64 MB fixture)\n";
} finally { unlink($path); }
