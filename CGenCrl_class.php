<?php

class CGenCrl {
	public function getInterface() {
//		echo "\n!!! IN getInterface() !!!\n";

		$result = "<h1><font color = '#9CF'>Generate Certificate Revokation List</font></h1>";

		// table with info from opensslapi config file
		$infoString = "<table>\n";
		
		require_once('nusoap.php');

		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$confPath = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));
		
		$confClient = new nusoap_client("$url/CConfSoapServer.php");
		
		// ca section
		$infoString .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
					. "<center>ca</center></font></td></tr>";
					
		$ca_sec_info = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => 'ca',
																					'confPath' => $confPath));
		
		foreach($ca_sec_info as $attr_name => $attr_value) {
			$infoString .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
		}
		
		//default ca section
		$infoString .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
					. "<center>" . $ca_sec_info["default_ca"] . "</center></font></td></tr>";
		
		$defca_sec_info = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => $ca_sec_info["default_ca"],
																					'confPath' => $confPath));

		foreach($defca_sec_info as $attr_name => $attr_value) {
			$infoString .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
		}

		$infoString .= "<tr><td colspan = '2' style = 'padding: 16'><center><br>"
					. "To change the config use the <span class = \"link\" onmousedown=\"get_inter('CConf')\">CConf</span> module!"
					. "</center></td></tr>";
		
		$infoString .= "</table>";
	

	
		// table with form to submit
		$formString = "<form method = 'POST' name = 'frm'>\n<table>\n";
		
		// Config Path
		$formString .= "<tr><td>Config Path`:</td><td>$confPath<input type = 'hidden' name = 'conf_path' value = '"
																	. $confPath . "'</td></tr>";
				
		// Default Ca Section Name
		$formString .= "<tr><td>Default Ca Section Name*:</td><td><input type = 'text' name = 'ca_sec_name' value = '"
																		. $ca_sec_info["default_ca"] . "'></td></tr>";

		// Root Certificate Path & Root Certificate Private Key Path
		$rootCertPath = $pathClient->call('CPath.getPath', array('pathName' => "RootCertPath"));
		$rootKeyPath = $pathClient->call('CPath.getPath', array('pathName' => "RootKeyPath"));
		
		$formString .= "<tr><td>Root Certificate Path`:</td>"
					. "<td>$rootCertPath<input type = 'hidden' name = 'rootcert_path' value = '" . $rootCertPath . "'></td></tr>"
					. "<tr><td>Root Certificate Private Key Path`:</td>"
					. "<td>$rootKeyPath<input type = 'hidden' name = 'rootcert_key_path' value = '" . $rootKeyPath . "'></td></tr>";
		
		// Private Key Password
		$formString .= "<tr><td>Private Key Password*:</td><td><input type = 'password' name = 'rootcert_key_pass'></td></tr>";
		
		// Certificate Revokation List Path
		$crlPath = $pathClient->call('CPath.getPath', array('pathName' => "CrlPath"));
		$formString .= "<tr><td>Output CRLs Folder Path`:</td>"
					. "<td>$crlPath<input type = 'hidden' name = 'crl_path' value = '" . $crlPath . "'</td></tr>";
		
		//Certificate Revokation List Lifetime
		$crl_days = isset($defca_sec_info["default_crl_days"]) ? $defca_sec_info["default_crl_days"] : "";
		
		$formString .= "<tr><td>Certificate Revokation List Lifetime*:</td><td><input type = 'text' name = 'crl_days' value = '"
																. $crl_days . "'></td></tr>";								
		
		//Message Digest To Use
		$md = isset($defca_sec_info["default_md"]) ? $defca_sec_info["default_md"] : "";
		
		$formString .= "<tr><td>Default Message Digest*:</td><td><select name = 'md'>\n";
		
		$formString .= "<option value = 'sha1'";
		$formString .= ($md == 'sha1') ? ' selected' : '';
		$formString .= ">sha1</option>\n";
		
		$formString .= "<option value = 'mdc2'";
		$formString .= ($md == 'mdc2') ? ' selected' : '';
		$formString .= ">mdc2</option>\n";
		
		$formString .= "<option value = 'md5'";
		$formString .= ($md == 'md5') ? ' selected' : '';
		$formString .= ">md5</option>\n";
		
		$formString .= "</select>\n</td></tr>";
		
		// submit button
		$formString .= "<tr><td colspan = '2' style = 'padding: 16'><center>"
					. "` To change these values use <span class = \"link\" onmousedown=\"get_inter('CPath')\">CPath</span><br>"
					. "* Required fields<br><br>"
					. "<input type = 'hidden' name = 'sub' value = 'CGenCrl'>"
					. "<input type = 'submit' value = 'Generate CRL'></center></td></tr>";
		
		$formString .= "</table>\n</form>";

		
		$result .= "<table bgcolor = '#9CF' border = '5'>\n<tr>\n<td>\n$infoString\n</td>\n<td>\n<div id = 'spkacDiv'></div>\n$formString\n</td>\n</tr>\n</table>";
		return $result;
	}

	public function processData($arr) {
//		echo "\n!!! IN signSpkac() !!!\n";

		unset($arr["sub"]);

		require_once("COpenSslApiImpl_class.php");
		$opensslObj = new COpenSslApiImpl();

		$res = $opensslObj->genCrl($arr['conf_path'], $arr['ca_sec_name'], $arr['rootcert_path'], $arr['rootcert_key_path'], $arr['rootcert_key_pass'], $arr['crl_path'], $arr['crl_days'], $arr['md']);
		
		if($res == 1) return "Error in CRL generating command...";
		elseif($res == false) return "Error in configuration or paths : (";
		else return "Success!<BR><BR>$res";

		return $ret_str;
	}
	
	// mode == 1 - only ____n.
	// mode == 2 - both ____n. and _n
	private function decode($name, $mode) {
		if(strpos($name, '_') === FALSE)
			return $name;

		if(preg_match("/^____[0-9]+\.(.+)$/", $name, $match)) {
			$name = $match[1];
		}
		
		if($mode == 2) {
			if(preg_match("/^_([0-9].+)$/", $name, $match)) {
				$name = $match[1];
			}
		}
		
		return $name;
	}
	
	public function getName(){
		return "Generate CRL";
	}
}

?>