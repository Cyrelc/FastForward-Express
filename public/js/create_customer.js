$(document).ready(function() {
	$('#subLocation, #separateBillingAddr, #giveDiscount, #giveCommission, #giveDriverCommission, #balanceOwingInterest, #gstExempt, #useCustomField, #existing_account').change(function() {
		if(this.checked){
		    $('#' + $(this).attr('data-div')).fadeIn();
		}
		else {
		    $('#' + $(this).attr('data-div')).fadeOut();
		}
	});

	$('#subLocation, #separateBillingAddr, #giveDiscount, #giveCommission, #giveDriverCommission, #balanceOwingInterest, #gstExempt, #useCustomField, #existing_account').each(function (i, e) {
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
	var errors = {string: "\0"};
	var check = ['name', 'first_name1', 'last_name1', 'primary_phone1', 'street_delivery', 'zip_postal_delivery', 'city_delivery', 'state_province_delivery', 'country_delivery'];

	for (var i = 0; i < check.length; i++) {
		$('[name="'+check[i]+'"]').parent().removeClass('has-error');
	}

	for (var i = 0; i < check.length; i++) {
		notBlank(check[i], errors);
	}

	if ($('#secondary_contact').is(':checked')) {
		var check = ['first_name2', 'last_name2', 'primary_phone2'];
		for (var i = 0; i < check.length; i++) {
			notBlank(check[i], errors);
		}
	}

	if ($('#billing_address').is(':checked')) {
		var check = ['street_billing', 'zip_postal_billing', 'city_billing', 'state_province_billing', 'country_billing'];
		for (var i = 0; i < check.length; i++) {
			notBlank(check[i], errors);
		}
	}

//validate Parent Company ID
	if ($('#subLocation').is(':checked') && $('#parent_account_id').find(":selected").val() < 0) {
		errors.string += "Please select a valid Parent Account\n";
		$('#parent_account_id').parent().addClass('has-error');
	}

	if ($('#giveDiscount').is(':checked') && $('[name="discount"]').val().length == 0) {
		errors.string += "Discount field cannot be empty\n";
		$('[name="discount"]').parent().addClass('has-error');
	}

	if ($('#giveCommission').is(':checked') && ($('[name="commission_employee_id"]').val().length == 0 || $('[name="commission_percent"]').val().length == 0)) {
		errors.string += "Both commission employee and amount must not be empty\n";
		$('[name="commission_employee_id"]').parent().addClass('has-error');
		$('[name="commission_percent"]').parent().addClass('has-error');
	}

	if ($('[name="invoice_interval"]').find(':selected').val() < 0) {
		errors.string += "Invalid invoice interval\n";
		$('[name="invoice_interval"]').parent().addClass('has-error');
	}

	if ($('[name="existing_account"]').is(':checked') && $('[name="account_number"]').val().length == 0) {
		errors.string += "Account number cannot be empty\n";
		$('[name="account_number"]').parent().addClass('has-error');
	}

	if (errors.string.length == 0) {
		return true;
	}
	$('#errors').removeClass('hidden');
	$('#errors').text(errors.string);
	return false;
}
