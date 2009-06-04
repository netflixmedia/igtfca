<?php
/*
form check:
key_len - positive integer; 383 < key_len; reccomended key_len <= 1024
					// java script code to check key_len
					. "if(!(/^\d*$/.test(document.frm.key_len.value))) alert(\"Key length must be a positive integer!\");"
					. "else if (document.frm.key_len.value < 384) "
					. "alert(\"Key length must be at least 384 bit, recommended not greater than 2048!\");"
					. "else document.frm.submit();"
pass - not empty
dn - not empty; to have this form /C=AM/O=ArmeSFo/OU=bla bla/CN=bugaga/emailAddress=b@g.d/ST=Yerevan State
days - positive integer
*/

class CNewCert {
	public function getInterface() {
//		echo "\n!!! IN getInterface() !!!\n";
		$result = "<h1><font color = '#9CF'>Create New Root CA Certificate And Private Key</font></h1>";
		
		$result .= "<form method = 'POST' name = 'frm'>\n";		
		
		$configTable = "<span class='tabletext'>";
		$configTable .= "<table border = '0'>";
		$configTable .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
				 . "<center>req</center></font></td></tr>";
		
		require_once('nusoap.php');

		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$sslConfPath = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));
		
		$confClient = new nusoap_client("$url/CConfSoapServer.php");
		$req_sec_info = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => 'req',
																					'confPath' => $sslConfPath));
																					
		$other_section_names = array("distinguished_name" => "",
									 "attributes" => "",
									 "x509_extensions" => ""
									);

		foreach($req_sec_info as $attr_name => $attr_value) {
			$configTable .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
			foreach($other_section_names as $sec_def => $sec_name) {
				if($attr_name == $sec_def) {
					$other_section_names[$sec_def] = $attr_value;
				}
			}
		}

		$pol_sec = $confClient->call('CConf.getDefCaSecPolicyName', array('confPath' => $sslConfPath));
		if(!empty($pol_sec)) {
				$configTable .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
						. "<center>$pol_sec</center></font></td></tr>";
				$sec_info = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => $pol_sec,
																						'confPath' => $sslConfPath));

				foreach($sec_info as $attr_name => $attr_value) {
					$configTable .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
				}
		}
		
		foreach($other_section_names as $sec_def => $sec_name) {
			if(!empty($sec_name)) {
				$configTable .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
						. "<center>$sec_name</center></font></td></tr>";
				$sec_info = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => $sec_name,
																						'confPath' => $sslConfPath));

				foreach($sec_info as $attr_name => $attr_value) {
					$configTable .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
				}
			}
		}

		$exts = $confClient->call('CConf.getExtensionSections', array('conf_file_path' => $sslConfPath));

		if(!empty($other_section_names["x509_extensions"]))
			for($i = 0; $i < sizeof($exts); ++$i)
				if($exts[$i] == $other_section_names['x509_extensions'])
					unset($exts[$i]);
		
		foreach($exts as $ext_name) {
			$configTable .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
					. "<center>$ext_name</center></font></td></tr>";
			$info_arr = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => $ext_name,
																					'confPath' => $sslConfPath));
			foreach($info_arr as $attr_name => $attr_value) {
				$configTable .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
			}
		}
		
		$configTable .= "<tr><td colspan = '2' style = 'padding: 16'><br><center>"
					 . "To change the config use the <span class = \"link\" onmousedown=\"get_inter('CConf')\">CConf</span> module!"
					 . "</center></td></tr>";
		
		$configTable .= "</table>";
	
		
		//THE FORM
		$formTable = "<table border='0'>\n";
		
		$formTable .= "<tr><td>Config Path`:</td><td>$sslConfPath<input type = 'hidden' name = 'conf_path' value = '"
																	. $sslConfPath . "'></td></tr>";
		
		$key_len = isset($req_sec_info["default_bits"]) ? $req_sec_info["default_bits"] : "";
		
		$formTable .= "<tr><td>Key Len*:</td><td><input type = 'text' name = 'key_len' value = '" . $key_len . "'></td></tr>";
		
		$rootCertPath = $pathClient->call('CPath.getPath', array('pathName' => "RootCertPath"));
		$rootKeyPath = $pathClient->call('CPath.getPath', array('pathName' => "RootKeyPath"));
		
		$formTable .= "<tr><td>Root Certificate Path`:</td><td>$rootCertPath<input type = 'hidden' name = 'cert_path' value = '"
																	. $rootCertPath . "'></td></tr>"
					. "<tr><td>Root Private Key Path`:</td><td>$rootKeyPath<input type = 'hidden' name = 'key_path' value = '"
																	. $rootKeyPath . "'></td></tr>";
					
		$formTable .= "<tr><td>Private Key Password*:</td><td><input type = 'password' name = 'pass'></td></tr>";
		
		$subjInfo = $confClient->call('CConf.getSsCertInfo', array('conf_file_path' => $sslConfPath));
		// subject:    "/C=AM/emailAddress=blablablkj/ST=blastate/L=locality/O=bla/OU=orgunit/"
		$subject = "/";
		if(isset($subjInfo['country'])) if(!empty($subjInfo['country'])) $subject .= "C={$subjInfo['country']}/";
		if(isset($subjsubjInfo['state'])) if(!empty($subjInfo['state'])) $subject .= "ST={$subjInfo['state']}/";
		if(isset($subjInfo['locality'])) if(!empty($subjInfo['locality'])) $subject .= "L={$subjInfo['locality']}/";
		if(isset($subjInfo['organization'])) if(!empty($subjInfo['organization'])) $subject .= "O={$subjInfo['organization']}/";
		if(isset($subjInfo['organizationUnit']))
			if(!empty($subjInfo['organizationUnit'])) $subject .= "OU={$subjInfo['organizationUnit']}/";
		if(isset($subjInfo['commonName'])) {
			if(!empty($subjInfo['commonName'])) $subject .= "CN={$subjInfo['commonName']}/";
			if(isset($subjInfo['email'])) if(!empty($subjInfo['email'])) 
				$subject .= "emailAddress={$subjInfo['email']}/";
		}
		
		$formTable .= "<tr><td>Subject Distinguished Name*:</td><td><input type = 'text' name = 'subject' value = '"
																		. $subject . "'></td></tr>";

		$formTable .= "<tr><td>Certificate Lifetime*:</td><td><input type = 'text' name = 'days'></td></tr>";
		
		$md = isset($req_sec_info["default_md"]) ? $req_sec_info["default_md"] : "";
		
		$formTable .= "<tr><td>Default Message Digest*:</td><td><select name = 'md'>\n"
					. "<option value = 'sha1' selected>sha1</option>\n"
					. "<option value = 'mdc2'>mdc2</option>\n"
					. "<option value = 'md5'>md5</option>\n"
					. "<option value = 'md2'>md2</option>\n"
					. "</select>\n</td></tr>";
		
		$formTable .= "<tr><td>x509 Extension Section:</td><td><select name = 'ext_name'>\n";
		if(!empty($other_section_names["x509_extensions"]))
			$formTable .= "<option value = '" . $other_section_names["x509_extensions"] . "' selected>"
						. $other_section_names["x509_extensions"] . "</option>\n";
		foreach($exts as $ext_name)
			$formTable .= "<option value = '" . $ext_name . "'>$ext_name</option>\n";
		$formTable .= "</select>\n</td></tr>";
		
		$formTable .= "<tr><td colspan = '2' style = 'padding: 16'><center>"
					. "` To change these values use <span class = \"link\" onmousedown=\"get_inter('CPath')\">CPath</span><br>"
					. "* Required fields<br><br>"
					. "<input type = 'hidden' name = 'sub' value = 'CNewCert'>"
					. "<input type = 'submit' value = 'Create CA'></center></td></tr>";
		
		$formTable .= "</table>\n";

		$result .= "<table border = '3' bgcolor ='#9CF'>\n<tr>\n<td>\n$configTable\n</td>\n<td>\n$formTable\n</td>\n</tr>\n</table>";
		return $result;
	}

	public function processData($arr) {
//		echo "\n!!! IN createRootCa() !!!\n";

		unset($arr["sub"]);

		require_once("COpenSslApiImpl_class.php");
		$opensslObj = new COpenSslApiImpl();

		$res = $opensslObj->genSsCert($arr["conf_path"], $arr["key_len"], $arr["cert_path"], $arr["key_path"],
											$arr["pass"], $arr["subject"], $arr["days"], $arr["md"], $arr["ext_name"]);

		if($res == 1) return "Error in certificate generating command...";
		elseif($res == false) return "Error in configuration or paths : (";
		else return "Success!<BR><BR>$res";
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
		return "Generate CA Root Cert";
	}
}
?>