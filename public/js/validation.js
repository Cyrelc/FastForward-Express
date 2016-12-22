function notBlank(name, errors) {
	if ($('[name="'+name+'"]').val().length > 0) {
		return true;
	} 
	else {
		errors.string += $('[name="'+name+'"]').attr('placeholder') + " cannot be blank\n";
		$('[name="'+name+'"]').parent().addClass('has-error');
	}
}

function validateName(name, modifiers){
	//for each modifier in modifiers
	//switch statement, base case to succeed in case incorrect modifier is passed.
}
