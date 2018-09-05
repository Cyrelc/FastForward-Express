$(document).ready(function() {
    dateInput('start_date');
    dateInput('end_date');
});

function getAccountsToInvoice(){
	var start_date = $("#start_date").val();
	var end_date = $('#end_date').val();
	var invoice_intervals = $("#invoice_intervals").val();
	var _token = $("input[name='_token").val();

    $.ajax({
    	type: "POST",
    	url: '/invoices/getAccountsToInvoice',
    	data: {'start_date' : start_date, 'end_date' : end_date, 'invoice_intervals' : invoice_intervals},
    	'success': function(results){
    		if (results.length > 0) {
    			$('#preview_list_placeholder').addClass('hidden');

    			$("#account_preview_table tbody").empty();
    			$('#account_count').val(results.length);

	    		for (i = 0; i < results.length; i++) {
	    			var cur = results[i];

	    			var row = $("<tr>");

					if(cur.incomplete_bill_count == 0)
						row.append("<td><input type='checkbox' name='checkboxes[" + i + "]' checked value='" + cur.account_id + "' /></td>");
					else
						row.append('<td><input type="checkbox" name="checkboxes[' + i + ']" value="' + cur.account_id + '"/></td>');
					row.append('<td>' + cur.account_id + '</td>');
					row.append('<td>' + cur.account_number + '</td>');
					row.append("<td>" + cur.name + "</td>");
					row.append('<td>' + cur.invoice_interval + '</td>');
					row.append("<td>" + cur.bill_count + "</td>");
					if(cur.incomplete_bill_count > 0)
						row.append('<td><font color="red">' + cur.incomplete_bill_count + '</font></td>');
					else
						row.append('<td></td>');
					if(cur.legacy_bill_count > 0)
						row.append('<td><font color="red">' + cur.legacy_bill_count + '</font></td>');
					else
						row.append('<td></td>');

	    			$("#account_preview_table tbody").append(row);
	    		}
    		}
    		else
    			$('#preview_list_placeholder').removeClass('hidden');
    	}
	});
}

function generateInvoices(){
	var data = $('#invoice-form').serialize();

	$.ajax({
		'url': '/invoices/store',
		'type': 'POST',
		'data': data,
		'success': function() {
			toastr.success('Invoices successfully created', 'Success', {
				'progressBar': true,
				'positionClass': 'toast-top-full-width',
				'showDuration': 500,
				'onHidden': function(){location.replace('/invoices')}
			})
		},
		'error': function(response){
			var errorText = '';
			for(var key in response.responseJSON){
				errorText += response.responseJSON[key][0] + '</br>';
			}
			toastr.error(errorText, 'Errors', {'timeOut': 0, 'extendedTImeout': 0});
		}
	});
}
