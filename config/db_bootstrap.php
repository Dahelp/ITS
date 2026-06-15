<?php
// config/db_bootstrap.php
// Безопасная инициализация RedBean: setup только если ещё не настроено.

if (!class_exists('\RedBeanPHP\R')) {
    require_once ROOT . '/vendor/autoload.php';
}

// глобальный алиас R (если где-то в проекте используют R:: без namespace)
if (!class_exists('R', false) && class_exists('\RedBeanPHP\R')) {
    class_alias('\RedBeanPHP\R', 'R');
}

$adapter = null;
try {
    // если база уже была настроена ранее — адаптер не null
    $adapter = \RedBeanPHP\R::getDatabaseAdapter();
} catch (\Throwable $e) {
    $adapter = null;
}

if ($adapter === null) {
    $db = require CONF . '/config_db.php';
    \RedBeanPHP\R::setup($db['dsn'], $db['user'], $db['pass']);
    \RedBeanPHP\R::freeze(true);
}
