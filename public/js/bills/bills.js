$(document).ready(function() {
    var temp = window.location.search;
    var table = $('#table').DataTable({
        ajax: {url:'/bills/buildTable' + window.location.search, dataSrc: ''},
        dom: 'f<"columnVis"B>lrtip',
        buttons: [{extend: 'print', exportOptions: {columns: ':visible'}},'colvis'],
        ColVis: {exclude: [0]},
        pageLength: 50,
        stateSave: true,
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
            var links = [{'db_id_field' : 'bill_id', 'url' : '/bills/edit/', 'db_name_field' : 'bill_id'},
                        {'db_id_field' : 'charge_account_id', 'url' : '/accounts/edit/', 'db_name_field' : 'charge_account_name'},
                        {'db_id_field' : 'pickup_employee_id','url' : '/employees/edit/', 'db_name_field' : 'pickup_employee_name'},
                        {'db_id_field' : 'delivery_employee_id','url' : '/employees/edit/', 'db_name_field' : 'delivery_employee_name'},
                        {'db_id_field' : 'interliner_id', 'url':'/interliners/edit/', 'db_name_field' : 'interliner_name'},
                        {'db_id_field' : 'invoice_id', 'url' : '/invoices/view/', 'db_name_field' : 'invoice_id'},
                        {'db_id_field' : 'pickup_manifest_id', 'url' : '/manifests/view', 'db_name_field' : 'pickup_manfiest_id'},
                        {'db_id_field' : 'delivery_manifest_id', 'url' : '/manifests/view', 'db_name_field' : 'delivery_manifest_id'}];
            for(link in links) {
                var cur_row = links[link];
                if(data[cur_row['db_id_field']] != '' && data[cur_row['db_id_field']] != null)
                    $('.' + cur_row['db_id_field'], row).html('<a href="' + cur_row['url'] + data[cur_row['db_id_field']] + '" >' + data[cur_row['db_name_field']] + '</a>');
            }
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
            {data: 'charge_account_name', className: 'charge_account_id'},
            {data: 'pickup_employee_name', className: 'pickup_employee_id'},
            {data: 'delivery_employee_name', className: 'delivery_employee_id'},
            {data: 'interliner_name', className: 'interliner_id', visible: false},
            {data: 'description', visible: false},
            {data: 'package_count', visible: false},
            {data: 'invoice_id', className: 'invoice_id', visible: false},
            {data: 'pickup_manifest_id', className: 'pickup_manifest_id', visible: false},
            {data: 'delivery_manifest_id', className: 'delivery_manifest_id', visible: false},
            {data: 'amount'},
            {data: 'charge_type', visible: false},
            {data: 'percentage_complete', className: 'percentage_complete'}
        ]
    })

    table.buttons().container().appendTo('#example_wrapper .col-sm-6:eq(0)');

    $('#bills_advanced_filter').change(function() { //TODO - remove auto refresh when live for clients
        table.ajax.reload();
    });

    setInterval(function() {table.ajax.reload();}, 600000);
});

function deleteBill(id) {
    $('#delete_modal #delete_button').attr('href', '/bills/delete/' + id);
}

