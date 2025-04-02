<?php

// запрет прямого обращения
define('BASEPATH', TRUE);

if($_SERVER['REQUEST_URI'] == "/main") {
	header( "Location: /", TRUE, 301 );
	exit();
}

if (strpos($_SERVER['REQUEST_URI'], 'public')) {
	$public = str_replace("/public",'', $_SERVER['REQUEST_URI']);

	if ($public) {
		header("HTTP/1.1 301 Moved Permanently");
		header('Location: https://'.$_SERVER['SERVER_NAME'].''.$public.'');
		exit();
	}
}

// Убираем слеш в конце ссылок
$uri = preg_replace("/\?.*/i",'', $_SERVER['REQUEST_URI']);
 
if ((!strpos($uri, 'simpla'))  && (strlen($uri)>1)) {
  if (rtrim($uri,'/')!=$uri) {
    header("HTTP/1.1 301 Moved Permanently");
    header('Location: https://'.$_SERVER['SERVER_NAME'].str_replace($uri, rtrim($uri,'/'), $_SERVER['REQUEST_URI']));
    exit();    
  }
} 

require_once dirname(__DIR__) . '/config/init.php';
require_once LIBS . '/functions.php';
require_once CONF . '/routes.php';

new \ishop\App();



