function get_inter(modul){
	ARequest.sendGET("ajax_server.php?modname=" + modul, show_inter);	
}

function show_inter(ajax_response){
//alert("bla");
	var cont = document.getElementById("cont");
	cont.innerHTML = "<pre>"+ajax_response.responseText+"</pre>";

	var ob = document.getElementById("recaptcha");
	if(ob.text!=null) eval(ob.text);
	
}