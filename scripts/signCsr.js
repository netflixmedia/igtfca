function getSpkac(path) {
	ARequest.sendGET("ajax_server_for_common_use.php?spkac_path=" + path, showSpkac);
}

function showSpkac(ajax_response) {
	var div = document.getElementById("spkacDiv");
	div.innerHTML = ajax_response.responseText;
}