$(document).ready(function() {
	$('#start_date, #end_date').datetimepicker({format: 'MMMM Do, YYYY', useCurrent: false});
});

function getAccountsToInvoice(){
	var start_date = $("#start_date").val();
	var end_date = $('#end_date').val();
	var temp_date = new Date($('#start_date').data('DateTimePicker').date());
	var legacy_date = temp_date.getFullYear() + '-' + (temp_date.getMonth() + 1) + '-' + temp_date.getDate();
	var invoice_intervals = $("#invoice_intervals").val();
	// var _token = $("input[name='_token").val();

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

					if(cur.bill_count == 0)
						row.append("<td><input type='checkbox' name='checkboxes[" + i + "]' disabled value='" + cur.account_id + "'/></td>");
					else if(cur.incomplete_bill_count == 0 && cur.legacy_bill_count == 0 && cur.skipped_bill_count == 0)
						row.append("<td><input type='checkbox' name='checkboxes[" + i + "]' checked value='" + cur.account_id + "' /></td>");
					else
						row.append('<td><input type="checkbox" name="checkboxes[' + i + ']" value="' + cur.account_id + '"/></td>');
					row.append('<td>' + cur.account_id + '</td>');
					row.append('<td>' + cur.account_number + '</td>');
					row.append("<td>" + cur.name + "</td>");
					row.append('<td>' + cur.invoice_interval + '</td>');
					row.append("<td>" + cur.bill_count + "</td>");
					if(cur.incomplete_bill_count > 0)
						row.append('<td><font color="red"><a href="/bills?filter[charge_account_id]=' + cur.account_id + '&filter[complete]=false&filter[skip_invoicing]=0">' + cur.incomplete_bill_count + '</a></font></td>');
					else
						row.append('<td></td>');
					if(cur.skipped_bill_count > 0)
						row.append('<td><font color="orange"><a href="/bills?filter[charge_account_id]=' + cur.account_id + '&filter[skip_invoicing]=true">' + cur.skipped_bill_count + '</a></font></td>');
					else
						row.append('<td></td>');
					if(cur.legacy_bill_count > 0)
						row.append('<td><font color="red"><a href="/bills?filter[charge_account_id]=' + cur.account_id + '&filter[skip_invoicing]=0&filter[invoiced]=false&filter[date_between]=,' + legacy_date + '">'  + cur.legacy_bill_count + '</a></font></td>');
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
				'onHidden': function(){location.replace('/invoices?filter[balance_owing]=0,')}
			})
		},
		'error': function(response){handleErrorResponse(response)}
	});
}
