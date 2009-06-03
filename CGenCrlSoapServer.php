<?php

require_once("CGenCrl_class.php");
require_once('nusoap.php');

$debug = 1;

$server = new nusoap_server;
$server->register('CGenCrl.getInterface');
$server->register('CGenCrl.processData');
$server->register('CGenCrl.getName');

//Вот кто поймет что здесь написанно ТОМУ ПРИЗ!!!!!!!!!!!!
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>