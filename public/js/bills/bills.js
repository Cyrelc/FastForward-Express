$(document).ready(function() {
    var table = $('#table').DataTable({
        ajax: {url:'/bills/buildTable', dataSrc: '', data: function(d) { d.filter = $('#bills_advanced_filter').val()}},
        dom: 'lf<"columnVis"B>rtip',
        buttons: ['colvis'],
        ColVis: {exclude: [0]},
        pageLength: 50,
        order: [1, 'desc'],
        deferRender: true,
        columnDefs: [{ 'visible': false, 'targets': 4 }],
        createdRow: function(row, data, index) {
            var deleteButton = '<a class="fa fa-trash-alt btn btn-danger btn-xs" title="Delete Bill" data-toggle="modal" data-target="#delete_modal" onclick="deleteBill(' + data.bill_id + ')" />';
            var progress_bar;
            if(data.percentage_complete < 0.33) 
                progress_bar = 'progress-bar-danger';
            else if (data.percentage_complete < 0.66)
                progress_bar = 'progress-bar-warning';
            else if (data.percentage_complete == 1)
                progress_bar = 'progress-bar-success';
            else 
                progress_bar = 'progress-bar-info';
            if(data.editable)
                $('.actions', row).html('<div class="hover-div" >' + deleteButton + '</div>');
            $('.bill_id', row).html('<a href="/bills/edit/' + data.bill_id + '" >' + data.bill_id + '</a>');
            $('.charge_account', row).html('<a href="/accounts/edit/' + data.charge_account_id + '" >' + data.charge_account_name + '</a>');
            $('.pickup_employee', row).html('<a href="/employees/edit/' + data.pickup_employee_id + '" >' + data.pickup_employee_name + '</a>');
            $('.delivery_employee', row).html('<a href="/employees/edit/' + data.delivery_employee_id + '" >' + data.delivery_employee_name + '</a>');
            $('.interliner').html('<a href="/interliners/edit/' + data.interliner_id + '" >' + data.interliner_name + '</a>');
            $('.invoice', row).html('<a href="/invoices/view/' + data.invoice_id + '" >' + data.invoice_id + '</a>');
            $('.pickup_manifest', row).html('<a href="/manifests/view/' + data.pickup_manifest_id + '" >' +  data.pickup_manifest_id + '</a>');
            $('.delivery_manifest', row).html('<a href="/manifests/view/' + data.delivery_manifest_id + '" >' + data.delivery_manifest_id + '</a>');
            $('.percentage_complete', row).html('<div class="progress-bar ' + progress_bar + '" role="progressbar" aria-valuenow="' + data.percentage_complete * 100 + '" style="width:' + data.percentage_complete * 100 + '%">' + data.percentage_complete * 100 + '%</div>');
        },
        columns: [
            {className:'actions', orderable: false, data: null, defaultContent: '', colvis: false},
            {data: 'bill_id', className:'bill_id'},
            {data: 'bill_number'},
            {data: 'time_pickup_scheduled'},
            {data: 'time_delivery_scheduled'},
            {data: 'delivery_type'},
            {data: 'charge_account_number'},
            {data: 'charge_account_name', className: 'charge_account'},
            {data: 'pickup_employee_name', className: 'pickup_employee'},
            {data: 'delivery_employee_name', className: 'delivery_employee'},
            {data: 'interliner_name', className: 'interliner', visible: false},
            {data: 'description', visible: false},
            {data: 'package_count', visible: false},
            {data: 'invoice_id', className: 'invoice', visible: false},
            {data: 'pickup_manifest_id', className: 'pickup_manifest', visible: false},
            {data: 'delivery_manifest_id', className: 'delivery_manifest', visible: false},
            {data: 'amount'},
            {data: 'percentage_complete', className: 'percentage_complete'}
        ]
    })

    $('#bills_advanced_filter').change(function() { //TODO - remove auto refresh when live for clients
        table.ajax.reload();
    });

    setInterval(function() {table.ajax.reload();}, 600000);
});

function deleteBill(id) {
    console.log('deleteBill called');
    $('#delete_modal #delete_button').attr('href', '/bills/delete/' + id);
}
