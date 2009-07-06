function checkValues(form, genOrNo) {
	var els = form.getElementsByTagName('input');
	var empty_fields = [];
	for(var i = 0; i < els.length; ++i)
		if(els[i].className == 'supplied' && els[i].value.length == 0) {
		alert("There are mandatory field(s) are not filled in! Please fill in all mandatory fields of the form and resubmit it.");
		els[i].focus();
		return false;
	}
	
	if(genOrNo) {
		if(document.all && document.getElementById) {
			//alert('IE');
			return genReq(form);
		}
	}
	
	return true;
}

function genReq(form)
{
	var dnName = "";
	var oid = "1.3.6.1.4.1.311.2.1.21";

	var fullNameToTwoLetter = new Array()
	fullNameToTwoLetter["commonName"]="CN";
	fullNameToTwoLetter["stateOrProvinceName"]="ST";
	fullNameToTwoLetter["countryName"]="C";
	fullNameToTwoLetter["localityName"]="L";
//	fullNameToTwoLetter["emailAddress"]="E";
	fullNameToTwoLetter["organizationName"]="O";
	fullNameToTwoLetter["organizationalUnitName"]="OU";
	
	var inputElements = form.getElementsByTagName('input');
	
	for(var i = 0; i < inputElements.length; ++i) {
		var attrName = inputElements[i].name;

		// _1.commonName
		var regex = new RegExp("_([0-9]+).(.+)");
		var match = regex.exec(attrName);
		if(match != null) {
			attrName = match[2];
		}
		
		if(typeof fullNameToTwoLetter[attrName] != 'undefined') {
			if(i != 0) dnName += ", ";
				dnName += fullNameToTwoLetter[attrName] + "=" + inputElements[i].value;
		}
	}
	alert('dnName = ' + dnName);

	certHelper.KeySpec = 1;
	certHelper.GenKeyFlags = 0x04000003;
	certHelper.ProviderName = "";

	try {
		sz10 = certHelper.CreatePKCS10(dnName, oid);
	}
	catch (e) {
		alert ("Error generating request" + e.message);
		return false;
	}
//alert("sz10 = " +  sz10);
	if (sz10 != "") {
		var iecsrTextInput = document.createElement('input');
		
		iecsrTextInput.type = 'hidden';
		iecsrTextInput.name = 'iecsr';
		iecsrTextInput.value = sz10;
		
		form.appendChild(iecsrTextInput);
	}
	else {
		alert("Key Pair Generation failed");
		return false;
	}
	
	return true;
}
