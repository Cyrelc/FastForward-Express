$(document).ready(function() {
	dateInput('start_date');

	$("#account_number").focusout(function(){
		var curr = '{{$model->account->account_number}}';
		var _token = $("input[name='_token").val();
		var newNum = $("#account_number").val();
		if (!newNum) return;
		if (curr && curr == newNum ) return;

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

	$('#name').blur(function() {
		if($('#account_id').val() == '' || $('#account_id').val() == null) {
			$('#delivery_name').val($('#name').val());
		}
	});

	cleave();
});

function storeAccount(){
    var radio = $('#account_basic input:radio[name="email_is_primary[]"]');
    var primaryIndex = radio.index(radio.filter(':checked'));
    radio.filter(':checked').val(primaryIndex);
    radio = $('#account_basic input:radio[name="phone_is_primary[]"]');
    primaryIndex = radio.index(radio.filter(':checked'));
    radio.filter(':checked').val(primaryIndex);
	var data = $('#account_basic, #account_advanced').serialize();

	$.ajax({
		'url': '/accounts/store',
		'type': 'POST',
		'data': data,
		'success': function() {
			var isEdit = $('#account_id').val() == '' ? false : true;
			var accountName = $('#account_name').val();
			toastr.clear();
			if (isEdit) {
				toastr.success(accountName + ' was successfully updated!', 'Success');
			} else {
				toastr.success(accountName + ' was succesfully created', 'Success', {
					'progressBar' : true,
					'positionClass' : 'toast-top-full-width',
					'showDuration': 500,
					'onHidden': function(){location.reload()}
				})
			}
		},
		'error': function(response){handleErrorResponse(response)}
	})
};
