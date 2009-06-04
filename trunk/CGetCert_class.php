<?php

class CGetCert {
	public function getInterface() {
		$formStr = "<h1><font color = '#9CF'>Install the certificate into your browser</font></h1>";

		$formStr .= "<form method = 'POST'>";
		
		$formStr .= "<table  border = '3' bgcolor ='#9CF'><tr><td>";
	
		$formStr .= "ID: <input type = 'text' name = 'path'>\n";
		
		$formStr .= "<input type = 'hidden' name = 'sub' value = 'CGetCert'>"
				  . "<input type = 'submit' value = 'Install the Certificate'>";
				  
		$formStr .= "</table>";
		
		$formStr .= "</form>";
		
		return $formStr;
	}
	
	public function processData($arr) {
		return false;
	}
	
	public function getName() {
		return "Install My Certificate";
	}
}

?>