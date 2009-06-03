<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="index.css" />
	<script type="text/javascript" src="scripts/ajax.js"> </script>
	<script type="text/javascript" src="scripts/request.js"> </script>
	<script type="text/javascript" src="scripts/signCsr.js"></script>
	<title>Untitled Document</title>
</head>

<body bgcolor="#0A284B">

<?php
require_once('recaptchalib.php');
$form_response = "";
if(isset($_POST['sub'])) {
	$module_name = $_POST['sub'];

	$lang = "php";
	if(isset($_POST['lang']))
		$lang = $_POST['lang'];
	
	require_once('nusoap.php');

	$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

	$client = new nusoap_client("$url/$module_name" . "SoapServer." . $lang);

	$form_response = "<pre>" . $client->call($module_name . '.processData', array('arrToWrite' => $_POST)) . "</pre>";
/*
echo "<pre>";
echo '<h2>Request</h2>';
echo htmlspecialchars($client->request, ENT_QUOTES);
echo '<h2>Response</h2>';
echo htmlspecialchars($client->response, ENT_QUOTES);
echo "</pre>";
*/
}
?>

<div id="conteiner">

	<div id="header">
    <div class="logo">
    </div>

	</div>
	
    <div class="clear">
	</div>
	
	<div id="midd">
		<div class="left">
			<p>&nbsp;</p>
			<p>&nbsp;</p>
			<?php include("left_menu.php") ?>
		</div>
        <div id="cont">
			<?php  require_once('recaptchalib.php'); echo "<font color = '#9CF'>$form_response</font>" ?>
		</div>
	</div>
</div>

</body>

</html>