/*
|--------------------------------------------------------------------------
| 	POPULATE STOPS
|--------------------------------------------------------------------------
|
*/
 
function add_stop() {
    var total_list_stops = document.getElementById('total_list_stops').value;
    total_list_stops++;

    // document.getElementById("show_stops_div").innerHTML += '<div class="col-lg-12"><div class="mb-3"><label class="form-label fw-semibold">Stop ' + total_list_stops + ': </label><select class="form-select" name="stop[]" id="stop' + total_list_stops + '"><option value="0"></option></select></div></div>';

    // This is to preserve the values of previously dynamicall created elements
    document.getElementById('show_stops_div').insertAdjacentHTML("beforebegin", '<div class="col-lg-12"><div class="mb-3"><label class="form-label fw-semibold">Stop ' + total_list_stops + ': </label><select class="form-select" name="stop[]" id="stop' + total_list_stops + '"><option value="0"></option></select></div></div>'); 

    document.getElementById('total_list_stops').value = total_list_stops;
    setTimeout(function() {
        ajax_populate_stops(total_list_stops);
    }, 500);
}


/*
|--------------------------------------------------------------------------
| 	COUNT CHARS
|--------------------------------------------------------------------------
|
*/
// 

function char_count(input_name) {
    // alert(input_name);

    var total_chars = 0;
    var input_value	 	= document.getElementById(input_name).value;

    total_chars = input_value.length;

    document.getElementById('span_'+input_name+'').innerHTML = total_chars;
 }


/*
|--------------------------------------------------------------------------
| 	MAKE FORM READONLY
|--------------------------------------------------------------------------
|
*/

/* code from qodo.co.uk */ 
function toggleFormElements() {
	var bDisabled = true;
	 
    var inputs = document.getElementsByTagName("input"); 
    for (var i = 0; i < inputs.length; i++) { 
        inputs[i].disabled = bDisabled;
    } 
    var selects = document.getElementsByTagName("select");
    for (var i = 0; i < selects.length; i++) {
        selects[i].disabled = bDisabled;
    }
    var textareas = document.getElementsByTagName("textarea"); 
    for (var i = 0; i < textareas.length; i++) { 
        textareas[i].disabled = bDisabled;
    }
    var buttons = document.getElementsByTagName("button");
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].disabled = bDisabled;
    }
}


/*
|--------------------------------------------------------------------------
| 	PASSWORD STRENGHT
|--------------------------------------------------------------------------
|
*/
 function checkPasswordStrength(inputfield) {
	var number = /([0-9])/;
	var alphabets = /([a-zA-Z])/;
	var special_characters = /([~,!,@,#,$,%,^,&,*,-,_,+,=,?,>,<])/;
	var password = $('#'+inputfield).val().trim();
	if (password.length < 6) {
		$('#'+inputfield+'-strength-status').removeClass();
		$('#'+inputfield+'-strength-status').addClass('weak-password');
		$('#'+inputfield+'-strength-status').html("Weak (should be atleast 6 characters.)");
	} else {
		if (password.match(number) && password.match(alphabets) && password.match(special_characters)) {
			$('#'+inputfield+'-strength-status').removeClass();
			$('#'+inputfield+'-strength-status').addClass('strong-password');
			$('#'+inputfield+'-strength-status').html("Strong");
		}
		else {
			$('#'+inputfield+'-strength-status').removeClass();
			$('#'+inputfield+'-strength-status').addClass('medium-password');
			$('#'+inputfield+'-strength-status').html("Medium (should include alphabets, numbers and special characters.)");
		}
	}
}

// https://phppot.com/jquery/jquery-password-strength-checker/
// function checkPasswordStrength() {
// 	var number = /([0-9])/;
// 	var alphabets = /([a-zA-Z])/;
// 	var special_characters = /([~,!,@,#,$,%,^,&,*,-,_,+,=,?,>,<])/;
// 	var password = $('#password').val().trim();
// 	if (password.length < 6) {
// 		$('#password-strength-status').removeClass();
// 		$('#password-strength-status').addClass('weak-password');
// 		$('#password-strength-status').html("Weak (should be atleast 6 characters.)");
// 	} else {
// 		if (password.match(number) && password.match(alphabets) && password.match(special_characters)) {
// 			$('#password-strength-status').removeClass();
// 			$('#password-strength-status').addClass('strong-password');
// 			$('#password-strength-status').html("Strong");
// 		}
// 		else {
// 			$('#password-strength-status').removeClass();
// 			$('#password-strength-status').addClass('medium-password');
// 			$('#password-strength-status').html("Medium (should include alphabets, numbers and special characters.)");
// 		}
// 	}
// }