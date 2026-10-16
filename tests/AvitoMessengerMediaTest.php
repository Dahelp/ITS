<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\AvitoApiClient;
use app\services\AvitoMessenger;

class MediaFakeApi extends AvitoApiClient
{
    public array $calls = [];
    public string $voiceUrl = 'https://media.example.test/voice.mp4?signature=test';
    public function __construct() {}
    public function api(string $method, string $path, array $options = []): array
    {
        $this->calls[] = [$method, $path, $options];
        if ($path === '/core/v1/accounts/self') return ['id'=>12345];
        if (substr($path, -13) === '/uploadImages') return ['image.example-id'=>['1280x960'=>'https://media.example.test/photo.jpg']];
        if (strpos($path, '/getVoiceFiles?') !== false) {
            parse_str(parse_url($path, PHP_URL_QUERY), $query);
            return ['voices_urls'=>[$query['voice_ids']=>$this->voiceUrl]];
        }
        return ['id'=>'sent-image-message', 'type'=>'image'];
    }
}
function mediaCheck($value, $message) { if (!$value) throw new RuntimeException($message); }
$api = new MediaFakeApi(); $client = new AvitoMessenger($api);
$photo = tempnam(sys_get_temp_dir(), 'avito-photo-');
$invalid = tempnam(sys_get_temp_dir(), 'avito-invalid-');
$large = tempnam(sys_get_temp_dir(), 'avito-large-');
try {
    // A neutral PNG fixture, no personal data or live messages.
    file_put_contents($photo, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a0ioAAAAASUVORK5CYII='));
    $result = $client->sendPhoto('chat/with?reserved', $photo);
    mediaCheck($result['type'] === 'image', 'Photo message result');
    mediaCheck($api->calls[1][0] === 'POST' && $api->calls[1][1] === '/messenger/v1/accounts/12345/uploadImages', 'Account-scoped upload');
    $file = $api->calls[1][2]['multipart']['uploadfile[]'];
    mediaCheck($file instanceof CURLFile && $file->getMimeType() === 'image/png' && $file->getPostFilename() === 'photo.png', 'Actual file content determines MIME and filename');
    mediaCheck($api->calls[2] === ['POST','/messenger/v1/accounts/12345/chats/chat%2Fwith%3Freserved/messages/image',['json'=>['image_id'=>'image.example-id']]], 'Uploaded image ID sent to selected chat');
    $count = count($api->calls);
    file_put_contents($invalid, '<?php echo "not an image";');
    $handle = fopen($large, 'wb'); ftruncate($handle, AvitoMessenger::maxUploadBytes()+1); fclose($handle);
    foreach ([$invalid, $large, $photo.'.missing'] as $path) {
        try { $client->uploadImage($path); throw new RuntimeException('Invalid file accepted'); }
        catch (InvalidArgumentException $expected) {}
    }
    try { $client->sendPhoto('', $photo); throw new RuntimeException('Invalid chat accepted'); }
    catch (InvalidArgumentException $expected) {}
    mediaCheck(count($api->calls) === $count, 'Invalid uploads and chat send no network requests');
    $url = $client->voiceUrl('voice/id+example');
    mediaCheck($url['url'] === $api->voiceUrl, 'Voice URL mapping');
    mediaCheck(strpos(end($api->calls)[1], 'voice_ids=voice%2Fid%2Bexample') !== false, 'Voice ID is safely query encoded');
    foreach (['http://media.example.test/voice', 'javascript:alert(1)', 'https://name:pass@media.example.test/voice'] as $unsafe) {
        $api->voiceUrl = $unsafe;
        try { $client->voiceUrl('voice'); throw new RuntimeException('Unsafe URL accepted'); }
        catch (RuntimeException $expected) { mediaCheck($expected->getMessage() !== 'Unsafe URL accepted', 'Reject unsafe voice URL'); }
    }
    mediaCheck(AvitoMessenger::maxUploadBytes() <= 24 * 1024 * 1024, 'Observe Avito upload limit');
    echo "AvitoMessengerMedia: OK\n";
} finally { unlink($photo); unlink($invalid); unlink($large); }
