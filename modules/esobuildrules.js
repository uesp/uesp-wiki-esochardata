$(document).ready(function () {
    if (window.console && console.log) console.log("ESO Build Rules Editor JavaScript");

     $("#matchRegex").on("input", RegexValidate);
     $("#displayRegex").on("input", RegexValidate);
     $("#edit_matchRegex").on("input", RegexValidate);
     $("#edit_displayRegex").on("input", RegexValidate);
     $("#edit_regexVar").on("input", RegexValidate);



     $("#version").on("input", NumberValidate);

     $("#nameId").on("input", NameIdValidate);
     $("#edit_nameId").on("input", NameIdValidate);
});



window.RegexValidate = function()
{
	var isValid = true;
	var m = $(this).val().match(/^([/~@;%#'])(.*?)\1([gimsuy]*)$/);

	var errorMsg = $(this).next(".errorMsg");

	try {
			isValid = m ? !!new RegExp(m[2], m[3]) :  false;
	    new RegExp($(this).val());
	}
	catch(e) {
	    isValid = false;
	}

	if(isValid || ($(this).val() == '') ) {
		errorMsg.text("");
		$(this).removeClass("badRegex");
		$('.submit_btn').prop('disabled', false);
	}
	else {
		errorMsg.text("Error: please enter a valid regex");
    $(this).addClass("badRegex");
		$('.submit_btn').prop('disabled', true);
	}
}


window.BuffNameValidate = function()
{
	const char = "'";
	var buffName = $(this).val();
	var errorMsg = $(this).next(".errorMsg");

	if(buffName.includes(char)) {
		errorMsg.text("Error: please enter a valid buff name");
    $(this).addClass("badRegex");
		$('.submit_btn').prop('disabled', true);
	}
	else {
		errorMsg.text("");
		$(this).removeClass("badRegex");
		$('.submit_btn').prop('disabled', false);
	}

}

window.NameIdValidate = function() {
	const char = "'";
	var nameId = $(this).val();
	var ruleType = $("#ruleType").val()
	var errorMsg = $(this).next(".errorMsg");

	if (ruleType == "buff") {
		if(nameId.includes(char)) {
			errorMsg.text("Error: please enter a valid name id");
	    $(this).addClass("badRegex");
			$('.submit_btn').prop('disabled', true);
		}
		else {
			errorMsg.text("");
			$(this).removeClass("badRegex");
			$('.submit_btn').prop('disabled', false);
		}
	}

}

window.NumberValidate = function()
{
	var input = $(this).val();
	var errorMsg = $(this).next(".errorMsg");
	var isNum = input.match(/^[0-9pts]+$/);

	if((isNum != null) || input == '') {
		errorMsg.text("");
		$(this).removeClass("badRegex");
		$('.submit_btn').prop('disabled', false);
	}
	else {
		errorMsg.text("Error: please enter a valid number");
    $(this).addClass("badRegex");
		$('.submit_btn').prop('disabled', true);
	}
}
