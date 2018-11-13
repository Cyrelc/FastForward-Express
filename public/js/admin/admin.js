function storeGST(){
    var data = $('#gst_form').serialize();
	$.ajax({
		'url': '/appsettings/storeGST',
		'type': 'POST',
		'data': data,
		'success': function() {
			toastr.success('GST was successfully updated!', 'Success');
		},
		'error': function(response){handleErrorReponse(response)}
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
		'error': function(response){handleErrorReponse(response)}
	})
}

function copyHashToClipboard() {
	$('#password-hash').select();
	document.execCommand('copy');
}
