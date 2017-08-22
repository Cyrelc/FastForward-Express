$(document).ready(function() {

    dateInput('date');
    comboInput('account_id', 'Select an Account');
    comboInput('pickup_driver_id', 'Select a Pickup Driver');
    comboInput('delivery_driver_id', 'Select a Delivery Driver');
    comboInput('interliner_id', 'Select an Interliner (optional)');

//select who pays

	$('input[name=charge_selection]:radio').change(function(){
		if ($('#charge_pickup_account').prop('checked')) {
			$('#pickup_use_account').click();
			$('#charge_selection_submission').val('pickup_account');
			$('input[name=pickup_use]:radio').attr('disabled', 'disabled');
			$('input[name=delivery_use]:radio').removeAttr('disabled', 'disabled');
			$('#charge_account').addClass('hidden');
			$('#select_charge').addClass('col-lg-12');
			$('#select_charge').removeClass('col-lg-8');
			$('#payment_type').parent('div').parent('div').addClass('hidden');
		} else if ($('#charge_delivery_account').prop('checked')) {
			$('#delivery_use_account').click();
			$('#charge_selection_submission').val('delivery_account');
			$('input[name=delivery_use]:radio').attr('disabled', 'disabled');
			$('input[name=pickup_use]:radio').removeAttr('disabled', 'disabled');
			$('#charge_account').addClass('hidden');
			$('#select_charge').addClass('col-lg-12');
			$('#select_charge').removeClass('col-lg-8');
			$('#payment_type').parent('div').parent('div').addClass('hidden');
		} else if ($('#charge_other_account').prop('checked')) {
			$('#charge_selection_submission').val('other_account');
			$('input[name=delivery_use]:radio').removeAttr('disabled', 'disabled');
			$('input[name=pickup_use]:radio').removeAttr('disabled', 'disabled');
			$('#charge_account').removeClass('hidden');
			$('#select_charge').addClass('col-lg-12');
			$('#select_charge').removeClass('col-lg-8');
			$('#payment_type').parent('div').parent('div').addClass('hidden');
		} else if ($('#pre_paid').prop('checked')) {
			$('#charge_selection_submission').val('pre-paid');
			$('#select_charge').removeClass('col-lg-12');
			$('#select_charge').addClass('col-lg-8');
			$('#payment_type').parent('div').parent('div').removeClass('hidden');
			$('input[name=delivery_use]:radio').removeAttr('disabled', 'disabled');
			$('input[name=pickup_use]:radio').removeAttr('disabled', 'disabled');
			$('#charge_account').addClass('hidden');
		}
	});

//display custom field if present for the account.

    $('#pickup_account_id, #delivery_account_id, #charge_account_id').change(function(){
    	if ($(this).attr('data-reference-field-name')) {
    		$(this).parent('div').parent('div').removeClass('col-lg-12');
    		$(this).parent('div').parent('div').addClass('col-lg-6');
    		$('#' + $(this).attr('data-reference')).removeClass('hidden');
	    	document.getElementById($(this).attr('data-reference') + '_name').innerHTML = ($("option:selected", this).attr('data-reference-field-name'));
	    } else {
	    	$(this).parent('div').parent('div').removeClass('col-lg-6');
	    	$(this).parent('div').parent('div').addClass('col-lg-12');
    		$('#' + $(this).attr('data-reference')).addClass('hidden');
	    }
    });

//driver commission auto-populate

	$("#pickup_driver_id").change(function(){
		$("#pickup_driver_commission").val($("option:selected", this).attr('data-driver-commission')*100);
		//TODO - auto-populate delivery driver information if currently blank.
		if (!$("#delivery_driver_id").val()) {
			$("#delivery_driver_id").find('option[value="' + $('#pickup_driver_id').val() + '"]').attr('selected','selected');
		}
	});

	$("#delivery_driver_id").change(function(){
		$("#delivery_driver_commission").val($("option:selected", this).attr('data-driver-commission')*100);
	});

//pickup account/address selection buttons

	$('input[name=pickup_use]:radio').change(function(){
		if ($("#pickup_use_account").prop('checked')){
			$("#pickup_account").removeClass('hidden');
			$("#pickup_address").addClass('hidden');
			$("#pickup_use_submission").val('account');
		} else {
			$("#pickup_address").removeClass('hidden');
			$('#pickup_account').addClass('hidden');
			$('#pickup_use_submission').val('address');
		}
	});

	$('input[name=delivery_use]:radio').change(function(){
		if ($("#delivery_use_account").prop('checked')){
			$("#delivery_account").removeClass('hidden');
			$("#delivery_address").addClass('hidden');
			$('#delivery_use_submission').val('account');
		} else {
			$("#delivery_address").removeClass('hidden');
			$('#delivery_account').addClass('hidden');
			$('#delivery_use_submission').val('address');
		}
	});


});
