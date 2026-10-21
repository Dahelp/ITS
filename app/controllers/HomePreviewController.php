<?php
namespace app\controllers;

class HomePreviewController extends MainController
{
    public function indexAction()
    {
        header('X-Robots-Tag: noindex, nofollow, noarchive', true);
        parent::indexAction();
        $this->data['metaRobots'] = 'noindex, nofollow';
        $this->setMeta('Тестовая главная — ИТС-Центр', 'Предварительная версия каталога шин', '', 'ИТС-Центр', '', PATH . '/');
    }
}
