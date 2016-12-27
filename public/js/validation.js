function notBlank(name, errors) {
	if ($('[name="'+name+'"]').val().length > 0) {
		return true;
	} 
	else {
		errors.string += $('[name="'+name+'"]').attr('placeholder') + " cannot be blank\n";
		$('[name="'+name+'"]').parent().addClass('has-error');
		return false;
	}
}

function enableBody(me, bodyName) {
	if ($('#'+ me +'').is(':checked')) {
		$('.' + bodyName).prop('disabled', false);
	}
	else {
		$('.' + bodyName).val('');
		$('.' + bodyName).prop('disabled', 'disabled');
	}
};

function validatePhone(name, errors) {
	$contents = $('[name="'+name+'"]').val();
	if ($contents.length != 10) {
		errors.string += $('[name="'+name+'"]').attr('placeholder') + " must contain 10 digits\n";
		$('[name="'+name+'"]').parent().addClass('has-error');
		return false;
	}
	if (!is_numeric($contents)) {
		errors.string += $('[name="'+name+'"]').attr('placeholder') + " must contain only numbers\n";
		return false;
	}
	return true;
}

function validateName(name, modifiers) {
	//for each modifier in modifiers
	//switch statement, base case to succeed in case incorrect modifier is passed.
}
