<?php
error_reporting(E_ALL & ~ E_STRICT & ~ E_NOTICE);

/* @MARK: 这部分应该交给WebServer去做 */
header("Content-type: text/html; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Pragma: no-cache");

define('APPLICATION_PATH', dirname(__FILE__));
define('TPL_PATH', APPLICATION_PATH  . '/application/views');

$application = new Yaf_Application( APPLICATION_PATH . "/conf/application.ini");

$application->bootstrap()->run();
?>
