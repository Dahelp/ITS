<?php

namespace ishop\base;

class View {

    public $route;
    public $controller;
    public $model;
    public $view;
    public $prefix;
    public $layout;
    public $data = [];
    public $meta = [];

    public function __construct($route, $meta, $layout = '', $view = ''){
        $this->route = $route;
        $this->controller = $route['controller'];
        $this->view = $view;
        $this->model = $route['controller'];
        $this->prefix = $route['prefix'];
        $this->meta = $meta;

        if ($layout === false) {
            $this->layout = false;
        } else {
            $this->layout = $layout ?: LAYOUT;
        }
    }

    public function render($data)
    {
        if (is_array($data)) {
            extract($data, EXTR_SKIP);
        }

        // Нормализуем prefix/controller/view/layout (PHP 8+ строгий)
        $this->prefix = str_replace('\\', '/', (string)($this->prefix ?? ''));
        $this->controller = (string)($this->controller ?? '');
        $this->view = $this->view ?? 'index';
        $this->layout = $this->layout ?? LAYOUT;

        // FIX: view не должен быть массивом (иначе будет "Array.php")
        if (is_array($this->view)) {
            // Попытка аккуратно вытащить строковое имя вида
            $this->view = $this->view['view'] ?? $this->view[0] ?? 'index';
        }
        $this->view = (string)$this->view;

        // FIX: layout тоже должен быть строкой или false
        if ($this->layout !== false) {
            if (is_array($this->layout)) {
                $this->layout = $this->layout['layout'] ?? $this->layout[0] ?? LAYOUT;
            }
            $this->layout = (string)($this->layout ?: LAYOUT);
        }

        // Формирование пути к виду
        $viewFile = APP . "/views/" . TEMPLATE . "/{$this->prefix}{$this->controller}/{$this->view}.php";

        if (is_file($viewFile)) {
            ob_start();
            require $viewFile;
            $content = ob_get_clean();
        } else {
            // Диагностика на случай, если view/controller пришли не теми типами
            $dbg = [
                'prefix'     => $this->prefix,
                'controller' => $this->controller,
                'view'       => $this->view,
                'layout'     => $this->layout,
                'route'      => $this->route ?? null,
            ];
            throw new \Exception("Не найден вид {$viewFile}. DBG=" . json_encode($dbg, JSON_UNESCAPED_UNICODE), 500);
        }

        // Подключение layout
        if ($this->layout !== false) {

            // layout должен быть строкой, иначе fallback
            if (!is_string($this->layout) || $this->layout === '') {
                $this->layout = LAYOUT;
            }

            $layoutFile = APP . "/views/" . TEMPLATE . "/layouts/{$this->layout}.php";

            // если файла нет — fallback на LAYOUT (watches)
            if (!is_file($layoutFile)) {
                $this->layout = LAYOUT;
                $layoutFile = APP . "/views/" . TEMPLATE . "/layouts/{$this->layout}.php";
            }

            if (is_file($layoutFile)) {
                require $layoutFile;
            } else {
                throw new \Exception("Не найден шаблон {$layoutFile}", 500);
            }
        }
    }

    public function getMeta()
    {
        $title = $this->meta['title'] ?? '';
        $desc = $this->meta['desc'] ?? '';
        $keywords = trim($this->meta['keywords'] ?? '');

        $shopName = $this->meta['shop_name'] ?? '';
        $shopImg  = $this->meta['shop_img'] ?? '';
        $shopUrl  = $this->meta['shop_url'] ?? '';

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        /**
         * Для страниц пагинации page>1:
         * 1. Убираем из title хвост вида "- Страница 2"
         * 2. canonical делаем без query-параметров
         */
        if ($page > 1 && $title) {
            $title = preg_replace('~\s*[-–—|]\s*Страница\s+\d+\s*$~ui', '', $title);
        }

        /**
         * Canonical без ?page=2, ?sort=, ?filter= и прочих GET-параметров.
         *
         * Было:
         * https://its-center.ru/category/shiny-dlya-vilochnyh-pogruzchikov?page=2
         *
         * Станет:
         * https://its-center.ru/category/shiny-dlya-vilochnyh-pogruzchikov
         */
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $canonicalUrl = !empty($shopUrl) ? $shopUrl : PATH . $requestPath;
        $canonicalUrl = strtok($canonicalUrl, '?');

        /**
         * Для og:url лучше тоже отдавать чистый URL без GET-параметров.
         */
        $ogUrl = $canonicalUrl;

        $output = '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . PHP_EOL;

        if ($desc !== '') {
            $output .= '<meta name="description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '" />' . PHP_EOL;
        }

        if ($keywords !== '') {
            $output .= '<meta name="keywords" content="' . htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') . '" />' . PHP_EOL;
        }

        $output .= '<meta property="og:type" content="' . htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8') . '" />' . PHP_EOL;
        $output .= '<meta property="og:locale" content="ru_RU" />' . PHP_EOL;
        $output .= '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" />' . PHP_EOL;

        if ($shopImg !== '') {
            $output .= '<meta property="og:image" content="' . htmlspecialchars($shopImg, ENT_QUOTES, 'UTF-8') . '" />' . PHP_EOL;
        }

        if ($desc !== '') {
            $output .= '<meta property="og:description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '" />' . PHP_EOL;
        }

        $output .= '<meta property="og:url" content="' . htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8') . '" />' . PHP_EOL;
        $output .= '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '" />' . PHP_EOL;

        return $output;
    }

    public function safePeriod(?string $datetime, ?string $today = null): string
    {
        if (empty($datetime)) {
            return '';
        }

        $today = $today ?: date('Y-m-d');

        $parts = explode(' ', $datetime, 2);
        $date  = $parts[0] ?? '';

        if ($date === '') {
            return '';
        }

        return \ishop\App::getPeriod($date, $today);
    }

}
