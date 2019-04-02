function storeContact(button) {
    $(button).attr('disabled', true);
    var radio = $('#emergency_contact_form input:radio[name="email_is_primary[]"]');
    var primaryIndex = radio.index(radio.filter(':checked'));
    radio.filter(':checked').val(primaryIndex);
    radio = $('#emergency_contact_form input:radio[name="phone_is_primary[]"]');
    primaryIndex = radio.index(radio.filter(':checked'));
    radio.filter(':checked').val(primaryIndex);

    var data = $('#emergency_contact_form').serialize();
    $.ajax({
        type: 'POST',
        data: data,
        url: '/employees/editEmergencyContact',
        'success' : function() {
            toastr.clear();
            toastr.success('Contact successfully updated', 'Success');
            $('#edit_contact_modal').modal('toggle');
        },
        'error': function(response) {
            handleErrorResponse(response);
            $(button).attr('disabled', false);
        }
    });
}

