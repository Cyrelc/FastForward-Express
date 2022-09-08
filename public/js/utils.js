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

function roundTimeToNextFifteenMinutes(time = null) {
    var rounded = time ? new Date(time) : new Date()

    rounded.setMilliseconds(0);
    rounded.setSeconds(0)
    rounded.setMinutes(Math.ceil(rounded.getMinutes() / 15) * 15)

    return rounded
}

function getDatetimeDifferenceInHours(datetime, datetime2) {
    return Math.abs(datetime - datetime2) / 36e5
}

function poundsToKilograms(pounds){
    return +(pounds / 2.2046).toFixed(3);
}

function kilogramsToPounds(kilograms){
    return +(kilograms * 2.2046).toFixed(3);
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
    $('.has-error').removeClass('has-error');
    for(var key in response.responseJSON.errors) {
        $('[name="' + key + '"]').parent().addClass('has-error');
        errorText += response.responseJSON.errors[key][0] + '</br>';
    }
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

/**
 * 
 * @param {string} url 
 * @param {string} type 
 * @param {object} data 
 * @param {function} callback 
 * 
 * Born out of a need to determine whether the Laravel session has expired, to redirect user to login page, instead of failing silently
 * All functions must use the following wrappers to perform Ajax or Fetch requests
 */

function makeAjaxRequest(url, type, data, callback, errorCallback = null) {
    $.ajax({
        'url': url,
        'type': type,
        'data': data,
        'success': (response, textStatus, xhr) => {
            console.log('XHR.status: ' + xhr.status)
            callback(response)
        },
        'error': (response, textStatus, xhr) => {
            console.log(response.status)
            if(response.status === 401 || response.message === 'CSRF token mismatch.')
                location.reload()
            else if(response.status === 404)
                window.location.href = '/app/error404'
            else if(response.status === 500) {
                toastr.clear()
                toastr.error('An unexpected server error was encountered. Please contact support.')
            } else if(response.status === 403) {
                responseText = JSON.parse(response.responseText)
                if(responseText.message)
                    toastr.error(responseText.message, 'Permission Denied', {'timeOut': 4000, 'extendedTImeout': 4000})
                else
                    toastr.error('Authenticated User does not have permission to perform the requested action', 'Permission Denied', {'timeOut': 4000, 'extendedTImeout': 4000})
            } else {
                if(errorCallback)
                    errorCallback(response)
                handleErrorResponse(response)
            }
        }
    })
}

function makeFetchRequest(url, callback) {
    fetch(url)
    .then(response => {
        if(response.redirected && response.url.toString().toLowerCase().indexOf('/login') > -1) {
            location.reload()
            return Promise.reject(response)
        } else if (!response.ok) {
            if(response.statusCode === 404)
                window.location.href = '/'
            else if(response.statusCode === 500) {
                toastr.clear()
                toastr.error('An unexpected error was encountered. Please contact support.')
            }
            else {
                toastr.clear()
                toastr.error(response.error, '', {'timeOut' : 0, 'extendedTImeout' : 0});
            }

            return Promise.reject(response)
        }

        return response.json()
    })
    .then(data => callback(data))
}

Date.prototype.addDays = function(days) {
    var date = new Date(this.valueOf());
    date.setDate(date.getDate() + days);
    return date;
}

function configureFakeLink(url, redirectFunction, altDisplayField = null, altRedirectField = null) {
    return {
        cellClick: (e, cell) => {
            const redirectTo = altRedirectField ? cell.getRow().getData()[altRedirectField] : cell.getValue()
            if(redirectTo)
                redirectFunction(`${url}${redirectTo}`)
        },
        headerClick: false,
        formatter: (cell) => {
            const displayValue = altDisplayField ? cell.getRow().getData()[altDisplayField] : cell.getValue()
            if(displayValue)
                return `<a href="javascript::void(0)">${displayValue}</a>`
            return ''
        }
    }
}

Array.prototype.sortBy = function(p) {
    return this.slice(0).sort(function(a,b) {
        return (a[p].localeCompare(b[p], undefined, {numeric: true, sensitivity: 'base'}))
    })
}

function toFixedNumber(num, digits, base) {
    var pow = Math.pow(base || 10, digits)
    return Math.round(num * pow) / pow
}

function formatPhoneNumber(phone) {
    let cleaned = ('' + phone).replace(/\D/g, '')
    let match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/)
    if (match) {
        return `(${match[1]}) ${match[2]}-${match[3]}`
    }
}
