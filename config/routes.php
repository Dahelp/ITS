<?php

use ishop\Router;

/** Мусорные/сканерные пути — сразу 410 GONE, без загрузки ядра/БД */
Router::add('^(wp-admin|wp-login\.php|wp-json|xmlrpc\.php|wp-content|wp-includes|\.env|vendor|composer\.(json|lock)|phpinfo\.php|phpmyadmin|\.git|\.hg|\.svn)(/.*)?$',
    ['controller' => 'Static', 'action' => 'gone']);

/** Явные обращения к "файлам" в корне — статическая 404 без БД */
Router::add('^([a-z0-9._-]+\.(php|txt|bak|zip|gz|rar|7z))$',
    ['controller' => 'Static', 'action' => 'notFound']);

Router::add('^product/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Product', 'action' => 'view']);
Router::add('^catalog/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Catalog', 'action' => 'index']);
Router::add('^category/(?P<alias>[a-z0-9\-_]+)/(?P<filter_alias>[a-z0-9\.\-_]+(?:/[a-z0-9\.\-_]+)*)/?$', ['controller' => 'Category', 'action' => 'view']);

Router::add('^category/(?P<alias>[a-z0-9-]+)$', [
    'controller' => 'Category',
    'action' => 'view'
]);
Router::add('^complete/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Complete', 'action' => 'view']);
Router::add('^podbor/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Podbor', 'action' => 'index']);
Router::add('^size/(?P<alias>[0-9./-]+)/?$', ['controller' => 'Size', 'action' => 'view']);
Router::add('^cross/(?P<alias>(?:%[0-9a-f]{2}|[a-z0-9._-])+)/?$', ['controller' => 'Cross', 'action' => 'view']);
Router::add('^technics/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Technics', 'action' => 'view']);
Router::add('^technics/type/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Technics', 'action' => 'type']);
Router::add('^technics/(?P<type>[a-z0-9-]+)/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Technics', 'action' => 'manufacturer']);
Router::add('^cron/refresh-tovars-server$', ['controller' => 'Cron', 'action' => 'refreshTovarsServer']);
Router::add('^cron/refresh-tovars-from-file', ['controller' => 'Cron', 'action' => 'refreshTovarsFromFile']);
Router::add('^cron/download-tovars-file$', ['controller' => 'Cron', 'action' => 'downloadTovarsFile']);
Router::add('^size/(?P<alias>[a-z0-9\.\-_]+(?:/[a-z0-9\.\-_]+)*)/?$', ['controller' => 'Size', 'action' => 'view']);


//Size//
Router::add('^size/(?P<alias>[a-z0-9\.\-_]+)/?$', ['controller' => 'Size', 'action' => 'view']);
//AndSize//






//Protector//
Router::add('^protector/(?P<alias>[a-z0-9\.\-_]+)/?$', ['controller' => 'Protector', 'action' => 'view']);
//AndProtector//

//Brand//
Router::add('^brand/(?P<alias>[a-z0-9\.\-_]+)/?$', ['controller' => 'Brand', 'action' => 'view']);
//AndBrand//

//Disk//
Router::add('^disk/(?P<alias>[a-z0-9\.\-_]+)/?$', ['controller' => 'Disk', 'action' => 'view']);
//AndDisk//

//Tipshiny//
Router::add('^tipshiny/(?P<alias>[a-z0-9\.\-_]+)/?$', ['controller' => 'Tipshiny', 'action' => 'view']);
//AndTipshiny//

//Ground//
Router::add('^ground/(?P<alias>[a-z0-9\.\-_]+)/?$', ['controller' => 'Ground', 'action' => 'view']);
//AndGround//
//Rim//
Router::add('^rim/(?P<alias>[a-z0-9\.\-_]+)/?$', ['controller' => 'Rim', 'action' => 'view']);
//AndRim//
//Pages//
Router::add('^pages/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Pages', 'action' => 'view']);
//AndPages//
//News//
Router::add('^news/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'News', 'action' => 'view']);
//AndNews//
//Services//
Router::add('^services/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Services', 'action' => 'view']);
//AndServices//
//Promo//
Router::add('^promo/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Promo', 'action' => 'view']);
//AndPromo//
//Articles//
Router::add('^articles/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Articles', 'action' => 'view']);
//AndArticles//
//  Add here

// Добавим ручной маршрут логирования cookie
Router::add('^cookie-log$', ['controller' => 'Cookie', 'action' => 'log']);

// GPT ответчик

// Маршрут для импорта Avito 
Router::add('^admin/avito/import-xls/?$', [
    'controller' => 'Avito',
    'action'     => 'importXls',
    'prefix'     => 'admin',
]);

// default routes
Router::add('^admin$', ['controller' => 'Main', 'action' => 'index', 'prefix' => 'admin']);
Router::add('^admin/?(?P<controller>[a-z-]+)/?(?P<action>[a-z-]+)?$', ['prefix' => 'admin']);

Router::add('^$', ['controller' => 'Main', 'action' => 'index']);
Router::add('^(?!wp-|xmlrpc\.php|phpmyadmin|vendor|composer\.|\.env)(?P<controller>[a-z-]+)/?(?P<action>[a-z-]+)?$');
