function storeUser(button) {
    $(button).attr('disabled', true);
    var radio = $('#user_form input:radio[name="email_is_primary[]"]');
    var primaryIndex = radio.index(radio.filter(':checked'));
    radio.filter(':checked').val(primaryIndex);
    radio = $('#user_form input:radio[name="phone_is_primary[]"]');
    primaryIndex = radio.index(radio.filter(':checked'));
    radio.filter(':checked').val(primaryIndex);

    $data = $('#user_form').serialize();
    $.ajax({
        type: 'POST',
        data: $data,
        url: '/users/storeAccountUser',
        'success' : function() {
            toastr.clear();
            toastr.success('User successfully updated', 'Success');
            $('#edit_user_modal').modal('toggle');
        },
        'error': function(response){
            handleErrorResponse(response);
            $(button).attr('disabled', false);
        }
    });
}

