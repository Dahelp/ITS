<?php

namespace ishop;

class ErrorHandler{

    public function __construct(){
        if (DEBUG) {
            error_reporting(-1);
        } else {
            error_reporting(0);
        }

        set_exception_handler([$this, 'exceptionHandler']);
    }

    public function exceptionHandler($e){
        $code = (int)$e->getCode();
        if ($code < 100 || $code > 599) {
            $code = 500;
        }

        $this->logErrors($e->getMessage(), $e->getFile(), $e->getLine());
        $this->displayError('Исключение', $e->getMessage(), $e->getFile(), $e->getLine(), $code);
    }

    protected function logErrors($message = '', $file = '', $line = ''){
        error_log(
            "[" . date('Y-m-d H:i:s') . "] Текст ошибки: {$message} | Файл: {$file} | Строка: {$line}\n=================\n",
            3,
            ROOT . '/tmp/errors.log'
        );
    }

    protected function displayError($errno, $errstr, $errfile, $errline, $responce = 404){
        $responce = (int)$responce;
        if ($responce < 100 || $responce > 599) {
            $responce = 404;
        }

        http_response_code($responce);

        if ($responce === 404 && !DEBUG) {
            $this->render404();
            die;
        }

        if (DEBUG) {
            require WWW . '/errors/dev.php';
        } else {
            require WWW . '/errors/prod.php';
        }
        die;
    }

    protected function render404(){
        try {
            if (defined('ITS_RENDERING_404')) {
                require WWW . '/errors/404.php';
                return;
            }
            define('ITS_RENDERING_404', true);

            $title = 'Ошибка 404 - Страница не найдена';
            $description = 'Запрашиваемая страница не найдена.';
            $keywords = '';
            $robots = 'noindex, nofollow';

            $route = [
                'controller' => 'Error',
                'action' => '404',
            ];

            $breadcrumbs = '';
            $canonical = '';
            $pagination = '';
            $category = null;
            $product = null;

            $view404 = APP . '/views/' . TEMPLATE . '/Error/404.php';
            $layout404 = APP . '/views/' . TEMPLATE . '/layouts/error_404.php';

            if (!is_file($view404)) {
                throw new \Exception('404 view not found: ' . $view404);
            }

            if (!is_file($layout404)) {
                throw new \Exception('404 layout not found: ' . $layout404);
            }

            ob_start();
            require $view404;
            $content = ob_get_clean();

            require $layout404;

        } catch (\Throwable $e) {
            $this->logErrors('404 render failed: ' . $e->getMessage(), $e->getFile(), $e->getLine());
            require WWW . '/errors/404.php';
        }
    }

}