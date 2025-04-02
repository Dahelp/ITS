<?php

use ishop\Router;

Router::add('^product/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Product', 'action' => 'view']);
Router::add('^catalog/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Catalog', 'action' => 'index']);
Router::add('^category/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Category', 'action' => 'view']);
Router::add('^complete/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Complete', 'action' => 'view']);
Router::add('^podbor/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Podbor', 'action' => 'index']);
Router::add('^size/(?P<alias>[a-z0-9-./\s]+)/?$', ['controller' => 'Size', 'action' => 'view']);
Router::add('^cross/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Cross', 'action' => 'view']);
Router::add('^technics/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Technics', 'action' => 'view']);
Router::add('^technics/type/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Technics', 'action' => 'type']);
Router::add('^technics/(?P<type>[a-z0-9-]+)/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Technics', 'action' => 'manufacturer']);


//Protector//
Router::add('^protector/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Protector', 'action' => 'view']);
//AndProtector//
//Brand//
Router::add('^brand/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Brand', 'action' => 'view']);
//AndBrand//
//Disk//
Router::add('^disk/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Disk', 'action' => 'view']);
//AndDisk//
//Tipshiny//
Router::add('^tipshiny/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Tipshiny', 'action' => 'view']);
//AndTipshiny//
//Ground//
Router::add('^ground/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Ground', 'action' => 'view']);
//AndGround//
//Rim//
Router::add('^rim/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Rim', 'action' => 'view']);
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
//Size//
Router::add('^size/(?P<alias>[a-z0-9-]+)/?$', ['controller' => 'Size', 'action' => 'view']);
//AndSize//
//  Add here

// default routes
Router::add('^admin$', ['controller' => 'Main', 'action' => 'index', 'prefix' => 'admin']);
Router::add('^admin/?(?P<controller>[a-z-]+)/?(?P<action>[a-z-]+)?$', ['prefix' => 'admin']);

Router::add('^$', ['controller' => 'Main', 'action' => 'index']);
Router::add('^(?P<controller>[a-z-]+)/?(?P<action>[a-z-]+)?$');