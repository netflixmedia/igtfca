<?php

require_once("CGetCert_class.php");
require_once('nusoap.php');

$debug = 1;

$server = new nusoap_server;
$server->register('CGetCert.getInterface');
$server->register('CGetCert.processData');
$server->register('CGetCert.getName');

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>