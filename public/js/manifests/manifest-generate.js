$(document).ready(function() {
    dateInput('start_date');
    dateInput('end_date');
});

function getDriversToManifest() {
    var start_date = $('#start_date').val();
    var end_date = $('#end_date').val();
    var _token = $('#_token').val();

    $.ajax({
        'url': '/manifests/getDriversToManifest',
        'type': 'GET',
        'data': {'_token': _token, 'start_date': start_date, 'end_date': end_date},
        'success': function(response) {
            toastr.clear();
            $('#driver_list').html(response);
        },
        'error': function(response) {
            var errorText = '';
            for(var key in response.responseJSON){
                errorText += response.responseJSON[key][0] + '</br>';
            }
            toastr.error(errorText, 'Errors', {'timeOut': 0, 'extendedTImeout': 0});
        }
    });
}

function generateManifests(){
	var data = $('#manifest-form').serialize();

	$.ajax({
		'url': '/manifests/store',
		'type': 'POST',
		'data': data,
		'success': function(response) {
			toastr.success(response, 'Success', {
				'progressBar': true,
				'positionClass': 'toast-top-full-width',
				'showDuration': 500,
				// 'onHidden': function(){location.replace('/manifests')}
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
