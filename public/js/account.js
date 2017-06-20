$(document).ready(function() {
	var checkboxes = '#send-bills, #sub-location, #separate-billing-addr, #give-discount, #give-commission-1, #give-commission-2, #has-invoice-comment, #has-fuel-surcharge, #charge-interest, #gst-exempt, #use-custom-field, #existing-account, #can-be-parent, #existing-account';
	$(checkboxes).change(function() {
		if(this.checked){
            $("input[name='" + $(this).attr('data-hidden-name') + "']").val('true');
            $('#' + $(this).attr('data-div')).fadeIn();
		}
		else {
            $("input[name='" + $(this).attr('data-hidden-name') + "']").val('false');
		    $('#' + $(this).attr('data-div')).fadeOut();
		}
	});

	$(checkboxes).each(function (i, e) {
	    $("#" + $(this).attr('data-div')).css('display', 'none');
	});
});

$('#advFilter input[type="checkbox"]').each(function(i,j) {
	if(j.checked){
		$('tr#' + j.id).fadeIn();
	}
	else{
		$('tr#' + j.id).fadeOut();
	}
});

function validate() {
	return true;
	//TODO: discuss client-side validation
	var errors = {string: "\0"};
	var check = ['name', 'contact-1-first-name', 'contact-1-last-name', 'contact-1-phone1', 'delivery-street', 'delivery-zip-postal', 'delivery-city', 'delivery-state-province', 'delivery-country'];

	$(':input').parent().removeClass('has-error');

	for (var i = 0; i < check.length; i++) {
		notBlank(check[i], errors);
	}

	if ($('#secondary-contact').is(':checked')) {
		var check = ['secondary-first-name', 'secondary-last-name', 'secondary-phone1'];
		for (var i = 0; i < check.length; i++) {
			notBlank(check[i], errors);
		}
	}

	if ($('#billing-address').is(':checked')) {
		var check = ['billing-street', 'billing-zip-postal', 'billing-city', 'billing-state-province', 'billing-country'];
		for (var i = 0; i < check.length; i++) {
			notBlank(check[i], errors);
		}
	}

//validate Parent Company ID
	if ($('#sub-location').is(':checked') && $('#parent-account-id').find(":selected").val() < 0) {
		errors.string += "Please select a valid Parent Account\n";
		$('#parent-account-id').parent().addClass('has-error');
	}

	if ($('#give-discount').is(':checked') && $('[name="discount"]').val().length == 0) {
		errors.string += "Discount field cannot be empty\n";
		$('[name="discount"]').parent().addClass('has-error');
	}

	if ($('#give-commission-1').is(':checked') && ($('[name="driver-commission-employee-id"]').val().length == 0 || $('[name="driver-commission-percent"]').val().length == 0)) {
		errors.string += "Both commission employee and amount must not be empty\n";
		$('[name="driver-commission-employee-id"]').parent().addClass('has-error');
		$('[name="driver-commission-percent"]').parent().addClass('has-error');
	}

	if ($('#give-commission-2').is(':checked') && ($('[name="sales-commission-employee-id"]').val().length == 0 || $('[name="sales-commission-percent"]').val().length == 0)) {
		errors.string += "Both commission employee and amount must not be empty\n";
		$('[name="sales-commission-employee-id"]').parent().addClass('has-error');
		$('[name="sales-commission-percent"]').parent().addClass('has-error');
	}

	if ($('[name="invoice-interval"]').find(':selected').val() < 0) {
		errors.string += "Invalid invoice interval\n";
		$('[name="invoice-interval"]').parent().addClass('has-error');
	}

	if ($('[name="existing-account"]').is(':checked') && $('[name="account-number"]').val().length == 0) {
		errors.string += "Account number cannot be empty\n";
		$('[name="account-number"]').parent().addClass('has-error');
	}

	if (errors.string == "\0") {
		return true;
	}
	$('#errors').removeClass('hidden');
	$('#errors').text(errors.string);
	return false;
}
