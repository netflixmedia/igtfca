<?php

class CReqGen {
	public function getInterface() {
		$res = "";	
		
		require_once('nusoap.php');
		
		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		
		//get OpenSsl config file and root cert file paths in $confPath and $cert_path
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$confPath = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));
		$cert_path = $pathClient->call('CPath.getPath', array('pathName' => "RootCertPath"));
		
		//get all policy sections names in $pol_sec array
		$confClient = new nusoap_client("$url/CConfSoapServer.php");	
		$pol_sec = $confClient->call('CConf.getPolicySections', array('confPath' => $confPath));
		
		//get data about all policy sections from $pol_sec into polarr[section_name][attr_name] = attr_value
		$polarr = array();
		foreach($pol_sec as $sec) {
			$polarr[$sec] = $confClient->call('CConf.getInfoForGivenSectionName', array('section_name' => $sec,
																						'confPath' => $confPath));
		}

		//put together interface form string into $res
		$res .= "<font color = '#9CF'>";
		$res .= "'`' - Match <br>'*' - Supplied <br>' ' - Optional</font>";
		
		//one table for each section
		foreach($polarr as $sections => $nameArr)
		{
			$res .= "<br><br><br><form method = 'post' name = 'frm' onsubmit = 'return checkValues(document.frm);'>";
			$res .= "<table border = '5' bgcolor='#9CF'>";
				
			$res .= "<tr><td colspan = '2' align = 'center'>$sections</td></tr>";
			
			$res .= "<tr><td>Email:</td><td><input type = 'text' name = 'email'>";
			$res .= "<tr><td colspan = '2' align = 'center'> </td></tr>";
			
			$res .= "<tr><td>KeyLength:    	</td><td><keygen name = 'pukey' challenge = 'mit'>";
			foreach($nameArr as $names => $vals){
				if ($vals == "supplied") $zvz = '*';
				else if ($vals == "match") $zvz = '`'; 
				else $zvz =' ';
				
				//decode
				// only ____ because in SPKAC there is only stuff from policy that can't be lik 1.bla
				$nameToShow = $this->decode($names, 1);
				
				$res .= "<tr><td> $zvz $nameToShow</td>";
				
				if($vals == "match") {
					$certInfoClient = new nusoap_client("$url/CCertInfoSoapServer.php");
					$subjAttrVal = $certInfoClient->call('CCertInfo.getSubjAttrVal', array('subj_attr_name' => $names, 
																							'root_cert_path' => $cert_path));
					//if($subjAttrVal === false) return $res . "ERROR!! Wrong attribute name!";
					$res .= "<td><input type = 'hidden' name = \"$names\" value = \"$subjAttrVal\">$subjAttrVal</td></tr>";
				}
				else
					$res .= "<td><input type = 'text' name = \"$names\"></td></tr>";
			}
			
			/*			
			//!!!!!!!!!!!!CAPCHA
			require_once('recaptchalib.php');

			$publickey = "6Lf5PQYAAAAAAMtDd-rlanRktDWcX2MjRb22CsUE";

			# the error code from reCAPTCHA, if any
			$error = null;

			$res .= "<tr><td colspan = '2' align = 'center'>" . recaptcha_get_html($publickey, $error) . "</td></tr>";
			//!!!!!!!!!!!!!!!!!!!!CAPCHA END
*/	
			
			//$res .= "<tr><td colspan = '2' align = 'center'><br></td></tr>";
			//$res .= "<tr><td>Code word</td><td><input type = 'text' name = 'code'></td></tr>";
			
			$res .= "<tr><td colspan = '2' align = 'center'>";
			$res .= "<input type = 'hidden' name = 'pol_sec' value = '" . $sections . "'>";
			$res .= "<input type = 'hidden' name = 'sub' value = 'CReqGen'>";
			$res .= "<input type = 'submit' value = 'Generate SPKAC'>";
			$res .= "</td></tr>";
			$res .= "</table>";
			$res .= "</form>";
		}
		
		return $res;			
	}
	
	public function processData($arr) {
		unset($arr["sub"]);
		
/*		# the response from reCAPTCHA
		$resp = null;
		
		# reCAPTCHA
		if (isset($arr["recaptcha_response_field"])) {
			require_once('recaptchalib.php');
			
			$privatekey = "6Lf5PQYAAAAAACUI0C0SNgXrjIvNVGGAsJsNnDsC";
 		    $resp = recaptcha_check_answer ($privatekey,
                                       $_SERVER["REMOTE_ADDR"],
                                       $arr["recaptcha_challenge_field"],
                                       $arr["recaptcha_response_field"]);

 		      if ($resp->is_valid) {
*/
			  
		$form = "";
		
		$key = $arr["pukey"];
		unset($arr["pukey"]);
		
		$pol_sec = $arr["pol_sec"];
		unset($arr["pol_sec"]);
		
		$email = $arr["email"];
		unset($arr["email"]);
		
		if(!preg_match("#^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,6}$#", $email))
			return "Invalid e-mail address!";
		
		// remove values not filled in the form so that they will not be included in the resulting spkac file
		foreach($arr as $name => $value) {
			if(empty($value))
				unset($arr[$name]);
		}
		
		require_once('nusoap.php');
		
		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		
		//get root cert file path
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$cert_path = $pathClient->call('CPath.getPath', array('pathName' => "RootCertPath"));

		//get OpenSsl config file path
		$confPath = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));
		
		//create soap clients for CConf and CCertInfo
		$confClient = new nusoap_client("$url/CConfSoapServer.php");
		$certInfoClient = new nusoap_client("$url/CCertInfoSoapServer.php");
		
		//checking match
		$polarr = $confClient->call('CConf.getInfoForGivenSectionName', array('sec_name' => $pol_sec,
																			  'confPath' => $confPath));
		foreach($polarr as $names => $vals){
			if($vals == "match") {
				$encodedName = $this->decode($names, 3);
				$decodedName = $this->decode($names, 1);
				$valFromCert = $certInfoClient->call('CCertInfo.getSubjAttrVal', array('subj_attr_name' => $decodedName, 
																					 'root_cert_path' => $cert_path));
//$form .= "\nvalfrmcert = $valFromCert\nattrName = $decodedName\n";
				if($arr[$encodedName] != $valFromCert)
				//return print_r($arr, 1) . "\n\n";
					return("Что ты тут делаешь?!?! \nWhat are you doing here?!?!\n\n{$arr[$encodedName]}[$encodedName] != {$valFromCert}[$decodedName]\n");
			}
			if($vals == "supplied") {
				$encodedName = $this->decode($names, 3);
				if(!isset($arr[$encodedName]) || $arr[$encodedName] == "")
					return "'Supplied' fealds must be given!";
			}
		}
		
		// generation of string of spkac file (in $form)
		$form .= "SPKAC=" . str_replace(str_split(" \t\n\r\0\x0B"), '', $key);
		foreach ($arr as $names => $val){
			$form .= "\n" . $this->decode($names, 1) . '=' . $val;
		}
		
		//writing string into file
		$file_name = date("y-m-d_H-i-s") . "_" . substr(md5(uniqid()), 0, 2) . ".spkac";
		$spkacFilesFolderPath = $pathClient->call('CPath.getPath', array('pathName' => "SpkacFolderPath"));
		$file=fopen($spkacFilesFolderPath . '/' . $file_name, 'w');
		if($file === false) return "cant open file";
		fwrite($file, $form);
		fclose($file);
		
		$form = '<h2>' . $file_name . '</h2>' . $form;
		return $form;
		


/*

			}
			else 
			{
				$error = $resp->error;
				return $error;
			}
		}*/
	}
	
	// mode == 1 -  ____n. -> n.
	private function decode($name, $mode) {
		if($mode == 3) {
			if(preg_match("/^____([0-9]+)\.(.+)$/", $name, $match)) {
				$name = "____" . $match[1] . '_' . $match[2];
			}
			return $name;
		}
		
		if(strpos($name, '____') === 0)
			if(preg_match("/^____([0-9]+)[\._](.+)$/", $name, $match))
				$name = $match[1] . '.' . $match[2];
		
		return $name;
	}
	
	public function getName(){
		return "SPKAC generator";
	}
}

?>