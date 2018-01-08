function addPhone(prefix, phone_id = "") {
    var _token = $("input[name='_token").val();
    var table = $('#' + prefix + '-table');
	var id = parseInt($('#' + prefix + '-next-id').val());
	var phone_prefix = prefix + '-' + id;
    $('#' + prefix + '-next-id').val(++id);

    var data = {
        '_token': _token,
		'phone_prefix' : phone_prefix,
		'phone_id': phone_id
	}

    $.ajax({
        type: 'POST',
        url: '/partials/phone',
    	data: data,
    	'success': function(results){
            table.children('tbody').append("<tr><td>" + results + "</td></tr>");
        }
    });
}

function deletePhone(this_button, prefix) {
    var row = $(this_button).closest('tr');
    $('#' + prefix + '-action').val('delete');
    row.hide();
}
