<?php

namespace app\controllers;

class StaticController {
    public function gone(){
        http_response_code(410);
        @readfile(__DIR__ . '/../../public/404.html');
        exit;
    }
    public function notFound(){
        http_response_code(404);
        @readfile(__DIR__ . '/../../public/404.html');
        exit;
    }
}
