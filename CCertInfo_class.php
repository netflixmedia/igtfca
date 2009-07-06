<?php

class CCertInfo {
	private $apiObj;
	private $subj;
	private $attrValArr = array(
		"commonName" => "CN",
		"stateOrProvinceName" => "ST",
		"countryName" => "C",
		"localityName" => "L",
		"emailAddress" => "emailAddress",
		"organizationName" => "O",
		"organizationalUnitName" => "OU"
	);

	public function __construct() {
		require_once("COpenSslApiImpl_class.php");
		$this->apiObj = new COpenSslApiImpl();
	}
	
	public function getSubject($cert_path) {
	//subject= /C=AM/CN=blabla/O=org1/O=org2/OU=orgunit
		return $this->apiObj->getCertSubject($cert_path);
	}
	
	public function getSubjAttrVal($attrName, $cert_path){
	/*
	Уважаемая Гаяне.
	Прошу вас передать мне $attrVal, так как мне позарез нужно знать ГДЕ Я ЖИВУ!!!!!!!!!!
	со всем уважением ReqGenClass.
	*/
		if(!isset($this->subj)) $this->subj = $this->getSubject($cert_path);
					
		//attribute with numbers like 1.organizationName
		$number = -1;
		if(preg_match("/^_*([0-9]+).(.+)$/", $attrName, $match) ) {
			$attrName = $match[2];
			$number = $match[1];
		}
		
		$pairs = explode('/', $this->subj);
		
		$counter = 0; //sequence number of the value from the same multiple type attribute names ($arr[0]) 
		foreach($pairs as $pair) {
			$arr = explode("=", $pair);

			if(!isset($this->attrValArr[$attrName])) return "!!no such attribute name existing!!"; //return false;
			if(trim($arr[0]) == $this->attrValArr[$attrName]) {
				if(($number == -1) || ($number == $counter)) { // common attribute
					return trim($arr[1]);
				}
				else {
					++$counter;
				}
			}
		}
		
		return "";
	}
	
	public function getText($cert_path) {
		return $this->apiObj->getCertText($cert_path);
	}
	
	public function getInterface() {
		$formStr = "<h1><font color = '#9CF'>Choose a path of the certificate to show</font></h1>";

		$formStr .= "<form method = 'POST'>";
		
		$formStr .= "<table  border = '3' bgcolor ='#9CF'><tr><td>";
	
		require_once('nusoap.php');
		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$rootCertPath = $pathClient->call('CPath.getPath', array('pathName' => "RootCertPath"));
		
		$formStr .= "Path: <select name = 'path'>\n"
				  . "<option value = '" . $rootCertPath . "'>$rootCertPath</option>\n";
		
		$certsDir = $pathClient->call('CPath.getPath', array('pathName' => "OutCertsPath"));
		if($handle = opendir($certsDir)) {	// browse directory
			while (false !== ($file = readdir($handle))) {
				if($file != '.' && $file != '..') {
					$formStr .= "<option value = '" . $certsDir . '/' . $file . "'>$file</option>\n";
				}
			}
			closedir($handle);
		}
		else die("Error when opening certs dir!");
		
		$certsDir = $pathClient->call('CPath.getPath', array('pathName' => "UserCertsPath"));
		if($handle = opendir($certsDir)) {	// browse directory
			while (false !== ($file = readdir($handle))) {
				if($file != '.' && $file != '..') {
					$formStr .= "<option value = '" . $certsDir . '/' . $file . "'>$file</option>\n";
				}
			}
			closedir($handle);
		}
		else die("Error when opening certs dir!");
		
		$formStr .= "</select>\n</td></tr><BR>";
		
		$formStr .= "<input type = 'hidden' name = 'sub' value = 'CCertInfo'>"
				  . "<input type = 'submit' value = 'Show Certificate Text'>";
				  
		$formStr .= "</table>";
		
		$formStr .= "</form>";
		
		return $formStr;
	}
	
	public function processData($arr) {
		return $this->getText($arr['path']);
	}
	public function getName(){
		return "Cert Info";
	}
}

?>