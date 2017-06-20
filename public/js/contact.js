function saveScContact() {
    var fName = $("#first-name").val();
    var lName = $("#last-name").val();
    var sPpn = $("#phone1").val();
    var sPpnExt = $("#phone1-ext").val();
    var sSpn = $("#phone2").val();
    var sSpnExt = $("#phone2-ext").val();
    var sem = $("#email1").val();
    var sem2 = $("#email2").val();

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

    newTabPill(id, fName, lName, isPrimary);
    newTabBody(id, fName, lName, null, sPpn, sPpnExt, null, sSpn, sSpnExt, null, sem, null, sem2, isPrimary, true);
    clearScForm();
    $("#first-name").focus();
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

function newTabPill(id, fName, lName, isPrimary) {
    var star = "";

    if (isPrimary)
        star = "<i class='fa fa-star'></i> ";

    var pill = "<li role='presentation'><a data-id='" + id + "' href='#" + id + "-panel' aria-controls='" + id + "' role='tab' data-toggle='tab'>" + star + fName + " " + lName + "</a></li>";

    $("#contact-tabs").append(pill);
}

function newTabBody(id, fName, lName, ppnId, ppn, ppnExt, spnId, spn, spnExt, emId, em, em2Id, em2, isPrimary, isNew) {
    var makePrimaryButton = "";
    if(!isPrimary)
        makePrimaryButton = '<li title="Make Primary"><a onclick="makePrimary(this); return false;"><i class="fa fa-star"></i></a></li>';

    var spnEl = tabBodyPhone(id, spnId, spn, spnExt);
    var em2El = tabBodyEmail(id, em2Id, em2);

    var body =
        '<div role="tabpanel" class="tab-pane" id="' + id + '-panel">' +
        '<input type="hidden" name="contact-id-' + id + '" data-contact-id="true" value="' + id +  '" />' +
        '<div class="col-lg-12" style="padding:15px;">' +
        '<div class="clearfix form-section well" style="padding:15px;">' +
        '<div class="col-lg-6 clearfix bottom15">' +
        '<input type="text" class="form-control contact-body" name="contact-' + id + '-first-name" placeholder="First Name" value="' + fName + '"/>' +
        '</div>' +
        '<div class="col-lg-6 clearfix bottom15">' +
        '<input type="text" class="form-control contact-body" name="contact-' + id + '-last-name" placeholder="Last Name" value="' + lName + '"/>' +
        '</div>' +
        '<div class="col-lg-6 clearfix bottom15">' +
        '<div class="input-group">' +
        '<input type="hidden" name="contact-' + id + '-phone1-id" value="' + ppnId + '" />' +
        '<input type="tel" id="contact-' + id + '-phone1" class="form-control contact-body" name="contact-' + id + '-phone1" placeholder="Primary Phone" value="' + ppn + '"/>' +
        '<span class="input-group-addon">Ext.</span>' +
        '<input type="text" id="contact-' + id + '-phone1-ext" class="form-control contact-body" name="contact-' + id + '-phone1-ext" placeholder="Extension" value="' + ppnExt + '"/>' +
        '</div>' +
        '</div>' +
        '<div class="col-lg-6 clearfix bottom15">' +
        spnEl +
        '</div>' +
        '<div class="col-lg-6 clearfix bottom15">' +
        '<input type="hidden" name="contact-' + id + '-email1-id" value="' + emId + '" />' +
        '<input type="email" class="form-control contact-body" name="contact-' + id + '-email1" placeholder="Primary Email" value="' + em + '"/>' +
        '</div>' +
        '<div class="col-lg-6 clearfix bottom15">' +
        em2El +
        '</div>' +
        '<ul class="nav nav-pills">' +
        '<li title="Save">' +
        '<a href="javascript:saveScContact()"><i class="fa fa-save"></i></a>' +
        '</li>' +
        '<li title="Delete">' +
        '<a href="javascript:removeSc(' + id + ', ' + isNew + ',' + isPrimary + ')"><i class="fa fa-trash"></i></a>' +
        '</li>'	+
        makePrimaryButton +
        '</ul>' +
        '</div>' +
        '</div>' +
        '</div>';

    $("#contact-bodies").append(body);
    phoneInput('contact-' + id + '-phone1');
    phoneInput('contact-' + id + '-phone2');

    if (isPrimary)
        $("#" + id + '-panel').append('<input type="hidden" name="contact-action-change-primary" value="' + id + '" />');

    if(isNew)
        $('#' + id + '-panel').append('<input type="hidden" name="contact-action-add[]" value="' + id + '" />');
    else
        $("#" + id + '-panel').append('<input type="hidden" name="contact-action-update[]" value="' + id + '" />');
}

function tabBodyPhone(id, spnId, spn, spnExt) {
    var spnEl;
    var spnField = '';
    var spnBtn = '';

    //Pseudo-new
    if (spnId === -2) {
        spnField = '<input type="hidden" name="pn-action-add-' + id + '" value="add" />';
        spnBtn = '<span class="input-group-btn"><button type="button" data-new="true" onclick="deleteInputs(this, \'pn\', \'' + spnId + '\')" class="btn btn-danger"><i class="fa fa-trash"></i></button></span>';
    } else {
        //Update
        if (spnId && spn) {
            spnField = '<input type="hidden" name="contact-' + id + '-phone2-id" value="' + spnId + '" />';
            spnBtn = '<span class="input-group-btn"><button type="button" onclick="deleteInputs(this, \'pn\', \'' + spnId + '\')" class="btn btn-danger"><i class="fa fa-trash"></i></button></span>';
        }
    }

    if (spnId) {
        spnEl =
            '<div class="input-group">' +
            spnField +
            '<input type="tel" id="contact-' + id + '-phone2" class="form-control" name="contact-' + id + '-phone2" placeholder="Primary Phone" value="' + spn + '"/>' +
            '<span class="input-group-addon">Ext.</span>' +
            '<input type="text" id="contact-' + id + '-phone2-ext" class="form-control" name="contact-' + id + '-phone2-ext" placeholder="Extension" value="' + spnExt + '"/>' +
            spnBtn +
            '</div>';
    } else {
        spnEl =
            '<div class="input-group">' +
            '<input disabled type="tel" id="contact-' + id + '-phone2" class="form-control" name="contact-' + id + '-phone2" placeholder="Primary Phone" value=""/>' +
            '<span class="input-group-addon">Ext.</span>' +
            '<input disabled type="text" id="contact-' + id + '-phone2-ext" class="form-control" name="contact-' + id + '-phone2-ext" placeholder="Extension" value=""/>' +
            '<span class="input-group-btn"><button type="button" onclick="enableField(this, \'pn\', ' + id + ')" class="btn btn-success"><i class="fa fa-plus-square-o"></i></button></span>' +
            '</div>';
    }

    return spnEl;
}

function tabBodyEmail(id, em2Id, em2) {
    var em2El;
    var emField = '';
    var emBtn = '';

    if (em2Id === -2) {
        emField = '<input type="hidden" name="em-action-add-' + id + '" value="add" />';
        emBtn = '<span class="input-group-btn"><button type="button" data-new="true" onclick="deleteInputs(this, \'em\', \'' + spnId + '\')" class="btn btn-danger"><i class="fa fa-trash"></i></button></span>';
    } else {
        //Update
        if (em2Id && em2) {
            emField = '<input type="hidden" name="contact-' + id + '-email2-id" value="' + em2Id + '" />';
            emBtn = '<span class="input-group-btn"><button type="button" onclick="deleteInputs(this, \'em\', \'' + em2Id + '\')" class="btn btn-danger"><i class="fa fa-trash"></i></button></span>';
        }
    }

    if (em2Id) {
        em2El =
            '<div class="input-group">' +
            emField +
            '<input type="email" class="form-control" name="contact-' + id + '-email2" placeholder="Secondary Email" value="' + em2 + '" />' +
            emBtn +
            '</div>';
    } else {
        em2El =
            '<div class="input-group">' +
            '<input type="email" disabled class="form-control" name="contact-' + id + '-email2" placeholder="Secondary Email" value="' + em2 + '" />' +
            '<span class="input-group-btn"><button type="button" onclick="enableField(this, \'em\', ' + id + ')" class="btn btn-success"><i class="fa fa-plus-square-o"></i></button></span>' +
            '</div>';
    }

    return em2El;
}

function clearScForm() {
    $("#first-name").val('');
    $("#last-name").val('');
    $("#phone1").val('');
    $("#phone2").val('');
    $("#email1").val('');
    $("#email2").val('');
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
}

function addDeleted(id) {
    $("#contact-bodies").append('<input type="hidden" name="contact-action-delete[]" value="' + id + '" />');
}

function deleteSecondary(code, id, contactId) {
    var type = code === 'pn' ? "phone2" : "email2";

    deleteInput($('input[name="contact-' + contactId + '-' + type + '-id"]').parent().find('button'), code, id);
}