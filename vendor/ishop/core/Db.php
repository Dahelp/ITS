<?php

namespace ishop;

class Db{
    use TSingletone;

    protected function __construct(){
        $db = require_once CONF . '/config_db.php';
        class_alias('\RedBeanPHP\R', '\R');
        \R::setup($db['dsn'], $db['user'], $db['pass']);
        if (!\R::testConnection()) {
            throw new \Exception("Нет соединения с БД", 500);
        }

        // ВАЖНО: плагины RedBean — до freeze/после setup
        \R::ext('xdispense', function($type){
            // совместимо с любыми версиями RedBean
            return \R::getRedBean()->dispense($type);
            // или короче: return \R::dispense($type);
        });

        // Если где-то вызывается R::setLogger(...), регистрируем и его
        \R::ext('setLogger', function($logger){
            \R::getDatabaseAdapter()->getDatabase()->setLogger($logger);
        });

        \R::freeze(true);

        if (DEBUG) {
            \R::debug(true, 1);
            // При желании повесить логгер:
            // \R::setLogger(new \RedBeanPHP\Logger\RDefault\Debug());
        }

        \R::ext('xdispense', function($type){
            // дублирование не страшно, но на всякий случай оставим один раз
            return \R::getRedBean()->dispense($type);
        });
    }
}
