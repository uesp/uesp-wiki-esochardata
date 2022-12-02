$(document).ready(function () {
    if (window.console && console.log) console.log("ESO Build Rules Editor JavaScript");

     $("#matchRegex").on("input", RegexValidate);
     $("#displayRegex").on("input", RegexValidate);
     $("#edit_matchRegex").on("input", RegexValidate);
     $("#edit_displayRegex").on("input", RegexValidate);
});



window.RegexValidate = function()
{
	var isValid = true;
	var m = $(this).val().match(/^([/~@;%#'])(.*?)\1([gimsuy]*)$/);
	isValid = m ? !!new RegExp(m[2], m[3]) :  false;

	var errorMsg = $(this).next(".errorMsg");

	try {
	    new RegExp($(this).val());
	}
	catch(e) {
	    isValid = false;
	}

	if(!isValid) {
		errorMsg.text("Error: please enter a valid regex");
    $(this).addClass("badRegex");
	}
	else {
		errorMsg.text("");
		$(this).removeClass("badRegex");
	}
}
