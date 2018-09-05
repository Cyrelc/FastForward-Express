$(document).ready(function() {
    dateInput('pickup_date_scheduled');
    dateInput('delivery_date_scheduled');
    $('#pickup_time_expected').datetimepicker({format: 'LT'});
    $('#delivery_time_expected').datetimepicker({format: 'LT'});

    $('#pickup_date_scheduled').change(function() {
        console.log('fired');
        if($('#delivery_date_scheduled').val() < $('#pickup_date_scheduled').val())
            $('#delivery_date_scheduled').val($('#pickup_date_scheduled').val()).trigger('change');
    });

//display custom field if present for the account.
    $('#pickup_account_id, #delivery_account_id, #charge_account_id').change(function(){
        if ($('option:selected', this).attr('data-reference-field-name')) {
            $('#' + $(this).attr('data-reference')).removeClass('hidden');
            $('#' + $(this).attr('data-reference') + ' input').removeAttr('disabled');
            document.getElementById($(this).attr('data-reference') + '_name').innerHTML = ($("option:selected", this).attr('data-reference-field-name'));
        } else {
            $('#' + $(this).attr('data-reference')).addClass('hidden');
            $('#' + $(this).attr('data-reference') + ' input').attr('disabled', 'disabled');
        }
        if($(this).val()) {
            if($(this).attr('name') === 'pickup_account_id')
                setAddressFromAccount($(this).val(), 'pickup');
            else if ($(this).attr('name') === 'delivery_account_id')
                setAddressFromAccount($(this).val(), 'delivery');
        }
    }).trigger('change');

//extend table to == piece_count
    $('#add_package').click(function(){
        addPackage();
    });

    $(document).on('click', '#remove_package', function() {
        var row = $(this).closest("tr");
        row.remove();
    });
});

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

function setAddressFromAccount(accountId, prefix) {
	var data = {'account_id' : accountId};

	$.ajax({
		'url': '/accounts/getShippingAddress',
		'type': 'GET',
		'data': data,
		'success': function(response) {
            $('#' + prefix + '-name').val(response.name);
            $('#' + prefix + '-street').val(response.street); //street2 city province zip country
            $('#' + prefix + '-street2').val(response.street2);
            $('#' + prefix + '-city').val(response.city);
            $('#' + prefix + '-province').val(response.state_province);
            $('#' + prefix + '-zip').val(response.zip_postal);
            $('#' + prefix + '-country').val(response.country);
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
