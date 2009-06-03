<?php

class CPath{
	private $confPath = "C:\ca\CPathConf.cnf";

	public function getInterface(){
		$htmlForm  = "<form method = 'post' name = 'frm'>";
		
		$htmlForm .= "<table bgcolor='#9CF' border = '5'>";
		
		$osIsWindows = (strpos(`ver`, 'Windows') !== FALSE) ? true : false;

		$fh = fopen($this->confPath, 'r') or die("can't open file");
		while(!feof($fh)) {
			$line = fgets($fh);
			if(empty($line)) break;
			
			preg_match("/^(.+)=(.+)$/", $line, $match);
			if(!(trim($match[1]) == "OpenSslExePath" && !$osIsWindows))
				$htmlForm .= "<tr><td>Input " . trim($match[1]) . "\n"
						. "<input type='text' name = '" . trim($match[1]) . "' value = '" . trim($match[2])
										 . "' onfocus = 'this.nextSibling.nextSibling.checked = true;'> "
						. "<input type = 'checkbox' name = 'chp[]' value = '" . trim($match[1]) . "'>\n</td></tr>";
			
		}
		fclose($fh);
		
		$htmlForm .= "<input type = 'hidden' value = 'CPath' name = 'sub'><br>";
		$htmlForm .= "<input type = 'submit' value = 'Change Paths' onclick=\"alert('dada')\"><br>";
		$htmlForm .= "</table>";
		$htmlForm .= "</form>";
		
		
		return $htmlForm;
	}
	
	public function processData($arrToWrite){
		$toWriteInFile = array();
		if (isset($arrToWrite["chp"]))
		{
			foreach($arrToWrite["chp"] as $chpvalue){
				$toWriteInFile[$chpvalue] = $arrToWrite[$chpvalue];
			}
		}
		
		$fileString = file_get_contents($this->confPath) or die("ERROR! Wrong file path!");
		
		$data = $fileString;
		foreach($toWriteInFile as $name => $path){
			preg_match("/(.*)" . $name . " = (.*)/s", $data, $matchArr);
			$matchArrDD = strstr($matchArr[2], chr(13));

			$data = $matchArr[1] . $name . " = " . $toWriteInFile[$name] . $matchArrDD ;
		}
		
		file_put_contents($this->confPath, $data);
		return $data;
	}
	
	public function getPath($pathName){
		$fileString = file_get_contents($this->confPath) or die("ERROR!!! CPath conf file not found!");
		preg_match("/(.*)" . $pathName . " = (.*)/s", $fileString, $matchArr);
		$pos = strpos($matchArr[2], chr(13));
		return substr($matchArr[2], 0, $pos);
	
	}
	
	public function getName(){
		return "Path Config";
	}
	
}

?>