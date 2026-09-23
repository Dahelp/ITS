<?php

namespace app\controllers;

use app\models\admin\Avito;

class AvitoController extends AppController
{
    public function refreshTovarsAction()
    {
        $this->layout = false;
        $this->view = false;

        $xml = Avito::buildFeedXml();

        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $xml;
        exit;
    }
}
