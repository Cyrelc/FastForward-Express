$(document).ready(function() {

    $checkboxes = '#use-interliner, #skip-invoicing';
	$($checkboxes).change(function() {
		if(this.checked){
            $("input[name='" + $(this).attr('data-hidden-name') + "']").val('true');
            $('#' + $(this).attr('data-div')).fadeIn();
		}
		else {
            $("input[name='" + $(this).attr('data-hidden-name') + "']").val('false');
		    $('#' + $(this).attr('data-div')).fadeOut();
		}
	});

	$($checkboxes).each(function (i, e) {
	    $("#" + $(this).attr('data-div')).css('display', 'none');
	});

    $("input[data-checkbox-id]").each(function(i,e) {
        var value = $(e).val() == 'true';
        if (value) {
            var me = $(e).attr('data-me');
            var check_box_id = "#" +$(e).attr('data-checkbox-id');
            if (me) {
                var body = $(e).attr('data-body');
                $(check_box_id).prop('checked', true);
                enableBody(me, body);
            } else
                $(check_box_id).click();
        }
    });

	dateInput('date');

//select who pays

	updateChargeSelection();

	$('#charge_selection').change(updateChargeSelection);

//display custom field if present for the account.

    $('#pickup_account_id, #delivery_account_id, #charge_account_id').change(function(){
    	if ($('option:selected', this).attr('data-reference-field-name')) {
    		$('#' + $(this).attr('data-reference')).removeClass('hidden');
	    	document.getElementById($(this).attr('data-reference') + '_name').innerHTML = ($("option:selected", this).attr('data-reference-field-name'));
	    } else {
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

	$("#pickup_driver_commission").val($("option:selected", "#pickup_driver_id").attr('data-driver-commission')*100);
	$("#delivery_driver_commission").val($("option:selected", "#delivery_driver_id").attr('data-driver-commission')*100);

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

//extend table to = piece_count
	$('#add_package').click(function(){
		addPackage();
	});

	$(document).on('click', '#remove_package', function(){
		var row = $(this).closest("tr");
		row.remove();
	});

});

function updateChargeSelection() {
	switch ($('#charge_selection').val()) {
		case 'pickup_account' :
			$('#pickup_use_account').click();
			$('input[name=pickup_use]:radio').attr('disabled', 'disabled');
			$('input[name=delivery_use]:radio').removeAttr('disabled', 'disabled');
			$('#charge_account').addClass('hidden');
			$('#select_charge').addClass('col-lg-12');
			$('#select_charge').removeClass('col-lg-8');
			$('#payment_type').parent('div').parent('div').addClass('hidden');
			break;
		case 'delivery_account' :
			$('#delivery_use_account').click();
			$('input[name=delivery_use]:radio').attr('disabled', 'disabled');
			$('input[name=pickup_use]:radio').removeAttr('disabled', 'disabled');
			$('#charge_account').addClass('hidden');
			$('#select_charge').addClass('col-lg-12');
			$('#select_charge').removeClass('col-lg-8');
			$('#payment_type').parent('div').parent('div').addClass('hidden');
			break;
		case 'other_account':
			$('input[name=delivery_use]:radio').removeAttr('disabled', 'disabled');
			$('input[name=pickup_use]:radio').removeAttr('disabled', 'disabled');
			$('#charge_account').removeClass('hidden');
			$('#select_charge').addClass('col-lg-12');
			$('#select_charge').removeClass('col-lg-8');
			$('#payment_type').parent('div').parent('div').addClass('hidden');
			break;
		case 'pre-paid':
			console.log('pre-paid');
			$('#select_charge').removeClass('col-lg-12');
			$('#select_charge').addClass('col-lg-8');
			$('#payment_type').parent('div').parent('div').removeClass('hidden');
			$('input[name=delivery_use]:radio').removeAttr('disabled', 'disabled');
			$('input[name=pickup_use]:radio').removeAttr('disabled', 'disabled');
			$('#charge_account').addClass('hidden');
			break;
		default:
			console.log('none');
			break;
	}
}

function addPackage(weight = 0, length = 0, width = 0, height = 0, package_id = null) {
	var table = $('#package_table');
	var id = $('#next_piece_id').val();
	id ++;
	$('#next_piece_id').val(id);
	table.children('tbody').append(
		"<tr>" +
			"<input type='hidden' name='package" + id + "_id' value='" + package_id + "' />" +
			"<td><button type='button' id='remove_package'><i class='fa fa-minus'></i></button></td>" +
			"<td><input type='number' name='package" + id + "_weight' step='0.1' value='" + weight + "'/> kg </td>" +
			"<td><input type='number' name='package" + id + "_length' step='0.01' value='" + length + "' /> cm </td>" +
			"<td><input type='number' name='package" + id + "_width' step='0.01' value='" + width + "' /> cm </td>" +
			"<td><input type='number' name='package" + id + "_height' step='0.01' value='" + height + "' /> cm </td>" +
		"</tr>"
	);
}

function storeBill(){
	var data = $('#bill-form, #bill-persistence-form').serialize();

	$.ajax({
		'url': '/bills/store',
		'type': 'POST',
		'data': data,
		'success': function() {
			var isEdit = $('#bill_id').val() == '' ? false : true;
			toastr.clear();
			if(isEdit){
				var billNumber = $('#bill_number').val();
				toastr.success('Bill ' + billNumber + ' successfully updated');
			} else {
				toastr.success('Bill created successfully', 'Success', {
					'progressBar': true, 
					'positionClass': 'toast-top-full-width',
					'showDuration': 500,
					'onHidden': function(){location.reload()}
				})
			}
		},
		'error': function(response){
			var errorText = '';
			for(var key in response.responseJSON){
				errorText += response.responseJSON[key][0] + '</br>';
			}
			toastr.clear();
			toastr.error(errorText, 'Errors', {'timeOut' : 0, 'extendedTImeout' : 0});
		}
	})
}
