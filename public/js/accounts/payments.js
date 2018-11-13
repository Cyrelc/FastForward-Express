$(document).ready(function() {

    var data = {'account-id':$('#account-id').val()};

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
            {data:'date'},
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

    $('#select_payment').change(handleReferenceValue);
    handleReferenceValue();

    stickyTabs();
});

function handleReferenceValue() {
    switch($('#select_payment').val()) {
        case 'cheque':
            $('#reference_value_div').removeClass('hidden');
            $('#reference_value').attr('placeholder', 'Cheque Number');
            break;
        case 'bank_transfer':
            $('#reference_value_div').removeClass('hidden');
            $('#reference_value').prop('placeholder', 'Bank Transfer ID');
            break;
        case 'credit_card':
            $('#reference_value_div').removeClass('hidden');
            $('#reference_value').prop('placeholder', 'Last Four Digits');
            break;
        case 'account':
            $('#reference_value_div').addClass('hidden');
            $('#payment_amount').val($('#select_payment option:selected').attr('data-amount'));
            break;
        default:
            $('#reference_value_div').addClass('hidden');
            break;
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
        var max = $(this).attr('max');
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

function submitPayment() {
    var data = $('#payment_form').serialize();
    data += '&account-id=' + $('#account-id').val();

    $.ajax({
        'url': '/payments/accountPayment',
        'type': 'post',
        'data': data,
        'success': function() {
            $('#payment_modal').modal('hide');
            toastr.success('Payment successfully submitted', 'Success', {
                'progressBar': true,
                'positionClass': 'toast-top-full-width',
                'showDuration': 500,
                'onHidden': function(){location.reload()}
            });
        },
		'error': function(response){handleErrorReponse(response)}
    });
}
