// ========== AJAX Request ====================================================================================================

var ARequest = new Object();

ARequest.send = function(url, method, callback, data) {
	var ajaxObject = new Object();	//ONLY ONE TIME
	if(window.XMLHttpRequest) 
		ajaxObject = new XMLHttpRequest();
	else if(window.ActiveXObject) {
		ajaxObject = new ActiveXObject("Microsoft.XMLHTTP");
	}
	if(callback){
	ajaxObject.onreadystatechange = function() 
	{
		if (ajaxObject.readyState == 4) {
			if (ajaxObject.status == 200) 
				callback(ajaxObject);
			else
				alert("There was a problem loading data :\n" + ajaxObject.status + "/" + ajaxObject.statusText);
		}
	}
	}
	if (method=="POST") {
		ajaxObject.open(method, url, true);
		ajaxObject.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		ajaxObject.send(data);
	} else if(method=="GET"){
		ajaxObject.open(method, url, true);
		ajaxObject.send(null);
	}
	return ajaxObject;
}

ARequest.sendPOST = function(url, callback, data)
{
	return ARequest.send(url, "POST", callback, data);
}

ARequest.sendGET = function(url, callback) 
{
	return ARequest.send(url, "GET", callback, '');
}