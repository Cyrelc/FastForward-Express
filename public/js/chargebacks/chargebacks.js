$(document).ready(function(){
    dateInput('start_date');

    $('#continuous').change(function() {
        if(this.checked)
            $('#charge_count').attr('disabled', 'disabled');
        else 
            $('#charge_count').removeAttr('disabled', 'disabled');
    });
});

function setDeactivateId(id) {
    $("#deactivate_button").attr('onclick', 'deactivate(' + id + ')');
}

function submitChargeback() {
    var data = $('#chargeback_create_form').serialize();
//manually include "employees" on this form if entry is missing for validation later. See validation rules
    if(!data.includes('employees'))
        data += '&employees=';
    
    $.ajax({
        'url': '/chargebacks/store',
        'type': 'POST',
        'data': data,
        'success': function() {
			toastr.clear();
            toastr.success('Chargebacks created successfully');
            $('#chargeback_create_form').trigger('reset');
        },
		'error': function(response){handleErrorResponse(response)}
    });
}

function updateChargeback(id) {
    var data = $('#chargeback_' + id + ' input').serialize();

    $.ajax({
        'url': '/chargebacks/edit/' + id,
        'type': 'POST',
        'data': data,
        'success': function() {
            toastr.clear();
            toastr.success('Chargeback ' + id + ' was successfully updated');
        },
		'error': function(response){handleErrorResponse(response)}
    });
}

function updateChargebacksList() {
    var contentDiv = $('#manage');

    $.ajax({
        'url': '/chargebacks/edit',
        'type': 'GET',
        'success': function(response) {
			toastr.clear();
            contentDiv.html(response);
        },
		'error': function(response){handleErrorResponse(response)}
    });
}

function deactivate(id) {
    $.ajax({
        'url': '/chargebacks/deactivate/' + id,
        'type': 'POST',
        'data': {'_token' : $('#_token').val()},
        'success': function() {
            $('#deactivate_modal').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            toastr.clear();
            toastr.success('Chargeback ' + id + ' was successfully deactivated');
            updateChargebacksList();
        }, 
		'error': function(response){handleErrorResponse(response)}
    })
}
