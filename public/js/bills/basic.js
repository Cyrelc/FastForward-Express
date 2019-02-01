$(document).ready(function() {
    $('#time_pickup_scheduled, #time_delivery_scheduled').datetimepicker({format: 'MMMM Do, YYYY h:mm A', stepping: 5});

    $('#time_pickup_scheduled').change(function() {
        if($('#time_delivery_scheduled').val() < $('#time_pickup_scheduled').val())
            $('#time_delivery_scheduled').val($('#time_pickup_scheduled').val()).trigger('change');
    });

    $('#pickup_address_type, #delivery_address_type').change(function(){
        $('option', this).each(function(){$('#' + $(this).val()).removeClass('in active')});
        $('#' + $(this).val()).addClass('in active');
    }).trigger('change');

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

function addPackage(count = 1, weight = '', length = '', width = '', height = '', package_id = null) {
	var table = $('#package_table');
	var id = $('#next_piece_id').val();
	id ++;
	$('#next_piece_id').val(id);
	table.children('tbody').append(
		"<tr>" +
			"<input type='hidden' name='package" + id + "_id' value='" + package_id + "' />" +
            "<td><button type='button' id='remove_package'><i class='fa fa-minus'></i></button></td>" +
            "<td><input type='number' name='package" + id + "_count' step=1 min='1' value='" + count + "'/></td>" +
			"<td><input type='number' name='package" + id + "_weight' step='0.1' min='0' value='" + weight + "'/> kg </td>" +
			"<td><input type='number' name='package" + id + "_length' step='0.01' min='0' value='" + length + "' /> cm </td>" +
			"<td><input type='number' name='package" + id + "_width' step='0.01' min='0' value='" + width + "' /> cm </td>" +
			"<td><input type='number' name='package" + id + "_height' step='0.01' min='0' value='" + height + "' /> cm </td>" +
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
		'error': function(response){handleErrorResponse(response)}
	})
}
