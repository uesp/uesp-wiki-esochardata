$(document).ready(function () {
    if (window.console && console.log) console.log("ESO Build Rules Editor JavaScript");

     $("#matchRegex").on("input", RegexValidate);
});



window.RegexValidate = function()
{
	var isValid = true;
	var m = $(this).val().match(/^([/~@;%#'])(.*?)\1([gimsuy]*)$/);
	isValid = m ? !!new RegExp(m[2], m[3]) :  false;

	try {
	    new RegExp($("#matchRegex").val());
	}
	catch(e) {
	    isValid = false;
	}

	if(!isValid) {
		//alert("Invalid regular expression");
		$("#matchRegex").css("backgroundColor", "#ffcccc")
		$("#errMsg").html("Please enter a valid regex")
	}
}
