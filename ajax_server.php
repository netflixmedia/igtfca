<?php

if(!isset($_GET["modname"]))
	die ("modname not set in _GET[]!\n");
		
$modname = $_GET["modname"];

require_once("nusoap.php");

$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

$client = new nusoap_client("$url/".$modname. "SoapServer.php");

echo $client->call($modname . '.getInterface');

/*
echo '<h2>Request</h2>';
echo '<pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
echo '<h2>Response</h2>';
echo '<pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
*/
?>