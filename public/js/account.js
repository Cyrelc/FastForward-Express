$(document).ready(function() {
	var checkboxes = '#send-invoices, #send-bills, #sub-location, #give-discount, #give-commission-1, #give-commission-2, #has-fuel-surcharge, #charge-interest, #gst-exempt, #use-custom-field, #existing-account, #can-be-parent, #existing-account';
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

	$("#billing-address").change(function(){
		if ($("#billing-address").prop('checked'))
			$("input[name='hasBillingAddress']").val('true');
		else
			$("input[name='hasBillingAddress']").val('');
	});

	dateInput('start-date');
	comboInput('parent-account-id', 'Select a Parent Account');
	comboInput('driver,select', 'Select a Driver');

	$("input[data-checkbox-id]").each(function(i,e){
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

	$("#account_number").focusout(function(){
		var curr = '{{$model->account->account_number}}';
		var _token = $("input[name='_token").val();
		var newNum = $("#account_number").val();
		if (!newNum) return;
		if (curr && curr == newNum ) return;

		console.log('{{URL::to('/')}}/accounts/is_unique');

		$("#account_number_result").children('i').remove();
		$("#account_number_result").append('<i class="fa fa-spinner fa-spin text-info"></i>');
		$("#account_number_result").attr('title', 'Looking up Account Number!');

		$.ajax({
			'url': '/accounts/is_unique',
			'type': 'POST',
			'data': {'number' : newNum, '_token' : _token},
			'success': function(e) {
				if (e.success) {
					if (e.accounts.length == 0) {
						$("#account_number_result").append('<i class="fa fa-check text-success"></i>');
						$("#account_number_result").attr('title', 'Account Number is unique!');
					} else {
						$("#account_number_result").append('<i class="fa fa-exclamation-triangle text-warning"></i>');
						$("#account_number_result").attr('title', 'Account Number is not unique! Number is taken by ' + e.accounts[0].name);
					}
				} else {
					$("#account_number_result").append('<i class="fa fa-exclamation-circle text-danger"></i>');
					$("#account_number_result").attr('title', 'Account Number check failed! This account number might not be unique!');
				}
			},
			'error': function() {
				$("#account_number_result").append('<i class="fa fa-exclamation-circle text-danger"></i>');
				$("#account_number_result").attr('title', 'Account Number check failed! This account number might not be unique!');
			},
			'complete': function() {
				$("#account_number_result").children('i.text-info').remove();
			}
		});
	});

});

function storeAccount(){
	var data = $('#account_form').serialize();

	$.ajax({
		'url': '/accounts/store',
		'type': 'POST',
		'data': data,
		'success': function(e) {
			location.reload();
			// setTimeout(toastr.success("Success!"), 3000);
			// $(document).ready(function(){
			// 	toastr.success("Success!");
			// })
		},
		'error': function(response){
			var errors = $('#errors');
			var errorText = '';
			for(var key in response.responseJSON) {
				errorText += response.responseJSON[key][0] + '\n';
			}
			toastr.error(errorText, 'Errors', {'timeOut' : '0', 'extendedTImeout': '0'});
		}
	})
}

$('#advFilter input[type="checkbox"]').each(function(i,j) {
	if(j.checked){
		$('tr#' + j.id).fadeIn();
	}
	else{
		$('tr#' + j.id).fadeOut();
	}
});
