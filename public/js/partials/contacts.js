function addContact(parent_prefix, show_address = false, make_primary = false) {
    var new_contact_id = parseInt($('#' + parent_prefix + '-new-contact-id').val());
    $('#' + parent_prefix + '-new-contact-id').val(new_contact_id + 1);
    var prefix = 'contact-' + new_contact_id;

    var _token = $("input[name='_token").val();

    var data = {
        '_token': _token,
        'prefix': 'contact-' + new_contact_id,
        'show_address': show_address,
        'action': 'create',
        'multi': true,
        'parent_prefix': parent_prefix
    }

    $.ajax({
        type: 'POST',
        url: '/partials/contact',
    	data: data,
    	'success': function(results){
            $('#' + parent_prefix + '-contact-tabs').append("<li id='" + prefix + "-tab'><a href='#" + prefix + "-panel' data-toggle='tab'>New Contact</a></li>");
            $('#' + parent_prefix + '-contact-bodies').append("<div role='tabpanel' class='tab-pane' id='" + prefix + "-panel'>" + results + "</div>");
            $('#' + parent_prefix + '-contact-tabs a:last').tab('show');
            $(document).on('keyup', '#' + prefix + '-first-name, #' + prefix + '-last-name', function(){
                var name = $('#' + prefix + '-first-name').val() + ' ' + $('#' + prefix + '-last-name').val();
                if (name == ' ') {
                    $('#' + prefix + '-tab a').html('New Contact');
                } else {
                    $('#' + prefix + '-tab a').html($('#' + prefix + '-first-name').val() + ' ' + $('#' + prefix + '-last-name').val());
                }
            });
            if (make_primary)
                makePrimary(parent_prefix, prefix);
        }
    });
}

function deleteContact(parent_prefix, prefix) {
    if(prefix == $('#' + parent_prefix + '-current-primary').val())
        return;
    else
    $('#' + prefix + '-action').val('delete');
    $('#' + prefix + '-tab').hide();
    $('#' + prefix + '-panel').hide();
    var current_primary = $('#' + parent_prefix + '-current-primary').val();
    $('#' + current_primary + '-tab a').tab('show');
}

function makePrimary(parent_prefix, prefix) {
    var current_primary = $('#' + parent_prefix + '-current-primary').val();
    makeSecondary(current_primary);
    $('#' + prefix + '-tab a').prepend('<i class="fa fa-star">&nbsp&nbsp</i>');
    $('#' + parent_prefix + '-current-primary').val(prefix);
    $('#' + prefix + '-make-primary').prop('disabled', true);
    $('#' + prefix + '-delete').prop('disabled', true);
}

function makeSecondary(prefix) {
    $('#' + prefix + '-tab i').remove();
    $('#' + prefix + '-make-primary').prop('disabled', false);
    $('#' + prefix + '-delete').removeAttr('disabled', false);
}
