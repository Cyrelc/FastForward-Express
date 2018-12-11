$(document).ready(function() {
	if($('#read_only').val() == 'true') {
		$(':input').attr('readonly', true);
		$('select').prop('disabled', true);
		$('#add_package, #remove_package').attr('disabled', 'disabled');
	}

    stickyTabs();
});

function storeBill(){
	var data = $('#bill-form, #bill_options_form').serialize();

	$.ajax({
		'url': '/bills/store',
		'type': 'POST',
		'data': data,
		'success': function(response) {
			var isEdit = $('#bill_id').val() == '' ? false : true;
			toastr.clear();
			if(isEdit){
				var billNumber = $('#bill_number').val();
				toastr.success('Bill ' + billNumber + ' successfully updated');
			} else {
				toastr.success('Bill ' + response.id + ' created successfully', 'Success', {
					'progressBar': true,
					'positionClass': 'toast-top-full-width',
					'showDuration': 500,
					'onHidden': function(){location.reload()}
				})
			}
		},
		'error': function(response){handleErrorResponse(response)}
	})
}
