$(document).ready(function () {
    if (window.console && console.log) console.log("ESO Build Rules Editor JavaScript");

     $("#regex").on("input", RegexValidate);
});



window.RegexValidate = function()
{
	var isValid = true;

	try {
	    new RegExp(document.getElementById("regex").value);
	}
	catch(e) {
	    isValid = false;
	}

	if(!isValid) {
		//alert("Invalid regular expression");
		document.getElementById("regex").style.backgroundColor = "#FFCCCC";
		document.getElementById("errMsg").innerHTML = "Please enter a valid Regex";
	}
}
