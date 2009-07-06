<?php

interface SslApi {
	function genSsCert($conf_path, $key_len, $cert_path, $key_path, $pass, $subject, $days, $md, $ext_name);
	function signSpkac($conf_path, $ca_sec_name, $rootcert_path, $rootcert_key_path, $rootcert_key_pass, $spkacfile_path,
									$outcerts_path, $user_outcerts_path, $days, $md, $pol_sec, $user_id, $ext_sec_name);
	function genCrl($conf_path, $ca_sec_name, $rootcert_path, $rootcert_key_path, $rootcert_key_pass, $crl_path, $crl_days, $md);
	function getCertSubject($cert_path);
	function getCertText($cert_path);
	function getSpkacText($spkac_path);
	//function genCSR();
	//function signCSR() ;
};


class COpenSslApiImpl implements SslApi {
	private $enginePath;
	private $logPath;
	
	private function initLogPath() {
		if(!isset($this->logPath)) {
			require_once('nusoap.php');
			$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
			$pathClient = new nusoap_client("$url/CPathSoapServer.php");
			$this->logPath = $pathClient->call('CPath.getPath', array('pathName' => "OpenSslApiLogPath"));
		}
	}
	
	private function log($command) {
		$this->initLogPath();
		
		$fh = fopen($this->logPath, 'a');
		fwrite($fh, $command . "\n\n");
		fclose($fh); 		
	}

	public function __construct() {
		if(strpos(`ver`, 'Windows') !== FALSE) {
			require_once('nusoap.php');
			$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
			$pathClient = new nusoap_client("$url/CPathSoapServer.php");
			$this->enginePath = $pathClient->call('CPath.getPath', array('pathName' => "OpenSslExePath"));
			if(empty($this->enginePath)) die("Set the OpenSslExePath!\n");
			$this->enginePath = '"' . $this->enginePath . '" ';
		}
		else $this->enginePath = "openssl ";
	}
	
	public function genSsCert($conf_path, $key_len, $cert_path, $key_path, $pass, $subject, $days, $md, $ext_name) {
		//c: command is a useless command, added here to bypass the using of multiple sets of double quotes bug of php
		if(strpos(`ver`, 'Windows') !== FALSE)
			$command = "c: & ";
		else $command = "";
		$command .= "c: & " . $this->enginePath . "req -x509 -config $conf_path -newkey rsa:$key_len "
				. "-out $cert_path -outform PEM -keyout $key_path -passout pass:$pass "
				. "-$md -subj \"$subject\" -days $days";
		if(!empty($ext_name))
			$command .= " -extensions $ext_name";
		
		$this->log($command);
		
		system($command, $result);
		
		if($result) return $result;
		else return $this->getCertText($cert_path);
	}
/*	
	public function genCSR() {
		system("openssl req -newkey rsa:1024 -keyout testkey.pem -keyform PEM -out testreq.pem -outform PEM -passout pass:$pass "
					. "-subj '/C=AM/emailAddress=blablablkj/ST=blastate/L=locality/O=bla/OU=orgunit/'", $result);
		return $result;
	}
*/	
/*	public function signCSR() {
		// -outdir (new_certs_dir), -cert (certificate), -keyfile (private_key), -startdate (default_startdate), -days (default_days), -md (default_md), -batch ("isn't prompting").
		system("openssl ca -in testreq.pem -passin pass:qwert -outdir ./certs -cert cacert.pem "
					."-keyfile ./private/privkey.pem -days 361 -md sha1 -batch -config ./openssl.cnf", $result);
		return $result;
	}
*/
	public function signSpkac($conf_path, $ca_sec_name, $rootcert_path, $rootcert_key_path, $rootcert_key_pass, $spkacfile_path,
									$outcerts_path, $user_outcerts_path, $days, $md, $pol_sec, $user_id, $ext_sec_name) {
								
		$command = $this->enginePath . "ca -config $conf_path -name $ca_sec_name "
				. "-cert $rootcert_path -keyfile $rootcert_key_path -passin pass:$rootcert_key_pass "
				. "-spkac $spkacfile_path -outdir $outcerts_path -out $user_outcerts_path/$user_id.der "
				. "-days $days -md $md -policy $pol_sec -batch";
		if(!empty($ext_sec_name))
			$command .= " -extensions $ext_sec_name";
		
		$this->log($command);
		
		system($command, $result);
		
		if($result) return $result;
		else return $this->getCertText("$user_outcerts_path/$user_id.der");
	}
	
	public function genCrl($conf_path, $ca_sec_name, $rootcert_path, $rootcert_key_path, $rootcert_key_pass,
								$crl_path, $crl_days, $md) {
	
		$command = $this->enginePath . "ca -config $conf_path -name $ca_sec_name "
				. "-cert $rootcert_path -keyfile $rootcert_key_path -passin pass:$rootcert_key_pass "
				. "-gencrl -out $crl_path -crldays $crl_days -md $md";
				
		$this->log($command);
		
		system($command, $result);
		
		if($result) return $result;
		else return $this->getCrlText($crl_path);
	}
	
	public function getCertSubject($cert_path) {
		$command = $this->enginePath . "x509 -in $cert_path -noout -subject";
		
		$this->log($command);
				
		exec($command, $resultArr);
		$str = implode("\n", $resultArr);
		
		return substr($str, strlen("subject = /") - 1);
	}
	
	public function getCertText($cert_path) {
		$command = $this->enginePath . "x509 -in $cert_path -noout -text";
		
		if(substr($cert_path, strlen($cert_path) - 3) == "der")
			$command .= " -inform DER";
		
		$this->log($command);

		exec($command, $resultArr);
		$str = implode("\n", $resultArr);

		return substr($str, strlen("subject = /") - 1);
	}
	
	public function getCrlText($crl_path) {
		$command = $this->enginePath . "crl -in $crl_path -noout -text";	
		
		$this->log($command);

		exec($command, $resultArr);
		$str = implode("\n", $resultArr);

		return substr($str, strlen("subject = /") - 1);
	}
	
	public function getSpkacText($spkac_path) {
		$command = $this->enginePath . "spkac -in $spkac_path";	
		
		$this->log($command);

		exec($command, $resultArr);
		$str = implode("\n", $resultArr);

		return substr($str, strlen("subject = /") - 1);
	}
}
?>