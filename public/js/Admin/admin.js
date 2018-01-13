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