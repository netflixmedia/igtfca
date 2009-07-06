<?php

// CODE FOR SENDING CERTIFICATES
if(isset($_POST['sub'])) {
	if($_POST['sub'] == "CGetCert") {
		require_once('nusoap.php');
		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$certsDir = $pathClient->call('CPath.getPath', array('pathName' => "UserCertsPath"));
	
		$path = $certsDir . "/" . $_POST['path'] . ".der";

		$len = @filesize($path);
		
		$fh = fopen($path, 'rb');
		
		header("Content-type: application/x-x509-user-cert\n");
		header("Content-Length: " . $len . "\n");
		header("Content-transfer-encoding: binary\n");
		header("Expires: 0\n");
		fpassthru($fh);
		exit;
	}
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="index.css" />
	<script type="text/javascript" src="scripts/ajax.js"> </script>
	<script type="text/javascript" src="scripts/request.js"> </script>
	<script type="text/javascript" src="scripts/signCsr.js"></script>
	<script type="text/javascript" src="scripts/formChecks.js"></script>
	<title>Titled Document</title>
</head>

<body bgcolor="#FFFFFF">

<?php

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

echo "<pre>";
echo '<h2>Request</h2>';
echo htmlspecialchars($client->request, ENT_QUOTES);
echo '<h2>Response</h2>';
echo htmlspecialchars($client->response, ENT_QUOTES);
echo "</pre>";

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
		<div class="left" align="left">
			<p>&nbsp;</p>
			<p>&nbsp;</p>
			<?php include("left_menu.php") ?>
		</div>
		<span class="clear">
		</span>
        <div id="cont">
			<?php echo "<font color = '#9CF'>" . $form_response . "</font>" ?>
		</div>
</div>
</div>

</body>

</html>
