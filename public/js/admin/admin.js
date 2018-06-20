function storeGST(){
    var data = $('#gst_form').serialize();
	$.ajax({
		'url': '/appsettings/storeGST',
		'type': 'POST',
		'data': data,
		'success': function() {
			toastr.success('GST was successfully updated!', 'Success');
		},
		'error': function(response){
			var errorText = '';
			for(var key in response.responseJSON) {
				errorText += response.responseJSON[key][0] + '</br>';
			}
			toastr.error(errorText, 'Errors', {'timeOut' : '0', 'extendedTImeout': '0'});
		}
	})
}

function generateHash() {
	var data = $('#password-form').serialize();
	$.ajax({
		'url': '/appsettings/hashPassword',
		'type': 'POST',
		'data': data,
		'success': function(response) {
			$('#password-hash').val(response);
		},
		'error': function(response) {
			var errorText = '';
			for(var key in response.responseJSON) {
				errorText += response.responseJSON[key][0] + '</br>';
			}
			toastr.error(errorText, 'Errors', {'timeOut' : '0', 'extendedTImeout': '0'});
		}
	})
}

function copyHashToClipboard() {
	$('#password-hash').select();
	document.execCommand('copy');
}
