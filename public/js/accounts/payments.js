$(document).ready(function() {

    var data = {'account_id':$('#account_id').val()};

    var invoicesTable = $('#invoices_table').DataTable({
        ajax: {url:'/invoices/getOutstanding', dataSrc:'', data:data},
        dom: 'lfrtip',
        pageLength: 10,
        searching: false,
        initComplete: autoCalculatePayments,
        language: {'emptyTable' : 'No invoices found with outstanding balance'},
        createdRow: function(row, data, index) {
            $('.invoice_id', row).html('<a href="/invoices/view/' + data.invoice_id + '" >' + data.invoice_id + '</a>');
            $('.payment_amount', row).html('<input type="number" name="' + data.invoice_id + '_payment_amount" step="0.01" max="' + data.balance_owing + '" class="form-control" />');
        },
        columns: [
            {data:'invoice_id', className:'invoice_id'},
            {data:'bill_end_date'},
            {data:'balance_owing'},
            {data:null, className:'payment_amount'}
        ]
    });

    var paymentsTable = $('#payments_table').DataTable({
        ajax: {url:'/payments/getPaymentsTableByAccount', dataSrc:'', data: data},
        dom: 'lf<"columnVis"B>rtip',
        buttons: ['colvis'],
        columnDefs: [{'sWidth':'20px', 'aTargets':[1]}],
        pageLength: 50,
        order: [0, 'desc'],
        createdRow: function (row, data, index) {
            data.invoice_id == null ? '' : $('.invoice_id', row).html('<a href="/invoices/view/' + data.invoice_id + '" >' + data.invoice_id + '</a>');
        },
        columns: [
            {data: 'payment_id'},
            {data: 'invoice_id', className:'invoice_id'},
            {data: 'date'},
            {data: 'amount'},
            {data: 'payment_type'},
            {data: 'reference_value'},
            {data: 'comment'}
        ]
    });

    $('#payment_amount').blur(autoCalculatePayments);
    $('input[id$=_payment_amount]').blur(autoCalculatePayments);

    $('#payment_type_id').change(handleReferenceValue);
    handleReferenceValue();

    stickyTabs();
});

function handleReferenceValue() {
    const referenceValue = $('#payment_type_id').find(':selected').attr('reference_value')
    if(referenceValue) {
        $('#reference_value_div').removeClass('hidden');
        $('#reference_value').attr('placeholder', referenceValue);
    } else {
        $('#reference_value_div').addClass('hidden');
    }
}

function autoCalculatePayments() {
    var payment_amount = Number($('#payment_amount').val());

    var invoices = $('#invoices_table > tbody > tr .payment_amount :input');

    var auto_pay = $('#auto_pay').is(':checked');

    invoices.each(function() {
        if(auto_pay) {
            if(payment_amount == 0) {
                $(this).val(0);
                return;
            }
        }
        var max = parseFloat($(this).attr('max').replace(/\,/g,'')).toFixed(2);
        if(max > payment_amount) {
            if(auto_pay)
                $(this).val(payment_amount.toFixed(2));
            payment_amount = 0;
        } else {
            if(auto_pay)
                $(this).val(max);
            (payment_amount -= Number(max)).toFixed(2);
        }
    })
    $('#on_account').val(payment_amount.toFixed(2));
}

function submitCredit() {
    var data = $('#account_credit_form').serialize();
    data += '&account_id=' + $('#account_id').val();

    $.ajax({
        'url' : '/accounts/giveCredit',
        'type' : 'post',
        'data': data,
        'success': function() {
            $('#account_credit_modal').modal('hide');
            toastr.success('Account credited successfully', 'Success', {
                'progressBar': true,
                'showDuration': 500,
                'onHidden': function(){location.reload()}
            })
        },
        'error': function(response){handleErrorResponse(response)}
    })
}

function submitPayment() {
    var data = $('#payment_form').serialize();
    data += '&account-id=' + $('#account_id').val();

    $.ajax({
        'url': '/payments/accountPayment',
        'type': 'post',
        'data': data,
        'success': function() {
            $('#payment_modal').modal('hide');
            toastr.success('Payment successfully submitted', 'Success', {
                'progressBar': true,
                'showDuration': 500,
                'onHidden': function(){location.reload()}
            });
        },
		'error': function(response){handleErrorResponse(response)}
    });
}
