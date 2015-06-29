<?php
/*------- Edit this block --------*/

define("DOMAIN", "https://");
define("ADMIN_URL", "");
define("ADOMAIN", DOMAIN);
define("ROOT_PATH", '/opt/APP/');

/*---------------*/

date_default_timezone_set('Europe/Moscow');

error_reporting(E_ERROR | E_PARSE | E_NOTICE);
ini_set('display_errors', 'on');

define("EDITOR", "tiny_mce");
define("ENABLE_CACHING", false);

setlocale(LC_ALL, 'ru_RU.utf-8', 'rus_RUS.utf8', 'Russian_Russia.utf-8', 'Russian_Russia.utf8', 'Russian_Russia.65001');
setlocale(LC_NUMERIC, 'en_US');
?>