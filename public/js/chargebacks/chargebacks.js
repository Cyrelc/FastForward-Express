$(document).ready(function(){
    dateInput('start_date');

    $('#continuous').change(function(){
        if(this.checked)
            $('#charge_count').attr('disabled', 'disabled');
        else 
            $('#charge_count').removeAttr('disabled', 'disabled');
    });
});

function submitChargeback() {
    var data = $('#chargeback_create_form').serialize();

    $.ajax({
        'url': '/chargebacks/store',
        'type': 'POST',
        'data': data,
        'success': function() {
			toastr.clear();
            toastr.success('Chargebacks created successfully');
            $('#chargeback_create_form').trigger('reset');
        },
		'error': function(response){
			var errorText = '';
			for(var key in response.responseJSON){
				errorText += response.responseJSON[key][0] + '</br>';
			}
			toastr.clear();
			toastr.error(errorText, 'Errors', {'timeOut' : 0, 'extendedTImeout' : 0});
		}
    });
}
