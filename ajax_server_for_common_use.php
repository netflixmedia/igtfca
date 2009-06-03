<?php

if(isset($_GET["spkac_path"])) {
	$spkac_path = $_GET["spkac_path"];
	
	require_once("COpenSslApiImpl_class.php");
	$opensslObj = new COpenSslApiImpl();

	$text = $opensslObj->getSpkacText($spkac_path);
	
	$text .= "\n" . strstr(file_get_contents($spkac_path), "\n");
	
	echo $text;
}

?>