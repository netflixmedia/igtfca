<?php

require_once('nusoap.php');

$debug = 1;
$server = new nusoap_server;

require_once("CNewCert_class.php");
$server->register('CNewCert.getInterface');
$server->register('CNewCert.processData');
$server->register('CNewCert.getName');

//Вот кто поймет что здесь написанно ТОМУ ПРИЗ!!!!!!!!!!!!
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>