<?php
namespace app\controllers;

use app\services\AvitoMobileAuth;
use app\services\AvitoChatOperations;

class AvitoMobileController extends \ishop\base\Controller
{
    public function handleAction()
    {
        $this->layout = false; $this->view = false;
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        $operation = (string)($this->route['operation'] ?? '');
        try {
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') throw new \RuntimeException('Требуется HTTPS.',400);
            $reads = array_merge(['me','sessions','capabilities'],AvitoChatOperations::READS);
            $writes = array_merge(['login','logout','logout-all'],AvitoChatOperations::WRITES);
            if (!in_array($operation,array_merge($reads,$writes),true)) throw new \RuntimeException('Метод не найден.',404);
            $expected = in_array($operation,$writes,true) ? 'POST' : 'GET';
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== $expected) { header('Allow: '.$expected); throw new \RuntimeException('Неверный HTTP-метод.',405); }
            $input = [];
            if ($expected === 'POST') {
                $type = strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE'] ?? '')[0]));
                if ($type === 'application/json') {
                    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0)>65536) throw new \RuntimeException('Запрос слишком большой.',413);
                    $body = file_get_contents('php://input',false,null,0,65537);
                    if (strlen($body)>65536) throw new \RuntimeException('Запрос слишком большой.',413);
                    $input = json_decode($body,true,16,JSON_THROW_ON_ERROR);
                    if (!is_array($input) || array_is_list($input) && $input !== []) throw new \InvalidArgumentException('Требуется JSON-объект.');
                    foreach ($input as $value) if (!is_scalar($value) && $value !== null) throw new \InvalidArgumentException('Некорректное поле запроса.');
                } elseif ($type === 'multipart/form-data' && in_array($operation,['send-image','upload-document'],true)) {
                    $input = $_POST;
                } else throw new \RuntimeException('Используйте application/json или multipart для файла.',415);
            }
            foreach (array_merge($_GET,$input) as $value) if (!is_scalar($value) && $value !== null) throw new \InvalidArgumentException('Некорректное поле запроса.');
            new \app\models\AppModel();
            $auth = new AvitoMobileAuth();
            if ($operation === 'login') {
                $result = $auth->login((string)($input['email']??''),(string)($input['password']??''),(string)($input['device_name']??''),(string)($_SERVER['REMOTE_ADDR']??''));
            } else {
                $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
                if (!preg_match('/\ABearer ([a-f0-9]{64})\z/iD',$header,$match)) throw new \RuntimeException('Требуется Bearer-токен.',401);
                $token = $match[1]; $user = $auth->authenticate($token);
                if ($operation === 'me') $result = $user;
                elseif ($operation === 'sessions') $result = ['sessions'=>$auth->sessions($user['id'],$token)];
                elseif ($operation === 'logout' || $operation === 'logout-all') { $auth->logout($token,$user['id'],$operation === 'logout-all'); $result = ['ok'=>true]; }
                elseif ($operation === 'capabilities') $result = ['version'=>1,'timezone'=>'Europe/Moscow','token_ttl'=>AvitoMobileAuth::TTL,'read_operations'=>AvitoChatOperations::READS,'write_operations'=>AvitoChatOperations::WRITES,'max_text_length'=>1000,'max_image_bytes'=>\app\services\AvitoMessenger::maxUploadBytes(),'max_document_bytes'=>\app\services\AvitoDocumentStore::maxUploadBytes(),'document_types'=>['pdf','docx'],'events_poll_seconds'=>5];
                else $result = (new AvitoChatOperations())->execute($operation,$_GET,$input,$_FILES,$user['id']);
            }
            echo json_encode(['data'=>$result],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\JsonException | \InvalidArgumentException $e) {
            http_response_code(400); echo json_encode(['error'=>['code'=>'invalid_request','message'=>$e instanceof \JsonException ? 'Некорректный JSON.' : $e->getMessage()]],JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(),[400,401,403,404,405,413,415,429],true) ? $e->getCode() : 502;
            http_response_code($status);
            if ($status===401) header('WWW-Authenticate: Bearer');
            if ($status===429) header('Retry-After: 900');
            echo json_encode(['error'=>['code'=>'http_'.$status,'message'=>$status===502 ? 'Сервис временно недоступен. Повторите запрос позже.' : $e->getMessage()]],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
        }
        exit;
    }
}
