<?php

require_once("CCertInfo_class.php");
require_once('nusoap.php');

$debug = 1;

$server = new nusoap_server;
$server->register('CCertInfo.getSubject');
$server->register('CCertInfo.getSubjAttrVal');
$server->register('CCertInfo.getText');
$server->register('CCertInfo.getInterface');
$server->register('CCertInfo.processData');
$server->register('CCertInfo.getName');

//Вот кто поймет что здесь написанно ТОМУ ПРИЗ!!!!!!!!!!!!
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>