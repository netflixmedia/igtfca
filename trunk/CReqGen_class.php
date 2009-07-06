<?php

class CReqGen {
	public function getInterface() {
		$res = $this->getFormHtml("initial", null, null, null);
		
		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		$res .= "<br><br>View an <A onClick = "
	. "'window.open(\"$url/screenshot.jpg\",\"mywindow\",\"width=800,height=500,left=150,top=100,screenX=150,screenY=100\")'"
					. ">example</A>";	

		return $res;
	}
	
	public function processData($arr) {
		unset($arr["sub"]);
		
		if(isset($arr["stage"])) {
			if($arr["stage"] == "confirmation") {
				$stage = $arr["stage"];
				unset($arr["stage"]);
				
				//put together interface form string into $res
				$res = "";
				
				/*
				# the response from reCAPTCHA
				$resp = null;
		
				# reCAPTCHA
				if (isset($arr["recaptcha_response_field"])) {
					require_once('recaptchalib.php');
			
					$privatekey = "6Lf5PQYAAAAAACUI0C0SNgXrjIvNVGGAsJsNnDsC";
					$resp = recaptcha_check_answer ($privatekey,
					$_SERVER["REMOTE_ADDR"],
					$arr["recaptcha_challenge_field"],
					$arr["recaptcha_response_field"]);

					if (!$resp->is_valid) { return "byaka"; }
				}
				*/
		
				// server side input values check
				$errorMsgStr = "";
				$errorNamesArr = array();
				
				/* 
				// check values to be in min and max boundaries (min and max from config)
				require_once('nusoap.php');
				$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		
				//get OpenSsl config file path in $confPath
				$pathClient = new nusoap_client("$url/CPathSoapServer.php");
				$confPath = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));
	
				//initialize conf client
				$confClient = new nusoap_client("$url/CConfSoapServer.php");	

				//get data from config
				$conf_data = array_merge($confClient->call('CConf.getInfoForGivenSectionName', 
											array('section_name' => "req_distinguished_name", 'confPath' => $confPath)),
										$confClient->call('CConf.getInfoForGivenSectionName',
											array('section_name' => "additional_data", 'confPath' => $confPath)));
				
				foreach($arr as $name => $value) {
					$name = $this->decode($name, 3);
					
					if(isset($dn[$name . "_min"])) {
						if(strlen($value) < $dn[$name . "_min"]) {
							$errorNamesArr[] = $name;
							$errorMsgStr .= "error message";
						}
					}
					
					if(isset($dn[$name . "_max"])) {
						if(strlen($value) > $dn[$name . "_max"]) {
							$errorNamesArr[] = $name;
							$errorMsgStr .= "error message";
						}
					}
				}
				*/
				
				// VALUE CHECKS
				//validate common name
				if(isset($arr["commonName"])) {
					$FNandLNs = explode(' ', $arr["commonName"]);
					
					if(sizeof($FNandLNs) <= 1) {
						$errorNamesArr[] = "commonName";
						$errorMsgStr .= "First and last names SEPARATED BY SPACE.\n";
					}
					
					if($this->validate_first_name($FNandLNs[0]) == false) {
						$errorNamesArr[] = "commonName";
						$errorMsgStr .= "The first name has a wrong format.\n";
					}
					
					for($i = 1; $i < sizeof($FNandLNs); ++$i) {
						if($this->validate_last_name($FNandLNs[$i]) == false) {
							$errorNamesArr[] = "commonName";
							$errorMsgStr .= "The last name has a wrong format.\n";
						}
					}
				}
				
				// validate email
				if(isset($arr["emailAddress"])) {
					if($this->validate_email_address($arr["emailAddress"]) == false) {
						$errorNamesArr[] = "emailAddress";
						$errorMsgStr .= "Email address has a wrong format.\n";
					}
				}
				
				// decode the ___0_organizationName type attributes
				foreach($arr as $name => $value) {
					if(($name1 = $this->decode($name, 2)) != $name) {
						$arr[$name1] = $arr[$name];
						unset($arr[$name]);
					}
				}
				
				$res .= $this->getFormHtml($stage, $arr, $errorNamesArr, $errorMsgStr);
				
				return $res;			
				
			}


			elseif($arr["stage"] == "submit") {
				unset($arr["stage"]);
		
				$ra = $arr["ra"];
				unset($arr["ra"]);

				// remove values not filled in the form so that they will not be included in the resulting CSR
				foreach($arr as $name => $value) {
					if(empty($value))
						unset($arr[$name]);
				}
				
				// excluding additional data from $arr and putting it into $additional_values_from_post array
				//nusoap stuff
				require_once('nusoap.php');
				$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
				$pathClient = new nusoap_client("$url/CPathSoapServer.php");
				$confPath = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));
				$confClient = new nusoap_client("$url/CConfSoapServer.php");	

				//get additional data from config
				$add_data = $confClient->call('CConf.getInfoForGivenSectionName',array('section_name' => "additional_data",
																						'confPath' => $confPath));

				$additional_values_from_post = array();
				foreach($add_data as $name => $value) {
					if(strpos($name, '_', 1) === FALSE) {
						if(isset($arr[$name])) {
							$additional_values_from_post[$name] = $arr[$name];
							unset($arr[$name]);
						}
					}
				}
				
				
				$fileFolderPath = "";
				$fileExt = "";
				$fileContent = "";

				
				if(isset($arr["pukey"])) {
					$key = $arr["pukey"];
					unset($arr["pukey"]);
		
					// generation of string of spkac file (in $fileContent)
					$fileContent .= "SPKAC=" . str_replace(str_split(" \t\n\r\0\x0B"), '', $key);
					foreach ($arr as $name => $val){
						$fileContent .= "\n" . $this->decode($name, 3) . '=' . $val;
					}
				
					$fileExt = "spkac";
				}
				
				elseif(isset($arr["iecsr"])) {
					$fileContent = "-----BEGIN CERTIFICATE REQUEST-----\n"
								. chunk_split(preg_replace('/[\r\n]+/', '', $arr["iecsr"]), 64)
								. "-----END CERTIFICATE REQUEST-----\n";
				
					$fileExt = "pem";
				}
				
				else die("No CSR found!");
				
				
				//add addtitional data
				$fileContent .= "\n\nAdditional Data";
				foreach ($additional_values_from_post as $name => $val){
					$fileContent .= "\n" . $this->decode($name, 3) . '=' . $val;
				}
			
				// add RA data
				$fileContent .= "\n\nRegistration Authority";
				$fileContent .= "\n" . "RA Name=" . $ra;
				
				//writing CSR string into file
				// getting filename (requests serial number) from requests serial file
				$reqSerialPath = $pathClient->call('CPath.getPath', array('pathName' => "ReqSerialPath"));
				$reqSerialFileHandle = fopen($reqSerialPath, 'r+');
				$serialString = fread($reqSerialFileHandle, 32) or die("ERROR! Wrong file path! $reqSerialPath");
				
				$fileFolderPath = $pathClient->call('CPath.getPath', array('pathName' => ucfirst($fileExt) . "FolderPath"));
				
				$fileName = $serialString . '.' . $fileExt;
				
				$file = fopen($fileFolderPath . '/' . $fileName, 'w');
				if($file === false) return "cant open file";
				fwrite($file, $fileContent);
				fclose($file);
				
				// update serial
				$serialNumber = (int)$serialString;
				rewind($reqSerialFileHandle);
				fwrite($reqSerialFileHandle, $serialNumber + 1);
				fclose($reqSerialFileHandle);

				
				$res = "";

/*				$res .= "Your request is the following:\n\n";
				//$res .= '' . $fileName . '';
				$res .= $fileContent;
*/				
				// giving user the link to his req
				$res .= "Your request is successfuly generated!"
						. "<br>Download it here (right click and choose Save As): ";
				$res .= "<a href = '" . $url . '/ca/' . $fileExt . '/' . $fileName . "' target = '_blank'>$fileName</a><br><br>";
				
				// giving user further instructions. Get them from the ReqFinalTextFilePath file
				$outputTextPath = $pathClient->call('CPath.getPath', array('pathName' => "ReqFinalTextFilePath"));
				$res .= file_get_contents($outputTextPath);
				
				// giving user his RA contacts
				$ra_cont = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => "ra_contacts", 
																					'confPath' => $confPath));
				$ra_var = "";
				foreach($ra_cont as $name => $val) {
					if($val == $ra) {
						$ra_var = $name;
						break;
					}
				}
				if(!isset($ra_cont[$ra_var . "_contacts"])) die("RA contacts not found!!!");
				$res .= "<br>{$ra_cont[$ra_var . '_contacts']}";
				
				return $res;
			}
		}
	}
	

	public function getName(){
		return "SPKAC generator";
	}
	


	
	private function getFormHtml($stage, $valsArr, $errorNamesArr, $errorMsgStr) {
		$button_names = array(
			"confirmation" => "Next",
			"submit" => "Submit"
		);
		
		$next_stage_name = array(
			"initial" => "confirmation",
			"confirmation" => "submit"
		);
	
		$res = "";
//		$res .= print_r($errorNamesArr, 1);
		
		// for further browser detection
		/*$res .= "<script type='text/javascript'>"
				. "var ie = (document.all && document.getElementById);"
				. "var ns = (!document.all && document.getElementById);"
				. "</script>";
		*/
		
		// Microsoft ActiveX control to generate the certificate. Should be ignored in mozilla
		$res .= "<object classid='clsid:127698e4-e730-4e5c-a2b1-21490a70c8a1' codebase='/certcontrol/xenroll.dll' id='certHelper'>"
			. "</object>";
		
		//put together interface form string into $res
		$genOrNo = false;
		if($stage == "confirmation")
			if(empty($errorNamesArr))
				$genOrNo = true;
				
		$res .= "<form method = 'post' name = 'frm' onsubmit = 'return checkValues(document.frm, $genOrNo);'>";
		$res .= "<table class = 'tbl'>";
		$odd = 0;
		$res .= "<caption>Personal certificate request form</caption>";

		if(empty($errorMsgStr)) {
			if($stage != "initial") 
				$res .= "<tr><td colspan = '2'><font color='#33cc33'><b>Here is the data you entered. Please check that they are correct, then choose your key size (Medium Grade) and submit the form. </b></font></td</tr>";
		}	
		else {
			$res .= "<tr><td colspan = '2'><font color='#cc66cc'>There were problems with inputted data:<br>$errorMsgStr </font></td</tr>";
		}
		
		require_once('nusoap.php');
		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		
		//get OpenSsl config file path in $confPath
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$confPath = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));
	
		//initialize conf client
		$confClient = new nusoap_client("$url/CConfSoapServer.php");	

		//get data from config
		$conf_data = array_merge($confClient->call('CConf.getInfoForGivenSectionName', 
									array('section_name' => "req_distinguished_name", 'confPath' => $confPath)),
								$confClient->call('CConf.getInfoForGivenSectionName',
									array('section_name' => "additional_data", 'confPath' => $confPath)));
		
		// get the names of all the request attributes
		// names of request attributes are those attributes of the config that don't contain '_'  (toporniy sposob kone4no, no 4to zh podelat')
		$attribute_names = array();
		foreach($conf_data as $name => $value) {
			if(strpos($name, '_', 1) === FALSE) {
				$attribute_names[] = $name;
			}
		}

		
		// for each attribute name from config create it's input field
		foreach($attribute_names as $attr_name){
			$name = ($conf_data[$attr_name] != "") ? $conf_data[$attr_name] : die("Who the hell created this config o_O! Without description for an attribute...	attribute - " . $attr_name);
			
			$description = (isset($conf_data[$attr_name . "_description"])) ? $conf_data[$attr_name . "_description"] : "";

			$default = (isset($conf_data[$attr_name . "_default"])) ? $conf_data[$attr_name . "_default"] : "";
			
			$min = (isset($conf_data[$attr_name . "_min"])) ? $conf_data[$attr_name . "_min"] : "";
			
			$max = (isset($conf_data[$attr_name . "_max"])) ? $conf_data[$attr_name . "_max"] : "";
			
			$policy = (isset($conf_data[$attr_name . "_policy"])) ? $conf_data[$attr_name . "_policy"] : die("Who the hell created this config o_O! Without defining a POLICY for an attribute... attribute - " . $attr_name);
			
			
			$res .= "<tr";

			
			if($stage == "confirmation") {
				foreach($errorNamesArr as $errName) {
					if($attr_name == $errName) {
						$res .= " class = 'inputError'";
					}
				}
			}
			$res .= ($odd == 1) ? " class = 'odd'" : "";
			$odd = ($odd == 1) ? 0 : 1;
			
			$res .= "><td>$name";
			if($description) {
				$res .= '<br /><i>' . $description . '</i>';
			}

			$value = isset($valsArr[$attr_name]) ? $valsArr[$attr_name] : "";
			
			//$attr_name = $this->decode($attr_name, 2);
			
			switch($policy) {
			case "match": 
				$res .= ":</td><td>";
				if($default == "") die("The DEFAULT VALUE for the mandatory attribute is not defined");
				$res .= "<input type = 'text' name = '$attr_name' value = '$default' readonly = 'readonly'>";
				break;
			case "supplied":
				$res .= '<font color="#FF0000"> *</font>:</td>';
				$res .= "<td><input class = 'supplied' type = 'text' name = '$attr_name' value = '$value'
									maxlength = '$max'></td></tr>";
				break;
			case "optional":
				$res .= ":</td><td><input type = 'text' name = '$attr_name' value = '$value' maxlength = '$max'></td></tr>";
				break;
			default:
				die("Very funny... Policy values can be ONLY one of the following: match, supplied, optional!
					attribute - " . $attr_name);
			}
		}
		
		// get data conserning RAs
		$ra_cont = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => "ra_contacts", 
																					'confPath' => $confPath));
		// RA combo box part
		if(!isset($ra_cont['ra_names'])) {
			die("Define an attribute ra_names in the [ ra_contacts ] section of config.
				Set it's value to the names of variables you use for describing RAs, separated with commas");
		}
		if($ra_cont['ra_names'] == "") {
			die("Define an attribute ra_names in the [ ra_contacts ] section of config.
				Set it's value to the names of variables you use for describing RAs, separated with commas");
		}
		
		$res .= ($odd == 1) ? "<tr class = 'odd'>" : "<tr>";
		$odd = ($odd == 1) ? 0 : 1;
		$res .= "<td>Choose your Registration Authority:</td><td><select name = 'ra'>";
		$ra_names = explode(',', $ra_cont['ra_names']);
		foreach($ra_names as $name) {
			$name = trim($name);
			if(!isset($ra_cont["$name"])) die("No description for RA " . $name);
			if($ra_cont["$name"] == "") die("No description for RA " . $name);

			$res .= "<option value = '{$ra_cont[$name]}'";
			if(isset($valsArr['ra']))
				if($valsArr['ra'] == $ra_cont[$name])
					$res .= " selected";
			$res .= ">$ra_cont[$name]</option>\n";
		}
		$res .= "</select></td></tr>";

		if($stage == "confirmation")
			if(empty($errorNamesArr)) {
				/*$res .= "<script type='text/javascript'>"
						// cut the part of keygen if not netscape family browser
						. "if(!ns) { document.write('BLAAAH<!--'); }"
						. "</script>";*/
						
				$res .= ($odd == 1) ? "<tr class = 'odd'>" : "<tr>";
				$odd = ($odd == 1) ? 0 : 1;
				$res .= "<td>Key size:</td><td><keygen name = 'pukey' challenge = 'mit'></td></tr>";
				
				$res .= "<!-- end netscape specific part -->";
			}
		
			/*			
			//!!!!!!!!!!!!CAPCHA
			require_once('recaptchalib.php');

			$publickey = "6Lf5PQYAAAAAAMtDd-rlanRktDWcX2MjRb22CsUE";

			# the error code from reCAPTCHA, if any
			$error = null;

			$res .= "<tr><td colspan = '2' align = 'center'>" . recaptcha_get_html($publickey, $error) . "</td></tr>";
			//!!!!!!!!!!!!!!!!!!!!CAPCHA END */
			
			//$res .= "<tr><td colspan = '2' align = 'center'><br></td></tr>";
			//$res .= "<tr><td>Code word</td><td><input type = 'text' name = 'code'></td></tr>";

		$res .= "<tr><td colspan = '2' align = 'center'><br>";
		$res .= "<font color='#FF0000'>*</font> - mandatory fields</td></tr>";
		
		$res .= "<tr><td colspan = '2' align = 'center'><br>";
		$res .= "<input type = 'hidden' name = 'sub' value = 'CReqGen'>";
		
		$nextStage = empty($errorNamesArr) ? $next_stage_name[$stage] : $stage;
		$res .= "<input type = 'hidden' name = 'stage' value = '$nextStage'>";
		$res .= "<input type = 'submit' value = '" . $button_names[$nextStage] . "'>";
		$res .= "</td></tr>";
		$res .= "</table>";
		$res .= "</form>";
		
		return $res;	
	}
	
	// mode == 1 -  ____n. -> n.
	private function decode($name, $mode) {
		if($mode == 3) {
			if(preg_match("/^_([0-9]+)_(.+)$/", $name, $match)) {
				$name = $match[1] . '.' . $match[2];
			}
		}
		
		if($mode == 2) {
			if(preg_match("/^(_[0-9]+)_(.+)$/", $name, $match)) {
				$name = $match[1] . '.' . $match[2];
			}
		}
		
		return $name;
	}
	
	
	private function validate_first_name($name) {
		return preg_match('/^[a-zA-Z]+$/', trim($name));
	}

	private function validate_last_name($name) {
		return preg_match('/^[a-zA-Z]+$/', trim($name)) || preg_match('/^[a-zA-Z]+\s*-\s*[a-zA-Z]+$/', trim($name));
	}

	private function validate_email_address($email) {
		# Taken from: http://www.ilovejackdaniels.com/php/email-address-validation
		# First, we check that there's one @ symbol, 
		# and that the lengths are right.
		if (!ereg('^[^@]{1,64}@[^@]{1,255}$', trim($email)))
			# Email invalid because wrong number of characters 
			# in one section or wrong number of @ symbols.
			return false;
			
		# Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i++) {
			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", 
								trim($local_array[$i])))
				return false;
		}

		# Check if domain is IP. If not, 
		# it should be valid domain name
		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1]))
			$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2)
			return false; # Not enough parts to domain
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if(!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", chop($domain_array[$i])))
					return false;
		}
	
		return true;
	}

}

?>
