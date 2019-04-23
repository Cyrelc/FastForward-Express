$(document).ready(function() {
    $('#start_date, #end_date').datetimepicker({format: 'MMMM Do, YYYY', useCurrent: false});
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
		'error': function(response){handleErrorResponse(response)}
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
		'error': function(response){handleErrorResponse(response)}
	});
}
