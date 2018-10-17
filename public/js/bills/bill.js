$(document).ready(function() {
	if($('#read_only').val() == 'true') {
		$(':input').attr('readonly', true);
		$('select').prop('disabled', true);
		$('#add_package, #remove_package').attr('disabled', 'disabled');
	}
});

function storeBill(){
	var data = $('#bill-form, #bill-persistence-form, #bill_options_form').serialize();

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
