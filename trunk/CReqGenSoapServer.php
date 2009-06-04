<?php

require_once("CReqGen_class.php");
require_once('nusoap.php');

$debug = 1;

$server = new nusoap_server;
$server->register('CReqGen.getInterface');
$server->register('CReqGen.processData');
$server->register('CReqGen.getName');

//Вот кто поймет что здесь написанно ТОМУ ПРИЗ!!!!!!!!!!!!
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>


