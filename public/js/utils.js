function numberFilter(e) {
    // Allow: backspace, delete, tab, escape, enter
    if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
        // Allow: Ctrl+A
        (e.keyCode == 65 && e.ctrlKey === true) ||
        // Allow: Ctrl+C
        (e.keyCode == 67 && e.ctrlKey === true) ||
        // Allow: Ctrl+X
        (e.keyCode == 88 && e.ctrlKey === true) ||
        // Allow: home, end, left, right
        (e.keyCode >= 35 && e.keyCode <= 39)) {
        // let it happen, don't do anything
        return;
    }
    // Ensure that it is a number and stop the keypress
    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
        e.preventDefault();
    }
}

function phoneInput(id) {
    new Cleave('#' + id, {
        delimiters: ['(', ')', ' ', '-'],
        blocks: [0, 3, 0, 3, 4]
    });
    $("#" + id).keydown(function(e){numberFilter(e);});

    $("#" + id).focusout(function(e){
        if ($("#" + id).val() == '('){
            $("#" + id).val('');
        }
    });
}

function zipInput(id){
    new Cleave("#" + id, {
        blocks: [3, 3],
        delimiter: ' ',
        uppercase: true
    });
}

function dateInput(id) {
    $('#' + id).datetimepicker({
        format: 'dddd, MMMM Do YYYY'
    });

    $('#' + id + ' input').focus(function(e){
        $('#' + id).data("DateTimePicker").show();
    });
}

function comboInput(id, placeholderText) {
    $("#" + id).combobox({
        appendId: '-cb'
    });

    $("#" + id + "-cb").attr('placeholder', placeholderText);
}

function stickyTabs() {
    var url = document.location.toString();
    if (url.match('#')) {
        $('.nav-tabs a[href="#' + url.split('#')[1] + '"]').tab('show');
    } 

    // Change hash for page-reload
    $('.nav-tabs a').on('shown.bs.tab', function (e) {
        window.location.hash = e.target.hash;
        window.scrollTo(0, 0);
    })
}

function handleErrorResponse(response) {
    var errorText = '';
    for(var key in response.responseJSON.errors)
        errorText += response.responseJSON.errors[key][0] + '</br>';
    toastr.clear();
    toastr.error(errorText, response.responseJSON.message, {'timeOut' : 0, 'extendedTImeout' : 0});
}

function cleave() {
    $('.phone_number').toArray().forEach(function(field){
        new Cleave(field, {
            numericOnly: true,
            delimiters: ['(',')',' ','-'],
            blocks: [0, 3, 0, 3, 4]
        });
    });

    $('.phone_ext').toArray().forEach(function(field){
        new Cleave(field, {
            numeral: true,
            numeralThousandsGroupStyle: 'none',
            numeralPositiveOnly: true
        });
    });
}

