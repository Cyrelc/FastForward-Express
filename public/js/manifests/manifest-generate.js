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
        'error': function() {

        }
    });
}
