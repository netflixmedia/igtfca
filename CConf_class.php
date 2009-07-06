<?php

class CConf {
	private $attr_values;

	// !!!!attributes with same names such as multiple subject attributes in policy sections (organizationName = match \n organizationName = supplied)
	// must come one after another with no other attributes between them (organizationName = match \n countryName = match \n organizationName = supplied)
	// also attributes with same names but in different sections are considered independent from each other rather then multiple 
	private function readFile($conf_file_path) {
//		echo "\n!!! IN readFile() !!!\n";

		$attr_values = array();

		$fh = fopen($conf_file_path, 'r') or die("can't open file");

		$line_ind = 0;
		$current_section = "no_section";
		$collision_index;
		$collision_attr = "";
		$collision_section = "";
		while(!feof($fh)) {
			$line = fgets($fh);
//			echo $line;


			if(preg_match("/^ *#.*$/", $line, $match)) {
//				echo "comment line\n";
				$attr_values["comment_line"][$line_ind] = $match[0];
			}
			
			elseif(preg_match("/^ *\[(.+)\]$/", $line, $match)) {
//				echo "section\n";
				if($pos = strpos($match[1], '#') == 0)
					$current_section = trim($match[1]);
				else {
//					echo "line with comment\n";
					$current_section = trim(substr($match[1], 0, $pos));
					$attr_values["comment"][$line_ind] = trim(substr($match[1], $pos));
				}
			}
			
			elseif(preg_match("/^(.+)=(.+)$/", $line, $match)) {
//				echo "value\n";
				$value;
				
				$pos = strpos($match[2], '#');
				if($pos === FALSE)
					$value = trim($match[2]);
				else {
//					echo "line with comment\n";
					$value = trim(substr($match[2], 0, $pos));
					$attr_values["comment"][$line_ind] = trim(substr($match[2], $pos));
				}
				
				$attr = trim($match[1]);
				if(!isset($attr_values[$current_section][$attr]))
					$attr_values[$current_section][$attr] = $value;
				else {
					if($attr == $collision_attr && $current_section == $collision_section) {
						++$collision_index;
					}
					else {
						$collision_section = $current_section;
						$collision_attr = $attr;
						$collision_index = 1;
					}
					
					$attr_values[$current_section]["____" . $collision_index . "." . $attr] = $value;
				}
			}
			
			elseif(preg_match("/^$/", $line, $match)) {
//				echo "empty line\n";
				$attr_values["empty_line"][$line_ind] = "";
			}
			
			else {
				echo "ERROR. Invalid format! Error in config file on line " . $line_ind . "!\n";
				--$line_ind;
			}
			
			++$line_ind;
		}
		
		$attr_values["file_length"][0] = $line_ind;
		
		fclose($fh);
		
		return $attr_values;
	}

	
	private function writeToFile($array_to_write, $conf_file_path) {
//		echo "\n!!! IN writeToFile() !!!\n";

		$file_length = $array_to_write["file_length"];
		unset($array_to_write["file_length"]);
		$file_string = "";
		foreach($array_to_write as $key => $value) {
			$section_attr = explode("_-_-_", $key);
			
			$this->attr_values[$section_attr[0]][$section_attr[1]] = $value;
		}
//		return $file_string;
//		return print_r($this->attr_values, 1);

		$file_string = "";
		$line_ind = 0;
		foreach($this->attr_values as $section => $name_val) {
			if($section != "comment_line" and $section != "empty_line" and $section != "comment") {
				if($section != "no_section") {
					$this->addToFileString($file_string, " [ $section ]", $line_ind);
				}
				foreach($name_val as $name => $val) {
					// decode    '_1_'    to   '1.'    and '____1_' to ''
					$name = $this->decode($name, 3);
			
					$this->addToFileString($file_string, "$name\t = $val", $line_ind);
				}
			}
		}
		
		while($line_ind < $file_length) {
			$this->addToFileString($file_string, "", $line_ind);
		}
		
		$fh = fopen($conf_file_path, 'w');
		if($fh == FALSE) return "ERROR! Could not open the file.\n";
		fwrite($fh, $file_string);
		fclose($fh);

		return "Write successful! : )\n\n\n$file_string";
	}

	
	private function addToFileString(&$file_string, $string_to_add, &$line_num) {
		if(isset($this->attr_values["empty_line"][$line_num])) {
			$file_string .= "\n";
//			echo "text: empty\n line_num: $line_num\n";
			++$line_num;
			$this->addToFileString($file_string, "", $line_num);
		}
		elseif(isset($this->attr_values["comment_line"][$line_num])) {
			$file_string .= $this->attr_values["comment_line"][$line_num] . "\n";
//			echo "text: " . $this->attr_values["comment_line"][$line_num] . "\n" . " line_num: $line_num\n";
			++$line_num;
			$this->addToFileString($file_string, "", $line_num);
		}
		elseif($string_to_add == "") {
//			echo "return\n";
			return;
		}
		
		if($string_to_add != "") {
			$file_string .= $string_to_add;
//			echo "text: $string_to_add ";
			if(isset($this->attr_values["comment"][$line_num])) {
				$file_string .= "\t" . $this->attr_values["comment"][$line_num];
//				echo "\t" . $this->attr_values["comment"][$line_num];
			}
			$file_string .= "\n";
//			echo "\nline_num: $line_num\n";
			++$line_num;
		}
	}

	
	public function getInterface() {
//		echo "\n!!! IN getInterface() !!!\n";

		require_once('nusoap.php');
		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$conf_file_path = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));

		$attr_values = $this->readFile($conf_file_path);
		
		$formStr = "<h1><font color = '#9CF'>La Configuration</font></h1>";
		$formStr .= "<form method = 'POST'>\n";
		
		$formStr .= "<input type = 'hidden' name = 'file_length' value = '"
				. $attr_values["file_length"][0] . "'>\n";
		unset($attr_values["file_length"]);
				
		$formStr .= "<table bgcolor = '#9CF' border = '5'>\n";

		$policy_sections = $this->getPolicySections($conf_file_path);
		
		foreach($attr_values as $section => $array) {
			if($section != "empty_line" and $section != "comment_line" and $section != "comment") {
				$isPolicySection = false;
				if($section != "no_section") {
					$formStr .= "<tr><td colspan = '2' style = 'padding: 16'><font size = '5'>"
								. "<center>$section</center></font></td></tr>";
					foreach($policy_sections as $sn) {
						if($section == $sn) {
							$isPolicySection = true;
							break;
						}
					}
				}
				foreach($array as $attr_name => $attr_value) {
					$name = $this->decode($attr_name, 2);
					
					if(!$isPolicySection) {
						$formStr .= "<tr><td>$name:</td><td>"
						. "<input type = 'text' name = '$section" . "_-_-_" . "$attr_name' value = '$attr_value'></td></tr>\n";
					}
					else {
						$formStr .= "<tr><td>$name:</td><td><select name = '$section" . "_-_-_" . "$attr_name'>\n";
						
						$options = array('match', 'supplied', 'optional');
						for($i = 0; $i < 3; ++$i) {
							$formStr .= "<option value = '$options[$i]'";
							if($attr_value == $options[$i]) $formStr .= " selected";
							$formStr .= ">$options[$i]</option>\n";
						}
						$formStr .= "</select>\n</td></tr>\n";
					}
				}
			}
			else {
				foreach($array as $attr_name => $attr_value) {
					$formStr .= "<input type = 'hidden' name = '$section" . "_-_-_" . "$attr_name' value = '$attr_value'>";
				}
			}
		}
		
		$formStr .= "<tr><td colspan = '2' style = 'padding: 16'><input type = 'hidden' name = 'sub' value = 'CConf'>"
					. "<center><input type = 'submit' value = 'Change Config'></center>"
					. "</td></tr>";
		$formStr .= "</table>\n</form>\n";

		return $formStr;
	}
	
	public function processData($arr_with_changes) {
//		echo "\n!!! IN changeConfig() !!!\n";
		
		unset($arr_with_changes["sub"]);
		
		require_once('nusoap.php');
		$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
		$pathClient = new nusoap_client("$url/CPathSoapServer.php");
		$conf_file_path = $pathClient->call('CPath.getPath', array('pathName' => "ConfPath"));

		return $this->writeToFile($arr_with_changes, $conf_file_path);
	}

	public function getPolicySections($conf_file_path) {
		$attr_values = $this->readFile($conf_file_path);
		
		$policy_sections = array();
		foreach($attr_values as $section => $array) {
			if(strpos($section, "policy_") === 0) {
				$policy_sections[] = $section;
			}
		}
		return $policy_sections;
	}
	
/*	public function getDefCaSectionName($conf_file_path) {
		$attr_values = $this->readFile($conf_file_path);
		return $attr_values["ca"]["default_ca"];
	}
*/	
	public function getDefCaSecPolicyName($conf_file_path) {
		$attr_values = $this->readFile($conf_file_path);
		$def_ca_sec = $attr_values["ca"]["default_ca"];
		return $attr_values[$def_ca_sec]["policy"];
	}
	
	public function getInfoForGivenSectionName($sec, $conf_file_path){
		$attr_values = $this->readFile($conf_file_path);

		$infoArr = array();
		
		foreach($attr_values as $sections => $arrij ){
			if($sections == $sec) {
				foreach($arrij as $key => $value) {
					//encode
					if(preg_match("/^[0-9].+$/", $key))
						$key = '_' . $key;
					$infoArr[$key] = $value;
				}
				break;
			}
		}
		
		return $infoArr;
	}
	
	public function getSsCertInfo($conf_file_path) {
//		echo "\n!!! IN getSsCertInfo() !!!\n";
		$attr_values = $this->readFile($conf_file_path);

		$dn_section = $attr_values["req"]["distinguished_name"];
	
		$info = array();

		if(isset($attr_values[$dn_section]["commonName_default"]))
			$info['commonName'] = $attr_values[$dn_section]["commonName_default"];		
		if(isset($attr_values[$dn_section]["countryName_default"]))
			$info['country'] = $attr_values[$dn_section]["countryName_default"];
		if(isset($attr_values[$dn_section]["emailAddress_default"]))
			$info['email'] = $attr_values[$dn_section]["emailAddress_default"];
		if(isset($attr_values[$dn_section]["stateOrProvinceName_default"]))
			$info['state'] = $attr_values[$dn_section]["stateOrProvinceName_default"];
		if(isset($attr_values[$dn_section]["localityName_default"]))
			$info['locality'] = $attr_values[$dn_section]["localityName_default"];
		if(isset($attr_values[$dn_section]["organizationName_default"]))
			$info['organization'] = $attr_values[$dn_section]["organizationName_default"];
		if(isset($attr_values[$dn_section]["organizationUnitName_default"]))
			$info['organizationUnit'] = $attr_values[$dn_section]["organizationUnitName_default"];

		return $info;
	}
	
/*	public function getSpkacSignInfo($conf_file_path) {
//		echo "\n!!! IN getSPKACInfo() !!!\n";
		$attr_values = $this->readFile($conf_file_path);

		$def_ca_name = $attr_values["ca"]["default_ca"];
			
		$info = array();
		$info['days_valid'] = $attr_values[$def_ca_name]["default_days"];
		$info['md'] = $attr_values[$def_ca_name]["default_md"];

		return $info;
	}*/
	
	public function getExtensionSections($conf_file_path) {
//		echo "\n!!! IN getExtensionSections() !!!\n";
		$attr_values = $this->readFile($conf_file_path);
		
		$extension_section = array();
		foreach($attr_values as $section => $array) {
			$sec_end_str = substr($section, strlen($section) - strlen("_extension"));
			if($sec_end_str == "_extension") {
				$extension_section[] = $section;
			}
		}
		
		return $extension_section;
	}
	
	// mode == 1 - only ____n_
	// mode == 2 - both ____n_ and _n
	private function decode($name, $mode) {
		if(strpos($name, '_') === FALSE)
			return $name;

		if(preg_match("/^____[0-9]+[\._](.+)$/", $name, $match)) {
			$name = $match[1];
		}
		
		if($mode == 2) {
			if(preg_match("/^_([0-9]+.+)$/", $name, $match)) {
				$name = $match[1];
			}
		}
		
		if($mode == 3) {
			if(preg_match("/^([0-9]+)_(.+)$/", $name, $match)) {
				$name = $match[1] . '.' . $match[2];
			}
		}
		
		return $name;
	}
	public function getName(){
		return "Configuration";
	}
}

?>