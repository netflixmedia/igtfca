<?php

class CSignCsr {
	public function getInterface() {
//		echo "\n!!! IN getInterface() !!!\n";

		$result = "<h1><font color = '#9CF'>Sign Certificate Signing Requests</font></h1>";

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

		//default policy section
		if(isset($defca_sec_info['policy'])) {
			if(!empty($defca_sec_info['policy'])) {
				$infoString .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
						. "<center>{$defca_sec_info['policy']}</center></font></td></tr>";
				$pol_sec_info = $confClient->call('CConf.getInfoForGivenSectionName',
													array('section_name' => $defca_sec_info['policy'],
														  'confPath' => $confPath)
												 );

				foreach($pol_sec_info as $attr_name => $attr_value) {
					$infoString .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
				}
			}
		}
		
		//all remaining policy sections
		$pol_secs = $confClient->call('CConf.getPolicySections', array('confPath' => $confPath));

		// remove default policy section from policy sections array not to show it twice
		if(isset($defca_sec_info['policy']))
			for($i = 0; $i < sizeof($pol_secs); ++$i)
				if($pol_secs[$i] == $defca_sec_info['policy'])
					unset($pol_secs[$i]);

		if(!empty($pol_secs)) {
			foreach($pol_secs as $sec_name) {
				$infoString .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
							. "<center>$sec_name</center></font></td></tr>";
				$sec_info = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => $sec_name,
																						'confPath' => $confPath));

				foreach($sec_info as $attr_name => $attr_value) {
					$infoString .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
				}
			}		
		}
		
		//default x509 extension section
		if(isset($defca_sec_info['x509_extensions'])) {
			if(!empty($defca_sec_info['x509_extensions'])) {
				$infoString .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
						. "<center>{$defca_sec_info['x509_extensions']}</center></font></td></tr>";
				$ext_sec_info = $confClient->call('CConf.getInfoForGivenSectionName',
													array('section_name' => $defca_sec_info['x509_extensions'],
														  'confPath' => $confPath)
												 );

				foreach($ext_sec_info as $attr_name => $attr_value) {
					$infoString .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
				}
			}
		}
		
		//all remaining x509 extension sections
		$ext_secs = $confClient->call('CConf.getExtensionSections', array('confPath' => $confPath));
		
		// remove default section not to show it twice
		if(isset($defca_sec_info['x509_extensions']))
			for($i = 0; $i < sizeof($ext_secs); ++$i)
				if($ext_secs[$i] == $defca_sec_info['x509_extensions'])
					unset($ext_secs[$i]);
		
		if(!empty($ext_secs)) {
			foreach($ext_secs as $sec_name) {
				$infoString .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
							. "<center>$sec_name</center></font></td></tr>";
				$sec_info = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => $sec_name,
																						'confPath' => $confPath));

				foreach($sec_info as $attr_name => $attr_value) {
					$infoString .= "<tr><td>" . $this->decode($attr_name, 2) . ":</td><td>$attr_value</td></tr>\n";
				}
			}		
		}
		
		$infoString .= "<tr><td colspan = '2' style = 'padding: 16'><center><br>"
					. "To change the config use the <span class = \"link\" onmousedown=\"get_inter('CConf')\">CConf</span> module!"
					. "</center></td></tr>";
		
		$infoString .= "</table>";
	

	
	
		// DIV for viewing SPKAC files
		$spkacDiv = "<div id = 'spkacDiv'></div>";

		
		
		
		// table with form to submit
		$formString = "<form method = 'POST' name = 'frm'>\n<table>\n";

		// SPKAC files list
		$formString .= "<tr><td colspan = '2'>SPKAC Files To Sign*:</td></tr>";

		$spkacDir = $pathClient->call('CPath.getPath', array('pathName' => "SpkacFolderPath"));
		if($handle = opendir($spkacDir)) {	// browse directory
			while (false !== ($file = readdir($handle))) {
				if($file != '.' && $file != '..') {
					$formString .= "<tr><td>"
								. "<input type='radio' name='spkacfile_path' value='" . $spkacDir . '/' . $file . "'/>"
								. " $file </td><td>"
								. "<input type='button' value='View File' onclick='getSpkac(\"" . $spkacDir . '/' . $file . "\")'>"
								."</td></tr>\n";
				}
			}
			closedir($handle);
		}
		else die("Error when opening spkac's dir!");
		
		$formString .= "<tr><td colspan = '2'><br><br></td></tr>";
		
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
		
		// Output Certificates Folder Path
		$outCertsPath = $pathClient->call('CPath.getPath', array('pathName' => "OutCertsPath"));
		$formString .= "<tr><td>Output Certificates Folder Path`:</td>"
					. "<td>$outCertsPath<input type = 'hidden' name = 'outcerts_path' value = '" . $outCertsPath . "'</td></tr>";
		
		// Output User Certificates Folder Path
		$userOutCertsPath = $pathClient->call('CPath.getPath', array('pathName' => "UserCertsPath"));
		$formString .= "<tr><td>User Output Certificates Folder Path`:</td>"
					. "<td>$userOutCertsPath<input type = 'hidden' name = 'user_outcerts_path' value = '"
					. $userOutCertsPath . "'</td></tr>";
		
		//Days To Certify For
		$days = isset($defca_sec_info["default_days"]) ? $defca_sec_info["default_days"] : "";
		
		$formString .= "<tr><td>Days To Certify For*:</td><td><input type = 'text' name = 'days' value = '"
																. $days . "'></td></tr>";
		
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
		
		// Policy Section To Use
		$formString .= "<tr><td>Policy Section To Use*:</td><td><select name = 'pol_sec'>\n";
		
		$def_pol_sec = isset($defca_sec_info["policy"]) ? $defca_sec_info["policy"] : "";
		if(!empty($def_pol_sec))
			$formString .= "<option value = '" . $def_pol_sec . "' selected>" . $def_pol_sec . "</option>\n";
		foreach($pol_secs as $sec_name)
			$formString .= "<option value = '" . $sec_name . "'>$sec_name</option>\n";
		$formString .= "</select>\n</td></tr>";

		// x509 Extension Section To Use
		$formString .= "<tr><td>x509 Extension Section:</td><td><select name = 'ext_sec_name'>\n";
		
		$def_ext_sec = isset($defca_sec_info["x509_extensions"]) ? $defca_sec_info["x509_extensions"] : "";
		if(!empty($def_ext_sec))
			$formString .= "<option value = '" . $def_ext_sec . "' selected>$def_ext_sec</option>\n";
		foreach($ext_secs as $sec_name)
			$formString .= "<option value = '" . $sec_name . "'>$sec_name</option>\n";
		$formString .= "</select>\n</td></tr>";
		
		// User Cert Identificator
		$formString .= "<tr><td>User Cert Identificator*:</td><td><input type = 'text' name = 'user_id' value = '" . substr(md5(uniqid()), 0, 6) . "'></td></tr>";
		
		// submit button
		$formString .= "<tr><td colspan = '2' style = 'padding: 16'><center>"
					. "` To change these values use <span class = \"link\" onmousedown=\"get_inter('CPath')\">CPath</span><br>"
					. "* Required fields<br><br>"
					. "<input type = 'hidden' name = 'sub' value = 'CSignCsr'>"
					. "<input type = 'submit' value = 'Sign CSR'></center></td></tr>";
		
		$formString .= "</table>\n</form>";

		
		$result .= "<table border = '3' bgcolor ='#9CF'>\n<tr>\n<td>\n$infoString\n</td>\n<td>\n$spkacDiv\n$formString\n</td>\n</tr>\n</table>";
		return $result;
	}

	public function processData($arr) {
//		echo "\n!!! IN signSpkac() !!!\n";

		unset($arr["sub"]);

		require_once("COpenSslApiImpl_class.php");
		$opensslObj = new COpenSslApiImpl();

		$res = $opensslObj->signSpkac($arr['conf_path'], $arr['ca_sec_name'], $arr['rootcert_path'], $arr['rootcert_key_path'], $arr['rootcert_key_pass'], $arr['spkacfile_path'], $arr['outcerts_path'], $arr['user_outcerts_path'], $arr['days'], $arr['md'], $arr['pol_sec'], $arr['user_id'], $arr['ext_sec_name']);

		if($res == 1) $ret_str = "Error in spkac signing command...";
		else if(empty($res)) $ret_str = "Error in configuration or paths when signing SPKAC : (";
		else {
			$ret_str = $res;
			
			require_once('nusoap.php');
			$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
			$pathClient = new nusoap_client("$url/CPathSoapServer.php");
			$signed_spkac_path = $pathClient->call('CPath.getPath', array('pathName' => "SignedSpkacsFolderPath"))
									. '/' . basename($arr['spkacfile_path']);
			
			if(rename($arr['spkacfile_path'], $signed_spkac_path) == FALSE)
				die("Oops, an error occured while moving signed spkac file...");
			else $ret_str .= "\nSPKAC file moved.";
		}

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
		return "Sign CSRs";
	}
}

?>