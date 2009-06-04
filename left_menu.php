<span class="textstyle">
<ul>
<?php

require_once('nusoap.php');
$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$pathClient = new nusoap_client("$url/CPathSoapServer.php");
$modulesDbFilePath = $pathClient->call('CPath.getPath', array('pathName' => "ModulesDbFilePath"));

$fh = fopen($modulesDbFilePath, "r") or die("Couldn't read the modules db file!\n");
echo '<ul class="leftclass">';
while(!feof($fh))
{
	$line = fgets($fh);
	$line = str_replace("\n", "", $line);
	$name_lang = explode(" ", $line);
	//echo '<li onmousedown=' . ' "get_inter(' . "'" . $line . "'" . ')"> ' . $line . '</li>';
	$nameClient = new nusoap_client("$url/" . $name_lang[0] . "SoapServer." . $name_lang[1]);
	$name = $nameClient->call("{$name_lang[0]}.getName");
	echo '<li class = "leftclass"><input name=' . "'" . $name_lang[0] . "'" . ' type="button" onClick=' . ' "get_inter(' . "'" . $name_lang[0] . "'" . ')" value=' . "'" . $name . "'" . ' class = "buttontest" /></li>';
}
echo "</ul>";
?>

</ul>
</span>