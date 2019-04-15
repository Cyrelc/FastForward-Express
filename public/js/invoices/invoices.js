$(document).ready(function() {

    var table = $('#table').DataTable({
        ajax: {url:'/invoices/buildTable' + window.location.search, dataSrc:''},
        dom: 'f<"columnVis"B>lrtip',
        buttons: [{extend: 'print', exportOptions: {columns: ':visible'}},'colvis'],
        columnDefs: [{'sWidth':'20px', 'aTargets':[1]}],
        pageLength: 50,
        stateSave: true,
        order: [2, 'desc'],
        createdRow: function (row, data, index) {
            var deleteButton = '<a class="fa fa-trash-alt btn btn-danger btn-xs" title="Delete Invoice" data-toggle="modal" data-target="#delete_modal" onclick="deleteInvoice(' + data.invoice_id + ')" />';
            $('td', row).eq(0).html('<input class="invoiceSelect" type="checkbox" name="checkboxes[' + data.invoice_id + ']" />');
            $actiontd = $('td', row).eq(1);
            $actiontd.html('<div class="hover-div" >' + deleteButton + '</div>');
            $('.account', row).html('<a href="/accounts/edit/' + data.account_id + '" >' + data.account_name + '</a>');
            $('.invoice_id', row).html('<a href="/invoices/view/' + data.invoice_id + '" >' + data.invoice_id + '</a>');
        },
        columns: [
            {className:'select', orderable: false, data: null, defaultContent: ''},
            {className:'actions', orderable: false, data: null, defaultContent: ''},
            {data: 'invoice_id', className: 'invoice_id'},
            {data: 'account_name', className: 'account'},
            {data: 'date'},
            {data: 'balance_owing'},
            {data: 'bill_cost'},
            {data: 'total_cost'},
            {data: 'bill_count'}
        ]
    });
});

function printMass() {
    var data = $('.invoiceSelect').serialize();
    if(data == '') {
        toastr.error('No Invoices were selected for download', 'Error');
        return;
    }
    $('#downloadModal').modal('show');

    $.ajax({
        url: '/invoices/printMass',
        type: 'post',
        data: data,
        success: function(response) {
            $('#downloadModal').modal('hide');
            toastr.clear();
            toastr.success('Your download will begin momentarily', 'Success');
            window.location = '/invoices/download/' + response;
        },
        error: function (response) {
            $('#downloadModal').modal('hide');
            var errorText = '';
            for(var key in response.responseJSON)
                errorText += response.responseJSON[key][0] + '</br>';
            toastr.clear();
            toastr.error(errorText, 'Errors', {'timeOut' : 0, 'extendedTIme' : 0});
        }
    })
}

function deleteInvoice(id) {
    $('#delete_modal #delete_button').attr('href', '/invoices/delete/' + id);
}
