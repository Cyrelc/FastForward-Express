function saveScContact(prefix, includeAddress) {
    var fName = $("#" + prefix + "-first-name").val();
    var lName = $("#" + prefix + "-last-name").val();
    var sPpn = $("#" + prefix + "-phone1").val();
    var sPpnExt = $("#" + prefix + "-phone1-ext").val();
    var sSpn = $("#" + prefix + "-phone2").val();
    var sSpnExt = $("#" + prefix + "-phone2-ext").val();
    var sem = $("#" + prefix + "-email1").val();
    var sem2 = $("#" + prefix + "-email2").val();

    var id = -1;
    $("input[data-contact-id='true']").each(function(index, element){
        var newId = $(element).val();

        if (newId > id)
            id = newId;

        id++;
    });

    if (id == -1)
        id = 1;

    var isPrimary = $("input[name^='contact-id-']").length == 0;

    if (!fName || !lName || !sPpn || !sem)
        return;

    $.ajax({
        url: '/partials/getcontact',
        type: 'POST',
        data: {
            'contact-id': id,
            'first-name': fName,
            'last-name': lName,
            'phone-number': sPpn,
            'phone-number-ext': sPpnExt,
            'secondary-phone-number': sSpn,
            'secondary-phone-number-ext': sSpnExt,
            'email': sem,
            'secondary-email': sem2,
            'prefix' : 'contact'
        },
        success:function(result) {
            $("#" + prefix + "-contact-tabs li").removeClass('active');
            $("#" + prefix + "-contact-bodies div.tab-pane").removeClass('active');
            $("#" + prefix + "-contact-tabs").append('<li class="active" role="presentation"><a data-id="' + id + '" href="#' + id + '"-panel" role="tab" data-toggle="tab">' + (isPrimary ? '<i class="fa fa-star"></i>' : '') + ' ' + fName + ' ' + lName + '</a></li>');
            $("#" + prefix + "-contact-bodies").append(
                '<div role="tabpanel" class="tab-pane active" id="' + id + '"-panel">' +
                    result +
                '</div>');
        }
    });

    clearScForm(prefix, includeAddress);
    $("#" + prefix + "first-name").focus();
    return true;
}

function makePrimary(element) {
    var idToMakePrimary = $(element).parent().parent().parent().parent().parent().find('input[data-contact-id="true"]').val();
    $("#contact-tabs a").each(function(index, el) {
        $(el).children('i.fa-star').remove();
    });

    $("a[data-id='" + idToMakePrimary + "']").prepend('<i class="fa fa-star"></i> ')
    $('input[name="contact-action-change-primary"]').remove();
    $("#" + idToMakePrimary + "-panel").append('<input type="hidden" name="contact-action-change-primary" value="' + idToMakePrimary + '" />');
}

function clearScForm(prefix, includeAddress) {
    $("#" + prefix + "-first-name").val('');
    $("#" + prefix + "-last-name").val('');
    $("#" + prefix + "-phone1").val('');
    $("#" + prefix + "-phone2").val('');
    $("#" + prefix + "-email1").val('');
    $("#" + prefix + "-email2").val('');


}

function removeSc(id, isNew, isPrimary) {
    $("#contact-tabs a[data-id='" + id + "']").parent().remove();
    $("#" + id + '-panel').remove();

    if (!isNew)
        $("#contact-bodies").append('<input type="hidden" name="contact-action-delete[]" value="' + id + '" />');

    //Promote next contact in list to primary contact
    if (isPrimary) {
        var next = $("a[onclick^='makePrimary']");
        makePrimary(next);
        var nextId = $(next).parent().parent().parent().parent().parent().find('input[data-contact-id="true"]').val();
        $('#' + nextId + '-panel li[title="Make Primary"]').remove();
        $('#' + nextId + '-panel li[title="Delete"] a').attr('href', 'javascript:removeSc(' + nextId + ', false, true)');
    }
}

function deleteInputs(element, type, id) {
    $(element).parent().parent().find('input[type="text"], input[type="tel"], input[type="email"]').each(function(index,input){
        $(input).val('');
        $(input).attr('disabled', 'disabled');
    });

    if ($(element).attr('data-new') && $(element).attr('data-new') === 'true')
        $(element).removeAttr('data-new');
    else
        $(element).parent().parent().append('<input type="hidden" name="' + type + '-action-delete[]" value="' + id + '" />');

    $(element).removeClass('btn-danger').addClass('btn-success').attr('onclick', 'enableField(this, "' + type + '", "' + id + '")');
    $(element).children('i').removeClass('fa-trash').addClass('fa-plus-square-o');
}

function enableField(element, type, contactId) {
    $(element).parent().parent().find('input[type="text"], input[type="tel"], input[type="email"]').each(function(index,input){
        $(input).removeAttr('disabled');
    });
    $(element).parent().parent().append('<input type="hidden" name="' + type + '-action-add-' + contactId + '" value="add" />');

    $(element).removeClass('btn-success').addClass('btn-danger').attr('onclick', 'deleteInputs(this, "' + type + '", "")');
    $(element).children('i').removeClass('fa-plus-square-o').addClass('fa-trash');
    $(element).attr('data-new', 'true');
    $(element).parent().parent().children('input[type="email"], input[type="tel"]').first().focus();
}

function addDeleted(id) {
    $("#contact-bodies").append('<input type="hidden" name="contact-action-delete[]" value="' + id + '" />');
}

function deleteSecondary(code, id, contactId) {
    var type = code === 'pn' ? "phone2" : "email2";

    deleteInput($('input[name="contact-' + contactId + '-' + type + '-id"]').parent().find('button'), code, id);
}
