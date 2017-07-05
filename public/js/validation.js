function enableBody(me, bodyName) {
	if ($('#'+ me +'').is(':checked')) {
		$('.' + bodyName).prop('disabled', false);
	}
	else {
		$('.' + bodyName).val('');
		$('.' + bodyName).prop('disabled', 'disabled');
	}
};

function switchDiv(element, id) {
	if ($(element).is(':checked'))
		$("#" + id + " input").removeAttr('disabled');
	else
        $("#" + id + " input").attr('disabled', 'disabled');
}
