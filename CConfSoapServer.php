<?php

require_once("nusoap.php");

$server = new nusoap_server;

require_once("CConf_class.php");

$server->register('CConf.getName');
$server->register('CConf.getInterface');
$server->register('CConf.processData');
$server->register('CConf.getPolicySections');
//$server->register('CConf.getDefCaSectionName');
$server->register('CConf.getDefCaSecPolicyName');
$server->register(
	'CConf.getInfoForGivenSectionName',
	array('sec_name' => 'xsd:string', 'conf_path' => 'xsd:string'),      // input parameters
    array(),    // output parameters
    'uri:helloworld',                   // namespace
    'uri:helloworld/hello',             // SOAPAction
    'rpc',                              // style
    'encoded'                           // use
	);
$server->register('CConf.getSsCertInfo');
//$server->register('CConf.getSpkacSignInfo');
$server->register('CConf.getExtensionSections');

$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>